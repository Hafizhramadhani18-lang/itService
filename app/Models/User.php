<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password', 'role', 'spesialisasi', 'divisi', 'no_hp', 'jabatan', 'foto',
    'status_ketersediaan', 'cuti_mulai', 'cuti_selesai', 'catatan_ketersediaan', 'status_ketersediaan_updated_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function getUsernameAttribute(): string
    {
        return explode('@', $this->email)[0];
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (!empty($this->foto)) {
            $filename = basename($this->foto);
            if (file_exists(public_path('uploads/avatars/' . $filename))) {
                return asset('uploads/avatars/' . $filename);
            }
            if (file_exists(public_path('storage/' . $this->foto))) {
                return asset('storage/' . $this->foto);
            }
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->foto)) {
                return asset('storage/' . $this->foto);
            }
        }
        return null;
    }

    public function getLabelJabatanAttribute(): string
    {
        if (!empty($this->jabatan)) {
            return $this->jabatan;
        }
        if ($this->isAdmin()) {
            return 'Koordinator IT';
        }
        if ($this->isTeknisi()) {
            return $this->label_spesialisasi;
        }
        return 'Staf ' . ($this->divisi ?? 'Operasional');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeknisi(): bool
    {
        return $this->role === 'teknisi';
    }

    public function isTeknisiLapangan(): bool
    {
        return $this->role === 'teknisi' && $this->spesialisasi === 'lapangan';
    }

    public function isTeknisiAplikasi(): bool
    {
        return $this->role === 'teknisi' && $this->spesialisasi === 'aplikasi';
    }

    public function isPegawai(): bool
    {
        return $this->role === 'pegawai';
    }

    public function getLabelSpesialisasiAttribute(): string
    {
        if ($this->isAdmin()) {
            return 'Koordinator IT';
        }
        if ($this->spesialisasi === 'lapangan') {
            return 'Teknisi Lapangan (Hardware & Jaringan)';
        }
        if ($this->spesialisasi === 'aplikasi') {
            return 'Teknisi Support (Aplikasi & Akun)';
        }
        return $this->divisi ?: 'IT Support';
    }

    public function getLabelTimAttribute(): string
    {
        if ($this->isAdmin()) {
            return 'Koordinator IT';
        }
        if ($this->spesialisasi === 'lapangan') {
            return 'Tim IT Lapangan';
        }
        if ($this->spesialisasi === 'aplikasi') {
            return 'Tim IT Support';
        }
        return 'Teknisi IT Support';
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'user_id');
    }

    public function handledServices()
    {
        return $this->hasMany(Service::class, 'teknisi_id');
    }

    /**
     * Cek apakah waktu saat ini berada dalam jam operasional kerja (08:00 - 17:00 WIB).
     */
    public static function isWithinWorkHours(?\Carbon\Carbon $moment = null): bool
    {
        $now = ($moment ? $moment->copy() : now())->timezone('Asia/Jakarta');
        $mins = ($now->hour * 60) + $now->minute;
        // Mulai boleh check-in pukul 08:00 WIB, otomatis mati pukul 17:00 WIB
        return $mins >= (8 * 60) && $now->hour < 17;
    }

    /**
     * Status ketersediaan efektif teknisi:
     * - 'cuti'     : Cuti / Izin (tetap terkunci sampai cuti_selesai lewat)
     * - 'available': Standby / Siap menerima tugas (jam kerja 08:00 - 17:00 WIB)
     * - 'on_task'  : Sedang bertugas / mengerjakan tiket atau PM (jam kerja 08:00 - 17:00 WIB)
     * - 'offline'  : Pulang / di luar jam kerja (>= 17:00 WIB) / belum aktif hari ini
     */
    public function getEffectiveAvailabilityAttribute(): string
    {
        $raw = strtolower(trim((string) ($this->status_ketersediaan ?: 'offline')));
        if ($raw === 'off') {
            $raw = 'offline';
        }
        if (!in_array($raw, ['available', 'on_task', 'cuti', 'offline'], true)) {
            $raw = 'offline';
        }

        if ($raw === 'cuti') {
            if ($this->cuti_selesai && $this->cuti_selesai->isPast() && !$this->cuti_selesai->isToday()) {
                return self::isWithinWorkHours() ? 'available' : 'offline';
            }
            return 'cuti';
        }

        if ($raw === 'offline') {
            return 'offline';
        }

        // Otomatis Offline jika sudah masuk jam pulang (>= 17:00 WIB) atau sebelum 08:00 WIB
        if (!self::isWithinWorkHours()) {
            return 'offline';
        }

        // Kunci Utama: Setiap hari baru / di pagi hari sebelum teknisi check-in, status WAJIB offline!
        // Jangan otomatis dibuat Available atau On Task untuk antisipasi teknisi yang tidak masuk / off / cuti.
        $hasCheckedInToday = $this->status_ketersediaan_updated_at
            && $this->status_ketersediaan_updated_at->copy()->timezone('Asia/Jakarta')->isToday();

        if (!$hasCheckedInToday) {
            return 'offline';
        }

        return $raw;
    }

    public function isTersedia(): bool
    {
        return $this->effective_availability === 'available';
    }

    public function isOnTask(): bool
    {
        return $this->effective_availability === 'on_task';
    }

    public function isWorkingNow(): bool
    {
        return in_array($this->effective_availability, ['available', 'on_task'], true);
    }

    public function isCuti(): bool
    {
        return $this->effective_availability === 'cuti';
    }

    public function isOffline(): bool
    {
        return $this->effective_availability === 'offline';
    }

    public function isOffShift(): bool
    {
        return $this->isOffline();
    }

    public function isUnavailable(): bool
    {
        return in_array($this->effective_availability, ['cuti', 'offline'], true);
    }

    /**
     * Sinkronisasi otomatis Available <-> On Task berdasarkan tiket aktif milik teknisi.
     * Tidak akan menimpa status Cuti. Di luar jam kerja otomatis Offline.
     * Di pagi hari sebelum teknisi check-in mandiri, status tetap Offline (tidak otomatis Available).
     */
    public function syncTaskAvailability(): void
    {
        if (!$this->isTeknisi() || $this->isCuti()) {
            return;
        }

        // Di luar jam kerja (sebelum 08:00 atau >= 17:00 WIB), otomatis offline
        if (!self::isWithinWorkHours()) {
            if ($this->status_ketersediaan !== 'offline') {
                $this->update([
                    'status_ketersediaan' => 'offline',
                    'status_ketersediaan_updated_at' => now(),
                ]);
            }
            return;
        }

        // Cek apakah teknisi sudah check-in / aktif hari ini
        $isToday = $this->status_ketersediaan_updated_at
            && $this->status_ketersediaan_updated_at->copy()->timezone('Asia/Jakarta')->isToday();

        // JIKA BELUM CHECK-IN HARI INI (masih awal hari / pagi sebelum teknisi aktif):
        // Status teknisi WAJIB tetap 'offline' (antisipasi tidak masuk/off/cuti).
        // Jangan pernah otomatis diubah menjadi available atau on_task!
        if (!$isToday || $this->status_ketersediaan === 'offline') {
            if ($this->status_ketersediaan !== 'offline') {
                $this->update([
                    'status_ketersediaan' => 'offline',
                ]);
            }
            return;
        }

        // JIKA TEKNISI SUDAH CHECK-IN HARI INI:
        // Sinkronkan otomatis antara Available <-> On Task berdasarkan ada/tidaknya tiket aktif
        $activeTicketsCount = Service::ownedByTeknisi($this)
            ->whereIn('status', ['Diproses', 'Menunggu sparepart'])
            ->count();

        $desired = $activeTicketsCount > 0 ? 'on_task' : 'available';

        if ($this->status_ketersediaan !== $desired) {
            $this->update([
                'status_ketersediaan' => $desired,
                'status_ketersediaan_updated_at' => now(),
            ]);
        }
    }

    public function getAvailabilityLabelAttribute(): string
    {
        return match ($this->effective_availability) {
            'on_task' => 'On Task',
            'cuti' => 'Sedang Cuti',
            'offline' => 'Offline',
            default => 'Available',
        };
    }

    public function getAvailabilityDotClassAttribute(): string
    {
        return match ($this->effective_availability) {
            'on_task' => 'bg-amber-500',
            'cuti' => 'bg-rose-500',
            'offline' => 'bg-slate-400',
            default => 'bg-emerald-500',
        };
    }

    public function getAvailabilityBadgeClassAttribute(): string
    {
        return match ($this->effective_availability) {
            'on_task' => 'bg-amber-50 text-amber-800 border-amber-200',
            'cuti' => 'bg-rose-50 text-rose-700 border-rose-200',
            'offline' => 'bg-slate-100 text-slate-600 border-slate-300',
            default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        };
    }

    public function getAvailabilityShortNoteAttribute(): string
    {
        $eff = $this->effective_availability;
        if ($eff === 'available') {
            return 'Available';
        }
        if ($eff === 'on_task') {
            return 'On Task';
        }
        if ($eff === 'cuti') {
            return $this->cuti_selesai
                ? 'Sedang Cuti s/d ' . $this->cuti_selesai->locale('id')->isoFormat('D MMM')
                : 'Sedang Cuti';
        }
        // Offline
        if (!self::isWithinWorkHours()) {
            return 'Offline (Luar jam kerja)';
        }
        if ($this->status_ketersediaan_updated_at && !$this->status_ketersediaan_updated_at->copy()->timezone('Asia/Jakarta')->isToday()) {
            return 'Offline (Belum aktif)';
        }
        return 'Offline';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'cuti_mulai' => 'date',
            'cuti_selesai' => 'date',
            'status_ketersediaan_updated_at' => 'datetime',
        ];
    }
}
