<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'kode_perangkat',
        'jenis_perangkat',
        'lokasi_ruangan',
        'keterangan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Generate kode aset unik secara otomatis berdasarkan nama aset, lokasi, dan jenis perangkat.
     * Contoh: "Kabel LAN" di "Ruang Kontrol Boiler" -> "LAN-BLR-03".
     */
    public static function generateKode(string $nama, ?string $lokasi = null, ?string $jenis = null): string
    {
        $haystack = mb_strtolower(trim($nama . ' ' . ($jenis ?? '')));

        // 1. Tentukan prefix tipe perangkat
        if (str_contains($haystack, 'ups') || (str_contains($haystack, 'rack') && str_contains($haystack, 'server'))) {
            $typePrefix = 'UPS';
        } elseif (str_contains($haystack, 'nvr') && str_contains($haystack, 'server')) {
            $typePrefix = 'SRV';
        } elseif (str_contains($haystack, 'cctv') || str_contains($haystack, 'kamera') || str_contains($haystack, 'nvr')) {
            $typePrefix = 'CCTV';
        } elseif (str_contains($haystack, 'switch')) {
            $typePrefix = 'SW';
        } elseif (str_contains($haystack, 'access point') || str_contains($haystack, 'wifi') || preg_match('/\bap\b/', $haystack)) {
            $typePrefix = 'AP';
        } elseif (str_contains($haystack, 'router') || str_contains($haystack, 'mikrotik') || str_contains($haystack, 'gateway')) {
            $typePrefix = 'RTR';
        } elseif (str_contains($haystack, 'lan') || str_contains($haystack, 'kabel') || str_contains($haystack, 'jaringan') || str_contains($haystack, 'utp')) {
            $typePrefix = 'LAN';
        } elseif (str_contains($haystack, 'printer') || str_contains($haystack, 'scanner') || str_contains($haystack, 'laserjet')) {
            $typePrefix = 'PRN';
        } elseif (str_contains($haystack, 'server') || str_contains($haystack, 'database') || str_contains($haystack, 'historian')) {
            $typePrefix = 'SRV';
        } elseif (str_contains($haystack, 'laptop') || str_contains($haystack, 'notebook')) {
            $typePrefix = 'LPT';
        } elseif (str_contains($haystack, 'pc') || str_contains($haystack, 'komputer') || str_contains($haystack, 'workstation') || str_contains($haystack, 'desktop') || str_contains($haystack, 'monitor')) {
            $typePrefix = 'PC';
        } elseif (str_contains($haystack, 'genset') || str_contains($haystack, 'inverter') || str_contains($haystack, 'power')) {
            $typePrefix = 'PWR';
        } else {
            $alpha = strtoupper(preg_replace('/[^A-Za-z]/', '', $nama));
            $typePrefix = substr($alpha ?: 'AST', 0, 3);
        }

        // 2. Tentukan kode area/lokasi
        $locLower = mb_strtolower(trim((string) $lokasi));
        if ($locLower === '') {
            $locCode = 'IT';
        } elseif (str_contains($locLower, 'boiler')) {
            $locCode = 'BLR';
        } elseif (str_contains($locLower, 'turbin')) {
            $locCode = 'TRB';
        } elseif (str_contains($locLower, 'coal handling')) {
            $locCode = 'CH';
        } elseif (str_contains($locLower, 'timbangan') || str_contains($locLower, 'coal yard')) {
            $locCode = 'WB';
        } elseif (str_contains($locLower, 'switchyard')) {
            $locCode = 'SWY';
        } elseif (str_contains($locLower, 'control room') || str_contains($locLower, 'ccr')) {
            $locCode = 'CR';
        } elseif (str_contains($locLower, 'server')) {
            $locCode = 'SRV';
        } elseif (str_contains($locLower, 'jetty')) {
            $locCode = 'JTY';
        } elseif (str_contains($locLower, 'security') || str_contains($locLower, 'k3')) {
            $locCode = 'SEC';
        } elseif (str_contains($locLower, 'admin') || str_contains($locLower, 'finance') || str_contains($locLower, 'hrd')) {
            $locCode = 'ADM';
        } elseif (str_contains($locLower, 'gudang') || str_contains($locLower, 'logistik') || str_contains($locLower, 'material')) {
            $locCode = 'LOG';
        } elseif (str_contains($locLower, 'maintenance') || str_contains($locLower, 'workshop')) {
            $locCode = 'MNT';
        } else {
            $words = preg_split('/\s+/', preg_replace('/[^a-zA-Z0-9\s]/', '', $locLower), -1, PREG_SPLIT_NO_EMPTY);
            if (count($words) >= 2) {
                $locCode = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 2));
            } else {
                $locCode = strtoupper(substr($words[0] ?? 'IT', 0, 3));
            }
        }

        $basePrefix = "{$typePrefix}-{$locCode}";
        $existingCodes = self::where('kode_perangkat', 'like', "{$basePrefix}-%")
            ->pluck('kode_perangkat')
            ->map(fn ($c) => strtoupper(trim((string) $c)))
            ->all();

        $maxNum = 0;
        foreach ($existingCodes as $code) {
            if (preg_match('/-(\d+)$/', $code, $m)) {
                $maxNum = max($maxNum, (int) $m[1]);
            }
        }

        $nextNum = $maxNum + 1;
        $candidate = sprintf('%s-%02d', $basePrefix, $nextNum);
        while (self::whereRaw('UPPER(kode_perangkat) = ?', [$candidate])->exists()) {
            $nextNum++;
            $candidate = sprintf('%s-%02d', $basePrefix, $nextNum);
        }

        return $candidate;
    }

    /**
     * Sinkronkan (firstOrCreate) aset & lokasi ke Data Master bila ada entri manual baru.
     */
    public static function syncFromInput(?string $nama, ?string $kode, ?string $lokasi = null, ?string $jenis = null): void
    {
        $cleanNama = trim((string) $nama);
        $cleanLokasi = trim((string) $lokasi);
        $cleanJenis = trim((string) ($jenis ?: 'Perangkat IT'));
        $cleanKode = strtoupper(trim((string) $kode));
        if ($cleanKode === '' && $cleanNama !== '') {
            $cleanKode = self::generateKode($cleanNama, $cleanLokasi, $cleanJenis);
        }

        if ($cleanLokasi !== '') {
            $existsLoc = Location::whereRaw('LOWER(nama) = ?', [mb_strtolower($cleanLokasi)])->exists();
            if (!$existsLoc) {
                Location::create([
                    'nama' => $cleanLokasi,
                    'deskripsi' => 'Ditambahkan otomatis dari penjadwalan pemeliharaan',
                    'is_active' => true,
                ]);
            }
        }

        if ($cleanNama !== '' && $cleanKode !== '') {
            $existsAsset = self::whereRaw('UPPER(kode_perangkat) = ?', [$cleanKode])
                ->orWhereRaw('LOWER(nama) = ?', [mb_strtolower($cleanNama)])
                ->exists();
            if (!$existsAsset) {
                self::create([
                    'nama' => $cleanNama,
                    'kode_perangkat' => $cleanKode,
                    'jenis_perangkat' => $cleanJenis,
                    'lokasi_ruangan' => $cleanLokasi !== '' ? $cleanLokasi : null,
                    'is_active' => true,
                ]);
            }
        }
    }
}
