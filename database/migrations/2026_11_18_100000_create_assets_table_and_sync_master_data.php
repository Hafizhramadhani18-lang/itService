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
        if (!Schema::hasTable('assets')) {
            Schema::create('assets', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->string('kode_perangkat')->unique();
                $table->string('jenis_perangkat')->nullable()->default('Perangkat IT');
                $table->string('lokasi_ruangan')->nullable();
                $table->string('keterangan')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 1. Sinkronkan seluruh lokasi resmi proyek ke Master Data `locations`
        $existingLocLower = [];
        if (Schema::hasTable('locations')) {
            foreach (DB::table('locations')->pluck('nama') as $locName) {
                $existingLocLower[mb_strtolower(trim((string) $locName))] = true;
            }
        }

        $addLocation = function (?string $locName, string $desc = 'Area operasional PLTU Karangkandri') use (&$existingLocLower) {
            if (!Schema::hasTable('locations')) {
                return;
            }
            $clean = trim((string) $locName);
            if ($clean === '') {
                return;
            }
            $key = mb_strtolower($clean);
            if (isset($existingLocLower[$key])) {
                return;
            }
            DB::table('locations')->insert([
                'nama' => $clean,
                'deskripsi' => $desc,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $existingLocLower[$key] = true;
        };

        // 2. Sinkronkan seluruh aset resmi proyek ke Master Data `assets`
        $seenCodes = [];
        $seenNames = [];
        foreach (DB::table('assets')->get(['nama', 'kode_perangkat']) as $row) {
            $seenCodes[strtoupper(trim((string) $row->kode_perangkat))] = true;
            $seenNames[mb_strtolower(trim((string) $row->nama))] = true;
        }

        $addAsset = function (string $nama, string $kode, ?string $lokasi, ?string $jenis = 'Perangkat IT', ?string $ket = null) use (&$seenCodes, &$seenNames, $addLocation) {
            $cleanNama = trim($nama);
            $cleanKode = strtoupper(trim($kode));
            $cleanLokasi = trim((string) $lokasi);
            $cleanJenis = trim((string) ($jenis ?: 'Perangkat IT'));

            if ($cleanNama === '' || $cleanKode === '') {
                return;
            }
            if ($cleanLokasi !== '') {
                $addLocation($cleanLokasi);
            }
            if (isset($seenCodes[$cleanKode]) || isset($seenNames[mb_strtolower($cleanNama)])) {
                return;
            }

            DB::table('assets')->insert([
                'nama' => $cleanNama,
                'kode_perangkat' => $cleanKode,
                'jenis_perangkat' => $cleanJenis,
                'lokasi_ruangan' => $cleanLokasi !== '' ? $cleanLokasi : null,
                'keterangan' => $ket,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $seenCodes[$cleanKode] = true;
            $seenNames[mb_strtolower($cleanNama)] = true;
        };

        // 2a. Ambil dari Master Jadwal Rutin aktif yang sudah ada di DB
        if (Schema::hasTable('maintenance_schedules')) {
            foreach (DB::table('maintenance_schedules')->orderBy('id')->get() as $ms) {
                $kode = trim((string) ($ms->kode_perangkat ?? ''));
                if ($kode === '') {
                    $slug = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $ms->nama_aset), 0, 6)) ?: 'ASET';
                    $kode = "AST-{$slug}-" . str_pad((string) $ms->id, 2, '0', STR_PAD_LEFT);
                }
                $addAsset(
                    (string) $ms->nama_aset,
                    $kode,
                    (string) ($ms->lokasi_ruangan ?? ''),
                    'Server / UPS',
                    'Tersinkronisasi dari Jadwal Pemeliharaan Rutin'
                );
            }
        }

        // 2b. Ambil dari Log Pemeliharaan yang sudah ada di DB
        if (Schema::hasTable('maintenance_logs')) {
            foreach (DB::table('maintenance_logs')->orderByDesc('id')->get() as $ml) {
                $kode = trim((string) ($ml->kode_perangkat ?? ''));
                if ($kode === '') {
                    continue;
                }
                $addAsset(
                    (string) $ml->nama_perangkat,
                    $kode,
                    (string) ($ml->lokasi_ruangan ?? ''),
                    (string) ($ml->jenis_perangkat ?: 'Perangkat IT'),
                    'Tersinkronisasi dari Log Pemeliharaan'
                );
            }
        }

        // 2c. Ambil aset fisik resmi dari tabel services (hanya yang memiliki kode_perangkat & bukan Akun)
        if (Schema::hasTable('services')) {
            $serviceAssets = DB::table('services')
                ->whereNotNull('kode_perangkat')
                ->where('kode_perangkat', '!=', '')
                ->where(function ($q) {
                    $q->whereNull('jenis_perangkat')
                      ->orWhere('jenis_perangkat', '!=', 'Akun');
                })
                ->orderByDesc('id')
                ->get();

            foreach ($serviceAssets as $svc) {
                $jenis = trim((string) ($svc->jenis_perangkat ?: 'Perangkat IT'));
                $lok = trim((string) ($svc->lokasi_ruangan ?? ''));
                $namaAset = $lok !== '' ? "{$jenis} - {$lok}" : $jenis;
                $addAsset(
                    $namaAset,
                    (string) $svc->kode_perangkat,
                    $lok,
                    $jenis,
                    'Tersinkronisasi dari Inventaris Tiket Layanan IT'
                );
            }
        }

        // 2d. Seed aset inti standar PLTU Karangkandri jika belum ada (untuk fresh install / testing)
        $coreAssets = [
            ['Rack server & UPS', 'UPS-SRV-01', 'Server Room', 'Server / UPS', 'Rack utama & sistem backup UPS ruang server'],
            ['Server aplikasi (database)', 'SRV-APP-01', 'Server Room', 'Server / UPS', 'Server aplikasi internal & database operasional'],
            ['Server NVR CCTV', 'SRV-CCTV-01', 'Pos Utama Security', 'Server / UPS', 'Server rekaman NVR CCTV area gerbang & perimeter'],
            ['Switch access layer SCADA', 'SW-CCR-02', 'Ruang Kontrol Boiler Lt. 2', 'Kabel LAN / Jaringan', 'Switch jaringan layer akses SCADA & kontrol boiler'],
            ['Printer jaringan', 'PRN-CR-01', 'Control Room', 'Printer', 'Printer laserjet jaringan operasional Control Room'],
            ['Workstation Control Room', 'PC-WS-02', 'Control Room Unit 1', 'Komputer PC', 'Workstation operator Control Room Unit 1'],
        ];

        foreach ($coreAssets as [$nama, $kode, $lok, $jenis, $ket]) {
            $addAsset($nama, $kode, $lok, $jenis, $ket);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
