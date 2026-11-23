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
        Schema::table('maintenance_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('maintenance_logs', 'status')) {
                $table->string('status')->default('Selesai')->index()->after('kondisi');
            }
            if (!Schema::hasColumn('maintenance_logs', 'tim')) {
                $table->string('tim')->nullable()->index()->after('status');
            }
            if (!Schema::hasColumn('maintenance_logs', 'kegiatan')) {
                $table->string('kegiatan')->nullable()->after('tim');
            }
        });

        if (!Schema::hasTable('maintenance_schedules')) {
            Schema::create('maintenance_schedules', function (Blueprint $table) {
                $table->id();
                $table->string('nama_aset');
                $table->string('lokasi_ruangan');
                $table->string('kode_perangkat')->nullable()->index();
                $table->string('frekuensi')->default('Setiap bulan');
                $table->string('tim')->default('Tim IT Lapangan')->index();
                $table->foreignId('teknisi_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('teknisi_nama')->nullable();
                $table->text('kegiatan')->nullable();
                $table->date('tanggal_berikutnya')->index();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');

        Schema::table('maintenance_logs', function (Blueprint $table) {
            if (Schema::hasColumn('maintenance_logs', 'kegiatan')) {
                $table->dropColumn('kegiatan');
            }
            if (Schema::hasColumn('maintenance_logs', 'tim')) {
                $table->dropColumn('tim');
            }
            if (Schema::hasColumn('maintenance_logs', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
