<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('device_categories') && !Schema::hasColumn('device_categories', 'solusi_cepat')) {
            Schema::table('device_categories', function (Blueprint $table) {
                $table->json('solusi_cepat')->nullable()->after('deskripsi');
            });

            $defaultSolusi = [
                'Jaringan' => [
                    'Cek kabel LAN dan lampu port di belakang CPU',
                    'Restart adaptor jaringan / Wi-Fi komputer',
                    'Pastikan koneksi Wi-Fi kantor terhubung dengan benar',
                ],
                'Printer' => [
                    'Pastikan kabel power dan kabel USB terpasang kencang',
                    'Cek antrian cetak dan kertas macet (paper jam)',
                    'Restart printer dan coba cetak ulang',
                ],
                'Hardware' => [
                    'Periksa kabel power dan stopkontak di bawah meja',
                    'Pastikan monitor menyala dan kabel display (HDMI/VGA) kencang',
                    'Catat bunyi beep atau lampu indikator jika CPU tidak mau menyala',
                ],
                'Akun' => [
                    'Periksa tombol Caps Lock saat mengetik password',
                    'Pastikan format domain email (@s2p.co.id) sudah benar',
                    'Jika akun terkunci karena salah PIN/password, tunggu 5 menit',
                ],
                'CCTV' => [
                    'Pastikan adaptor PoE atau switch CCTV dalam kondisi menyala',
                    'Catat nomor channel kamera yang hilang atau blank',
                    'Hubungi IT Support segera jika terjadi insiden keamanan mendesak',
                ],
                'Aplikasi' => [
                    'Coba tutup aplikasi dan jalankan kembali (Restart app)',
                    'Coba buka aplikasi melalui browser lain jika berbasis web',
                    'Pastikan koneksi jaringan LAN aktif sebelum login ke sistem',
                ],
                'Telepon' => [
                    'Periksa kabel gagang telepon dan jack RJ11 ke stopkontak',
                    'Untuk radio HT, pastikan baterai terisi penuh dan antena kencang',
                    'Pastikan saluran channel HT sesuai dengan grup divisi Anda',
                ],
                'Lainnya' => [
                    'Coba restart aplikasi atau komputer terlebih dahulu',
                    'Pastikan tidak ada update Windows yang sedang pending',
                    'Tuliskan pesan error lengkap pada formulir di samping',
                ],
            ];

            foreach ($defaultSolusi as $kode => $tips) {
                DB::table('device_categories')
                    ->where('kode', $kode)
                    ->whereNull('solusi_cepat')
                    ->update(['solusi_cepat' => json_encode($tips, JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('device_categories') && Schema::hasColumn('device_categories', 'solusi_cepat')) {
            Schema::table('device_categories', function (Blueprint $table) {
                $table->dropColumn('solusi_cepat');
            });
        }
    }
};
