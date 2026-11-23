<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    public const TEKNISI_PERSONEL = [
        'Hafizh' => 'Hafizh (Teknisi Lapangan)',
        'Bagas Aditya' => 'Bagas Aditya (Teknisi Lapangan)',
        'Hendra Wijaya, S.T.' => 'Hendra Wijaya, S.T. (Koordinator IT)',
        'Giska' => 'Giska (Teknisi Support / Aplikasi)',
        'Dimas' => 'Dimas (Teknisi Support / Akun)',
        'Arif' => 'Arif (Teknisi Support / Sistem)',
    ];

    protected $fillable = [
        'ticket_number',
        'judul_kendala',
        'user_id',
        'nama_pelapor',
        'divisi',
        'lokasi_ruangan',
        'jenis_perangkat',
        'kode_perangkat',
        'keluhan',
        'lampiran_foto',
        'prioritas',
        'status',
        'is_read',
        'teknisi_id',
        'teknisi_nama',
        'teknisi_pendamping',
        'tindakan_perbaikan',
        'sparepart',
        'checklist',
        'tanggal_selesai',
        'pelapor_confirmed_at',
        'foto_selesai',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'checklist' => 'array',
            'teknisi_pendamping' => 'array',
            'tanggal_selesai' => 'datetime',
            'pelapor_confirmed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teknisi()
    {
        return $this->belongsTo(User::class, 'teknisi_id');
    }

    public function comments()
    {
        return $this->hasMany(ServiceComment::class)->oldest();
    }

    /**
     * Query scope: batasi tiket yang dimiliki/ditangani oleh user teknisi tertentu
     * (menghindari tabrakan nama depan mirip seperti Arif vs Arifin).
     */
    public function scopeOwnedByTeknisi($query, User $user)
    {
        $teamAccountIds = User::whereRaw('LOWER(TRIM(name)) = ?', ['tim it support'])
            ->pluck('id')
            ->all();

        if (strcasecmp(trim($user->name), 'Tim IT Support') === 0) {
            return $query->where('teknisi_id', $user->id)
                ->where(function ($sub) {
                    $sub->whereNull('teknisi_nama')
                        ->orWhere('teknisi_nama', 'like', '%Tim IT Support%');
                });
        }

        $trimmedName = trim($user->name);
        $firstWord = explode(' ', $trimmedName)[0] ?? '';

        return $query->where(function ($q) use ($user, $teamAccountIds, $trimmedName, $firstWord) {
            $q->where('teknisi_id', $user->id)
              ->orWhere(function ($teamQuery) use ($user) {
                  $teamQuery->whereNotNull('teknisi_pendamping')
                      ->where(function ($jsonQ) use ($user) {
                          $jsonQ->whereJsonContains('teknisi_pendamping', (int) $user->id)
                                ->orWhereJsonContains('teknisi_pendamping', (string) $user->id)
                                ->orWhere('teknisi_pendamping', 'like', '%"id":' . $user->id . '%')
                                ->orWhere('teknisi_pendamping', 'like', '%"id": ' . $user->id . '%');
                      });
              })
              ->orWhere(function ($fallback) use ($teamAccountIds, $trimmedName, $firstWord) {
                  $fallback->where(function ($ownerSlot) use ($teamAccountIds) {
                      $ownerSlot->whereNull('teknisi_id');
                      if (!empty($teamAccountIds)) {
                          $ownerSlot->orWhereIn('teknisi_id', $teamAccountIds);
                      }
                  })->where(function ($nameMatch) use ($trimmedName, $firstWord) {
                      $nameMatch->whereRaw('LOWER(TRIM(teknisi_nama)) = ?', [mb_strtolower($trimmedName)]);
                      if (strlen($firstWord) >= 3) {
                          $fw = mb_strtolower($firstWord);
                          $nameMatch->orWhereRaw('LOWER(TRIM(teknisi_nama)) = ?', [$fw])
                              ->orWhereRaw('LOWER(TRIM(teknisi_nama)) LIKE ?', [$fw . ' %'])
                              ->orWhereRaw('LOWER(TRIM(teknisi_nama)) LIKE ?', ['% ' . $fw])
                              ->orWhereRaw('LOWER(TRIM(teknisi_nama)) LIKE ?', ['% ' . $fw . ' %']);
                      }
                  });
              });
        });
    }

    /**
     * Cek instance: apakah tiket ini ditangani oleh user teknisi terkait.
     */
    public function isOwnedByTeknisi(User $user): bool
    {
        $trimmedName = trim($user->name);
        $isGenericTeamAccount = strcasecmp($trimmedName, 'Tim IT Support') === 0;

        if ($isGenericTeamAccount) {
            if (!empty($this->teknisi_nama)) {
                return strcasecmp(trim($this->teknisi_nama), 'Tim IT Support') === 0;
            }
            return (int) $this->teknisi_id === (int) $user->id;
        }

        if ((int) $this->teknisi_id === (int) $user->id) {
            return true;
        }

        // Cek apakah user termasuk dalam tim teknisi pendamping (Kerja Tim / Multi-Teknisi)
        if (!empty($this->teknisi_pendamping)) {
            $coList = is_array($this->teknisi_pendamping) ? $this->teknisi_pendamping : (json_decode($this->teknisi_pendamping, true) ?: []);
            foreach ($coList as $co) {
                $coId = is_array($co) ? ($co['id'] ?? null) : $co;
                if ((int) $coId === (int) $user->id) {
                    return true;
                }
            }
        }

        // Jika teknisi_id menunjuk akun spesifik lain (bukan null & bukan Tim IT Support), bukan milik user ini
        if ($this->teknisi_id !== null) {
            $assignedTech = $this->relationLoaded('teknisi') ? $this->teknisi : User::find($this->teknisi_id);
            if ($assignedTech && strcasecmp(trim($assignedTech->name), 'Tim IT Support') !== 0) {
                return false;
            }
        }

        $techName = trim((string) ($this->teknisi_nama ?? ''));
        if ($techName === '') {
            return false;
        }

        if (strcasecmp($techName, $trimmedName) === 0) {
            return true;
        }

        $firstWord = explode(' ', $trimmedName)[0] ?? '';
        return strlen($firstWord) >= 3
            && preg_match('/\b' . preg_quote($firstWord, '/') . '\b/i', $techName) === 1;
    }

    /**
     * Memeriksa apakah tiket dapat dibuka oleh user tertentu (Admin, pemilik tiket, atau teknisi bertugas).
     */
    public function canBeOpenedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPegawai()) {
            return (int) $this->user_id === (int) $user->id;
        }

        if ($user->isTeknisi()) {
            // Tiket baru di antrian yang belum diambil siapapun dapat dibuka oleh teknisi manapun
            if ($this->status === 'Baru' && empty($this->teknisi_id) && empty($this->teknisi_nama)) {
                return true;
            }

            // Tiket selesai atau dibatalkan di riwayat tiket dapat dibuka untuk melihat detail/arsip
            if (in_array($this->status, ['Selesai', 'Dibatalkan'])) {
                return true;
            }

            return $this->isOwnedByTeknisi($user);
        }

        return false;
    }

    public function getDisplayJudulAttribute(): string
    {
        if (!empty($this->judul_kendala)) {
            return $this->judul_kendala;
        }

        // Ambil potongan kalimat pertama keluhan
        $parts = explode("\n", $this->keluhan);
        $first = trim($parts[0] ?? $this->keluhan);
        return \Illuminate\Support\Str::limit($first, 50);
    }

    /**
     * Cek apakah tiket ini ditugaskan sebagai kerja tim (multi-teknisi).
     */
    public function getIsTeamAttribute(): bool
    {
        return !empty($this->teknisi_pendamping) && is_array($this->teknisi_pendamping) && count($this->teknisi_pendamping) > 0;
    }

    /**
     * Dapatkan nama seluruh teknisi yang bertugas (Lead + Pendamping).
     */
    public function getTeknisiTimLabelAttribute(): string
    {
        $lead = $this->teknisi_nama ?: ($this->teknisi?->name ?? 'Teknisi');
        if (!$this->is_team) {
            return $lead;
        }

        $names = [];
        foreach ($this->teknisi_pendamping as $co) {
            if (is_array($co) && !empty($co['name'])) {
                $names[] = $co['name'];
            } elseif (is_numeric($co)) {
                $u = User::find($co);
                if ($u) {
                    $names[] = $u->name;
                }
            }
        }

        if (empty($names)) {
            return $lead;
        }

        return "{$lead} (Lead), " . implode(', ', array_unique($names));
    }

    /**
     * Target Tim Penanganan: 'lapangan' (Hardware & Jaringan) vs 'aplikasi' (Support & Akun)
     */
    public function getTargetSpesialisasiAttribute(): string
    {
        $jenis = strtolower($this->jenis_perangkat ?? '');
        if (str_contains($jenis, 'akun') || str_contains($jenis, 'aplikasi') || str_contains($jenis, 'software') || str_contains($jenis, 'sistem') || str_contains($jenis, 'web')) {
            return 'aplikasi';
        }
        return 'lapangan';
    }

    public function getBadgeTargetAttribute(): array
    {
        if ($this->target_spesialisasi === 'aplikasi') {
            return [
                'unit' => 'Tim Support & Aplikasi',
                'short' => 'Aplikasi / Remote',
                'icon' => 'fa-solid fa-laptop-code',
                'bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ];
        }
        return [
            'unit' => 'Tim IT Lapangan',
            'short' => 'Lapangan / Fisik',
            'icon' => 'fa-solid fa-wrench',
            'bg' => 'bg-amber-50 text-amber-700 border-amber-200',
        ];
    }

    // Helper to get default checklist based on device type
    public static function getDefaultChecklist(string $jenisPerangkat): array
    {
        return match ($jenisPerangkat) {
            'Printer', 'Printer & Scanner' => [
                ['task' => 'Konfirmasi gejala dengan pelapor', 'done' => false],
                ['task' => 'Cek koneksi kabel USB / jaringan printer', 'done' => false],
                ['task' => 'Cek status layanan Print Spooler', 'done' => false],
                ['task' => 'Instal ulang driver printer', 'done' => false],
                ['task' => 'Cetak halaman uji (test print)', 'done' => false],
            ],
            'Jaringan', 'Kabel LAN / Jaringan', 'Jaringan & Internet' => [
                ['task' => 'Cek koneksi fisik dan lampu indikator port RJ-45', 'done' => false],
                ['task' => 'Pengujian 8 pin kabel dengan LAN cable tester', 'done' => false],
                ['task' => 'Potong & krimping ulang konektor RJ-45 baru', 'done' => false],
                ['task' => 'Uji IP address (DHCP / Static) & Ping gateway', 'done' => false],
                ['task' => 'Verifikasi kecepatan koneksi 1 Gbps ke server', 'done' => false],
            ],
            'Hardware', 'PC Desktop', 'Laptop', 'Hardware & PC' => [
                ['task' => 'Pemeriksaan kabel power dan adaptor tegangan listrik', 'done' => false],
                ['task' => 'Bongkar casing & bersihkan debu heatsink/fan dengan kompresor', 'done' => false],
                ['task' => 'Pemeriksaan modul RAM & slot motherboard', 'done' => false],
                ['task' => 'Pengecekan temperatur processor & ganti thermal paste', 'done' => false],
                ['task' => 'Diagnostik kesehatan harddisk / SSD dan Event Viewer', 'done' => false],
            ],
            'Akun', 'Akun, Email & Sandi' => [
                ['task' => 'Verifikasi NIP dan identitas pemohon di database pegawai', 'done' => false],
                ['task' => 'Pengecekan status akun (terkunci / lockout di Active Directory)', 'done' => false],
                ['task' => 'Reset kata sandi & pembuatan password sementara', 'done' => false],
                ['task' => 'Uji login pertama dan pandu pegawai ganti password mandiri', 'done' => false],
            ],
            'Aplikasi', 'Aplikasi Internal', 'Sistem Informasi' => [
                ['task' => 'Verifikasi modul atau menu aplikasi yang mengalami kendala', 'done' => false],
                ['task' => 'Cek log error server aplikasi & koneksi database backend', 'done' => false],
                ['task' => 'Pembersihan cache browser / reset session login pengguna', 'done' => false],
                ['task' => 'Pembaruan hak akses peran (role permission) di sistem', 'done' => false],
                ['task' => 'Uji coba transaksi dan konfirmasi normal bersama pelapor', 'done' => false],
            ],
            'Rakit PC Baru' => [
                ['task' => 'Verifikasi kelengkapan spesifikasi komponen workstation', 'done' => false],
                ['task' => 'Perakitan motherboard, CPU, RAM, SSD NVMe, dan PSU', 'done' => false],
                ['task' => 'Manajemen kerapian kabel dalam casing (cable routing)', 'done' => false],
                ['task' => 'Instalasi OS Windows Enterprise & update driver resmi', 'done' => false],
                ['task' => 'Konfigurasi IP internal PLTU dan tes burn-in 1 jam', 'done' => false],
            ],
            default => [
                ['task' => 'Pemeriksaan fisik dan analisis keluhan awal', 'done' => false],
                ['task' => 'Identifikasi kerusakan komponen atau software', 'done' => false],
                ['task' => 'Tindakan perbaikan atau penggantian sparepart', 'done' => false],
                ['task' => 'Pengujian fungsi dan serah terima perangkat', 'done' => false],
            ],
        };
    }

    /**
     * Target SLA (dalam jam) berdasarkan prioritas
     */
    public function getSlaHoursAttribute(): int
    {
        return match ($this->prioritas) {
            'Mendesak' => 2,
            'Tinggi' => 8,
            'Sedang' => 24,
            default => 48,
        };
    }

    /**
     * Waktu deadline SLA
     */
    public function getSlaDeadlineAttribute(): ?\Carbon\Carbon
    {
        if (!$this->created_at) {
            return null;
        }
        return $this->created_at->copy()->addHours($this->sla_hours);
    }

    /**
     * Status pemenuhan SLA:
     * - 'ontime': selesai tepat waktu
     * - 'overdue_finished': selesai terlambat
     * - 'breached': masih aktif tapi melewati deadline
     * - 'warning': masih aktif dan sisa waktu <= 1 jam (atau <= 25%)
     * - 'safe': masih aktif dan waktu masih aman
     * - 'cancelled': tiket dibatalkan
     */
    public function getSlaStatusAttribute(): string
    {
        if ($this->status === 'Dibatalkan') {
            return 'cancelled';
        }

        $deadline = $this->sla_deadline;
        if (!$deadline) {
            return 'safe';
        }

        if ($this->status === 'Selesai') {
            $selesai = $this->tanggal_selesai ?? $this->updated_at;
            if ($selesai && $selesai->gt($deadline)) {
                return 'overdue_finished';
            }
            return 'ontime';
        }

        $now = now();
        if ($now->gt($deadline)) {
            return 'breached';
        }

        $remainingMinutes = $now->diffInMinutes($deadline, false);
        if ($remainingMinutes <= 60) {
            return 'warning';
        }

        return 'safe';
    }

    /**
     * Teks deskriptif SLA (misal: "Sisa 1j 20m", "Lewat 45m", "Selesai Tepat Waktu")
     */
    public function getSlaRemainingTextAttribute(): string
    {
        if ($this->status === 'Dibatalkan') {
            return 'Dibatalkan';
        }

        $deadline = $this->sla_deadline;
        if (!$deadline) {
            return '-';
        }

        if ($this->status === 'Selesai') {
            $selesai = $this->tanggal_selesai ?? $this->updated_at;
            if ($selesai && $selesai->gt($deadline)) {
                $diff = $deadline->diffForHumans($selesai, ['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
                return "Lewat {$diff}";
            }
            return 'Tepat Waktu';
        }

        $now = now();
        if ($now->gt($deadline)) {
            $diff = $deadline->diffForHumans($now, ['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
            return "Lewat {$diff}";
        }

        $diff = $now->diffForHumans($deadline, ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
        return "Sisa {$diff}";
    }

    /**
     * Link WhatsApp Pelapor dengan pesan template profesional
     */
    public function getPelaporWaUrlAttribute(): ?string
    {
        $raw = $this->user?->no_hp;
        if (!$raw) {
            return null;
        }
        $phone = preg_replace('/[^0-9]/', '', $raw);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        if (strlen($phone) < 9) {
            return null;
        }

        $teknisi = auth()->user()?->name ?? ($this->teknisi_nama ?: 'Tim IT Support');
        $msg = "Halo {$this->nama_pelapor}, saya {$teknisi} dari Tim IT Support PLTU Karangkandri menindaklanjuti tiket #{$this->ticket_number} mengenai kendala '{$this->display_judul}' di {$this->lokasi_ruangan}. Mohon info apakah unit saat ini bisa kami periksa di lokasi?";
        return "https://wa.me/{$phone}?text=" . rawurlencode($msg);
    }

    /**
     * Link WhatsApp Teknisi dengan pesan template ramah
     */
    public function getTeknisiWaUrlAttribute(): ?string
    {
        $tekUser = $this->teknisi ?: User::where('name', $this->teknisi_nama)->first();
        $raw = $tekUser?->no_hp;
        if (!$raw) {
            return null;
        }
        $phone = preg_replace('/[^0-9]/', '', $raw);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        if (strlen($phone) < 9) {
            return null;
        }

        $pelapor = auth()->user()?->name ?? $this->nama_pelapor;
        $msg = "Halo Pak/Mas {$this->teknisi_nama}, saya {$pelapor} pelapor tiket #{$this->ticket_number} ('{$this->display_judul}' di {$this->lokasi_ruangan}). Ingin menanyakan perihal penanganan unit kami. Terima kasih.";
        return "https://wa.me/{$phone}?text=" . rawurlencode($msg);
    }

    /**
     * Link WhatsApp Helpdesk / Koordinator IT untuk pelapor saat tiket belum ditugaskan teknisi
     */
    public function getAdminWaUrlAttribute(): ?string
    {
        $admin = User::where('role', 'admin')->first();
        $raw = $admin?->no_hp;
        if (!$raw) {
            return null;
        }
        $phone = preg_replace('/[^0-9]/', '', $raw);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        if (strlen($phone) < 9) {
            return null;
        }

        $pelapor = auth()->user()?->name ?? $this->nama_pelapor;
        $msg = "Halo Tim Helpdesk IT PLTU Karangkandri, saya {$pelapor} ingin menanyakan tindak lanjut tiket #{$this->ticket_number} ('{$this->display_judul}' di {$this->lokasi_ruangan}). Terima kasih.";
        return "https://wa.me/{$phone}?text=" . rawurlencode($msg);
    }

    /**
     * Durasi pengerjaan dalam format waktu asli manusiawi
     * (Contoh: "2 menit", "1 jam 20 mnt", "8 hari 4 jam")
     * Tidak akan memunculkan angka desimal membingungkan seperti "0,0 jam".
     */
    public function getFormattedDurasiAttribute(): string
    {
        if (!$this->created_at) {
            return '—';
        }

        $endAt = $this->tanggal_selesai ?: ($this->status === 'Selesai' ? $this->updated_at : null);
        if (!$endAt) {
            return '—';
        }

        $diffM = (int) abs($this->created_at->diffInMinutes($endAt));

        if ($diffM < 1) {
            return '< 1 menit';
        }
        if ($diffM < 60) {
            return "{$diffM} menit";
        }
        if ($diffM < 1440) {
            $hours = floor($diffM / 60);
            $mins = $diffM % 60;
            return $mins > 0 ? "{$hours} jam {$mins} mnt" : "{$hours} jam";
        }

        $days = floor($diffM / 1440);
        $remHours = floor(($diffM % 1440) / 60);
        return $remHours > 0 ? "{$days} hari {$remHours} jam" : "{$days} hari";
    }

    /**
     * Durasi desimal dalam satuan jam (misal 1.5)
     */
    public function getDurasiJamAttribute(): float
    {
        if (!$this->created_at) {
            return 0.0;
        }
        $endAt = $this->tanggal_selesai ?: ($this->status === 'Selesai' ? $this->updated_at : null);
        if (!$endAt) {
            return 0.0;
        }
        $diffM = $this->created_at->diffInMinutes($endAt);
        return max(0.1, round($diffM / 60, 1));
    }
}
