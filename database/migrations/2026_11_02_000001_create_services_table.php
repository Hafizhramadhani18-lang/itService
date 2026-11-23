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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nama_pelapor');
            $table->string('divisi');
            $table->string('lokasi_ruangan');
            $table->string('jenis_perangkat');
            $table->string('kode_perangkat')->nullable();
            $table->text('keluhan');
            $table->enum('prioritas', ['Rendah', 'Sedang', 'Tinggi', 'Mendesak'])->default('Sedang');
            $table->enum('status', ['Baru', 'Diproses', 'Menunggu sparepart', 'Selesai', 'Dibatalkan'])->default('Baru');
            $table->boolean('is_read')->default(false);
            $table->foreignId('teknisi_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('teknisi_nama')->nullable();
            $table->text('tindakan_perbaikan')->nullable();
            $table->string('sparepart')->nullable();
            $table->json('checklist')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
