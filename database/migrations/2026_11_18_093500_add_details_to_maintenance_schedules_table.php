<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_schedules', function (Blueprint $table) {
            $table->string('jam_mulai', 10)->nullable()->default('09:00')->after('tanggal_berikutnya');
            $table->json('checklist')->nullable()->after('kegiatan');
            $table->text('catatan_teknisi')->nullable()->after('checklist');
            $table->boolean('kirim_pengingat')->default(true)->after('catatan_teknisi');
            $table->text('kegiatan')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_schedules', function (Blueprint $table) {
            $table->dropColumn(['jam_mulai', 'checklist', 'catatan_teknisi', 'kirim_pengingat']);
        });
    }
};
