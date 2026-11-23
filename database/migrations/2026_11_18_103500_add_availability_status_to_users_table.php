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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'status_ketersediaan')) {
                $table->string('status_ketersediaan', 20)->default('available')->after('role');
            }
            if (!Schema::hasColumn('users', 'cuti_mulai')) {
                $table->date('cuti_mulai')->nullable()->after('status_ketersediaan');
            }
            if (!Schema::hasColumn('users', 'cuti_selesai')) {
                $table->date('cuti_selesai')->nullable()->after('cuti_mulai');
            }
            if (!Schema::hasColumn('users', 'catatan_ketersediaan')) {
                $table->string('catatan_ketersediaan', 255)->nullable()->after('cuti_selesai');
            }
            if (!Schema::hasColumn('users', 'status_ketersediaan_updated_at')) {
                $table->timestamp('status_ketersediaan_updated_at')->nullable()->after('catatan_ketersediaan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_filter([
                'status_ketersediaan',
                'cuti_mulai',
                'cuti_selesai',
                'catatan_ketersediaan',
                'status_ketersediaan_updated_at',
            ], fn ($c) => Schema::hasColumn('users', $c));

            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
