<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->string('jam_mulai', 10)->nullable()->after('tanggal_cek');
            $table->string('jam_selesai', 10)->nullable()->after('jam_mulai');
            $table->string('durasi_jam', 20)->nullable()->after('jam_selesai');
            $table->string('jenis_pemeliharaan', 50)->default('Rutin')->after('kegiatan');
            $table->string('foto_sebelum', 255)->nullable()->after('foto_dokumentasi');
            $table->string('foto_sesudah', 255)->nullable()->after('foto_sebelum');
            $table->unsignedBigInteger('jadwal_id')->nullable()->after('service_id');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropColumn([
                'jam_mulai',
                'jam_selesai',
                'durasi_jam',
                'jenis_pemeliharaan',
                'foto_sebelum',
                'foto_sesudah',
                'jadwal_id',
            ]);
        });
    }
};
