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
        // 1. Tabel Master Divisi
        if (!Schema::hasTable('divisions')) {
            Schema::create('divisions', function (Blueprint $table) {
                $table->id();
                $table->string('nama')->unique();
                $table->string('keterangan')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed Data Awal Divisi PLTU Karangkandri
            $defaultDivisions = [
                ['nama' => 'Operasi Boiler', 'keterangan' => 'Unit pembakaran dan penghasil uap tekanan tinggi', 'is_active' => true],
                ['nama' => 'Pemeliharaan Turbin', 'keterangan' => 'Unit turbin uap dan generator listrik PLTU', 'is_active' => true],
                ['nama' => 'K3 / HSE', 'keterangan' => 'Keselamatan, kesehatan kerja, dan lingkungan hidup', 'is_active' => true],
                ['nama' => 'Logistik & Coal Handling', 'keterangan' => 'Bongkar muat dan pasokan batu bara pembangkit', 'is_active' => true],
                ['nama' => 'Keuangan & SDM', 'keterangan' => 'Administrasi personalia, umum, dan keuangan', 'is_active' => true],
                ['nama' => 'Kimia & Laboratorium', 'keterangan' => 'Pengujian kualitas air demineralisasi dan kimia PLTU', 'is_active' => true],
                ['nama' => 'Batu Bara & Ash Handling', 'keterangan' => 'Pengelolaan abu fly ash dan bottom ash', 'is_active' => true],
                ['nama' => 'IT Support & Infrastructure', 'keterangan' => 'Pusat layanan teknologi informasi dan jaringan', 'is_active' => true],
            ];

            foreach ($defaultDivisions as $div) {
                DB::table('divisions')->insert(array_merge($div, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // 2. Tabel Master Kategori Perangkat & Layanan
        if (!Schema::hasTable('device_categories')) {
            Schema::create('device_categories', function (Blueprint $table) {
                $table->id();
                $table->string('nama')->unique();
                $table->string('kode', 50)->nullable();
                $table->string('deskripsi')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed Data Awal Kategori Perangkat IT
            $defaultCategories = [
                ['nama' => 'Hardware & Komputer PC', 'kode' => 'Hardware', 'deskripsi' => 'PC Desktop, Monitor, Laptop, RAM, PSU'],
                ['nama' => 'Printer & Scanner', 'kode' => 'Printer', 'deskripsi' => 'Printer laser, dot matrix, scanner barcode'],
                ['nama' => 'Jaringan & Kabel LAN / Switch', 'kode' => 'Jaringan', 'deskripsi' => 'Kabel UTP, konektor RJ45, Wi-Fi, port switch'],
                ['nama' => 'Akun, Email & Sandi', 'kode' => 'Akun', 'deskripsi' => 'Reset password Windows, email @s2p.co.id, hak akses'],
                ['nama' => 'Server CCTV & NVR Keamanan', 'kode' => 'CCTV', 'deskripsi' => 'Kamera pengawas area pembangkit dan NVR storage'],
                ['nama' => 'Software & Aplikasi Internal', 'kode' => 'Aplikasi', 'deskripsi' => 'Sistem absensi, portal karyawan, ERP PLTU'],
                ['nama' => 'Telepon PABX & Radio HT', 'kode' => 'Telepon', 'deskripsi' => 'Telepon kabel extension kantor dan handy talky'],
                ['nama' => 'Lainnya', 'kode' => 'Lainnya', 'deskripsi' => 'Kendala teknis khusus atau konsultasi infrastruktur IT'],
            ];

            foreach ($defaultCategories as $cat) {
                DB::table('device_categories')->insert(array_merge($cat, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_categories');
        Schema::dropIfExists('divisions');
    }
};
