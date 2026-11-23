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
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('judul');
            $table->string('kategori')->default('Jaringan & LAN');
            $table->string('area_penerapan')->nullable();
            $table->string('target_audience')->default('teknisi'); // 'teknisi' (Teknisi saja) atau 'all' (Teknisi dan pegawai)
            $table->text('pengantar')->nullable();
            $table->json('langkah_langkah')->nullable();
            $table->json('diagram_kabel')->nullable();
            $table->string('judul_perintah')->nullable();
            $table->text('perintah_cmd')->nullable();
            $table->text('peringatan')->nullable();
            $table->string('tag')->nullable();
            $table->boolean('perlu_izin_operator')->default(false);
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('status')->default('Terbit'); // 'Draft', 'Terbit'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};
