<?php

namespace App\Providers;

use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Carbon\Carbon::setLocale('id');

        // Composer untuk layout dan view teknisi, admin & pegawai agar badge antrian dan notifikasi selalu akurat di setiap halaman
        View::composer(['layouts.app', 'services.*', 'dashboard.*', 'partials.*', 'admin.*', 'pages.*', 'teknisi.*'], function ($view) {
            if (Auth::check() && (Auth::user()->isTeknisi() || Auth::user()->isAdmin())) {
                $user = Auth::user();
                $sidebarQueueCount = Service::where('status', 'Baru')->whereNull('teknisi_id')->count();

                // Untuk teknisi: hitung tugas aktif milik akunnya sendiri. Untuk admin: hitung total tugas aktif seluruh teknisi
                if ($user->isTeknisi()) {
                    $sidebarMyActiveCount = Service::ownedByTeknisi($user)
                        ->whereIn('status', ['Diproses', 'Menunggu sparepart'])
                        ->count();
                } else {
                    $sidebarMyActiveCount = Service::whereIn('status', ['Diproses', 'Menunggu sparepart'])->count();
                }

                // Notifikasi lonceng header menggunakan jendela harian (24 jam terakhir) sesuai SOP
                $notifData = \App\Services\NotificationCenterService::getForUser($user, 8, true);
                $headerNotifications = $notifData['items'];
                $headerUnreadNotifCount = $notifData['unread_count'];
                $headerNotifViewAllRoute = $user->isAdmin()
                    ? route('admin.notifikasi')
                    : route('notifications.index');

                $sidebarUnassignedCount = $sidebarQueueCount;
                $sidebarPendingSopCount = \App\Models\KnowledgeBase::where('status', 'Menunggu Tinjauan')->count();

                $sidebarOnlineTechCount = 0;
                $sidebarTotalTechCount = 0;
                if ($user->isAdmin()) {
                    $sidebarTechs = \App\Models\User::where('role', 'teknisi')
                        ->where('name', '!=', 'Tim IT Support')
                        ->get();
                    if ($sidebarTechs->isEmpty()) {
                        $sidebarTechs = \App\Models\User::where('role', 'teknisi')->get();
                    }
                    $sidebarTotalTechCount = $sidebarTechs->count();
                    $sidebarOnlineTechCount = $sidebarTechs->filter(fn ($t) => $t->isWorkingNow())->count();
                }

                $view->with([
                    'sidebarQueueCount' => $sidebarQueueCount,
                    'sidebarMyActiveCount' => $sidebarMyActiveCount,
                    'sidebarUnassignedCount' => $sidebarUnassignedCount,
                    'sidebarPendingSopCount' => $sidebarPendingSopCount,
                    'sidebarOnlineTechCount' => $sidebarOnlineTechCount,
                    'sidebarTotalTechCount' => $sidebarTotalTechCount,
                    'headerNotifications' => $headerNotifications,
                    'headerUnreadNotifCount' => $headerUnreadNotifCount,
                    'headerNotifViewAllRoute' => $headerNotifViewAllRoute,
                ]);
            } elseif (Auth::check() && Auth::user()->isPegawai()) {
                $pegawaiNeedConfirmCount = Service::where('user_id', Auth::id())
                    ->where('status', 'Selesai')
                    ->whereNull('pelapor_confirmed_at')
                    ->count();

                // Notifikasi lonceng Pegawai juga memakai jendela harian (reset otomatis setiap ganti hari pukul 00:00 WIB)
                $pegawaiNotifs = \App\Services\NotificationCenterService::getForUser(Auth::user(), 10, true);

                $view->with([
                    'pegawaiNeedConfirmCount' => $pegawaiNeedConfirmCount,
                    'headerNotifications' => $pegawaiNotifs['items'],
                    'headerUnreadNotifCount' => $pegawaiNotifs['unread_count'],
                    'headerNotifViewAllRoute' => route('services.index', ['status' => 'perlu_konfirmasi']),
                ]);
            }
        });
    }
}
