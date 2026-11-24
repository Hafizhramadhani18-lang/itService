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

            $systemPrompt = "Anda adalah Asisten Virtual AI IT Support resmi PT Sumber Segara Primadaya (S2P) - PLTU Karangkandri Cilacap.
Tugas Anda adalah membantu karyawan ({$userName} dari divisi {$userDivisi}) yang menghadapi gangguan perangkat atau sistem IT.
Gunakan data SOP yang disediakan di bawah ini sebagai rujukan utama.
Jawab dengan gaya profesional, sopan, empatik, terstruktur (gunakan poin 1, 2, 3), dan mudah dipahami.
Sertakan perintah teknis jika ada.
Di akhir jawaban, sampaikan bahwa jika kendala belum terselesaikan, teknisi Helpdesk IT siap membantu via WhatsApp (0812-3456-7890) atau tiket servis.";

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
