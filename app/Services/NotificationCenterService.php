<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceComment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NotificationCenterService
{
    /**
     * Ambil daftar notifikasi dinamis sesuai role user (Admin / Teknisi).
     * @param bool $dailyOnly Jika true, hanya memuat notifikasi hari ini (reset setiap pergantian hari pukul 00:00 WIB)
     */
    public static function getForUser(User $user, int $limit = 10, bool $dailyOnly = false): array
    {
        if ($user->isAdmin()) {
            $items = self::buildAdminItems($user, $dailyOnly);
            $cacheRole = 'admin';
        } elseif ($user->isTeknisi()) {
            $items = self::buildTeknisiItems($user, $dailyOnly);
            $cacheRole = 'teknisi';
        } else {
            $items = self::buildPegawaiItems($user, $dailyOnly);
            $cacheRole = 'pegawai';
        }

        // Ketika jendela harian aktif (dropdown lonceng), pastikan secara ketat hanya menampilkan notifikasi hari ini
        if ($dailyOnly) {
            $items = array_values(array_filter($items, function ($it) {
                if (!empty($it['timestamp']) && $it['timestamp'] instanceof Carbon) {
                    return $it['timestamp']->isToday();
                }
                return false;
            }));
        }

        // Urutkan berdasarkan waktu terbaru (tie-break unread & id terbesar)
        usort($items, function ($a, $b) {
            $tsA = $a['timestamp'] instanceof Carbon ? $a['timestamp']->timestamp : 0;
            $tsB = $b['timestamp'] instanceof Carbon ? $b['timestamp']->timestamp : 0;
            if ($tsB !== $tsA) {
                return $tsB <=> $tsA;
            }
            $unreadCmp = (!empty($b['force_unread']) ? 1 : 0) <=> (!empty($a['force_unread']) ? 1 : 0);
            if ($unreadCmp !== 0) {
                return $unreadCmp;
            }
            return ((int) ($b['service_id'] ?? 0)) <=> ((int) ($a['service_id'] ?? 0));
        });

        $items = array_slice($items, 0, $limit);

        return self::applyReadState($user, $cacheRole, $items);
    }

    /**
     * Bangun item notifikasi eksekutif untuk Koordinator IT (Admin).
     */
    private static function buildAdminItems(User $adminUser, bool $dailyOnly = false): array
    {
        $items = [];
        $usedTicketIds = [];
        $cutoff = $dailyOnly ? now()->startOfDay() : now()->subDays(30);

        // 1. Tiket Mendesak Belum Diambil (Fail-Safe SOP: Selalu pantau tiket darurat)
        $urgentQuery = Service::where('status', 'Baru')
            ->whereNull('teknisi_id')
            ->where('prioritas', 'Mendesak');
        if ($dailyOnly) {
            $urgentQuery->where('created_at', '>=', $cutoff);
        }
        $urgentTickets = $urgentQuery
            ->latest('created_at')
            ->take(3)
            ->get();

        foreach ($urgentTickets as $t) {
            $usedTicketIds[] = $t->id;
            $hoursOpen = $t->created_at ? (int) $t->created_at->diffInHours(now()) : 0;
            $team = self::determineTeam($t->jenis_perangkat);
            $ts = $t->created_at ?: now();
            $isToday = $ts->isToday();

            $items[] = [
                'id' => 'urgent_' . $t->id,
                'service_id' => $t->id,
                'type' => 'urgent',
                'icon' => 'fa-solid fa-clock-rotate-left',
                'icon_bg' => 'bg-rose-50 text-rose-500',
                'title' => $hoursOpen >= 2
                    ? 'Tiket mendesak belum diambil lebih dari 2 jam'
                    : 'Tiket mendesak baru belum diambil',
                'subtitle' => "{$t->ticket_number} · " . ($t->divisi ?: 'Umum') . " · {$team}",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('admin.penugasan'),
                'force_unread' => !$t->is_read,
                'is_today' => $isToday,
            ];
        }

        // 2. Laporan Baru Terkirim (Tiket Selesai)
        $completedQuery = Service::where('status', 'Selesai');
        if ($dailyOnly) {
            $completedQuery->where(function ($q) use ($cutoff) {
                $q->where('tanggal_selesai', '>=', $cutoff)
                  ->orWhere(function ($sq) use ($cutoff) {
                      $sq->whereNull('tanggal_selesai')->where('updated_at', '>=', $cutoff);
                  });
            });
        }
        $completedTickets = $completedQuery
            ->orderByRaw('COALESCE(tanggal_selesai, updated_at) DESC')
            ->take(3)
            ->get();

        foreach ($completedTickets as $t) {
            $usedTicketIds[] = $t->id;
            $endAt = $t->tanggal_selesai ?: $t->updated_at;
            $durasiStr = $t->formatted_durasi;
            $techName = explode(' ', trim($t->teknisi_nama ?: 'Teknisi'))[0];
            $ts = $endAt ?: now();
            $isToday = $ts->isToday();

            $items[] = [
                'id' => 'report_' . $t->id,
                'service_id' => $t->id,
                'type' => 'report',
                'icon' => 'fa-regular fa-file-lines',
                'icon_bg' => 'bg-emerald-50 text-emerald-600',
                'title' => 'Laporan baru terkirim',
                'subtitle' => "{$t->ticket_number} oleh {$techName} · durasi {$durasiStr}",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('services.show', $t),
                'force_unread' => false,
                'is_today' => $isToday,
            ];
        }

        // 3. Tiket Ditugaskan ke Teknisi
        $assignedQuery = Service::whereNotNull('teknisi_id')
            ->whereIn('status', ['Diproses', 'Menunggu sparepart'])
            ->whereNotIn('id', $usedTicketIds);
        if ($dailyOnly) {
            $assignedQuery->where(function ($q) use ($cutoff) {
                $q->where('updated_at', '>=', $cutoff)
                  ->orWhere('created_at', '>=', $cutoff);
            });
        }
        $assignedTickets = $assignedQuery
            ->latest('updated_at')
            ->take(2)
            ->get();

        foreach ($assignedTickets as $t) {
            $usedTicketIds[] = $t->id;
            $team = self::determineTeam($t->jenis_perangkat);
            $techName = explode(' ', trim($t->teknisi_nama ?: 'Teknisi'))[0];
            $ts = $t->updated_at ?: $t->created_at ?: now();
            $isToday = $ts->isToday();

            $items[] = [
                'id' => 'assigned_' . $t->id,
                'service_id' => $t->id,
                'type' => 'assigned',
                'icon' => 'fa-solid fa-user-group',
                'icon_bg' => 'bg-purple-50 text-purple-600',
                'title' => 'Tiket ditugaskan ke teknisi',
                'subtitle' => "{$t->ticket_number} ditangani {$techName} · {$team}",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('services.show', $t),
                'force_unread' => false,
                'is_today' => $isToday,
            ];
        }

        // 4. Tiket Menunggu Lama (> 12 Jam Belum Diambil)
        $waitingQuery = Service::where('status', 'Baru')
            ->whereNull('teknisi_id')
            ->whereNotIn('id', $usedTicketIds)
            ->where('created_at', '<=', now()->subHours(12));
        if ($dailyOnly) {
            $waitingQuery->where('created_at', '>=', $cutoff);
        }
        $waitingTickets = $waitingQuery
            ->latest('created_at')
            ->take(2)
            ->get();

        foreach ($waitingTickets as $t) {
            $usedTicketIds[] = $t->id;
            $days = (int) $t->created_at->diffInDays(now());
            $hours = (int) $t->created_at->diffInHours(now());
            $waitStr = $days >= 1 ? "selama {$days} hari" : "selama {$hours} jam";
            $ts = $t->created_at ?: now();
            $isToday = $ts->isToday();

            $items[] = [
                'id' => 'waiting_' . $t->id,
                'service_id' => $t->id,
                'type' => 'waiting',
                'icon' => 'fa-solid fa-clock-rotate-left',
                'icon_bg' => 'bg-amber-50 text-amber-600',
                'title' => 'Tiket menunggu lama',
                'subtitle' => "{$t->ticket_number} belum diambil {$waitStr}",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('admin.penugasan'),
                'force_unread' => !$t->is_read,
                'is_today' => $isToday,
            ];
        }

        // 5. Tiket Baru Masuk Antrian
        $newQueueQuery = Service::where('status', 'Baru')
            ->whereNull('teknisi_id')
            ->whereNotIn('id', $usedTicketIds);
        if ($dailyOnly) {
            $newQueueQuery->where('created_at', '>=', $cutoff);
        }
        $newQueueTickets = $newQueueQuery
            ->latest('created_at')
            ->take(2)
            ->get();

        foreach ($newQueueTickets as $t) {
            $usedTicketIds[] = $t->id;
            $team = self::determineTeam($t->jenis_perangkat);
            $ts = $t->created_at ?: now();
            $isToday = $ts->isToday();

            $items[] = [
                'id' => 'new_ticket_' . $t->id,
                'service_id' => $t->id,
                'type' => 'new_ticket',
                'icon' => 'fa-solid fa-inbox',
                'icon_bg' => 'bg-sky-50 text-sky-600',
                'title' => 'Tiket baru masuk antrian',
                'subtitle' => "{$t->ticket_number} · " . ($t->divisi ?: 'Umum') . " · {$team}",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('admin.penugasan'),
                'force_unread' => !$t->is_read,
                'is_today' => $isToday,
            ];
        }

        // 6. Akun Pengguna Baru
        $recentUsersQuery = User::where('id', '!=', $adminUser->id);
        if ($dailyOnly) {
            $recentUsersQuery->where('created_at', '>=', $cutoff);
        }
        $recentUsers = $recentUsersQuery
            ->latest('created_at')
            ->take(2)
            ->get();

        foreach ($recentUsers as $u) {
            $ts = $u->created_at ?: now()->subDays(2);
            $targetUnit = $u->divisi ?: ucfirst($u->role);
            $isToday = $ts->isToday();

            $items[] = [
                'id' => 'user_' . $u->id,
                'service_id' => null,
                'type' => 'user',
                'icon' => 'fa-regular fa-user',
                'icon_bg' => 'bg-blue-50 text-blue-600',
                'title' => 'Akun pengguna baru',
                'subtitle' => "{$u->name} ditambahkan ke {$targetUnit}",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('admin.users'),
                'force_unread' => false,
                'is_today' => $isToday,
            ];
        }

        // 6b. Status Cuti / Offline Manual Teknisi (Agar Admin segera alihkan tiket & jadwal PM)
        $unavailableTechs = User::where('role', 'teknisi')
            ->whereIn('status_ketersediaan', ['cuti', 'offline', 'off'])
            ->get()
            ->filter(function ($tech) use ($dailyOnly, $cutoff) {
                if (!$tech->isUnavailable()) {
                    return false;
                }
                $ts = $tech->status_ketersediaan_updated_at ?: $tech->updated_at;
                if ($dailyOnly && $ts && $ts->lt($cutoff)) {
                    return false;
                }
                return true;
            });

        foreach ($unavailableTechs as $tech) {
            $ts = $tech->status_ketersediaan_updated_at ?: $tech->updated_at ?: now();
            $actTickets = Service::where('teknisi_id', $tech->id)
                ->whereIn('status', ['Diproses', 'Menunggu sparepart'])
                ->count();
            $actPm = \App\Models\MaintenanceLog::where('user_id', $tech->id)
                ->whereIn('status', ['Terjadwal', 'Terlambat'])
                ->count();
            $taskInfo = ($actTickets > 0 || $actPm > 0)
                ? " · {$actTickets} tiket aktif, {$actPm} jadwal PM perlu dialihkan"
                : ($tech->catatan_ketersediaan ? " · {$tech->catatan_ketersediaan}" : '');

            $items[] = [
                'id' => 'tech_leave_' . $tech->id . '_' . $ts->timestamp,
                'service_id' => null,
                'type' => 'urgent',
                'icon' => 'fa-solid fa-calendar-xmark',
                'icon_bg' => 'bg-rose-50 text-rose-600',
                'title' => "{$tech->name} berstatus {$tech->availability_short_note}",
                'subtitle' => "{$tech->label_tim}{$taskInfo}",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('admin.users', ['role' => 'teknisi']),
                'force_unread' => $ts->isToday(),
                'is_today' => $ts->isToday(),
            ];
        }

        // 7. Tiket Dibatalkan oleh Pelapor
        $cancelledQuery = Service::where('status', 'Dibatalkan')
            ->whereNotIn('id', $usedTicketIds);
        if ($dailyOnly) {
            $cancelledQuery->where(function ($q) use ($cutoff) {
                $q->where('updated_at', '>=', $cutoff)
                  ->orWhere('created_at', '>=', $cutoff);
            });
        }
        $cancelledTickets = $cancelledQuery
            ->latest('updated_at')
            ->take(2)
            ->get();

        foreach ($cancelledTickets as $t) {
            $usedTicketIds[] = $t->id;
            $pelaporName = explode(' ', trim($t->nama_pelapor ?: 'Pelapor'))[0];
            $ts = $t->updated_at ?: $t->created_at ?: now();
            $isToday = $ts->isToday();

            $items[] = [
                'id' => 'cancelled_' . $t->id,
                'service_id' => $t->id,
                'type' => 'cancelled',
                'icon' => 'fa-solid fa-ban',
                'icon_bg' => 'bg-amber-50 text-amber-600',
                'title' => 'Tiket dibatalkan oleh pelapor',
                'subtitle' => "{$t->ticket_number} dibatalkan oleh {$pelaporName} (" . ($t->divisi ?: 'Umum') . ')',
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('services.show', $t),
                'force_unread' => false,
                'is_today' => $isToday,
            ];
        }

        // 8. Log Pemeliharaan (PM) yang Baru Diselesaikan Teknisi
        $completedPms = \App\Models\MaintenanceLog::where('status', 'Selesai')
            ->where('updated_at', '>=', $cutoff)
            ->latest('updated_at')
            ->take(3)
            ->get();

        foreach ($completedPms as $pm) {
            $ts = $pm->updated_at ?: ($pm->created_at ?: now());
            $kondisiText = $pm->isPrima() ? 'Baik' : ($pm->isPerluPerhatian() ? 'Perlu tindak lanjut' : 'Rusak');
            $techName = $pm->teknisi_nama ?: 'Teknisi IT';

            $items[] = [
                'id' => 'pm_done_' . $pm->id,
                'service_id' => $pm->id,
                'type' => 'report',
                'icon' => 'fa-solid fa-clipboard-check',
                'icon_bg' => 'bg-emerald-50 text-emerald-600',
                'title' => 'Log pemeliharaan (PM) selesai',
                'subtitle' => "{$pm->nama_perangkat} oleh {$techName} · kondisi {$kondisiText}",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('maintenance.show', $pm),
                'force_unread' => true,
                'is_today' => $ts->isToday(),
            ];
        }

        // 9. Jadwal PM Hari Ini & Terlambat untuk Admin (Monitoring Koordinator IT)
        $todayStr = now()->toDateString();
        $adminPmDueTasks = \App\Models\MaintenanceLog::whereIn('status', ['Terjadwal', 'Terlambat'])
            ->where(function ($q) use ($todayStr) {
                $q->whereDate('tanggal_cek', $todayStr)
                  ->orWhere(function ($lateQ) use ($todayStr) {
                      $lateQ->where('status', 'Terlambat')
                            ->orWhereDate('tanggal_cek', '<', $todayStr);
                  });
            })
            ->orderBy('tanggal_cek', 'asc')
            ->take(4)
            ->get();

        foreach ($adminPmDueTasks as $pm) {
            $tglJadwal = $pm->tanggal_cek ? $pm->tanggal_cek->format('d/m/Y') : '-';
            $jamSuffix = $pm->jam_mulai ? ' · ' . str_replace(':', '.', substr($pm->jam_mulai, 0, 5)) . ' WIB' : '';
            $isLate = $pm->isTerlambat();
            $pic = $pm->teknisi_nama ?: ($pm->tim ?: 'Tim IT');
            $ts = now()->startOfDay()->addHours(7);

            $items[] = [
                'id' => 'pm_admin_due_' . $pm->id . '_' . ($pm->tanggal_cek ? $pm->tanggal_cek->format('Ymd') : '0'),
                'service_id' => $pm->id,
                'type' => $isLate ? 'urgent' : 'assigned',
                'icon' => $isLate ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-calendar-check',
                'icon_bg' => $isLate ? 'bg-rose-50 text-rose-600' : 'bg-teal-50 text-teal-600',
                'title' => $isLate ? 'Jadwal PM terlambat (belum selesai)' : 'Jadwal PM hari ini',
                'subtitle' => "{$pm->nama_perangkat} ({$pm->frekuensi_label}) · PIC: {$pic} · {$tglJadwal}{$jamSuffix}",
                'time_label' => $isLate ? 'Terlambat' : 'Hari ini',
                'timestamp' => $ts,
                'url' => route('maintenance.index', ['frekuensi' => 'semua']),
                'force_unread' => true,
                'is_today' => true,
            ];
        }

        return $items;
    }

    /**
     * Bangun item notifikasi sesuai peran Teknisi IT (mengikuti mockup referensi Teknisi).
     */
    private static function buildTeknisiItems(User $teknisiUser, bool $dailyOnly = false): array
    {
        $items = [];
        $usedTicketIds = [];
        $cutoff = $dailyOnly ? now()->startOfDay() : now()->subDays(30);

        // 1. Tiket mendesak belum diambil
        $urgentQuery = Service::where('status', 'Baru')
            ->whereNull('teknisi_id')
            ->where('prioritas', 'Mendesak');
        if ($dailyOnly) {
            $urgentQuery->where('created_at', '>=', $cutoff);
        }
        $urgentTickets = $urgentQuery
            ->latest('created_at')
            ->take(3)
            ->get();

        foreach ($urgentTickets as $t) {
            $usedTicketIds[] = $t->id;
            $hoursOpen = $t->created_at ? (int) $t->created_at->diffInHours(now()) : 0;
            $judul = Str::limit($t->display_judul, 42);
            $suffix = $hoursOpen >= 2 ? 'melewati batas 2 jam' : ($t->divisi ?: 'Prioritas Mendesak');
            $ts = $t->created_at ?: now();
            $isToday = $ts->isToday();

            $items[] = [
                'id' => 'urgent_' . $t->id,
                'service_id' => $t->id,
                'type' => 'urgent',
                'icon' => 'fa-solid fa-clock-rotate-left',
                'icon_bg' => 'bg-rose-50 text-rose-500',
                'title' => 'Tiket mendesak belum diambil',
                'subtitle' => "{$t->ticket_number} · {$judul} · {$suffix}",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('services.show', $t),
                'force_unread' => !$t->is_read,
                'is_today' => $isToday,
            ];
        }

        // 2. Tiket baru untuk tim terkait (antrian masuk belum diambil)
        $newQueueQuery = Service::where('status', 'Baru')
            ->whereNull('teknisi_id')
            ->whereNotIn('id', $usedTicketIds);
        if ($dailyOnly) {
            $newQueueQuery->where('created_at', '>=', $cutoff);
        }
        $newQueueTickets = $newQueueQuery
            ->latest('created_at')
            ->take(3)
            ->get();

        foreach ($newQueueTickets as $t) {
            $usedTicketIds[] = $t->id;
            $targetTeam = self::determineTeam($t->jenis_perangkat);
            $judul = Str::limit($t->display_judul, 40);
            $ts = $t->created_at ?: now();
            $isToday = $ts->isToday();

            $items[] = [
                'id' => 'new_ticket_' . $t->id,
                'service_id' => $t->id,
                'type' => 'new_ticket',
                'icon' => 'fa-solid fa-inbox',
                'icon_bg' => 'bg-blue-50 text-blue-600',
                'title' => "Tiket baru untuk {$targetTeam}",
                'subtitle' => "{$t->ticket_number} · {$judul} · " . ($t->divisi ?: 'Umum'),
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('services.show', $t),
                'force_unread' => !$t->is_read,
                'is_today' => $isToday,
            ];
        }

        // Ambil kandidat tiket aktif & selesai untuk mengecek kepemilikan teknisi
        $activeAssignedQuery = Service::with(['comments' => function ($q) {
            $q->where('author_role', 'admin')->latest('created_at');
        }])
            ->whereIn('status', ['Diproses', 'Menunggu sparepart'])
            ->whereNotIn('id', $usedTicketIds);

        if ($dailyOnly) {
            $activeAssignedQuery->where(function ($q) use ($cutoff) {
                $q->where('updated_at', '>=', $cutoff)
                  ->orWhere('created_at', '>=', $cutoff)
                  ->orWhereHas('comments', function ($cq) use ($cutoff) {
                      $cq->where('created_at', '>=', $cutoff);
                  });
            });
        }

        $activeAssignedAll = $activeAssignedQuery
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->take(20)
            ->get();

        // Prioritaskan tiket milik teknisi ini yang memiliki penugasan/pengalihan admin
        $myAssignedTickets = $activeAssignedAll
            ->filter(fn ($t) => self::ticketBelongsToTeknisi($t, $teknisiUser))
            ->sortByDesc(fn ($t) => $t->comments->isNotEmpty() ? 1 : 0)
            ->take(2);

        foreach ($myAssignedTickets as $t) {
            $usedTicketIds[] = $t->id;
            $adminComment = $t->comments->first();
            $hasAdminAssign = $adminComment !== null;
            $judul = Str::limit($t->display_judul, 38);
            $ts = $adminComment?->created_at ?: ($t->updated_at ?: $t->created_at ?: now());
            $isToday = $ts->isToday();

            $items[] = [
                'id' => 'assigned_' . $t->id,
                'service_id' => $t->id,
                'type' => 'assigned',
                'icon' => 'fa-solid fa-user-group',
                'icon_bg' => 'bg-purple-50 text-purple-600',
                'title' => $hasAdminAssign ? 'Tiket dialihkan ke Anda' : 'Tiket dalam penanganan Anda',
                'subtitle' => $hasAdminAssign
                    ? "{$t->ticket_number} dari Koordinator IT · kendala: {$judul}"
                    : "{$t->ticket_number} · {$judul} · " . ($t->divisi ?: 'Umum'),
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('services.show', $t),
                'force_unread' => ($hasAdminAssign && !$t->is_read),
                'is_today' => $isToday,
            ];
        }

        $otherTaken = $activeAssignedAll
            ->filter(fn ($t) => !self::ticketBelongsToTeknisi($t, $teknisiUser) && !empty($t->teknisi_nama))
            ->take(2)
            ->all();

        // 4. Pelapor mengonfirmasi selesai (tiket Selesai milik teknisi ini)
        $completedQuery = Service::where('status', 'Selesai')
            ->whereNotIn('id', $usedTicketIds);

        if ($dailyOnly) {
            $completedQuery->where(function ($q) use ($cutoff) {
                $q->where('tanggal_selesai', '>=', $cutoff)
                  ->orWhere(function ($sq) use ($cutoff) {
                      $sq->whereNull('tanggal_selesai')->where('updated_at', '>=', $cutoff);
                  });
            });
        }

        $completedAll = $completedQuery
            ->orderByRaw('COALESCE(tanggal_selesai, updated_at) DESC')
            ->take(15)
            ->get();

        $confirmedCount = 0;
        foreach ($completedAll as $t) {
            if (!self::ticketBelongsToTeknisi($t, $teknisiUser)) {
                continue;
            }
            $usedTicketIds[] = $t->id;
            $confirmedCount++;
            $ts = $t->tanggal_selesai ?: ($t->updated_at ?: now());
            $isToday = $ts->isToday();

            $items[] = [
                'id' => 'confirmed_' . $t->id,
                'service_id' => $t->id,
                'type' => 'confirmed',
                'icon' => 'fa-solid fa-check',
                'icon_bg' => 'bg-emerald-50 text-emerald-600',
                'title' => 'Pelapor mengonfirmasi selesai',
                'subtitle' => "{$t->ticket_number} · " . ($t->nama_pelapor ?: 'Pelapor'),
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('services.show', $t),
                'force_unread' => false,
                'is_today' => $isToday,
            ];

            if ($confirmedCount >= 2) {
                break;
            }
        }

        // 5. Tiket diambil teknisi lain
        foreach ($otherTaken as $t) {
            $usedTicketIds[] = $t->id;
            $ts = $t->updated_at ?: ($t->created_at ?: now());
            $picker = $t->teknisi_nama ?: 'teknisi lain';
            $isToday = $ts->isToday();

            $items[] = [
                'id' => 'taken_' . $t->id,
                'service_id' => $t->id,
                'type' => 'taken',
                'icon' => 'fa-solid fa-inbox',
                'icon_bg' => 'bg-slate-100 text-slate-500',
                'title' => 'Tiket diambil teknisi lain',
                'subtitle' => "{$t->ticket_number} diambil {$picker} dan hilang dari antrian",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('services.show', $t),
                'force_unread' => false,
                'is_today' => $isToday,
            ];
        }

        // 6. Tiket dibatalkan oleh pelapor
        $cancelledQuery = Service::where('status', 'Dibatalkan')
            ->whereNotIn('id', $usedTicketIds);

        if ($dailyOnly) {
            $cancelledQuery->where(function ($q) use ($cutoff) {
                $q->where('updated_at', '>=', $cutoff)
                  ->orWhere('created_at', '>=', $cutoff);
            });
        }

        $cancelledTickets = $cancelledQuery
            ->latest('updated_at')
            ->take(2)
            ->get();

        foreach ($cancelledTickets as $t) {
            $usedTicketIds[] = $t->id;
            $pelaporName = explode(' ', trim($t->nama_pelapor ?: 'Pelapor'))[0];
            $ts = $t->updated_at ?: ($t->created_at ?: now());
            $isToday = $ts->isToday();

            $items[] = [
                'id' => 'cancelled_' . $t->id,
                'service_id' => $t->id,
                'type' => 'cancelled',
                'icon' => 'fa-solid fa-ban',
                'icon_bg' => 'bg-amber-50 text-amber-600',
                'title' => 'Tiket dibatalkan oleh pelapor',
                'subtitle' => "{$t->ticket_number} dibatalkan oleh {$pelaporName} dan keluar dari antrian",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('services.show', $t),
                'force_unread' => false,
                'is_today' => $isToday,
            ];
        }

        // 7. Notifikasi Pemeliharaan Rutin (PM): Penugasan Baru/Tim, Jadwal Hari Ini, Pengingat H-1, & Terlambat
        $todayStr = now()->toDateString();
        $tomorrowStr = now()->copy()->addDay()->toDateString();

        $pmNotifTasks = \App\Models\MaintenanceLog::whereIn('status', ['Terjadwal', 'Terlambat'])
            ->where(function ($q) use ($teknisiUser) {
                $q->where('user_id', $teknisiUser->id)
                  ->orWhere('teknisi_nama', $teknisiUser->name)
                  ->orWhereNull('user_id');
            })
            ->where(function ($q) use ($cutoff, $todayStr, $tomorrowStr) {
                $q->where('updated_at', '>=', $cutoff)
                  ->orWhereDate('tanggal_cek', $todayStr)
                  ->orWhereDate('tanggal_cek', $tomorrowStr)
                  ->orWhere(function ($lateQ) use ($todayStr) {
                      $lateQ->where('status', 'Terlambat')
                            ->orWhereDate('tanggal_cek', '<', $todayStr);
                  });
            })
            ->orderBy('tanggal_cek', 'asc')
            ->take(5)
            ->get();

        foreach ($pmNotifTasks as $pm) {
            $tglJadwal = $pm->tanggal_cek ? $pm->tanggal_cek->format('d/m/Y') : '-';
            $jamSuffix = $pm->jam_mulai ? ' · ' . str_replace(':', '.', substr($pm->jam_mulai, 0, 5)) . ' WIB' : '';
            $isLate = $pm->isTerlambat();
            $isDueToday = $pm->tanggal_cek && $pm->tanggal_cek->isToday();
            $isDueTomorrow = $pm->tanggal_cek && $pm->tanggal_cek->isTomorrow();
            $isUnassigned = empty($pm->user_id);

            $updatedTs = $pm->updated_at ?: ($pm->created_at ?: now());
            // Untuk pengingat H-1, jatuh tempo hari ini, atau terlambat, gunakan stempel waktu hari ini agar tampil di lonceng harian
            $ts = $updatedTs->isToday()
                ? $updatedTs
                : (($isDueToday || $isDueTomorrow || $isLate) ? now()->startOfDay()->addHours(7) : $updatedTs);

            $freqLabel = $pm->frekuensi_label;
            if ($isLate) {
                $title = 'Jadwal PM melewati batas waktu';
                $lateInfo = $pm->late_days_text ? " · {$pm->late_days_text}" : '';
                $teamTag = $isUnassigned ? ' · [Tugas Tim]' : '';
                $subtitle = "{$pm->nama_perangkat} ({$freqLabel}) · {$pm->lokasi_ruangan}{$teamTag}{$lateInfo}";
                $type = 'urgent';
                $icon = 'fa-solid fa-calendar-xmark';
                $iconBg = 'bg-rose-50 text-rose-600';
            } elseif ($isDueToday) {
                $title = 'Jadwal PM hari ini';
                $teamTag = $isUnassigned ? ' · [Tugas Tim]' : '';
                $subtitle = "{$pm->nama_perangkat} ({$freqLabel}) · {$pm->lokasi_ruangan}{$teamTag} · {$tglJadwal}{$jamSuffix}";
                $type = 'assigned';
                $icon = 'fa-regular fa-calendar-check';
                $iconBg = 'bg-teal-50 text-teal-600';
            } elseif ($isDueTomorrow && !$updatedTs->isToday()) {
                $title = 'Pengingat PM besok (H-1)';
                $teamTag = $isUnassigned ? ' · [Tugas Tim]' : '';
                $subtitle = "{$pm->nama_perangkat} ({$freqLabel}) · {$pm->lokasi_ruangan}{$teamTag} · besok {$tglJadwal}{$jamSuffix}";
                $type = 'assigned';
                $icon = 'fa-regular fa-bell';
                $iconBg = 'bg-sky-50 text-sky-600';
            } else {
                $title = $isUnassigned ? 'Jadwal PM baru untuk tim' : 'Penugasan pemeliharaan baru (PM)';
                $teamTag = $isUnassigned ? ' · [Tugas Tim]' : '';
                $subtitle = "{$pm->nama_perangkat} ({$freqLabel}) · {$pm->lokasi_ruangan}{$teamTag} · jadwal {$tglJadwal}{$jamSuffix}";
                $type = 'assigned';
                $icon = 'fa-regular fa-calendar-check';
                $iconBg = 'bg-teal-50 text-teal-600';
            }

            $items[] = [
                'id' => 'pm_assigned_' . $pm->id . '_' . ($pm->updated_at ? $pm->updated_at->timestamp : 0),
                'service_id' => $pm->id,
                'type' => $type,
                'icon' => $icon,
                'icon_bg' => $iconBg,
                'title' => $title,
                'subtitle' => $subtitle,
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('maintenance.create', ['from_schedule' => $pm->id]),
                'force_unread' => true,
                'is_today' => $ts->isToday(),
            ];
        }

        return $items;
    }

    /**
     * Notifikasi Khusus Pegawai (Pelapor)
     */
    private static function buildPegawaiItems(User $pegawaiUser, bool $dailyOnly = false): array
    {
        $items = [];
        $cutoff = $dailyOnly ? now()->startOfDay() : now()->subDays(30);

        // 1. Tiket Selesai (Perlu Konfirmasi) - Prioritas tertinggi untuk Pegawai
        $needConfirmQuery = Service::where('user_id', $pegawaiUser->id)
            ->where('status', 'Selesai')
            ->whereNull('pelapor_confirmed_at');

        if ($dailyOnly) {
            $needConfirmQuery->where(function ($q) use ($cutoff) {
                $q->where('tanggal_selesai', '>=', $cutoff)
                  ->orWhere(function ($sq) use ($cutoff) {
                      $sq->whereNull('tanggal_selesai')->where('updated_at', '>=', $cutoff);
                  });
            });
        }

        $needConfirmTickets = $needConfirmQuery->latest('updated_at')->get();

        foreach ($needConfirmTickets as $t) {
            $judul = Str::limit($t->display_judul, 36);
            $tek = $t->teknisi_nama ?: 'Teknisi IT';
            $ts = $t->tanggal_selesai ?: ($t->updated_at ?: now());

            $items[] = [
                'id' => 'resolved_' . $t->id,
                'service_id' => $t->id,
                'type' => 'resolved',
                'icon' => 'fa-solid fa-circle-check',
                'icon_bg' => 'bg-emerald-50 text-emerald-600',
                'title' => 'Tiket Selesai · Perlu Konfirmasi',
                'subtitle' => "{$t->ticket_number} · {$judul} · Diselesaikan {$tek}",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('services.show', $t),
                'force_unread' => true,
                'is_today' => $ts->isToday(),
            ];
        }

        // 2. Tiket Sedang Ditangani (Diproses atau Menunggu Sparepart)
        $inProgressQuery = Service::where('user_id', $pegawaiUser->id)
            ->whereIn('status', ['Diproses', 'Menunggu sparepart']);

        if ($dailyOnly) {
            $inProgressQuery->where(function ($q) use ($cutoff) {
                $q->where('updated_at', '>=', $cutoff)
                  ->orWhere('created_at', '>=', $cutoff);
            });
        }

        $inProgressTickets = $inProgressQuery->latest('updated_at')->get();

        foreach ($inProgressTickets as $t) {
            $judul = Str::limit($t->display_judul, 36);
            $ts = $t->updated_at ?: now();
            $isSparepart = ($t->status === 'Menunggu sparepart');
            $tek = $t->teknisi_nama ?: 'Teknisi IT';

            $items[] = [
                'id' => ($isSparepart ? 'sparepart_' : 'process_') . $t->id,
                'service_id' => $t->id,
                'type' => $isSparepart ? 'sparepart' : 'process',
                'icon' => $isSparepart ? 'fa-solid fa-boxes-packing' : 'fa-solid fa-screwdriver-wrench',
                'icon_bg' => $isSparepart ? 'bg-purple-50 text-purple-600' : 'bg-amber-50 text-amber-600',
                'title' => $isSparepart ? 'Menunggu Suku Cadang' : 'Tiket Sedang Dikerjakan',
                'subtitle' => "{$t->ticket_number} · {$judul} · {$tek}",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('services.show', $t),
                'force_unread' => false,
                'is_today' => $ts->isToday(),
            ];
        }

        // 3. Pesan/Komentar dari Teknisi/Admin di tiket pegawai
        $commentsQuery = ServiceComment::whereHas('service', function ($q) use ($pegawaiUser) {
                $q->where('user_id', $pegawaiUser->id);
            })
            ->where('author_role', '!=', 'pegawai');

        if ($dailyOnly) {
            $commentsQuery->where('created_at', '>=', $cutoff);
        }

        $comments = $commentsQuery
            ->latest('created_at')
            ->take(5)
            ->get();

        foreach ($comments as $c) {
            $t = $c->service;
            if (!$t) continue;
            $msg = Str::limit($c->message, 40);
            $ts = $c->created_at ?: now();

            $items[] = [
                'id' => 'comment_' . $c->id,
                'service_id' => $t->id,
                'type' => 'comment',
                'icon' => 'fa-regular fa-comment-dots',
                'icon_bg' => 'bg-teal-50 text-teal-600',
                'title' => "Pesan dari {$c->author_name}",
                'subtitle' => "{$t->ticket_number} · {$msg}",
                'time_label' => self::formatShortTime($ts),
                'timestamp' => $ts,
                'url' => route('services.show', $t),
                'force_unread' => false,
                'is_today' => $ts->isToday(),
            ];
        }

        return $items;
    }

    /**
     * Evaluasi dan terapkan status baca/belum dibaca secara seragam per user.
     */
    private static function applyReadState(User $user, string $cacheRole, array $items): array
    {
        $readIds = Cache::get("{$cacheRole}_read_notifs_{$user->id}", []);
        $markedAllAt = (int) Cache::get("{$cacheRole}_notifs_marked_all_at_{$user->id}", 0);

        $defaultKey = "{$cacheRole}_initial_unread_{$user->id}";
        $initialDefaultIds = Cache::get($defaultKey);

        if ($markedAllAt === 0 && $initialDefaultIds === null && count($items) > 0) {
            $forceIds = [];
            foreach ($items as $it) {
                if (!empty($it['force_unread'])) {
                    $forceIds[] = $it['id'];
                }
            }

            if (count($forceIds) >= 3) {
                $initialDefaultIds = array_slice($forceIds, 0, 3);
            } else {
                $initialDefaultIds = $forceIds;
                foreach ($items as $it) {
                    if (!in_array($it['id'], $initialDefaultIds, true)) {
                        $initialDefaultIds[] = $it['id'];
                    }
                    if (count($initialDefaultIds) >= min(3, count($items))) {
                        break;
                    }
                }
            }

            Cache::forever($defaultKey, $initialDefaultIds);
        }

        $initialDefaultIds = $initialDefaultIds ?? [];
        $unreadCount = 0;

        foreach ($items as &$item) {
            $ts = $item['timestamp'] instanceof Carbon ? $item['timestamp']->timestamp : 0;

            if (in_array($item['id'], $readIds, true)) {
                $item['is_unread'] = false;
            } elseif (!empty($item['force_unread']) && ($markedAllAt === 0 || $ts >= $markedAllAt)) {
                $item['is_unread'] = true;
            } elseif ($markedAllAt > 0) {
                $item['is_unread'] = ($ts > $markedAllAt);
            } else {
                $item['is_unread'] = in_array($item['id'], $initialDefaultIds, true);
            }

            if ($item['is_unread']) {
                $unreadCount++;
            }
        }
        unset($item);

        return [
            'items' => $items,
            'unread_count' => $unreadCount,
        ];
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public static function markAsRead(User $user, string $notifId): void
    {
        $cacheRole = $user->isAdmin() ? 'admin' : ($user->isTeknisi() ? 'teknisi' : 'pegawai');
        $readIds = Cache::get("{$cacheRole}_read_notifs_{$user->id}", []);
        if (!in_array($notifId, $readIds, true)) {
            $readIds[] = $notifId;
            Cache::forever("{$cacheRole}_read_notifs_{$user->id}", array_values($readIds));
        }

        if (preg_match('/^(?:urgent|waiting|new_ticket|assigned)_(\d+)$/', $notifId, $matches)) {
            Service::where('id', (int) $matches[1])->update(['is_read' => true]);
        }
    }

    /**
     * Sinkronkan status baca saat user membuka detail tiket langsung dari /services/{id}.
     */
    public static function markServiceAsReadForUser(User $user, int $serviceId): void
    {
        $cacheRole = $user->isAdmin() ? 'admin' : ($user->isTeknisi() ? 'teknisi' : 'pegawai');
        $readIds = Cache::get("{$cacheRole}_read_notifs_{$user->id}", []);
        $prefixes = ['urgent_', 'waiting_', 'new_ticket_', 'assigned_', 'report_', 'confirmed_', 'taken_', 'resolved_', 'process_', 'sparepart_', 'comment_'];

        $updated = false;
        foreach ($prefixes as $prefix) {
            $candidate = $prefix . $serviceId;
            if (!in_array($candidate, $readIds, true)) {
                $readIds[] = $candidate;
                $updated = true;
            }
        }

        if ($updated) {
            Cache::forever("{$cacheRole}_read_notifs_{$user->id}", array_values($readIds));
        }
    }

    /**
     * Tandai seluruh notifikasi user sebagai sudah dibaca.
     */
    public static function markAllAsRead(User $user): void
    {
        $cacheRole = $user->isAdmin() ? 'admin' : ($user->isTeknisi() ? 'teknisi' : 'pegawai');
        $current = self::getForUser($user, 40);
        $ids = array_column($current['items'], 'id');

        $existing = Cache::get("{$cacheRole}_read_notifs_{$user->id}", []);
        Cache::forever("{$cacheRole}_read_notifs_{$user->id}", array_values(array_unique(array_merge($existing, $ids))));
        Cache::forever("{$cacheRole}_notifs_marked_all_at_{$user->id}", now()->timestamp);

        Service::where('status', 'Baru')->where('is_read', false)->update(['is_read' => true]);
    }

    /**
     * Cek apakah tiket dimiliki/ditangani oleh user teknisi terkait.
     */
    private static function ticketBelongsToTeknisi(Service $service, User $user): bool
    {
        return $service->isOwnedByTeknisi($user);
    }

    /**
     * Tentukan tim IT target berdasarkan kategori perangkat.
     */
    private static function determineTeam(?string $jenisPerangkat): string
    {
        $j = strtolower($jenisPerangkat ?? '');
        if (
            str_contains($j, 'akun') ||
            str_contains($j, 'aplikasi') ||
            str_contains($j, 'software') ||
            str_contains($j, 'email') ||
            str_contains($j, 'sistem')
        ) {
            return 'Tim IT Support';
        }
        return 'Tim IT Lapangan';
    }

    /**
     * Format waktu singkat standar notifikasi Indonesia ("Baru saja", "10 mnt", "1 jam", "Kemarin", "2 hari").
     */
    private static function formatShortTime(Carbon $date): string
    {
        if ($date->isFuture()) {
            return 'Baru saja';
        }

        $diffMins = (int) $date->diffInMinutes(now());
        if ($diffMins < 2) {
            return 'Baru saja';
        }
        if ($diffMins < 60) {
            return $diffMins . ' mnt';
        }
        if ($date->isToday()) {
            $hours = max(1, (int) $date->diffInHours(now()));
            return $hours . ' jam';
        }
        if ($date->isYesterday()) {
            return 'Kemarin';
        }

        $days = max(2, (int) $date->diffInDays(now()));
        return $days . ' hari';
    }
}
