<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_number',
        'tanggal_cek',
        'periode_bulan',
        'kode_perangkat',
        'nama_perangkat',
        'jenis_perangkat',
        'divisi',
        'lokasi_ruangan',
        'user_id',
        'teknisi_nama',
        'teknisi_pendamping',
        'kondisi',
        'status',
        'tim',
        'kegiatan',
        'jam_mulai',
        'jam_selesai',
        'durasi_jam',
        'jenis_pemeliharaan',
        'checklist',
        'tindakan',
        'catatan_rekomendasi',
        'status_tindak_lanjut',
        'service_id',
        'jadwal_id',
        'foto_dokumentasi',
        'foto_sebelum',
        'foto_sesudah',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_cek' => 'date',
            'checklist' => 'array',
            'teknisi_pendamping' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teknisi()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function jadwal()
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'jadwal_id');
    }

    public function getFrekuensiLabelAttribute(): string
    {
        if ($this->jadwal && !empty($this->jadwal->frekuensi)) {
            return $this->jadwal->frekuensi;
        }
        $match = MaintenanceSchedule::where('nama_aset', $this->nama_perangkat)->first();
        if ($match && !empty($match->frekuensi)) {
            return $match->frekuensi;
        }
        return 'Setiap bulan';
    }

    /**
     * Helpers for Schedule & Execution Status (Mockup Referensi Admin)
     */
    public function isSelesai(): bool
    {
        return strtolower($this->status ?: 'selesai') === 'selesai';
    }

    public function isTerlambat(): bool
    {
        if (strtolower($this->status) === 'terlambat') {
            return true;
        }
        if (strtolower($this->status) === 'terjadwal' && $this->tanggal_cek && $this->tanggal_cek->isPast() && !$this->tanggal_cek->isToday()) {
            return true;
        }
        return false;
    }

    public function isTerjadwal(): bool
    {
        if ($this->isTerlambat()) {
            return false;
        }
        return strtolower($this->status) === 'terjadwal';
    }

    public function isDraft(): bool
    {
        return strtolower((string) $this->status) === 'draft';
    }

    public function getComputedStatusAttribute(): string
    {
        if ($this->isDraft()) {
            return 'Draft';
        }
        if ($this->isTerlambat()) {
            return 'Terlambat';
        }
        if ($this->isTerjadwal()) {
            return 'Terjadwal';
        }
        return 'Selesai';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->computed_status) {
            'Draft' => 'bg-amber-50 text-amber-700 border-amber-200/80',
            'Terlambat' => 'bg-rose-50 text-rose-700 border-rose-200/80',
            'Terjadwal' => 'bg-sky-50 text-sky-700 border-sky-200/80',
            default => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
        };
    }

    public function getLateDaysTextAttribute(): ?string
    {
        if (!$this->isTerlambat() || !$this->tanggal_cek) {
            return null;
        }
        $days = (int) abs($this->tanggal_cek->copy()->startOfDay()->diffInDays(now()->startOfDay()));
        return $days > 0 ? "Lewat jadwal {$days} hari" : "Lewat jadwal hari ini";
    }

    public function getFormattedJamAttribute(): ?string
    {
        if (!empty($this->jam_mulai) && !empty($this->jam_selesai)) {
            $m = str_replace(':', '.', substr($this->jam_mulai, 0, 5));
            $s = str_replace(':', '.', substr($this->jam_selesai, 0, 5));
            return "{$m} - {$s} WIB";
        }
        if (!empty($this->jam_mulai)) {
            $m = str_replace(':', '.', substr($this->jam_mulai, 0, 5));
            return "{$m} WIB";
        }
        return null;
    }

    public function getDisplayKegiatanAttribute(): string
    {
        if (!empty($this->kegiatan)) {
            return $this->kegiatan;
        }
        return $this->tindakan ?: 'Pemeriksaan fisik & sistem berkala';
    }

    public function getTimLabelAttribute(): string
    {
        if (!empty($this->tim)) {
            return $this->tim;
        }
        if ($this->teknisi && !empty($this->teknisi->divisi)) {
            return $this->teknisi->divisi;
        }
        return 'Tim IT Lapangan';
    }

    /**
     * Helpers for Kondisi
     */
    public function isPrima(): bool
    {
        return in_array(strtolower($this->kondisi), ['prima', 'normal', 'baik', 'prima / normal']);
    }

    public function isPerluPerhatian(): bool
    {
        return in_array(strtolower($this->kondisi), ['perlu perhatian', 'perhatian', 'warning']);
    }

    public function isKritis(): bool
    {
        return in_array(strtolower($this->kondisi), ['kritis', 'rusak', 'kritis / rusak', 'perlu perbaikan']);
    }

    public function getKondisiBadgeClassAttribute(): string
    {
        if ($this->isPrima()) {
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-emerald-500/20';
        }
        if ($this->isPerluPerhatian()) {
            return 'bg-amber-50 text-amber-700 border-amber-200 ring-amber-500/20';
        }
        return 'bg-rose-50 text-rose-700 border-rose-200 ring-rose-500/20';
    }

    public function getKondisiIconAttribute(): string
    {
        if ($this->isPrima()) {
            return 'fa-circle-check text-emerald-500';
        }
        if ($this->isPerluPerhatian()) {
            return 'fa-triangle-exclamation text-amber-500';
        }
        return 'fa-circle-xmark text-rose-500';
    }

    /**
     * Cek apakah log pemeliharaan ini dilakukan oleh user teknisi terkait (sebagai Lead atau Pendamping)
     */
    public function isOwnedByTeknisi(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ((int) $this->user_id === (int) $user->id) {
            return true;
        }

        if (!empty($this->teknisi_nama) && strcasecmp(trim($this->teknisi_nama), trim($user->name)) === 0) {
            return true;
        }

        // Cek teknisi pendamping (Kerja Tim IT)
        if (!empty($this->teknisi_pendamping) && is_array($this->teknisi_pendamping)) {
            foreach ($this->teknisi_pendamping as $item) {
                if (is_numeric($item) && (int) $item === (int) $user->id) {
                    return true;
                }
                if (is_string($item) && (strcasecmp(trim($item), trim($user->name)) === 0 || str_contains(strtolower($item), strtolower($user->name)))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Scope untuk query log milik teknisi tertentu
     */
    public function scopeOwnedByTeknisi($query, User $user)
    {
        $userId = $user->id;
        $userName = trim($user->name);

        return $query->where(function ($q) use ($userId, $userName) {
            $q->where('user_id', $userId)
              ->orWhere('teknisi_nama', 'like', "%{$userName}%")
              ->orWhere(function ($sub) use ($userId, $userName) {
                  $sub->whereNotNull('teknisi_pendamping')
                      ->where(function ($tq) use ($userId, $userName) {
                          $tq->whereJsonContains('teknisi_pendamping', (int) $userId)
                             ->orWhereJsonContains('teknisi_pendamping', (string) $userId)
                             ->orWhereJsonContains('teknisi_pendamping', $userName)
                             ->orWhere('teknisi_pendamping', 'like', "%\"{$userName}\"%")
                             ->orWhere('teknisi_pendamping', 'like', "%{$userId}%");
                      });
              });
        });
    }

    /**
     * Label Teknisi Lengkap (Lead + Co-technicians)
     */
    public function getTeknisiLengkapAttribute(): string
    {
        $nama = $this->teknisi_nama ?: 'Teknisi IT';
        $pendamping = $this->teknisi_pendamping;

        if (is_array($pendamping) && count($pendamping) > 0) {
            $coNames = [];
            foreach ($pendamping as $item) {
                if (is_numeric($item)) {
                    $u = User::find($item);
                    if ($u) {
                        $coNames[] = $u->name;
                    }
                } elseif (is_string($item) && trim($item) !== '') {
                    $coNames[] = $item;
                }
            }
            if (!empty($coNames)) {
                return $nama . ' (+ ' . implode(', ', $coNames) . ')';
            }
        }

        return $nama;
    }

    /**
     * Auto generate unique sequential log number: PM-YYYYMM-XXX
     */
    public static function generateLogNumber(?Carbon $date = null): string
    {
        $targetDate = $date ?: now();
        $prefix = 'PM-' . $targetDate->format('Ym') . '-';
        
        $latest = static::where('log_number', 'like', $prefix . '%')
            ->orderBy('log_number', 'desc')
            ->first();

        if ($latest) {
            $lastSeq = (int) substr($latest->log_number, -3);
            $nextSeq = str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextSeq = '001';
        }

        return $prefix . $nextSeq;
    }

    /**
     * Standard PLTU Checklists Templates by Device Type
     */
    public static function defaultChecklist(string $jenis): array
    {
        $jenisLower = strtolower($jenis);

        if (str_contains($jenisLower, 'jaringan') || str_contains($jenisLower, 'switch') || str_contains($jenisLower, 'lan')) {
            return [
                ['item' => 'Kebersihan port switch & debu industri/batu bara', 'checked' => true],
                ['item' => 'Indikator LED link/activity semua port aktif normal', 'checked' => true],
                ['item' => 'Suhu chassis switch & sirkulasi udara fan rack', 'checked' => true],
                ['item' => 'Kerapihan patch cord & labeling kode kabel', 'checked' => true],
                ['item' => 'Tes ping latency & paket loss ke Core Gateway PLTU', 'checked' => true],
                ['item' => 'Grounding rack server & proteksi sambaran petir', 'checked' => true],
            ];
        }

        if (str_contains($jenisLower, 'printer') || str_contains($jenisLower, 'scanner')) {
            return [
                ['item' => 'Kebersihan roller pickup kertas dari serbuk debu', 'checked' => true],
                ['item' => 'Level sisa toner cartridge / tinta di atas 30%', 'checked' => true],
                ['item' => 'Hasil cetak test page bersih tanpa garis atau noda', 'checked' => true],
                ['item' => 'Koneksi kabel USB / print server LAN stabil', 'checked' => true],
                ['item' => 'Tray kertas presisi tanpa potensi paper jam', 'checked' => true],
            ];
        }

        if (str_contains($jenisLower, 'server') || str_contains($jenisLower, 'ups') || str_contains($jenisLower, 'cctv')) {
            return [
                ['item' => 'Status baterai & backup power UPS (uji failover)', 'checked' => true],
                ['item' => 'Suhu ruang server / rack terukur 18°C - 22°C', 'checked' => true],
                ['item' => 'Pengecekan utilisasi storage & kesehatan RAID harddisk', 'checked' => true],
                ['item' => 'Verifikasi backup data rutin terjadwal berhasil', 'checked' => true],
                ['item' => 'Pemeriksaan log error OS & hardware server', 'checked' => true],
            ];
        }

        // Default: PC / Workstation / Desktop / Laptop
        return [
            ['item' => 'Pembersihan debu batu bara di casing, kipas fan & heatsink', 'checked' => true],
            ['item' => 'Pemeriksaan suhu prosesor CPU & kondisi pasta termal', 'checked' => true],
            ['item' => 'Kondisi kesehatan harddisk / SSD (S.M.A.R.T health > 90%)', 'checked' => true],
            ['item' => 'Pembaruan definisi antivirus & scan keamanan sistem', 'checked' => true],
            ['item' => 'Pembersihan temporary files & optimasi disk cache OS', 'checked' => true],
            ['item' => 'Pengecekan kabel daya listrik, grounding & soket stopkontak', 'checked' => true],
        ];
    }
}
