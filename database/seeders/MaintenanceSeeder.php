<?php

namespace Database\Seeders;

use App\Models\MaintenanceLog;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        $hafizh = User::where('email', 'hafizh@s2p.co.id')->first();
        $bagas = User::where('email', 'bagas@s2p.co.id')->first();
        $giska = User::where('email', 'giska@s2p.co.id')->first();
        $admin = User::where('role', 'admin')->first();

        // 1. Printer jaringan - Terlambat (mockup row 1)
        MaintenanceLog::updateOrCreate(
            ['log_number' => 'PM-202610-001'],
            [
                'tanggal_cek' => '2026-10-28',
                'periode_bulan' => '2026-10',
                'kode_perangkat' => 'PRN-CR-01',
                'nama_perangkat' => 'Printer jaringan',
                'jenis_perangkat' => 'Printer',
                'divisi' => 'Operasi Boiler',
                'lokasi_ruangan' => 'Control Room',
                'user_id' => null,
                'teknisi_nama' => 'Belum ada',
                'tim' => 'Tim IT Lapangan',
                'kegiatan' => 'Cek rol kertas, bersihkan serbuk debu pickup roller',
                'status' => 'Terlambat',
                'kondisi' => 'Perlu Perhatian',
                'tindakan' => 'Terlambat: Pemeriksaan belum terlaksana sejak 28 Oktober 2026.',
                'status_tindak_lanjut' => 'Perlu Pantauan',
            ]
        );

        // 2. Server aplikasi (database) - Terjadwal (mockup row 2)
        MaintenanceLog::updateOrCreate(
            ['log_number' => 'PM-202611-001'],
            [
                'tanggal_cek' => '2026-11-05',
                'periode_bulan' => '2026-11',
                'kode_perangkat' => 'SRV-APP-01',
                'nama_perangkat' => 'Server aplikasi (database)',
                'jenis_perangkat' => 'Server / UPS',
                'divisi' => 'IT Support & Infrastructure',
                'lokasi_ruangan' => 'Server Room',
                'user_id' => $giska?->id,
                'teknisi_nama' => $giska?->name ?: 'Giska',
                'tim' => 'Tim IT Support',
                'kegiatan' => 'Backup database, cek log service web server',
                'status' => 'Terjadwal',
                'kondisi' => 'Prima',
                'tindakan' => 'Dijadwalkan untuk backup database dan monitoring log sistem.',
                'status_tindak_lanjut' => 'Selesai',
            ]
        );

        // 3. Server NVR CCTV - Terjadwal (mockup row 3)
        MaintenanceLog::updateOrCreate(
            ['log_number' => 'PM-202611-002'],
            [
                'tanggal_cek' => '2026-11-06',
                'periode_bulan' => '2026-11',
                'kode_perangkat' => 'SRV-CCTV-01',
                'nama_perangkat' => 'Server NVR CCTV',
                'jenis_perangkat' => 'Server / UPS',
                'divisi' => 'K3 / HSE',
                'lokasi_ruangan' => 'Pos Utama Security',
                'user_id' => $hafizh?->id,
                'teknisi_nama' => $hafizh?->name ?: 'Hafizh',
                'tim' => 'Tim IT Lapangan',
                'kegiatan' => 'Cek hard disk dan kapasitas storage rekaman CCTV',
                'status' => 'Terjadwal',
                'kondisi' => 'Prima',
                'tindakan' => 'Dijadwalkan untuk pengecekan kapasitas storage harddisk RAID dan kamera online.',
                'status_tindak_lanjut' => 'Selesai',
            ]
        );

        // 4. Rack server & UPS - Selesai Baik (mockup row 4)
        MaintenanceLog::updateOrCreate(
            ['log_number' => 'PM-202611-003'],
            [
                'tanggal_cek' => '2026-11-04',
                'periode_bulan' => '2026-11',
                'kode_perangkat' => 'UPS-SRV-01',
                'nama_perangkat' => 'Rack server & UPS',
                'jenis_perangkat' => 'Server / UPS',
                'divisi' => 'IT Support & Infrastructure',
                'lokasi_ruangan' => 'Server Room',
                'user_id' => $hafizh?->id,
                'teknisi_nama' => $hafizh?->name ?: 'Hafizh',
                'tim' => 'Tim IT Lapangan',
                'kegiatan' => 'Cek suhu ruangan, bersihkan debu filter rack & failover test',
                'status' => 'Selesai',
                'kondisi' => 'Prima',
                'checklist' => MaintenanceLog::defaultChecklist('Server / UPS'),
                'tindakan' => "Cek suhu ruangan (19.5°C), bersihkan debu filter intake. Uji failover baterai UPS 45 menit normal.",
                'catatan_rekomendasi' => 'Kondisi: Baik. Semua unit UPS dan power supply berfungsi stabil.',
                'status_tindak_lanjut' => 'Selesai',
            ]
        );

        // 5. Switch access layer SCADA - Selesai Baik (mockup row 5)
        MaintenanceLog::updateOrCreate(
            ['log_number' => 'PM-202611-004'],
            [
                'tanggal_cek' => '2026-11-03',
                'periode_bulan' => '2026-11',
                'kode_perangkat' => 'SW-CCR-02',
                'nama_perangkat' => 'Switch access layer SCADA',
                'jenis_perangkat' => 'Kabel LAN / Jaringan',
                'divisi' => 'Operasi Boiler',
                'lokasi_ruangan' => 'Ruang Kontrol Boiler Lt. 2',
                'user_id' => $bagas?->id,
                'teknisi_nama' => $bagas?->name ?: 'Bagas Aditya',
                'tim' => 'Tim IT Lapangan',
                'kegiatan' => 'Cek port dan konektor RJ-45, uji ping latency gateway',
                'status' => 'Selesai',
                'kondisi' => 'Prima',
                'checklist' => MaintenanceLog::defaultChecklist('Kabel LAN / Jaringan'),
                'tindakan' => "Pembersihan port & grounding switch rack. Ping latency gateway 0.8ms packet loss 0%.",
                'catatan_rekomendasi' => 'Kondisi: Baik. 24 port gigabit aktif dan bebas bottleneck.',
                'status_tindak_lanjut' => 'Selesai',
            ]
        );

        // 6. Workstation Control Room - Selesai Perlu Tindak Lanjut (mockup row 6)
        $pcCheck = MaintenanceLog::defaultChecklist('Komputer PC');
        $pcCheck[1]['checked'] = false; // Pasta termal kering / suhu tinggi
        MaintenanceLog::updateOrCreate(
            ['log_number' => 'PM-202611-005'],
            [
                'tanggal_cek' => '2026-11-02',
                'periode_bulan' => '2026-11',
                'kode_perangkat' => 'PC-WS-02',
                'nama_perangkat' => 'Workstation Control Room',
                'jenis_perangkat' => 'Komputer PC',
                'divisi' => 'Operasi Boiler',
                'lokasi_ruangan' => 'Control Room Unit 1',
                'user_id' => $bagas?->id,
                'teknisi_nama' => $bagas?->name ?: 'Bagas',
                'tim' => 'Tim IT Lapangan',
                'kegiatan' => 'Bersihkan heatsink, cek suhu CPU & storage',
                'status' => 'Selesai',
                'kondisi' => 'Perlu Perhatian',
                'checklist' => $pcCheck,
                'tindakan' => "Pembersihan debu batu bara pada fan casing & heatsink. Suhu CPU terdeteksi 72°C idle akibat thermal paste kering.",
                'catatan_rekomendasi' => 'Kondisi: Perlu tindak lanjut. Segera jadwalkan repasting thermal paste dalam 3 hari ke depan.',
                'status_tindak_lanjut' => 'Perlu Pantauan',
            ]
        );

        // 7. Master Jadwal Rutin (Mockup Bottom Section)
        MaintenanceSchedule::updateOrCreate(
            ['nama_aset' => 'Rack server & UPS', 'lokasi_ruangan' => 'Server Room'],
            [
                'kode_perangkat' => 'UPS-SRV-01',
                'frekuensi' => 'Setiap bulan',
                'tim' => 'Tim IT Lapangan',
                'teknisi_id' => $hafizh?->id,
                'teknisi_nama' => 'Hafizh',
                'kegiatan' => 'Cek suhu ruangan, bersihkan debu filter rack & failover test baterai',
                'tanggal_berikutnya' => '2026-12-04',
                'is_active' => true,
            ]
        );

        MaintenanceSchedule::updateOrCreate(
            ['nama_aset' => 'Switch access layer SCADA', 'lokasi_ruangan' => 'Ruang Kontrol Boiler Lt. 2'],
            [
                'kode_perangkat' => 'SW-CCR-02',
                'frekuensi' => 'Setiap 3 bulan',
                'tim' => 'Tim IT Lapangan',
                'teknisi_id' => $admin?->id,
                'teknisi_nama' => 'Hendra Wijaya',
                'kegiatan' => 'Cek port dan konektor RJ-45, uji throughput & grounding',
                'tanggal_berikutnya' => '2027-02-03',
                'is_active' => true,
            ]
        );

        MaintenanceSchedule::updateOrCreate(
            ['nama_aset' => 'Server NVR CCTV', 'lokasi_ruangan' => 'Pos Utama Security'],
            [
                'kode_perangkat' => 'SRV-CCTV-01',
                'frekuensi' => 'Setiap bulan',
                'tim' => 'Tim IT Lapangan',
                'teknisi_id' => $hafizh?->id,
                'teknisi_nama' => 'Hafizh',
                'kegiatan' => 'Cek hard disk dan kapasitas storage rekaman CCTV',
                'tanggal_berikutnya' => '2026-11-06',
                'is_active' => true,
            ]
        );
    }
}
