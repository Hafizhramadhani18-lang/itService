<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatController extends Controller
{
    /**
     * Endpoint API Interaksi Asisten Virtual AI (Google Gemini AI + Local RAG SOP).
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'nullable|array|max:20',
        ]);

        $userMessage = trim($request->input('message'));
        $history = $request->input('history', []);
        $user = Auth::user();
        $userName = $user ? $user->name : 'Karyawan PLTU';
        $userDivisi = $user ? ($user->divisi ?: 'Operasional PLTU') : 'Operasional PLTU';

        // 1. Ambil SOP yang relevan dari database KnowledgeBase (RAG Context untuk pertanyaan IT/PLTU)
        $sopQuery = KnowledgeBase::where('status', 'Terbit');
        if ($user && $user->isPegawai()) {
            $sopQuery->whereIn('target_audience', ['all', 'pegawai']);
        }
        $availableSops = $sopQuery->get();

        // Cari SOP paling relevan dengan scoring kata kunci
        $matchedSop = $this->findBestMatchingSop($userMessage, $availableSops);
        $isItQuery = $matchedSop !== null || $this->isItSupportQuery($userMessage);

        // 2. Panggil Google Gemini API
        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        $model = config('services.gemini.model') ?: 'gemini-3.5-flash-lite';

        $geminiResponse = null;
        if (!empty($apiKey)) {
            $geminiResponse = $this->callGeminiApi($apiKey, $model, $userMessage, $history, $userName, $userDivisi, $matchedSop, $isItQuery);
        }

        // 3. Jika Gemini berhasil, kembalikan jawaban Gemini AI yang cerdas
        if (!empty($geminiResponse)) {
            $payload = [
                'success' => true,
                'source' => 'gemini_ai',
                'is_it_query' => $isItQuery,
                'reply' => $geminiResponse,
                'sop_title' => $matchedSop ? $matchedSop->judul : null,
                'sop_kategori' => $matchedSop ? $matchedSop->kategori : null,
                'steps' => $matchedSop && !empty($matchedSop->langkah_langkah) ? $matchedSop->langkah_langkah : null,
                'cmd_title' => $matchedSop ? $matchedSop->judul_perintah : null,
                'cmd' => $matchedSop ? $matchedSop->perintah_cmd : null,
                'warning' => $matchedSop ? $matchedSop->peringatan : null,
            ];

            // Tampilkan tombol bantuan teknisi & tiket hanya jika pertanyaan berkaitan dengan kendala IT / kantor
            if ($isItQuery) {
                $waText = "Halo Helpdesk IT PT S2P PLTU Karangkandri,\n\nSaya telah berkonsultasi dengan Asisten AI terkait kendala:\n\"{$userMessage}\"\n\n- Pelapor: {$userName}\n- Divisi: {$userDivisi}\n- Status: Butuh bantuan teknisi lapangan.\n\nMohon bantuannya. Terima kasih.";
                $payload['wa_url'] = 'https://wa.me/6281234567890?text=' . urlencode($waText);
                $payload['ticket_url'] = route('services.create', ['kendala' => $userMessage]);
            }

            return response()->json($payload);
        }

        // 4. Fallback Cerdas: Menggunakan Smart Local SOP Engine dari Database jika API tidak terjangkau
        return response()->json($this->buildLocalSopResponse($userMessage, $userName, $userDivisi, $matchedSop));
    }

    /**
     * Deteksi apakah pertanyaan berkaitan dengan kendala IT / Service Desk.
     */
    private function isItSupportQuery(string $query): bool
    {
        $lower = mb_strtolower($query);
        $itKeywords = [
            'printer', 'cetak', 'print', 'tinta', 'toner', 'paper jam', 'spooler',
            'wifi', 'wi-fi', 'lan', 'internet', 'jaringan', 'kabel', 'ip', 'dns', 'ping', 'koneksi',
            'komputer', 'pc', 'laptop', 'monitor', 'layar', 'bluescreen', 'bsod', 'hang', 'lambat', 'lemot',
            'password', 'kata sandi', 'akun', 'login', 'domain', 'outlook', 'email', 'zimbra',
            'sap', 'maximo', 'erp', 'proyektor', 'projector', 'hdmi', 'vga',
            'tiket', 'servis', 'teknisi', 'helpdesk', 'sop', 'perangkat', 'error', 'gangguan', 'excel', 'vlookup'
        ];

        foreach ($itKeywords as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/u', $lower)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Hitung kecocokan SOP terbaik berdasarkan kata kunci pencarian.
     */
    private function findBestMatchingSop(string $query, $sops)
    {
        $lowerQuery = mb_strtolower($query);
        $rawWords = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $lowerQuery)));

        // Daftar stopwords bahasa Indonesia umum agar tidak salah mencocokkan kata pendek secara parsial
        $stopwords = [
            'aku', 'kamu', 'saya', 'anda', 'dia', 'kita', 'kami', 'mereka',
            'ini', 'itu', 'apa', 'kenapa', 'bagaimana', 'kapan', 'dimana', 'kok', 'ko', 'ya', 'kan',
            'dan', 'atau', 'tapi', 'namun', 'dengan', 'ke', 'di', 'dari', 'pada', 'untuk', 'buat',
            'bisa', 'tolong', 'mohon', 'mau', 'dong', 'sih', 'deh', 'lah', 'pun', 'ada'
        ];

        $words = array_filter($rawWords, function($w) use ($stopwords) {
            return mb_strlen($w) >= 3 && !in_array($w, $stopwords);
        });

        if (empty($words)) {
            return null;
        }

        $bestSop = null;
        $highestScore = 0;

        foreach ($sops as $sop) {
            $score = 0;
            $searchable = mb_strtolower(
                $sop->judul . ' ' .
                $sop->kategori . ' ' .
                $sop->tag . ' ' .
                $sop->pengantar . ' ' .
                $sop->area_penerapan . ' ' .
                ($sop->perintah_cmd ?? '')
            );

            // Cek kata kunci spesifik dengan boundary regex agar tidak mencocokkan substring sembarangan
            foreach ($words as $w) {
                $quotedW = preg_quote($w, '/');
                if (preg_match('/\b' . $quotedW . '\b/u', $searchable)) {
                    $score += 4;
                    // Bobot ekstra jika cocok di judul secara utuh
                    if (preg_match('/\b' . $quotedW . '\b/u', mb_strtolower($sop->judul))) {
                        $score += 6;
                    }
                }
            }

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestSop = $sop;
            }
        }

        return $highestScore >= 8 ? $bestSop : null;
    }

    /**
     * Panggil API Google Gemini dengan kemampuan AI umum + konteks SOP PLTU (jika relevan).
     */
    private function callGeminiApi(string $apiKey, string $model, string $userMessage, array $history, string $userName, string $userDivisi, $matchedSop, bool $isItQuery): ?string
    {
        try {
            $systemPrompt = "Anda adalah Asisten AI serba bisa yang cerdas, ramah, dan membantu di lingkungan PT Sumber Segara Primadaya (S2P) - PLTU Karangkandri Cilacap, ditenagai oleh Google Gemini.
Pengguna saat ini adalah: {$userName} (Divisi: {$userDivisi}).

Aturan & Kemampuan Anda:
1. Anda dapat menjawab **SEMUA JENIS PERTANYAAN** layaknya AI modern pada umumnya: mulai dari obrolan keseharian, hobi, ide kreatif, pekerjaan kantor, manajemen projek, pemrograman/coding, penulisan dokumen/email, matematika, Excel, kesehatan, kuliner, hingga pengetahuan umum apa pun. Jangan pernah menolak pertanyaan non-IT atau membatasi diri hanya pada topik SOP.
2. Jika pengguna bertanya tentang kendala teknis IT atau sistem kantor PLTU (printer, LAN/Wi-Fi, PC lambat, password domain, Outlook, SAP/Maximo, proyektor), gunakan referensi SOP resmi IT PT S2P jika tersedia dan berikan panduan langkah demi langkah yang praktis.
3. Jawab dengan bahasa Indonesia yang natural, hangat, jelas, mudah dipahami, dan terstruktur rapi (gunakan poin-poin atau penomoran bila perlu). Sesuaikan nada bicara dengan konteks: santai/akrab untuk topik keseharian & hobi, profesional & solutif untuk topik pekerjaan/projek/IT.";

            if ($matchedSop) {
                $sopContext = "\n\n[REFERENSI SOP RESMI IT PT S2P YANG RELEVAN]:\n";
                $sopContext .= "Judul: {$matchedSop->judul} ({$matchedSop->kategori})\n";
                $sopContext .= "Pengantar: {$matchedSop->pengantar}\n";
                if (!empty($matchedSop->langkah_langkah)) {
                    $sopContext .= "Langkah-langkah:\n- " . implode("\n- ", (array)$matchedSop->langkah_langkah) . "\n";
                }
                if (!empty($matchedSop->perintah_cmd)) {
                    $sopContext .= "Perintah CMD: {$matchedSop->perintah_cmd}\n";
                }
                if (!empty($matchedSop->peringatan)) {
                    $sopContext .= "Peringatan K3/Sistem: {$matchedSop->peringatan}\n";
                }
                $systemPrompt .= $sopContext;
            } elseif ($isItQuery) {
                $systemPrompt .= "\n\nCatatan: Jika kendala teknis memerlukan penanganan fisik langsung di ruangan, Anda boleh menyarankan pengguna membuat Tiket Servis atau menghubungi Helpdesk IT.";
            }

            // Bangun riwayat percakapan multi-turn
            $contents = [];
            foreach (array_slice($history, -10) as $turn) {
                if (!is_array($turn) || empty($turn['text'])) {
                    continue;
                }
                $role = ($turn['role'] ?? 'user') === 'model' ? 'model' : 'user';
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => mb_substr(trim((string) $turn['text']), 0, 2000)]],
                ];
            }

            // Tambahkan pesan terbaru pengguna
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $userMessage]],
            ];

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(25)->post($url, [
                'systemInstruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 2048,
                ],
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $reply = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if (!empty($reply)) {
                    return trim($reply);
                }
            } else {
                Log::warning('Gemini API Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::warning('Gemini API Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Membangun respon terstruktur lokal berbasis database SOP jika tanpa API Gemini.
     */
    private function buildLocalSopResponse(string $userMessage, string $userName, string $userDivisi, $matchedSop): array
    {
        $lower = mb_strtolower(trim($userMessage));

        // 0. Respon Percakapan Ramah / Sapaan (Greeting & Small-talk)
        if (preg_match('/^(halo|hallo|hello|hi|hai|selamat\s+(pagi|siang|sore|malam)|assalamu[\'\w]*|pagi|siang|sore|malam|tes|test|ping)$/ui', $lower) ||
            preg_match('/^(halo|hallo|hello|hi|hai)\b/ui', $lower) && mb_strlen($lower) <= 20) {
            return [
                'success' => true,
                'source' => 'local_ai_knowledge',
                'reply' => "Halo **{$userName}**! 👋 Ada kendala perangkat atau sistem IT yang bisa saya bantu hari ini?\n\nAnda dapat menanyakan keluhan seperti komputer lambat/mati, printer offline, jaringan LAN putus, reset password domain, rumus Excel, atau klik tombol di bawah:",
                'sop_title' => 'Asisten Virtual IT Support S2P Siaga',
                'sop_kategori' => 'Layanan IT',
                'steps' => [
                    'Ketikkan langsung gejala atau kendala yang Anda alami di kolom chat.',
                    'Gunakan menu referensi dokumen SOP resmi di samping kanan untuk panduan standar.',
                    'Atau klik tombol WhatsApp di bawah jika membutuhkan kedatangan teknisi langsung ke ruangan Anda.'
                ],
                'wa_url' => 'https://wa.me/6281234567890?text=' . urlencode("Halo Helpdesk IT PT S2P, saya {$userName} dari divisi {$userDivisi} butuh bantuan."),
                'ticket_url' => route('services.create'),
            ];
        }

        // 1. Diagnosa PC / Komputer / Laptop Mati Total, No Display, atau Mati Mendadak
        if (preg_match('/\b(mati|gak nyala|ngga nyala|tidak menyala|padam|no display|layar hitam|black screen|beep|mati total|matot)\b/u', $lower) && 
            (preg_match('/\b(pc|komputer|cpu|laptop|monitor|layar)\b/u', $lower) || str_contains($lower, 'komputer') || str_contains($lower, 'pc'))) {
            return [
                'success' => true,
                'source' => 'local_ai_knowledge',
                'reply' => "Halo **{$userName}**, kendala PC atau komputer tidak menyala/mati total umumnya disebabkan oleh pasokan daya atau modul memori (RAM). Berikut langkah diagnosa mandiri yang disarankan teknisi IT:",
                'sop_title' => 'Diagnosa Mandiri PC Mati Total / No Display',
                'sop_kategori' => 'Hardware & PC',
                'steps' => [
                    '**Periksa Saklar & Kabel Power**: Pastikan kabel daya AC terpasang kuat di bagian belakang casing CPU dan saklar Power Supply (PSU) berada pada posisi strip **I** (ON), bukan lingkaran **O** (OFF).',
                    '**Uji Stop Kontak / Colokan**: Coba pindahkan colokan daya PC ke stop kontak lain atau bypass UPS/Stabilizer untuk memastikan bukan colokan yang mati.',
                    '**Cek Lampu Indikator Motherboard**: Buka penutup casing samping, perhatikan apakah ada lampu LED kecil di motherboard yang menyala saat kabel dicolokkan.',
                    '**Kipas Berputar Tapi Layar Mati (No Display)**: Cabut kabel listrik, lepas keping RAM, bersihkan pin tembaga kuning menggunakan penghapus pensil halus, lalu pasang kembali pada slot dengan bunyi *klik* yang rapat.',
                    '**Gejala Mati Mendadak (Overheat)**: Jika PC menyala beberapa menit lalu mati sendiri, kipas pendingin processor (Heatsink Fan) kemungkinan kotor atau pasta pendingin telah kering.'
                ],
                'warning' => 'Jangan menyentuh komponen dalam komputer saat tangan basah atau kabel daya masih terhubung ke stop kontak PLN demi keselamatan kerja (K3 Listrik).',
                'wa_url' => 'https://wa.me/6281234567890?text=' . urlencode("Halo Helpdesk IT PT S2P,\n\nSaya membutuhkan pengecekan teknisi untuk PC mati total/tidak menyala:\n- Pelapor: {$userName}\n- Divisi: {$userDivisi}\n- Keluhan: \"{$userMessage}\"\n\nMohon bantuannya."),
                'ticket_url' => route('services.create', ['kendala' => $userMessage]),
            ];
        }

        // 2. Diagnosa Audio / Speaker / Suara Tidak Keluar
        if (preg_match('/\b(suara|audio|speaker|headset|earphone|mic|mikrofon|bunyi|mute)\b/u', $lower) && 
            preg_match('/\b(hilang|mati|gak keluar|tidak keluar|rusak|kresek|kecil)\b/u', $lower)) {
            return [
                'success' => true,
                'source' => 'local_ai_knowledge',
                'reply' => "Halo **{$userName}**, untuk kendala audio atau suara tidak keluar pada komputer kantor, berikut solusi cepat yang dapat dicoba:",
                'sop_title' => 'Troubleshooting Audio & Speaker Komputer',
                'sop_kategori' => 'Hardware & PC',
                'steps' => [
                    '**Periksa Ikon Speaker Taskbar**: Klik ikon speaker di pojok kanan bawah desktop, pastikan volume tidak dalam posisi **Mute (X)** atau terlalu rendah.',
                    '**Pilih Output Perangkat yang Tepat**: Klik panah di samping slider volume dan pastikan perangkat output aktif adalah **Speakers (Realtek Audio)** atau headset yang sedang Anda gunakan.',
                    '**Periksa Jack Audio 3.5mm**: Pastikan jack kabel audio tercolok pada port warna **Hijau (Audio Out)** di casing belakang/depan, bukan port merah muda (Mic).',
                    '**Restart Audio Service**: Buka RUN (Win + R), ketik `services.msc`, cari **Windows Audio**, klik kanan lalu pilih **Restart**.'
                ],
                'wa_url' => 'https://wa.me/6281234567890?text=' . urlencode("Halo IT Support, kendala suara/audio komputer dari {$userName}"),
                'ticket_url' => route('services.create', ['kendala' => $userMessage]),
            ];
        }

        // 3. Diagnosa Mouse / Keyboard Tidak Merespon
        if (preg_match('/\b(mouse|keyboard|kursor|pointer)\b/u', $lower) && 
            preg_match('/\b(macet|rusak|mati|gak gerak|tidak gerak|tidak terdeteksi|delay|double click)\b/u', $lower)) {
            return [
                'success' => true,
                'source' => 'local_ai_knowledge',
                'reply' => "Halo **{$userName}**, kendala mouse atau keyboard tidak terdeteksi dapat diatasi dengan beberapa langkah mudah berikut:",
                'sop_title' => 'Troubleshooting Mouse & Keyboard Kantor',
                'sop_kategori' => 'Hardware & PC',
                'steps' => [
                    '**Pindahkan Port USB**: Cabut dongle/kabel USB mouse/keyboard dan colokkan ke port USB lain (disarankan port belakang motherboard yang langsung menempel di CPU).',
                    '**Cek Baterai (Perangkat Wireless)**: Jika menggunakan mouse tanpa kabel, ganti baterai AA/AAA dengan yang baru dan pastikan saklar switch di bawah mouse pada posisi ON.',
                    '**Tes di Device Manager**: Tekan **Win + X** lalu pilih **Device Manager**. Periksa apakah ada tanda seru kuning pada menu *Mice and other pointing devices*. Klik kanan lalu pilih **Scan for hardware changes**.'
                ],
                'wa_url' => 'https://wa.me/6281234567890?text=' . urlencode("Halo IT Support, butuh penggantian mouse/keyboard untuk {$userName}"),
                'ticket_url' => route('services.create', ['kendala' => $userMessage]),
            ];
        }

        // 4. Diagnosa Kecepatan Internet Lambat / Koneksi Putus-Putus
        if (preg_match('/\b(internet|wifi|wi-fi|koneksi|jaringan|lemot|lambat|lelet|ping tinggi|rto)\b/u', $lower) && 
            preg_match('/\b(lemot|lambat|lelet|putus|down|gangguan|rto|nge-lag|ngelag)\b/u', $lower)) {
            return [
                'success' => true,
                'source' => 'local_ai_knowledge',
                'reply' => "Halo **{$userName}**, jika koneksi internet terasa lambat atau putus-putus di ruangan kerja Anda, silakan ikuti diagnosa awal ini:",
                'sop_title' => 'Optimasi & Diagnosa Jaringan Internet Kantor',
                'sop_kategori' => 'Jaringan & LAN',
                'steps' => [
                    '**Uji Ping Gateway**: Tekan tombol **Win + R**, ketik `cmd`, lalu ketik `ping 8.8.8.8 -t` untuk melihat apakah ada paket data yang *Request Timed Out* (RTO).',
                    '**Flush DNS & Reset IP**: Jalankan perintah refresh koneksi Windows untuk membersihkan cache DNS jaringan.',
                    '**Periksa Aplikasi Download Latar Belakang**: Buka Task Manager (**Ctrl + Shift + Esc**), cek tab *Network* apakah ada aplikasi yang menyedot bandwidth besar (misal Windows Update atau OneDrive sync).',
                    '**Cek Fisik Kabel LAN**: Pastikan klip pengunci kabel LAN di belakang PC tidak patah dan lampu indikator hijau/oranye di port berkedip stabil.'
                ],
                'cmd_title' => 'Perintah Reset DNS Windows',
                'cmd' => 'ipconfig /flushdns && ipconfig /renew',
                'wa_url' => 'https://wa.me/6281234567890?text=' . urlencode("Halo IT Support Jaringan, lapor koneksi internet lambat dari divisi {$userDivisi}"),
                'ticket_url' => route('services.create', ['kendala' => $userMessage]),
            ];
        }

        // Smart general IT patterns (Excel, shortcuts, browser cache, display/proyektor, mouse/keyboard)
        if (str_contains($lower, 'excel') || str_contains($lower, 'vlookup') || str_contains($lower, 'rumus') || str_contains($lower, 'if(') || str_contains($lower, 'sumif')) {
            return [
                'success' => true,
                'source' => 'local_ai_knowledge',
                'reply' => "Halo **{$userName}**, berikut panduan singkat fungsi & rumus Microsoft Excel yang sering digunakan untuk administrasi PLTU:",
                'sop_title' => 'Tips & Rumus Produktivitas Microsoft Excel',
                'sop_kategori' => 'Aplikasi & Email',
                'steps' => [
                    '**VLOOKUP**: `=VLOOKUP(nilai_kunci, rentang_tabel, nomor_kolom, FALSE)` — mencari data dari kolom kiri ke kanan secara persis.',
                    '**XLOOKUP** (Excel modern): `=XLOOKUP(nilai_cari, kolom_cari, kolom_hasil)` — lebih fleksibel dibanding VLOOKUP.',
                    '**IF / Logika**: `=IF(kondisi, "Jika Benar", "Jika Salah")` — untuk conditional status (misal: Lulus/Tidak Lulus).',
                    '**SUMIFS**: `=SUMIFS(kolom_jumlah, kolom_kriteria1, "kriteria1")` — menjumlahkan angka berdasarkan kondisi tertentu.',
                    '**Tips Error #N/A**: Gunakan `=IFERROR(rumus, "-")` agar tampilan tabel tetap rapi jika data tidak ditemukan.'
                ],
                'warning' => 'Pastikan pemisah tanda rumus di Excel Anda (koma `,` atau titik koma `;`) sesuai pengaturan Regional Windows komputer Anda.',
                'wa_url' => 'https://wa.me/6281234567890?text=' . urlencode("Halo IT Support, saya butuh asistensi formula Excel dari divisi {$userDivisi}"),
                'ticket_url' => route('services.create', ['kendala' => $userMessage]),
            ];
        }

        if (str_contains($lower, 'shortcut') || str_contains($lower, 'tombol') || str_contains($lower, 'keyboard') || str_contains($lower, 'kombinasi')) {
            return [
                'success' => true,
                'source' => 'local_ai_knowledge',
                'reply' => "Halo **{$userName}**, berikut kumpulan pintasan keyboard (Windows Shortcuts) paling praktis untuk efisiensi kerja harian:",
                'sop_title' => 'Pintasan Keyboard Windows (Shortcuts)',
                'sop_kategori' => 'Hardware & PC',
                'steps' => [
                    '**Win + Shift + S**: Tangkap layar (Screenshot selektif area) langsung simpan di clipboard.',
                    '**Win + V**: Membuka Clipboard History (melihat riwayat teks/gambar yang pernah di-copy).',
                    '**Win + D**: Minimize semua jendela dan langsung kembali ke Desktop.',
                    '**Win + L**: Mengunci PC/Laptop seketika saat meninggalkan meja (Penting untuk keamanan data PLTU!).',
                    '**Ctrl + Shift + Esc**: Membuka Task Manager langsung untuk memantau aplikasi berat/hang.',
                    '**Win + E**: Membuka File Explorer dengan cepat.'
                ],
                'warning' => 'Selalu kunci layar (Win + L) saat meninggalkan komputer demi keamanan data operasional PLTU.',
                'wa_url' => 'https://wa.me/6281234567890?text=' . urlencode("Halo IT Support, konsultasi shortcut/keyboard dari {$userName}"),
                'ticket_url' => route('services.create', ['kendala' => $userMessage]),
            ];
        }

        if (str_contains($lower, 'cache') || str_contains($lower, 'history') || str_contains($lower, 'chrome') || str_contains($lower, 'browser') || str_contains($lower, 'lemot web')) {
            return [
                'success' => true,
                'source' => 'local_ai_knowledge',
                'reply' => "Halo **{$userName}**, kendala web portal/aplikasi web lambat atau tombol tidak merespon biasanya dapat diselesaikan dengan membersihkan cache browser:",
                'sop_title' => 'Pembersihan Cache & Cookie Browser Web',
                'sop_kategori' => 'Aplikasi & Email',
                'steps' => [
                    'Buka browser (Google Chrome / Microsoft Edge).',
                    'Tekan kombinasi tombol **Ctrl + Shift + Delete** di keyboard.',
                    'Pilih Rentang Waktu: **Semua Waktu (All time)**.',
                    'Centang opsi **Gambar dan file dalam cache** serta **Cookie dan data situs lainnya**.',
                    'Klik **Hapus data (Clear data)**, lalu tutup dan buka kembali browser Anda.'
                ],
                'cmd_title' => 'Hard Reload Shortcut',
                'cmd' => 'Ctrl + F5',
                'wa_url' => 'https://wa.me/6281234567890?text=' . urlencode("Halo IT Support, bantuan web browser dari {$userName}"),
                'ticket_url' => route('services.create', ['kendala' => $userMessage]),
            ];
        }

        if ($matchedSop) {
            $reply = "Berdasarkan **{$matchedSop->judul}** (Kategori: {$matchedSop->kategori}), berikut adalah diagnosa dan langkah penanganan mandiri yang dapat Anda coba:";

            $waText = "Halo Teknisi IT Support PT S2P,\n\nSaya telah mencoba panduan mandiri [{$matchedSop->judul}] untuk kendala:\n\"{$userMessage}\"\n\n- Pelapor: {$userName}\n- Divisi: {$userDivisi}\n- Status: Kendala belum teratasi, mohon kunjungan teknisi lapangan.\n\nTerima kasih.";

            return [
                'success' => true,
                'source' => 'local_sop_database',
                'reply' => $reply,
                'diagnosa' => $matchedSop->pengantar,
                'sop_title' => $matchedSop->judul,
                'sop_kategori' => $matchedSop->kategori,
                'area_penerapan' => $matchedSop->area_penerapan,
                'steps' => $matchedSop->langkah_langkah,
                'cmd_title' => $matchedSop->judul_perintah,
                'cmd' => $matchedSop->perintah_cmd,
                'warning' => $matchedSop->peringatan,
                'wa_url' => 'https://wa.me/6281234567890?text=' . urlencode($waText),
                'ticket_url' => route('services.create', ['kendala' => $userMessage]),
            ];
        }

        // Fallback jika tidak ada SOP yang cocok
        $waText = "Halo Helpdesk IT PT S2P PLTU Karangkandri,\n\nSaya memerlukan bantuan penanganan kendala teknis:\n\"{$userMessage}\"\n\n- Pelapor: {$userName}\n- Divisi: {$userDivisi}\n\nMohon bantuan teknisi. Terima kasih.";

        return [
            'success' => true,
            'source' => 'local_fallback',
            'reply' => "Terima kasih atas laporan Anda, **{$userName}**. Keluhan terkait *\"{$userMessage}\"* belum memiliki panduan mandiri spesifik. Anda dapat langsung meneruskan kendala ini ke tim teknisi siaga:",
            'sop_title' => 'Bantuan Langsung Helpdesk IT',
            'steps' => [
                'Periksa apakah kabel daya dan perangkat indikator menyala.',
                'Hubungi Helpdesk IT Support via Telepon Ext. 102 (Aktif 24/7).',
                'Atau klik tombol hijau di bawah untuk terhubung langsung ke WhatsApp Teknisi dengan format laporan siap kirim.'
            ],
            'wa_url' => 'https://wa.me/6281234567890?text=' . urlencode($waText),
            'ticket_url' => route('services.create', ['kendala' => $userMessage]),
        ];
    }
}
