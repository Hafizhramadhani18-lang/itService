<?php

namespace Database\Seeders;

use App\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        $itUser = User::where('role', 'teknisi')->first();
        $userId = $itUser ? $itUser->id : null;

        KnowledgeBase::firstOrCreate(['judul' => 'SOP Pengkabelan LAN/UTP Cat6 (T568B)'], [
            'user_id' => $userId,
            'kategori' => 'Jaringan & LAN',
            'area_penerapan' => 'Infrastruktur Jaringan Gigabit PLTU',
            'target_audience' => 'teknisi',
            'pengantar' => 'Di area pembangkit berdebu batubara, gunakan konektor RJ-45 Cat6 Shielded (FTP/STP). Urutan warna standar T-568B:',
            'diagram_kabel' => [
                '1. Putih-Oranye', '2. Oranye', '3. Putih-Hijau', '4. Biru',
                '5. Putih-Biru', '6. Hijau', '7. Putih-Cokelat', '8. Cokelat'
            ],
            'peringatan' => 'Wajib diuji dengan LAN tester: seluruh 8 pin harus menyala berurutan sebelum kabel dipasang ke PC atau switch operasional.',
            'tag' => 'lan, cat6, rj45, crimping',
            'status' => 'Terbit',
        ]);

        KnowledgeBase::firstOrCreate(['judul' => 'SOP Pembersihan Debu & Overheating PC'], [
            'user_id' => $userId,
            'kategori' => 'Hardware & PC',
            'area_penerapan' => 'Area Boiler, Coal Handling, & Jetty',
            'target_audience' => 'teknisi',
            'pengantar' => 'Debu batubara bersifat konduktif ringan dan memicu korslet jika lembap. Tahapan penanganan unit yang sering mati sendiri / overheat:',
            'langkah_langkah' => [
                'Koordinasikan dengan operator, matikan PC, cabut kabel daya, dan gunakan gelang antistatis.',
                'Bongkar casing dan tahan baling-baling fan agar tidak berputar berlebihan saat disemprot blower.',
                'Bersihkan sela-sela sirip heatsink dan slot RAM dengan kuas antistatis.',
                'Bersihkan sisa pasta lama pada processor dengan tisu dan alkohol isopropil.',
                'Oleskan thermal paste baru secara merata (tipe non-conductive, misal Arctic MX-4).',
                'Uji suhu CPU: saat idle idealnya di bawah 45°C.'
            ],
            'tag' => 'hardware, debu, overheating, cpu',
            'status' => 'Terbit',
        ]);

        KnowledgeBase::firstOrCreate(['judul' => 'SOP Diagnostik Bluescreen (BSOD) & RAM'], [
            'user_id' => $userId,
            'kategori' => 'Hardware & PC',
            'area_penerapan' => 'Workstation Kantor & Control Room',
            'target_audience' => 'teknisi',
            'pengantar' => 'Jika PC mengalami crash dengan layar biru (BSOD):',
            'langkah_langkah' => [
                'Catat stop code error (misal: MEMORY_MANAGEMENT atau PAGE_FAULT).',
                'Buka Command Prompt (Run as Administrator), jalankan perintah perbaikan sistem.',
                'Jalankan mdsched.exe (Win+R) untuk memeriksa sektor memori yang korup, lalu restart.'
            ],
            'judul_perintah' => 'Perintah Pemeriksaan Integritas Sistem (CMD Admin)',
            'perintah_cmd' => "sfc /scannow\nchkdsk /f /r",
            'tag' => 'bsod, bluescreen, ram, windows',
            'status' => 'Terbit',
        ]);

        KnowledgeBase::firstOrCreate(['judul' => 'SOP Reset IP Conflict & Jaringan Lokal'], [
            'user_id' => $userId,
            'kategori' => 'Jaringan & LAN',
            'area_penerapan' => 'Subnet Internal PLTU Cilacap',
            'target_audience' => 'all', // Teknisi dan pegawai
            'pengantar' => 'Jika muncul notifikasi "There is an IP address conflict with another system on this network":',
            'langkah_langkah' => [
                'Buka CMD (Command Prompt), lepas dan perbarui IP DHCP menggunakan perintah di bawah.',
                'Jika memakai IP statik, cek perangkat tamu dengan IP sama lewat perintah arp -a.',
                'Pastikan Wi-Fi kantor dan kabel LAN tidak terhubung bersamaan ke satu subnet.'
            ],
            'judul_perintah' => 'Perintah Reset IP DHCP',
            'perintah_cmd' => "ipconfig /release\nipconfig /flushdns\nipconfig /renew",
            'peringatan' => 'Jangan lakukan pada PC SCADA / kontrol operasional tanpa izin operator, karena koneksi akan terputus sesaat.',
            'tag' => 'ip, conflict, jaringan, dhcp',
            'status' => 'Terbit',
        ]);

        KnowledgeBase::firstOrCreate(['judul' => 'SOP Printer Offline di Control Room'], [
            'user_id' => $userId,
            'kategori' => 'Sistem & Printer',
            'area_penerapan' => 'Control Room dan kantor',
            'target_audience' => 'all', // Teknisi dan pegawai
            'pengantar' => 'Gejala dan kapan SOP ini dipakai: printer terhubung namun status di Windows offline atau antrian cetak macet.',
            'langkah_langkah' => [
                'Pastikan printer menyala dan kabel USB/LAN terpasang kencang.',
                'Hapus antrian cetak yang macet (clear spooler queue).',
                'Restart service print spooler melalui terminal command di bawah.'
            ],
            'judul_perintah' => 'Restart Print Spooler',
            'perintah_cmd' => "net stop spooler\nnet start spooler",
            'peringatan' => 'Jangan reset spooler jika PC server cetak sedang dipakai mencetak logsheet operasional.',
            'tag' => 'printer, offline, spooler',
            'status' => 'Terbit',
        ]);
    }
}

