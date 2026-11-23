<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Koordinator IT (Role Admin)
        $adminIT = User::create([
            'name' => 'Hendra Wijaya, S.T.',
            'email' => 'admin@s2p.co.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'divisi' => 'Koordinator IT Support',
            'no_hp' => '0812-1111-2222',
        ]);

        // 2. Akun Teknisi Mandiri & Tim IT Support PT S2P Cilacap
        // Tim IT Lapangan (Hardware & Jaringan)
        $teknisiHafizh = User::create([
            'name' => 'Hafizh',
            'email' => 'hafizh@s2p.co.id',
            'password' => Hash::make('password'),
            'role' => 'teknisi',
            'spesialisasi' => 'lapangan',
            'divisi' => 'IT Lapangan (Hardware & Jaringan)',
            'no_hp' => '0812-3456-7890',
        ]);

        $teknisiBagas = User::create([
            'name' => 'Bagas Aditya',
            'email' => 'bagas@s2p.co.id',
            'password' => Hash::make('password'),
            'role' => 'teknisi',
            'spesialisasi' => 'lapangan',
            'divisi' => 'IT Lapangan (Hardware & Jaringan)',
            'no_hp' => '0812-9988-7766',
        ]);

        // Tim IT Support & Aplikasi (Software, Akun, & Web)
        $teknisiGiska = User::create([
            'name' => 'Giska',
            'email' => 'giska@s2p.co.id',
            'password' => Hash::make('password'),
            'role' => 'teknisi',
            'spesialisasi' => 'aplikasi',
            'divisi' => 'IT Support (Aplikasi & Web)',
            'no_hp' => '0812-2233-8899',
        ]);

        $teknisiDimas = User::create([
            'name' => 'Dimas',
            'email' => 'dimas.it@s2p.co.id',
            'password' => Hash::make('password'),
            'role' => 'teknisi',
            'spesialisasi' => 'aplikasi',
            'divisi' => 'IT Support (Akun & Helpdesk)',
            'no_hp' => '0812-3344-9900',
        ]);

        $teknisiArif = User::create([
            'name' => 'Arif',
            'email' => 'arif@s2p.co.id',
            'password' => Hash::make('password'),
            'role' => 'teknisi',
            'spesialisasi' => 'aplikasi',
            'divisi' => 'IT Support (Aplikasi Internal)',
            'no_hp' => '0812-4455-0011',
        ]);

        $itSupport = User::create([
            'name' => 'Tim IT Support',
            'email' => 'it.support@s2p.co.id',
            'password' => Hash::make('password'),
            'role' => 'teknisi',
            'spesialisasi' => 'lapangan',
            'divisi' => 'IT Support & Infrastructure',
            'no_hp' => '0812-3456-7890',
        ]);

        // 3. Akun Pegawai Divisi PLTU Karangkandri
        $pegawaiOperasi = User::create([
            'name' => 'Rina Aprilia',
            'email' => 'operasi@s2p.co.id',
            'password' => Hash::make('password'),
            'role' => 'pegawai',
            'divisi' => 'Operasi Boiler',
            'no_hp' => '0813-1122-3344',
        ]);

        $pegawaiPemeliharaan = User::create([
            'name' => 'Ahmad Fauzi',
            'email' => 'pemeliharaan@s2p.co.id',
            'password' => Hash::make('password'),
            'role' => 'pegawai',
            'divisi' => 'Pemeliharaan Turbin',
            'no_hp' => '0813-2233-4455',
        ]);

        $pegawaiK3 = User::create([
            'name' => 'Siti Rahma',
            'email' => 'k3@s2p.co.id',
            'password' => Hash::make('password'),
            'role' => 'pegawai',
            'divisi' => 'K3 / HSE',
            'no_hp' => '0813-4455-6677',
        ]);

        $pegawaiLogistik = User::create([
            'name' => 'Doni Pratama',
            'email' => 'logistik@s2p.co.id',
            'password' => Hash::make('password'),
            'role' => 'pegawai',
            'divisi' => 'Logistik & Coal Handling',
            'no_hp' => '0813-3344-5566',
        ]);

        $pegawaiKeuangan = User::create([
            'name' => 'Maya Putri',
            'email' => 'keuangan@s2p.co.id',
            'password' => Hash::make('password'),
            'role' => 'pegawai',
            'divisi' => 'Keuangan & SDM',
            'no_hp' => '0813-5566-7788',
        ]);

        // 3. Tiket Sesuai Desain Referensi & Lapangan PLTU

        // TKT-0143: Mendesak K3, Switch POE Jaringan offline
        Service::create([
            'ticket_number' => 'TKT-0143',
            'user_id' => $pegawaiK3->id,
            'nama_pelapor' => $pegawaiK3->name,
            'divisi' => 'K3 / HSE',
            'lokasi_ruangan' => 'Pos Utama Security & K3',
            'jenis_perangkat' => 'Kabel LAN / Jaringan',
            'kode_perangkat' => 'SW-K3-01',
            'keluhan' => 'Switch POE jaringan workstation area K3 mendadak offline. Akses internet dan sistem pelaporan safety terputus.',
            'prioritas' => 'Mendesak',
            'status' => 'Baru',
            'teknisi_id' => null,
            'teknisi_nama' => null,
            'checklist' => Service::getDefaultChecklist('Kabel LAN / Jaringan'),
            'created_at' => now()->subMinutes(25),
        ]);

        // TKT-0142: Printer tidak mencetak (Penanganan oleh Hafizh)
        $tkt142 = Service::create([
            'ticket_number' => 'TKT-0142',
            'judul_kendala' => 'Printer tidak mencetak',
            'user_id' => $pegawaiOperasi->id,
            'nama_pelapor' => 'Rina Aprilia',
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Control Room Unit 1',
            'jenis_perangkat' => 'Printer',
            'kode_perangkat' => 'PRN-CR-01',
            'keluhan' => 'Printer menampilkan status offline setelah PC diperbarui. Dokumen menumpuk di antrian cetak.',
            'prioritas' => 'Sedang',
            'status' => 'Diproses',
            'teknisi_id' => $itSupport->id,
            'teknisi_nama' => 'Hafizh',
            'checklist' => [
                ['task' => 'Konfirmasi gejala dengan pelapor', 'done' => true],
                ['task' => 'Cek koneksi kabel USB / jaringan printer', 'done' => true],
                ['task' => 'Cek status layanan Print Spooler', 'done' => false],
                ['task' => 'Instal ulang driver printer', 'done' => false],
                ['task' => 'Cetak halaman uji', 'done' => false],
            ],
            'tindakan_perbaikan' => 'Pemeriksaan kabel USB printer normal. Sedang dilakukan restart service print spooler dan update driver.',
            'created_at' => now()->subHours(2),
        ]);

        $tkt142->comments()->create([
            'user_id' => $itSupport->id,
            'author_name' => 'Dimas Pratama',
            'author_role' => 'teknisi',
            'message' => 'Mohon pastikan printer menyala dan kabel USB terpasang.',
            'created_at' => now()->subMinutes(45),
        ]);

        // TKT-0138: Akun - Email tidak bisa login (Rina Aprilia)
        Service::create([
            'ticket_number' => 'TKT-0138',
            'judul_kendala' => 'Email tidak bisa login',
            'user_id' => $pegawaiOperasi->id,
            'nama_pelapor' => 'Rina Aprilia',
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Control Room Unit 1',
            'jenis_perangkat' => 'Akun',
            'kode_perangkat' => 'AKUN-OPR-01',
            'keluhan' => 'Kata sandi email webmail PT S2P gagal login setelah update kebijakan password berkala.',
            'prioritas' => 'Sedang',
            'status' => 'Selesai',
            'teknisi_id' => $itSupport->id,
            'teknisi_nama' => 'Hendra Wijaya, S.T.',
            'tindakan_perbaikan' => 'Reset password email korporat dan sinkronisasi SSO Active Directory berhasil.',
            'checklist' => Service::getDefaultChecklist('Akun'),
            'tanggal_selesai' => now()->subDays(3),
            'created_at' => now()->subDays(3)->subHours(2),
        ]);

        // TKT-0139: Pemeliharaan - PC sering restart (Penanganan oleh Hafizh)
        Service::create([
            'ticket_number' => 'TKT-0139',
            'user_id' => $pegawaiPemeliharaan->id,
            'nama_pelapor' => $pegawaiPemeliharaan->name,
            'divisi' => 'Pemeliharaan Turbin',
            'lokasi_ruangan' => 'Workshop Turbin Lt. 1',
            'jenis_perangkat' => 'PC Desktop',
            'kode_perangkat' => 'PC-WS-02',
            'keluhan' => 'PC sering mati dan restart sendiri saat menjalankan aplikasi pemantau vibrasi turbin.',
            'prioritas' => 'Sedang',
            'status' => 'Diproses',
            'teknisi_id' => $itSupport->id,
            'teknisi_nama' => 'Hafizh',
            'checklist' => [
                ['task' => 'Pemeriksaan kabel power dan adaptor tegangan listrik', 'done' => true],
                ['task' => 'Bongkar casing & bersihkan debu heatsink/fan dengan kompresor', 'done' => true],
                ['task' => 'Pemeriksaan modul RAM & slot motherboard', 'done' => false],
                ['task' => 'Pengecekan temperatur processor & ganti thermal paste', 'done' => false],
                ['task' => 'Diagnostik kesehatan harddisk / SSD dan Event Viewer', 'done' => false],
            ],
            'tindakan_perbaikan' => 'Pembersihan debu batu bara pada fan prosesor sudah selesai. Sedang proses penggantian thermal paste baru.',
            'created_at' => now()->subHours(4),
        ]);

        // TKT-0137: Logistik - Aplikasi stok error
        Service::create([
            'ticket_number' => 'TKT-0137',
            'user_id' => $pegawaiLogistik->id,
            'nama_pelapor' => $pegawaiLogistik->name,
            'divisi' => 'Logistik & Coal Handling',
            'lokasi_ruangan' => 'Gudang Material & Sparepart',
            'jenis_perangkat' => 'PC Desktop',
            'kode_perangkat' => 'PC-LOG-01',
            'keluhan' => 'Aplikasi stok sparepart error koneksi database lokal setelah switch ruangan di-restart.',
            'prioritas' => 'Rendah',
            'status' => 'Baru',
            'teknisi_id' => null,
            'teknisi_nama' => null,
            'checklist' => Service::getDefaultChecklist('PC Desktop'),
            'created_at' => now()->subMinutes(45),
        ]);

        // TKT-0131: Menunggu sparepart (Penanganan oleh Bagas Aditya)
        Service::create([
            'ticket_number' => 'TKT-0131',
            'user_id' => $pegawaiPemeliharaan->id,
            'nama_pelapor' => 'Bambang Irawan',
            'divisi' => 'Pemeliharaan Turbin',
            'lokasi_ruangan' => 'Ruang Server Switchyard',
            'jenis_perangkat' => 'Kabel LAN / Jaringan',
            'kode_perangkat' => 'SW-SWY-02',
            'keluhan' => 'Port SFP transceiver fiber optic switch nomor 24 rusak tersambar petir sekunder. Menunggu pengiriman modul pengganti dari vendor.',
            'prioritas' => 'Mendesak',
            'status' => 'Menunggu sparepart',
            'teknisi_id' => $itSupport->id,
            'teknisi_nama' => 'Bagas Aditya',
            'sparepart' => '1x SFP Transceiver Gigabit Single Mode',
            'tindakan_perbaikan' => 'Sudah dilakukan isolasi port yang rusak. Menunggu sparepart datang dari vendor resmi.',
            'checklist' => Service::getDefaultChecklist('Kabel LAN / Jaringan'),
            'created_at' => now()->subDays(4),
        ]);

        // TKT-0127: Keuangan - Wi-Fi ruang rapat putus
        Service::create([
            'ticket_number' => 'TKT-0127',
            'user_id' => $pegawaiKeuangan->id,
            'nama_pelapor' => $pegawaiKeuangan->name,
            'divisi' => 'Keuangan & SDM',
            'lokasi_ruangan' => 'Gedung Admin Lantai 2',
            'jenis_perangkat' => 'Kabel LAN / Jaringan',
            'kode_perangkat' => 'AP-ADM-03',
            'keluhan' => 'Access point ruang rapat utama tidak memancarkan sinyal SSID PLTU-Staff.',
            'prioritas' => 'Rendah',
            'status' => 'Baru',
            'teknisi_id' => null,
            'teknisi_nama' => null,
            'checklist' => Service::getDefaultChecklist('Kabel LAN / Jaringan'),
            'created_at' => now()->subMinutes(15),
        ]);

        // Tiket Selesai (Penanganan oleh Hafizh)
        Service::create([
            'ticket_number' => 'TKT-0125',
            'user_id' => $pegawaiOperasi->id,
            'nama_pelapor' => 'Budi Santoso',
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Ruang Kontrol Boiler Lt. 2',
            'jenis_perangkat' => 'Kabel LAN / Jaringan',
            'kode_perangkat' => 'LAN-BLR-02',
            'keluhan' => 'Koneksi LAN SCADA terputus, konektor RJ-45 korosi debu batu bara.',
            'prioritas' => 'Mendesak',
            'status' => 'Selesai',
            'teknisi_id' => $itSupport->id,
            'teknisi_nama' => 'Hafizh',
            'tindakan_perbaikan' => 'Pemotongan ujung kabel UTP Cat6, krimping ulang konektor RJ-45 baru shielded, test 8 pin kabel tester normal.',
            'sparepart' => '2x RJ-45 Cat6 Connector, 1x Plug Boot',
            'checklist' => [
                ['task' => 'Cek koneksi fisik dan lampu indikator port RJ-45', 'done' => true],
                ['task' => 'Pengujian 8 pin kabel dengan LAN cable tester', 'done' => true],
                ['task' => 'Potong & krimping ulang konektor RJ-45 baru', 'done' => true],
                ['task' => 'Uji IP address (DHCP / Static) & Ping gateway', 'done' => true],
                ['task' => 'Verifikasi kecepatan koneksi 1 Gbps ke server', 'done' => true],
            ],
            'tanggal_selesai' => now()->subMinutes(10),
            'created_at' => now()->subMinutes(30),
        ]);

        Service::create([
            'ticket_number' => 'TKT-0124',
            'user_id' => $pegawaiOperasi->id,
            'nama_pelapor' => 'Doni Pratama',
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Gedung Kontrol Coal Handling',
            'jenis_perangkat' => 'PC Desktop',
            'kode_perangkat' => 'PC-CH-05',
            'keluhan' => 'PC mati mendadak saat pantau batu bara karena overheat debu tebal.',
            'prioritas' => 'Mendesak',
            'status' => 'Selesai',
            'teknisi_id' => $itSupport->id,
            'teknisi_nama' => 'Hafizh',
            'tindakan_perbaikan' => 'Bongkar CPU, semprot blower debu batu bara, ganti thermal paste Arctic MX-4, suhu stabil 40°C.',
            'sparepart' => 'Thermal Paste Arctic MX-4',
            'checklist' => Service::getDefaultChecklist('PC Desktop'),
            'tanggal_selesai' => now()->subMinutes(20),
            'created_at' => now()->subMinutes(50),
        ]);

        Service::create([
            'ticket_number' => 'TKT-0123',
            'user_id' => $pegawaiPemeliharaan->id,
            'nama_pelapor' => 'Ahmad Fauzi',
            'divisi' => 'Pemeliharaan Turbin',
            'lokasi_ruangan' => 'Workshop Pemeliharaan Turbin',
            'jenis_perangkat' => 'Rakit PC Baru',
            'kode_perangkat' => 'PC-TRB-NEW-01',
            'keluhan' => 'Perakitan unit workstation baru spesifikasi Core i5, RAM 16GB, SSD 1TB.',
            'prioritas' => 'Sedang',
            'status' => 'Selesai',
            'teknisi_id' => $itSupport->id,
            'teknisi_nama' => 'Hafizh',
            'tindakan_perbaikan' => 'Rakit komponen lengkap, cable routing, instalasi OS Windows 11 Enterprise dan aplikasi getaran turbin.',
            'sparepart' => '1 Unit PC Workstation Baru',
            'checklist' => Service::getDefaultChecklist('Rakit PC Baru'),
            'tanggal_selesai' => now()->subHours(3),
            'created_at' => now()->subDays(1),
        ]);

        // Tiket Selesai oleh Hendra Wijaya, S.T. (Senior IT)
        Service::create([
            'ticket_number' => 'TKT-0120',
            'user_id' => $pegawaiOperasi->id,
            'nama_pelapor' => 'Budi Santoso',
            'divisi' => 'Operasi Boiler',
            'lokasi_ruangan' => 'Server Room Central Control Building',
            'jenis_perangkat' => 'Kabel LAN / Jaringan',
            'kode_perangkat' => 'SW-CCR-01',
            'keluhan' => 'Konfigurasi VLAN trunking switch access layer SCADA Unit 2 putus jalur redundancy.',
            'prioritas' => 'Tinggi',
            'status' => 'Selesai',
            'teknisi_id' => $itSupport->id,
            'teknisi_nama' => 'Hendra Wijaya, S.T.',
            'tindakan_perbaikan' => 'Re-konfigurasi LACP port-channel dan spanning tree protocol (RSTP) pada Cisco core switch, link normal dan failover test sukses.',
            'sparepart' => 'Patch Cord Cat6 Shielded 3 Meter',
            'checklist' => Service::getDefaultChecklist('Kabel LAN / Jaringan'),
            'tanggal_selesai' => now()->subDays(1)->subHours(2),
            'created_at' => now()->subDays(2),
        ]);

        // Tiket Selesai oleh Bagas Aditya (Hardware Lapangan)
        Service::create([
            'ticket_number' => 'TKT-0118',
            'user_id' => $pegawaiK3->id,
            'nama_pelapor' => 'Siti Rahma',
            'divisi' => 'K3 / HSE',
            'lokasi_ruangan' => 'Gedung Security Pos 1 & Jetty',
            'jenis_perangkat' => 'PC Desktop',
            'kode_perangkat' => 'PC-SEC-01',
            'keluhan' => 'PC workstation monitoring security Blue Screen (BSOD) saat memproses data gate pass.',
            'prioritas' => 'Sedang',
            'status' => 'Selesai',
            'teknisi_id' => $itSupport->id,
            'teknisi_nama' => 'Bagas Aditya',
            'tindakan_perbaikan' => 'Pembersihan modul RAM, penggantian thermal paste CPU, dan update driver chipset motherboard.',
            'sparepart' => 'Thermal Paste Arctic MX-4',
            'checklist' => Service::getDefaultChecklist('Hardware'),
            'tanggal_selesai' => now()->subDays(2)->subHours(3),
            'created_at' => now()->subDays(3),
        ]);

        // Tiket Selesai oleh Hendra Wijaya, S.T. (Printer Maintenance)
        Service::create([
            'ticket_number' => 'TKT-0115',
            'user_id' => $pegawaiKeuangan->id,
            'nama_pelapor' => 'Dewi Lestari',
            'divisi' => 'Keuangan & SDM',
            'lokasi_ruangan' => 'Ruang Finance Gedung Admin Lt. 1',
            'jenis_perangkat' => 'Printer',
            'kode_perangkat' => 'PRN-ADM-02',
            'keluhan' => 'Printer laserjet divisi keuangan paper jam terus-menerus dan roller penarik kertas aus.',
            'prioritas' => 'Sedang',
            'status' => 'Selesai',
            'teknisi_id' => $itSupport->id,
            'teknisi_nama' => 'Hendra Wijaya, S.T.',
            'tindakan_perbaikan' => 'Penggantian pickup roller karet baru dan pembersihan sisa serbuk toner pada fixing film assembly.',
            'sparepart' => '1 Set Pickup Roller HP LaserJet',
            'checklist' => Service::getDefaultChecklist('Printer'),
            'tanggal_selesai' => now()->subDays(3)->subHours(4),
            'created_at' => now()->subDays(4),
        ]);

        // Tiket Selesai oleh Bagas Aditya (PC Weighbridge)
        Service::create([
            'ticket_number' => 'TKT-0112',
            'user_id' => $pegawaiLogistik->id,
            'nama_pelapor' => 'Doni Pratama',
            'divisi' => 'Logistik & Coal Handling',
            'lokasi_ruangan' => 'Kantor Timbangan Truk Coal Yard',
            'jenis_perangkat' => 'PC Desktop',
            'kode_perangkat' => 'PC-WB-01',
            'keluhan' => 'Monitor timbangan digital blank hitam setelah terkena lonjakan daya genset cadangan.',
            'prioritas' => 'Mendesak',
            'status' => 'Selesai',
            'teknisi_id' => $itSupport->id,
            'teknisi_nama' => 'Bagas Aditya',
            'tindakan_perbaikan' => 'Penggantian unit Power Supply (PSU) 550W Bronze baru dan kalibrasi port COM serial RS-232 timbangan weighbridge.',
            'sparepart' => '1x PSU 550W 80+ Bronze',
            'checklist' => Service::getDefaultChecklist('PC Desktop'),
            'tanggal_selesai' => now()->subDays(4)->subHours(5),
            'created_at' => now()->subDays(5),
        ]);

        // Tiket Selesai oleh Giska (Tim Support & Aplikasi - Modul Stok Sparepart)
        Service::create([
            'ticket_number' => 'TKT-0110',
            'user_id' => $pegawaiLogistik->id,
            'nama_pelapor' => 'Doni Pratama',
            'divisi' => 'Logistik & Coal Handling',
            'lokasi_ruangan' => 'Gudang Material & Logistik',
            'jenis_perangkat' => 'Aplikasi Internal',
            'kode_perangkat' => 'APP-STOK-01',
            'keluhan' => 'Aplikasi stok sparepart error 500 saat generate laporan mutasi barang bulanan.',
            'prioritas' => 'Sedang',
            'status' => 'Selesai',
            'teknisi_id' => $teknisiGiska->id,
            'teknisi_nama' => 'Giska',
            'tindakan_perbaikan' => 'Optimasi query rekonsiliasi stok pada database Postgres, clear cache Redis server, dan uji download report XLSX.',
            'checklist' => Service::getDefaultChecklist('Aplikasi'),
            'tanggal_selesai' => now()->subDays(1)->subHours(1),
            'created_at' => now()->subDays(1)->subHours(4),
        ]);

        // Tiket Selesai oleh Dimas (Tim Support & Akun - Reset Webmail & SSO)
        Service::create([
            'ticket_number' => 'TKT-0108',
            'user_id' => $pegawaiKeuangan->id,
            'nama_pelapor' => 'Maya Putri',
            'divisi' => 'Keuangan & SDM',
            'lokasi_ruangan' => 'Gedung Admin Lt. 2',
            'jenis_perangkat' => 'Akun',
            'kode_perangkat' => 'AKUN-FIN-02',
            'keluhan' => 'Akun webmail terkunci (account lockout) karena salah input password berulang saat WFH.',
            'prioritas' => 'Sedang',
            'status' => 'Selesai',
            'teknisi_id' => $teknisiDimas->id,
            'teknisi_nama' => 'Dimas',
            'tindakan_perbaikan' => 'Unlock account di Active Directory DC01, generate temporary password, dan verifikasi login SSO berhasil.',
            'checklist' => Service::getDefaultChecklist('Akun'),
            'tanggal_selesai' => now()->subDays(2)->subHours(2),
            'created_at' => now()->subDays(2)->subHours(5),
        ]);

        // 4. Seeder Basis Pengetahuan & SOP Troubleshooting IT PLTU Karangkandri
        \App\Models\KnowledgeBase::firstOrCreate(['judul' => 'SOP Pengkabelan LAN/UTP Cat6 (T568B)'], [
            'user_id' => $itSupport->id,
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

        \App\Models\KnowledgeBase::firstOrCreate(['judul' => 'SOP Pembersihan Debu & Overheating PC'], [
            'user_id' => $itSupport->id,
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

        \App\Models\KnowledgeBase::firstOrCreate(['judul' => 'SOP Diagnostik Bluescreen (BSOD) & RAM'], [
            'user_id' => $itSupport->id,
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

        \App\Models\KnowledgeBase::firstOrCreate(['judul' => 'SOP Reset IP Conflict & Jaringan Lokal'], [
            'user_id' => $itSupport->id,
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

        \App\Models\KnowledgeBase::firstOrCreate(['judul' => 'SOP Printer Offline di Control Room'], [
            'user_id' => $itSupport->id,
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
