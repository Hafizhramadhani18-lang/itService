<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_number')->unique();
            $table->date('tanggal_cek')->index();
            $table->string('periode_bulan', 7)->index(); // YYYY-MM
            $table->string('kode_perangkat')->index();
            $table->string('nama_perangkat');
            $table->string('jenis_perangkat')->index();
            $table->string('divisi')->index();
            $table->string('lokasi_ruangan');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('teknisi_nama');
            $table->json('teknisi_pendamping')->nullable();
            $table->string('kondisi')->default('Prima')->index(); // Prima, Perlu Perhatian, Kritis
            $table->json('checklist')->nullable();
            $table->text('tindakan');
            $table->text('catatan_rekomendasi')->nullable();
            $table->string('status_tindak_lanjut')->default('Selesai'); // Selesai, Perlu Pantauan, Eskalasi Tiket
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('foto_dokumentasi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
