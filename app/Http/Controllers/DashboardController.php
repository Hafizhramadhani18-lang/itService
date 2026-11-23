<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return app(AdminController::class)->dashboard($request);
        }

        if ($user->isTeknisi()) {
            // Stat Cards
            $baruCount = Service::where('status', 'Baru')->whereNull('teknisi_id')->count();
            
            // Tugas aktif milik akun teknisi yang sedang login
            $prosesCount = Service::ownedByTeknisi($user)
                ->whereIn('status', ['Diproses', 'Menunggu sparepart'])
                ->count();

            $sparepartCount = Service::where('status', 'Menunggu sparepart')->count();
            $selesaiHariIniCount = Service::where('status', 'Selesai')->count();
            $selesaiTodayCount = Service::where('status', 'Selesai')
                ->whereDate('tanggal_selesai', now()->toDateString())
                ->count();

            // Antrian Tiket Table with Search & Filter Pills (Murni tiket antrian baru yang belum diambil teknisi)
            $query = Service::with(['user', 'teknisi'])
                ->where('status', 'Baru')
                ->whereNull('teknisi_id')
                ->latest();

            if ($request->filled('filter')) {
                $filter = $request->filter;
                if ($filter === 'Mendesak') {
                    $query->where('prioritas', 'Mendesak');
                }
            }

            if ($request->filled('tim')) {
                $tim = $request->tim;
                if ($tim === 'lapangan') {
                    $query->where(function ($q) {
                        $q->where('jenis_perangkat', 'not like', '%Akun%')
                          ->where('jenis_perangkat', 'not like', '%Aplikasi%')
                          ->where('jenis_perangkat', 'not like', '%Software%')
                          ->where('jenis_perangkat', 'not like', '%Sistem%')
                          ->where('jenis_perangkat', 'not like', '%Web%');
                    });
                } elseif ($tim === 'aplikasi') {
                    $query->where(function ($q) {
                        $q->where('jenis_perangkat', 'like', '%Akun%')
                          ->orWhere('jenis_perangkat', 'like', '%Aplikasi%')
                          ->orWhere('jenis_perangkat', 'like', '%Software%')
                          ->orWhere('jenis_perangkat', 'like', '%Sistem%')
                          ->orWhere('jenis_perangkat', 'like', '%Web%');
                    });
                }
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_number', 'like', "%{$search}%")
                      ->orWhere('divisi', 'like', "%{$search}%")
                      ->orWhere('keluhan', 'like', "%{$search}%")
                      ->orWhere('jenis_perangkat', 'like', "%{$search}%");
                });
            }

            $antrianTiket = $query->take(8)->get();

            // Widget 1: Tiket per Divisi
            $divisiCounts = Service::selectRaw('divisi, count(*) as total')
                ->groupBy('divisi')
                ->orderByDesc('total')
                ->get();
            $maxDivisiCount = $divisiCounts->max('total') ?: 1;

            // Widget 2: Perlu Perhatian (Hanya tiket aktif yang memerlukan perhatian/tindakan)
            $perluPerhatian = Service::whereNotIn('status', ['Selesai', 'Dibatalkan'])
                ->where(function ($q) {
                    $q->where(function ($sq) {
                        // Tiket Baru Prioritas Mendesak yang belum diambil teknisi
                        $sq->where('status', 'Baru')
                           ->where('prioritas', 'Mendesak')
                           ->whereNull('teknisi_id')
                           ->where(function ($tq) {
                               $tq->whereNull('teknisi_nama')->orWhere('teknisi_nama', '');
                           });
                    })->orWhere(function ($sq) {
                        // Tiket Menunggu Sparepart yang tertahan lebih dari 2 hari
                        $sq->where('status', 'Menunggu sparepart')
                           ->where(function ($tq) {
                               $tq->where('updated_at', '<=', now()->subDays(2))
                                  ->orWhere('created_at', '<=', now()->subDays(2));
                           });
                    })->orWhere(function ($sq) {
                        // Tiket Antrian Baru yang belum diambil lebih dari 12 jam
                        $sq->where('status', 'Baru')
                           ->where('created_at', '<=', now()->subHours(12))
                           ->whereNull('teknisi_id')
                           ->where(function ($tq) {
                               $tq->whereNull('teknisi_nama')->orWhere('teknisi_nama', '');
                           });
                    });
                })
                ->orderByRaw("CASE WHEN prioritas = 'Mendesak' AND status = 'Baru' THEN 1 WHEN status = 'Menunggu sparepart' THEN 2 ELSE 3 END")
                ->latest('created_at')
                ->take(5)
                ->get();

            return view('dashboard.teknisi', compact(
                'baruCount',
                'prosesCount',
                'sparepartCount',
                'selesaiHariIniCount',
                'selesaiTodayCount',
                'antrianTiket',
                'divisiCounts',
                'maxDivisiCount',
                'perluPerhatian'
            ));
        }

        // Dashboard Pegawai
        $totalMyTickets = Service::where('user_id', $user->id)->count();
        $myActiveCount = Service::where('user_id', $user->id)->whereIn('status', ['Baru', 'Diproses', 'Menunggu sparepart'])->count();
        
        // Tiket yang sudah selesai ditangani teknisi dan menunggu konfirmasi pelapor
        $myNeedConfirmCount = Service::where('user_id', $user->id)
            ->where('status', 'Selesai')
            ->whereNull('pelapor_confirmed_at')
            ->count();

        // Tiket selesai bulan ini (jika tanggal_selesai kosong, gunakan updated_at pada bulan/tahun berjalan)
        $mySelesaiBulanIniCount = Service::where('user_id', $user->id)
            ->where('status', 'Selesai')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('tanggal_selesai')
                       ->whereMonth('tanggal_selesai', now()->month)
                       ->whereYear('tanggal_selesai', now()->year);
                })->orWhere(function ($q2) {
                    $q2->whereNull('tanggal_selesai')
                       ->whereMonth('updated_at', now()->month)
                       ->whereYear('updated_at', now()->year);
                });
            })->count();

        $myMenungguCount = Service::where('user_id', $user->id)->where('status', 'Baru')->count();
        $myProsesCount = Service::where('user_id', $user->id)->where('status', 'Diproses')->count();
        $mySelesaiCount = Service::where('user_id', $user->id)->where('status', 'Selesai')->count();

        $myRecentTickets = Service::where('user_id', $user->id)
            ->with(['teknisi'])
            ->latest()
            ->take(6)
            ->get();

        return view('dashboard.pegawai', compact(
            'totalMyTickets',
            'myActiveCount',
            'myNeedConfirmCount',
            'mySelesaiBulanIniCount',
            'myMenungguCount',
            'myProsesCount',
            'mySelesaiCount',
            'myRecentTickets'
        ));
    }

    public function markAllNotificationsRead(Request $request)
    {
        $user = Auth::user();
        if (!$user->isTeknisi() && !$user->isAdmin()) {
            abort(403);
        }

        \App\Services\NotificationCenterService::markAllAsRead($user);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('info', 'Semua notifikasi telah ditandai sudah dibaca.');
    }

    public function markSingleNotificationRead(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isTeknisi()) {
            abort(403);
        }

        $notifId = $request->input('notif_id');
        if ($notifId) {
            \App\Services\NotificationCenterService::markAsRead($user, (string) $notifId);
        }

        return response()->json(['success' => true]);
    }

    public function notifikasi()
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isTeknisi()) {
            abort(403, 'Akses ditolak.');
        }

        $data = \App\Services\NotificationCenterService::getForUser($user, 40);
        $notifications = $data['items'];
        $unreadCount = $data['unread_count'];

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Endpoint polling ringan untuk audio chime & live counter notifikasi (Admin, Teknisi & Pegawai).
     */
    public function pollNotifications(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['unread_count' => 0, 'latest_id' => null], 401);
        }

        // Semua role (Admin, Teknisi, & Pegawai) memakai jendela harian agar otomatis reset tiap ganti hari (00:00 WIB)
        $notifData = \App\Services\NotificationCenterService::getForUser($user, 8, true);
        $items = $notifData['items'] ?? [];
        $unreadCount = $notifData['unread_count'] ?? 0;
        $latestId = !empty($items) ? $items[0]['id'] : null;
        $latestTitle = !empty($items) ? $items[0]['title'] : null;

        return response()->json([
            'unread_count' => $unreadCount,
            'latest_id' => $latestId,
            'latest_title' => $latestTitle,
            'has_urgent' => collect($items)->contains('type', 'urgent'),
        ]);
    }
}
