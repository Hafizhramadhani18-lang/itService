<?php

namespace Database\Seeders;

use App\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Seed basis pengetahuan & panduan mandiri resmi PT S2P PLTU Karangkandri.
     */
    public function run(): void
    {
        $itUser = User::where('role', 'teknisi')->first() ?: User::where('role', 'admin')->first();
        $userId = $itUser ? $itUser->id : null;

        $guides = [
            // =========================================================================
            // KATEGORI 1: SISTEM & PRINTER
            // =========================================================================
            [
                'judul' => 'SOP Mengatasi Printer Offline & Reset Print Spooler',
                'kategori' => 'Sistem & Printer',
                'area_penerapan' => 'Seluruh Kantor Administrasi & Control Room PLTU',
                'target_audience' => 'all',
                'pengantar' => 'Panduan penanganan saat printer terhubung kabel/jaringan namun status di Windows bertuliskan "Offline" atau dokumen antrian cetak macet (stuck in queue).',
                'langkah_langkah' => [
                    'Pastikan lampu indikator printer menyala hijau (Ready), kabel daya dan kabel USB/LAN terpasang kencang.',
                    'Buka Control Panel > Devices and Printers, klik kanan printer yang digunakan, pilih "See what\'s printing".',
                    'Klik menu "Printer", pastikan opsi "Use Printer Offline" dan "Pause Printing" TIDAK tercentang.',
                    'Jika antrian cetak macet tidak bisa dihapus manual, jalankan perintah reset spooler melalui Command Prompt Administrator di bawah.',
                    'Setelah spooler direstart, coba cetak halaman uji (Test Page).'
                ],
                'judul_perintah' => 'Perintah Bersihkan Antrian & Restart Print Spooler (CMD Admin)',
                'perintah_cmd' => "net stop spooler\ndel /Q /F /S \"%systemroot%\\System32\\Spool\\Printers\\*.*\"\nnet start spooler",
                'peringatan' => 'Jangan merestart print spooler jika PC sedang dipakai mencetak logsheet operasional pembangkit yang belum selesai diproses.',
                'tag' => 'printer, offline, spooler, cetak, queue',
                'status' => 'Terbit',
            ],
            [
                'judul' => 'SOP Menghubungkan Printer Jaringan LAN Ruangan',
                'kategori' => 'Sistem & Printer',
                'area_penerapan' => 'Ruang Kerja Divisi & Ruang Rapat PLTU',
                'target_audience' => 'all',
                'pengantar' => 'Panduan menghubungkan laptop atau PC kerja ke printer bersama (Shared Network Printer / IP Printer) yang berada di ruangan divisi Anda.',
                'langkah_langkah' => [
                    'Pastikan PC kerja Anda sudah terhubung ke jaringan LAN kabel atau Wi-Fi resmi kantor PLTU.',
                    'Ketahui alamat IP printer (tertera pada stiker fisik printer, contoh: 192.168.10.25).',
                    'Tekan tombol Windows + R pada keyboard, ketik: \\\\192.168.10.x (sesuai IP print server/printer) lalu tekan Enter.',
                    'Klik dua kali pada ikon printer yang muncul untuk menginstal driver otomatis ke Windows Anda.',
                    'Jadikan sebagai printer default jika Anda sering mencetak di ruangan tersebut (Set as default printer).'
                ],
                'judul_perintah' => 'Uji Koneksi ke IP Printer (CMD)',
                'perintah_cmd' => "ping 192.168.10.25 -t",
                'peringatan' => 'Jika hasil ping menghasilkan "Request timed out", periksa kabel LAN di belakang printer atau pastikan printer dalam kondisi menyala.',
                'tag' => 'printer, network, ip, lan, sharing',
                'status' => 'Terbit',
            ],
            [
                'judul' => 'SOP Penanganan Kertas Macet (Paper Jam) & Ganti Toner',
                'kategori' => 'Sistem & Printer',
                'area_penerapan' => 'Seluruh Unit Kerja PLTU Karangkandri',
                'target_audience' => 'all',
                'pengantar' => 'Langkah aman mengatasi kertas tersangkut pada roller penarik printer laserjet/inkjet dan tata cara penggantian cartridge toner baru.',
                'langkah_langkah' => [
                    'Matikan daya printer dan tunggu sekitar 3 menit agar unit pemanas (fuser) mendingin untuk mencegah luka bakar.',
                    'Buka penutup depan atau penutup belakang printer secara perlahan.',
                    'Pegang kertas macet dengan kedua tangan, lalu tarik PERLAHAN searah jalur keluarnya kertas (jangan ditarik berlawanan arah agar roda gigi roller tidak patah).',
                    'Periksa sisa sobekan kecil kertas dengan bantuan senter pada area dalam roller.',
                    'Untuk penggantian toner: kocok toner baru secara horizontal 5-6 kali agar serbuk merata, lepas segel plastik pelindung, masukkan ke slot hingga terkunci rapi.'
                ],
                'peringatan' => 'Dilarang menggunakan benda tajam seperti gunting, cutter, atau obeng untuk mencongkel kertas di dalam printer karena dapat merusak drum cartridge.',
                'tag' => 'printer, paper jam, kertas macet, toner, roller',
                'status' => 'Terbit',
            ],

            // =========================================================================
            // KATEGORI 2: JARINGAN & LAN
            // =========================================================================
            [
                'judul' => 'SOP Mengatasi Gangguan Koneksi LAN "No Internet / Unidentified"',
                'kategori' => 'Jaringan & LAN',
                'area_penerapan' => 'Workstation Kantor, Control Room, & Gedung Admin',
                'target_audience' => 'all',
                'pengantar' => 'Panduan cepat mandiri saat komputer tiba-tiba kehilangan koneksi jaringan lokal atau muncul tanda seru kuning / bola dunia "No Internet Access".',
                'langkah_langkah' => [
                    'Periksa konektor kabel LAN RJ-45 di belakang CPU: pastikan terpasang erat dan lampu LED indikator menyala berkedip.',
                    'Coba lepas konektor kabel LAN, tunggu 5 detik, lalu pasang kembali hingga terdengar suara "klik".',
                    'Buka Run (Win+R), ketik ncpa.cpl lalu tekan Enter untuk membuka Network Connections.',
                    'Klik kanan pada adapter Ethernet, pilih "Disable", tunggu 5 detik, lalu klik kanan dan pilih "Enable".',
                    'Buka Command Prompt, jalankan perintah flush DNS dan reset socket di bawah.'
                ],
                'judul_perintah' => 'Perintah Pembersihan Cache DNS & Reset Adapter (CMD Admin)',
                'perintah_cmd' => "ipconfig /flushdns\nnetsh winsock reset\nnetsh int ip reset",
                'peringatan' => 'Setelah menjalankan perintah reset winsock, restart komputer Anda untuk menerapkan pembaruan konfigurasi.',
                'tag' => 'lan, jaringan, no internet, unidentified, network',
                'status' => 'Terbit',
            ],
            [
                'judul' => 'SOP Reset IP Conflict & Jaringan Lokal',
                'kategori' => 'Jaringan & LAN',
                'area_penerapan' => 'Subnet Internal PLTU Cilacap',
                'target_audience' => 'all',
                'pengantar' => 'Jika muncul notifikasi "There is an IP address conflict with another system on this network" yang mengakibatkan koneksi terputus sesaat.',
                'langkah_langkah' => [
                    'Buka CMD (Command Prompt), lepas dan perbarui sewa IP DHCP menggunakan perintah di bawah.',
                    'Jika menggunakan IP statik kantor, laporkan ke Helpdesk IT untuk verifikasi tabel alokasi IP address ruangan.',
                    'Pastikan Wi-Fi kantor dan kabel LAN tidak terhubung bersamaan ke satu subnet yang sama secara bersamaan.'
                ],
                'judul_perintah' => 'Perintah Reset IP DHCP Windows',
                'perintah_cmd' => "ipconfig /release\nipconfig /flushdns\nipconfig /renew",
                'peringatan' => 'Jangan lakukan pada PC SCADA / kontrol operasional tanpa izin operator, karena koneksi akan terputus sesaat.',
                'tag' => 'ip, conflict, jaringan, dhcp, subnet',
                'status' => 'Terbit',
            ],
            [
                'judul' => 'SOP Pengkabelan LAN/UTP Cat6 (T568B) & Pengujian Tester',
                'kategori' => 'Jaringan & LAN',
                'area_penerapan' => 'Infrastruktur Jaringan Gigabit PLTU Karangkandri',
                'target_audience' => 'teknisi',
                'pengantar' => 'Di lingkungan pembangkit berdebu batubara dan berinduksi medan magnet motorik, gunakan kabel Cat6 Shielded (FTP/STP). Urutan warna standar industri T-568B:',
                'diagram_kabel' => [
                    '1. Putih-Oranye', '2. Oranye', '3. Putih-Hijau', '4. Biru',
                    '5. Putih-Biru', '6. Hijau', '7. Putih-Cokelat', '8. Cokelat'
                ],
                'langkah_langkah' => [
                    'Kupas jaket kabel luar sepanjang ±2.5 cm menggunakan cable stripper dengan hati-hati agar inti tembaga tidak tergores.',
                    'Luruskan dan pisahkan 4 pasang kabel pilin, potong separator silang plastik (cross divider).',
                    'Susun urutan warna kabel secara presisi sesuai standar T568B.',
                    'Ratakan ujung kabel dengan tang potong secara lurus menyisakan panjang ±1.2 cm.',
                    'Dorong susunan kabel ke dalam konektor RJ-45 Cat6 hingga seluruh ujung tembaga menyentuh pin depan konektor.',
                    'Crimp dengan tang crimping RJ-45 bertekanan stabil hingga pin tembaga menancap sempurna.',
                    'Uji kabel menggunakan LAN cable tester: seluruh pin 1 s/d 8 wajib menyala berurutan.'
                ],
                'peringatan' => 'Wajib diuji dengan LAN tester: seluruh 8 pin harus menyala berurutan sebelum kabel dipasang ke PC atau switch operasional.',
                'tag' => 'lan, cat6, rj45, crimping, t568b, tester',
                'status' => 'Terbit',
            ],
            [
                'judul' => 'SOP Troubleshooting Switch Access Ruangan & Port Dropping',
                'kategori' => 'Jaringan & LAN',
                'area_penerapan' => 'Distribution Rack & Switch Ruangan Divisi',
                'target_audience' => 'teknisi',
                'pengantar' => 'Prosedur teknisi IT menangani port switch ruangan yang mengalami flapping, error disable, atau throughput drop di bawah 100 Mbps.',
                'langkah_langkah' => [
                    'Periksa lampu indikator status port switch: hijau (Gigabit/1000Mbps), oranye (FastEthernet/100Mbps), atau berkedip abnormal.',
                    'Sambungkan kabel konsol ke port console switch dan buka sesi terminal PuTTY (Baud rate 9600).',
                    'Periksa status interface dan statistik error (CRC errors, frame drops, collisions).',
                    'Lakukan pemindahan sementara kabel patch cord ke port cadangan (spare port) di switch yang sama.',
                    'Jika port mengalami error-disable karena port security, lakukan shutdown lalu no shutdown pada interface terkait.'
                ],
                'judul_perintah' => 'Perintah Diagnosa Interface Switch (Cisco IOS)',
                'perintah_cmd' => "show interfaces status\nshow interfaces gigabitEthernet 0/1 | include errors\nshow log",
                'peringatan' => 'Pastikan gelang antistatis terpasang sebelum menyentuh modul chassis rack server/switch.',
                'tag' => 'switch, cisco, lan, port, dropping, rack',
                'status' => 'Terbit',
            ],

            // =========================================================================
            // KATEGORI 3: HARDWARE & PC
            // =========================================================================
            [
                'judul' => 'SOP Diagnostik Komputer Hang, Lambat, & Blue Screen (BSOD)',
                'kategori' => 'Hardware & PC',
                'area_penerapan' => 'Workstation Kantor & Control Room',
                'target_audience' => 'all',
                'pengantar' => 'Panduan langkah awal jika komputer kerja Anda mengalami penurunan performa drastis, aplikasi sering "Not Responding", atau restart sendiri dengan layar biru.',
                'langkah_langkah' => [
                    'Buka Task Manager (tekan Ctrl + Shift + Esc), periksa tab "Processes" dan urutkan berdasarkan penggunaan CPU dan Memory tertinggi.',
                    'Tutup aplikasi atau tab browser yang tidak diperlukan untuk melegakan beban RAM.',
                    'Periksa sisa kapasitas hard disk C: (pastikan memiliki ruang kosong minimal 15-20 GB agar virtual memory Windows bekerja normal).',
                    'Jika terjadi Blue Screen (BSOD), catat kode teks error yang muncul di layar (misal: MEMORY_MANAGEMENT, PAGE_FAULT_IN_NONPAGED_AREA).',
                    'Jalankan utilitas pengecekan integritas sistem Windows (System File Checker) melalui CMD Administrator.'
                ],
                'judul_perintah' => 'Perintah Pemeriksaan Integritas Sistem (CMD Admin)',
                'perintah_cmd' => "sfc /scannow\nDISM /Online /Cleanup-Image /RestoreHealth",
                'peringatan' => 'Proses pemindaian SFC membutuhkan waktu 5-10 menit. Jangan mematikan komputer saat proses sedang berjalan.',
                'tag' => 'bsod, bluescreen, lemot, hang, freeze, ram, sfc',
                'status' => 'Terbit',
            ],
            [
                'judul' => 'SOP Pembersihan Debu Batubara & Overheating PC Pembangkit',
                'kategori' => 'Hardware & PC',
                'area_penerapan' => 'Area Boiler, Coal Handling, Crusher, & Jetty',
                'target_audience' => 'teknisi',
                'pengantar' => 'Debu batubara bersifat konduktif ringan dan memicu korsleting jika lembap. Tahapan penanganan unit komputer yang sering mati sendiri akibat suhu berlebih (overheat):',
                'langkah_langkah' => [
                    'Koordinasikan dengan operator, matikan PC, cabut kabel daya, dan gunakan gelang antistatis.',
                    'Bongkar cover casing dan tahan baling-baling fan pendingin agar tidak berputar berlebihan saat disemprot blower.',
                    'Bersihkan sela-sela sirip heatsink pendingin CPU dan slot RAM dengan kuas berbulu halus antistatis.',
                    'Bersihkan sisa pasta lama pada permukaan processor dengan tisu halus dan cairan alkohol isopropil 99%.',
                    'Oleskan thermal paste baru secara merata berbentuk bulir kacang (tipe non-conductive, standar Arctic MX-4).',
                    'Pasang kembali pendingin heatsink dengan posisi baut mengunci menyilang secara merata.',
                    'Uji suhu CPU pada hardware monitor: saat idle idealnya di bawah 45°C dan full load di bawah 75°C.'
                ],
                'peringatan' => 'Wajib memakai masker partikulat (N95) saat membersihkan komputer dari area pabrik dan boiler.',
                'tag' => 'hardware, debu, batubara, overheating, cpu, thermal paste',
                'status' => 'Terbit',
            ],
            [
                'judul' => 'SOP Pemeliharaan Baterai & Perawatan UPS Ruang Kontrol',
                'kategori' => 'Hardware & PC',
                'area_penerapan' => 'Central Control Room (CCR) & Server Rack PLTU',
                'target_audience' => 'teknisi',
                'pengantar' => 'Prosedur pemeliharaan berkala unit UPS (Uninterruptible Power Supply) untuk menjamin pasokan daya tanpa jeda pada workstation vital operasional pembangkit.',
                'langkah_langkah' => [
                    'Pastikan lampu status Online (AC Normal) menyala dan indikator beban (Load) tidak melebihi 75% kapasitas maksimal.',
                    'Lakukan pengujian mandiri (Self-Test) dengan menekan tombol uji baterai pada panel depan UPS selama 3 detik.',
                    'Buka penutup baterai, periksa fisik aki kering (VRLA): pastikan tidak ada baterai yang kembung, retak, atau korosi pada terminal polaritas.',
                    'Ukur tegangan tiap blok baterai dengan multimeter digital (tegangan floating normal per blok aki 12V adalah 13.5V - 13.8V).',
                    'Bersihkan filter debu pada lubang ventilasi udara kipas pendingin UPS.',
                    'Tempelkan stiker label tanggal uji dan estimasi jatuh tempo penggantian baterai berikutnya.'
                ],
                'peringatan' => 'Jangan pernah melepas sambungan baterai saat UPS sedang menopang beban PC operasional tanpa mengaktifkan mode Manual Bypass.',
                'tag' => 'ups, baterai, daya, ccr, server, listrik',
                'status' => 'Terbit',
            ],

            // =========================================================================
            // KATEGORI 4: AKUN & KEAMANAN
            // =========================================================================
            [
                'judul' => 'SOP Akun Domain Terkunci (Locked Out) & Ganti Password',
                'kategori' => 'Akun & Keamanan',
                'area_penerapan' => 'Domain Active Directory PT S2P PLTU',
                'target_audience' => 'all',
                'pengantar' => 'Panduan saat muncul pesan "The referenced account is currently locked out" akibat salah memasukkan kata sandi komputer domain melebihi 3 kali.',
                'langkah_langkah' => [
                    'Pastikan tombol Caps Lock di keyboard komputer Anda tidak dalam keadaan aktif.',
                    'Sistem keamanan Active Directory PLTU menerapkan auto-lockout sementara selama 15 menit. Anda dapat menunggu 15 menit lalu mencoba login kembali dengan sandi yang benar.',
                    'Jika membutuhkan akses mendesak, hubungi Helpdesk IT Support via Telepon Ext. 102 atau tombol WhatsApp Helpdesk di sistem ini.',
                    'Sebutkan NIP dan nama lengkap Anda untuk verifikasi identitas pegawai oleh personel IT.',
                    'Setelah akun dibuka kuncinya, segera lakukan penggantian kata sandi dengan menekan Ctrl + Alt + Del > Change a password.',
                    'Kriteria kata sandi standar PLTU: minimal 8 karakter, kombinasi huruf besar, huruf kecil, angka, dan karakter simbol.'
                ],
                'peringatan' => 'Dilarang membagikan atau meminjamkan kata sandi akun domain pribadi kepada pihak lain demi kepatuhan audit keamanan informasi PLTU.',
                'tag' => 'akun, password, lockout, active directory, login, sandi',
                'status' => 'Terbit',
            ],
            [
                'judul' => 'SOP Pencegahan Phishing & Keamanan Media USB Flashdisk',
                'kategori' => 'Akun & Keamanan',
                'area_penerapan' => 'Seluruh Komputer Kantor & Perangkat Mobile PLTU',
                'target_audience' => 'all',
                'pengantar' => 'Panduan kepatuhan keamanan siber bagi seluruh pegawai PLTU untuk mencegah serangan Ransomware, pencurian data akun, dan virus USB.',
                'langkah_langkah' => [
                    'Waspadai email mencurigakan dari alamat pengirim eksternal yang meminta klik link verifikasi akun atau unduhan file lampiran .zip, .exe, .scr.',
                    'Periksa alamat URL situs web sebelum memasukkan kredensial login (pastikan domain resmi perusahaan).',
                    'Setiap flashdisk atau hard disk eksternal yang dicolokkan ke PC kantor wajib di-scan terlebih dahulu dengan antivirus resmi perusahaan.',
                    'Jika menemukan file dokumen berubah ekstensi menjadi acak (indikasi ransomware) atau muncul file README_RESTORE, SEGERA cabut kabel LAN dan matikan PC.',
                    'Segera hubungi tim IT Security tanggap darurat di Ext. 102.'
                ],
                'peringatan' => 'Dilarang keras menancapkan media flashdisk pribadi atau modem USB ke komputer DCS / SCADA pembangkit listrik.',
                'tag' => 'keamanan, phishing, ransomware, virus, flashdisk, usb',
                'status' => 'Terbit',
            ],

            // =========================================================================
            // KATEGORI 5: APLIKASI & EMAIL
            // =========================================================================
            [
                'judul' => 'SOP Mengatasi Mailbox Penuh & Troubleshooting Outlook',
                'kategori' => 'Aplikasi & Email',
                'area_penerapan' => 'Aplikasi Microsoft Outlook / Zimbra Mail PLTU',
                'target_audience' => 'all',
                'pengantar' => 'Panduan saat muncul peringatan "Mailbox is full", email baru tidak bisa masuk, atau Outlook mengalami crash saat dibuka.',
                'langkah_langkah' => [
                    'Buka Microsoft Outlook, periksa status koneksi di bilah status pojok kanan bawah (harus berstatus "Connected to Microsoft Exchange").',
                    'Kosongkan folder "Deleted Items" dan "Junk Email" untuk membebaskan ruang kuota penyimpanan email.',
                    'Lakukan pengarsipan email lama ke file penyimpanan lokal (.PST): klik File > Info > Tools > Clean Up Old Items (Archive).',
                    'Pilih folder yang ingin diarsipkan dan tentukan batas tanggal email lama.',
                    'Jika aplikasi Outlook membeku (freeze), coba buka Outlook dalam mode aman (Safe Mode) dengan menekan Win + R dan mengetik perintah di bawah.'
                ],
                'judul_perintah' => 'Perintah Membuka Outlook Safe Mode (Run / CMD)',
                'perintah_cmd' => "outlook.exe /safe",
                'peringatan' => 'Simpan file arsip .PST pada drive D:\\ (Data), jangan disimpan di drive C:\\ agar tidak hilang saat sistem operasi diinstal ulang.',
                'tag' => 'email, outlook, zimbra, mailbox, pst, archive',
                'status' => 'Terbit',
            ],
            [
                'judul' => 'SOP Penanganan Error Login & Session Freeze Aplikasi SAP / Maximo',
                'kategori' => 'Aplikasi & Email',
                'area_penerapan' => 'Sistem Informasi Manajemen Aset & Operasional PLTU',
                'target_audience' => 'all',
                'pengantar' => 'Panduan saat aplikasi Enterprise (SAP GUI / IBM Maximo) membeku, sesi login terkunci ganda, atau gagal membuka modul form work order.',
                'langkah_langkah' => [
                    'Pastikan komputer terhubung ke jaringan lokal Intranet PLTU (atau terhubung ke VPN resmi jika bekerja dari luar area).',
                    'Jika muncul notifikasi "User already logged on in another terminal": pilih opsi "Terminate this session and log on again".',
                    'Jika aplikasi SAP GUI tidak merespon (Not Responding), buka Task Manager (Ctrl+Shift+Esc), cari proses saplogon.exe lalu pilih "End Task".',
                    'Bersihkan cache sementara SAP GUI di folder AppData jika tampilan formulir rusak.',
                    'Buka kembali SAP Logon dan pastikan memilih server sistem produksi yang benar (Production System).'
                ],
                'judul_perintah' => 'Perintah Force Close SAP GUI yang Macet (CMD)',
                'perintah_cmd' => "taskkill /f /im saplogon.exe\ntaskkill /f /im sapgui.exe",
                'peringatan' => 'Pastikan dokumen transaksi atau work order yang sedang Anda kerjakan telah disimpan sebelum menutup paksa aplikasi.',
                'tag' => 'sap, maximo, erp, session, freeze, aplikasi',
                'status' => 'Terbit',
            ],
            [
                'judul' => 'SOP Akses Jarak Jauh (VPN) Jaringan Internal PLTU Karangkandri',
                'kategori' => 'Aplikasi & Email',
                'area_penerapan' => 'Akses Jarak Jauh Pegawai (WFA / Luar Area Pembangkit)',
                'target_audience' => 'all',
                'pengantar' => 'Tata cara menghubungkan laptop kerja ke jaringan privat PLTU Karangkandri dari luar kantor menggunakan VPN resmi perusahaan.',
                'langkah_langkah' => [
                    'Pastikan laptop kerja Anda sudah terhubung ke koneksi internet publik yang stabil.',
                    'Buka aplikasi klien VPN resmi perusahaan (FortiClient / GlobalProtect VPN).',
                    'Masukkan alamat gateway remote server: vpn.s2p.co.id (Port: 443).',
                    'Ketik Username dan Password domain akun kerja PLTU Anda.',
                    'Jika diminta autentikasi dua faktor (2FA), masukkan 6 digit kode token dari aplikasi authenticator di ponsel Anda.',
                    'Setelah status bertuliskan "Connected", periksa koneksi internal dengan melakukan ping ke intranet server.'
                ],
                'judul_perintah' => 'Uji Koneksi Intranet PLTU via VPN (CMD)',
                'perintah_cmd' => "ping 10.10.1.10 -t",
                'peringatan' => 'Putuskan sambungan VPN (Disconnect) setelah selesai bekerja untuk menjaga kapasitas bandwidth gateway VPN kantor.',
                'tag' => 'vpn, remote, wfa, forticlient, intranet, internet',
                'status' => 'Terbit',
            ],

            // =========================================================================
            // KATEGORI 6: AUDIO VISUAL & RUANG RAPAT
            // =========================================================================
            [
                'judul' => 'SOP Pengoperasian Proyektor & Video Conference Ruang Rapat',
                'kategori' => 'Audio Visual & Ruang Rapat',
                'area_penerapan' => 'Ruang Rapat Gedung Administrasi & Gedung CCR',
                'target_audience' => 'all',
                'pengantar' => 'Panduan mandiri mengaktifkan proyektor, menghubungkan layar laptop, dan mengatur sistem kamera/audio meeting (Zoom / MS Teams) di ruang rapat PLTU.',
                'langkah_langkah' => [
                    'Nyalakan proyektor dengan menekan tombol Power pada remote kontrol proyektor atau panel dinding.',
                    'Tancapkan kabel HDMI dari meja rapat ke laptop Anda. Jika laptop menggunakan port USB Type-C, gunakan adapter HDMI resmi.',
                    'Pada keyboard laptop, tekan tombol Windows + P lalu pilih opsi "Duplicate" (agar layar laptop tampil sama persis di proyektor).',
                    'Jika menggunakan video conference (Zoom / Microsoft Teams): buka menu Settings > Audio pada aplikasi rapat, pilih Speaker: "Crestron / Soundbar Ruang Rapat" dan Microphone: "Conference Mic Array".',
                    'Pada menu Video kamera rapat, pilih perangkat kamera "Logitech Rally / ConferenceCam".',
                    'Setelah rapat selesai, matikan proyektor dengan menekan tombol Power remote dua kali dan rapikan kabel HDMI di atas meja.'
                ],
                'peringatan' => 'Jangan langsung mencabut kabel daya proyektor saat proyektor baru dimatikan; tunggu hingga kipas pendingin lampu proyektor berhenti berputar otomatis.',
                'tag' => 'proyektor, meeting, ruang rapat, zoom, teams, hdmi, audio',
                'status' => 'Terbit',
            ],
        ];

        foreach ($guides as $data) {
            $data['user_id'] = $userId;
            KnowledgeBase::updateOrCreate(
                ['judul' => $data['judul']],
                $data
            );
        }
    }
}
