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
     * Endpoint API Interaksi Asisten Virtual IT Support AI (Gemini + Local RAG SOP).
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $userMessage = trim($request->input('message'));
        $user = Auth::user();
        $userName = $user ? $user->name : 'Karyawan PLTU';
        $userDivisi = $user ? ($user->divisi ?: 'Operasional PLTU') : 'Operasional PLTU';

        // 1. Ambil seluruh SOP yang relevan dari database KnowledgeBase (RAG Context)
        $sopQuery = KnowledgeBase::where('status', 'Terbit');
        if ($user && $user->isPegawai()) {
            $sopQuery->whereIn('target_audience', ['all', 'pegawai']);
        }
        $availableSops = $sopQuery->get();

        // Cari SOP paling relevan dengan scoring kata kunci
        $matchedSop = $this->findBestMatchingSop($userMessage, $availableSops);

        // 2. Cek apakah ada konfigurasi Google Gemini API
        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        $model = config('services.gemini.model') ?: 'gemini-1.5-flash';

        $geminiResponse = null;
        if (!empty($apiKey)) {
            $geminiResponse = $this->callGeminiApi($apiKey, $model, $userMessage, $userName, $userDivisi, $matchedSop, $availableSops);
        }

        // 3. Jika Gemini berhasil, gunakan jawaban Gemini
        if (!empty($geminiResponse)) {
            $waText = "Halo Helpdesk IT PT S2P PLTU Karangkandri,\n\nSaya telah berkonsultasi dengan Asisten AI terkait kendala:\n\"{$userMessage}\"\n\n- Pelapor: {$userName}\n- Divisi: {$userDivisi}\n- Status: Butuh bantuan teknisi lapangan.\n\nMohon bantuannya. Terima kasih.";

            return response()->json([
                'success' => true,
                'source' => 'gemini_ai',
                'reply' => $geminiResponse,
                'sop_title' => $matchedSop ? $matchedSop->judul : null,
                'sop_kategori' => $matchedSop ? $matchedSop->kategori : null,
                'steps' => $matchedSop && !empty($matchedSop->langkah_langkah) ? $matchedSop->langkah_langkah : null,
                'cmd_title' => $matchedSop ? $matchedSop->judul_perintah : null,
                'cmd' => $matchedSop ? $matchedSop->perintah_cmd : null,
                'warning' => $matchedSop ? $matchedSop->peringatan : null,
                'wa_url' => 'https://wa.me/6281234567890?text=' . urlencode($waText),
                'ticket_url' => route('services.create', ['kendala' => $userMessage]),
            ]);
        }

        // 4. Fallback Cerdas: Menggunakan Smart Local SOP Engine dari Database
        return response()->json($this->buildLocalSopResponse($userMessage, $userName, $userDivisi, $matchedSop));
    }

    /**
     * Hitung kecocokan SOP terbaik berdasarkan kata kunci pencarian.
     */
    private function findBestMatchingSop(string $query, $sops)
    {
        $lowerQuery = mb_strtolower($query);
        $words = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $lowerQuery)));

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

            // Cek kata kunci spesifik
            foreach ($words as $w) {
                if (mb_strlen($w) < 3) continue;
                if (str_contains($searchable, $w)) {
                    $score += 3;
                    // Bobot ekstra jika cocok di judul
                    if (str_contains(mb_strtolower($sop->judul), $w)) {
                        $score += 5;
                    }
                }
            }

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestSop = $sop;
            }
        }

        return $highestScore >= 3 ? $bestSop : null;
    }

    /**
     * Panggil API Google Gemini dengan prompt berlandaskan SOP (RAG).
     */
    private function callGeminiApi(string $apiKey, string $model, string $userMessage, string $userName, string $userDivisi, $matchedSop, $allSops): ?string
    {
        try {
            $contextText = "Berikut adalah SOP Resmi IT Support PT Sumber Segara Primadaya (S2P) PLTU Karangkandri Cilacap:\n\n";
            if ($matchedSop) {
                $contextText .= "--- SOP UTAMA ---\n";
                $contextText .= "Judul: {$matchedSop->judul}\n";
                $contextText .= "Kategori: {$matchedSop->kategori}\n";
                $contextText .= "Pengantar: {$matchedSop->pengantar}\n";
                if (!empty($matchedSop->langkah_langkah)) {
                    $contextText .= "Langkah-langkah:\n" . implode("\n- ", (array)$matchedSop->langkah_langkah) . "\n";
                }
                if (!empty($matchedSop->perintah_cmd)) {
                    $contextText .= "Perintah CMD: {$matchedSop->perintah_cmd}\n";
                }
                if (!empty($matchedSop->peringatan)) {
                    $contextText .= "Peringatan K3/Sistem: {$matchedSop->peringatan}\n";
                }
            } else {
                $contextText .= "SOP tersedia mencakup: Printer, Jaringan LAN/Wi-Fi, Komputer Hang/Bluescreen, Reset Password Domain, Outlook/Email, SAP/Maximo PLTU, Proyektor Ruang Rapat.\n";
            }

            $systemPrompt = "Anda adalah Asisten Virtual AI IT Support cerdas untuk PT Sumber Segara Primadaya (S2P) - PLTU Karangkandri Cilacap.
Tugas Anda adalah:
1. Menjawab pertanyaan pengguna ({$userName} dari divisi {$userDivisi}) mengenai kendala perangkat IT, software, sistem, jaringan, maupun pertanyaan umum IT (seperti rumus Excel/Word, shortcut Windows, troubleshooting hardware, tips produktivitas kantor, dsb).
2. Jika pertanyaan berkaitan dengan operasional atau SOP PLTU (printer, LAN, domain, akun PLTU, SAP/Maximo, dsb), prioritaskan rujukan SOP resmi yang terlampir.
3. Jawab dengan bahasa Indonesia yang ramah, sopan, jelas, terstruktur (gunakan bullet points atau penomoran 1, 2, 3), serta berikan solusi langsung yang aplikatif.
4. Di bagian akhir, tambahkan catatan ramah bahwa jika kendala memerlukan penanganan fisik di tempat, pengguna dapat membuat Tiket Servis atau menghubungi Helpdesk IT via WhatsApp (0812-3456-7890).";

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(8)->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => "{$systemPrompt}\n\n[DOKUMEN SOP REFERENSI]:\n{$contextText}\n\n[PERTANYAAN PENGGUNA ({$userName})]:\n\"{$userMessage}\""
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 800,
                ]
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
        $lower = mb_strtolower($userMessage);

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
