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
        if (!Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $table) {
                $table->id();
                $table->string('nama')->unique();
                $table->string('deskripsi')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed Data Awal Area Lokasi PLTU Karangkandri
            $defaultLocations = [
                ['nama' => 'Control Room', 'deskripsi' => 'Ruang kontrol utama Unit 1 & 2'],
                ['nama' => 'Ruang Kontrol Boiler', 'deskripsi' => 'Area operasional boiler pembangkit'],
                ['nama' => 'Gedung Kontrol Coal Handling', 'deskripsi' => 'Area dermaga jetty & conveyor batu bara'],
                ['nama' => 'Workshop Turbin', 'deskripsi' => 'Gedung workshop mekanik & turbin Lt. 1'],
                ['nama' => 'Server Room', 'deskripsi' => 'Ruang server IT & rack data center PLTU'],
                ['nama' => 'Pos Utama Security', 'deskripsi' => 'Pos gerbang depan dan pemantauan CCTV'],
                ['nama' => 'Gudang Material', 'deskripsi' => 'Gudang sparepart dan logistik pembangkit'],
                ['nama' => 'Gedung Admin', 'deskripsi' => 'Kantor administrasi, keuangan, dan HRD'],
            ];

            foreach ($defaultLocations as $loc) {
                DB::table('locations')->insert(array_merge($loc, [
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
        Schema::dropIfExists('locations');
    }
};
