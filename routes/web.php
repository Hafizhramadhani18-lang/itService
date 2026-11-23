<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/quick-login/{user}', [AuthController::class, 'quickLogin'])->name('quick.login');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifikasi', [DashboardController::class, 'notifikasi'])->name('notifications.index');
    Route::get('/notifications/poll', [DashboardController::class, 'pollNotifications'])->name('notifications.poll');
    Route::post('/notifications/mark-all-read', [DashboardController::class, 'markAllNotificationsRead'])->name('notifications.markAllRead');
    Route::post('/notifications/mark-read', [DashboardController::class, 'markSingleNotificationRead'])->name('notifications.markRead');

    // Dokumen & Laporan Cetak / Ekspor
    Route::get('/services/cetak', [ServiceController::class, 'cetak'])->name('services.cetak');
    Route::get('/services/export', [ServiceController::class, 'export'])->name('services.export');
    
    // Actions on Service Tickets
    Route::post('/services/{service}/claim', [ServiceController::class, 'claim'])->name('services.claim');
    Route::post('/services/{service}/checklist', [ServiceController::class, 'toggleChecklist'])->name('services.checklist');
    Route::post('/services/{service}/done', [ServiceController::class, 'markDone'])->name('services.done');
    Route::post('/services/{service}/confirm', [ServiceController::class, 'confirm'])->name('services.confirm');
    Route::post('/services/{service}/cancel', [ServiceController::class, 'cancel'])->name('services.cancel');
    Route::post('/services/{service}/comment', [ServiceController::class, 'storeComment'])->name('services.comment');

    // Basis Pengetahuan & SOP Troubleshooting IT
    Route::get('/basis-pengetahuan', [KnowledgeBaseController::class, 'index'])->name('knowledge.base');
    Route::get('/basis-pengetahuan/tulis', [KnowledgeBaseController::class, 'create'])->name('knowledge.create');
    Route::post('/basis-pengetahuan', [KnowledgeBaseController::class, 'store'])->name('knowledge.store');
    Route::get('/basis-pengetahuan/{knowledgeBase}/edit', [KnowledgeBaseController::class, 'edit'])->name('knowledge.edit');
    Route::put('/basis-pengetahuan/{knowledgeBase}', [KnowledgeBaseController::class, 'update'])->name('knowledge.update');
    Route::delete('/basis-pengetahuan/{knowledgeBase}', [KnowledgeBaseController::class, 'destroy'])->name('knowledge.destroy');

    // Pengaturan Profil Akun
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profil/ketersediaan', [ProfileController::class, 'updateAvailability'])->name('profile.availability');

    // Status Kerja Teknisi (Halaman khusus operasional harian 08.00 - 17.00 WIB)
    Route::get('/status-kerja', [ProfileController::class, 'statusKerja'])->name('teknisi.status.index');
    Route::post('/status-kerja', [ProfileController::class, 'updateAvailability'])->name('teknisi.status.update');

    // Menu Khusus Admin (Koordinator IT)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/profil', [ProfileController::class, 'edit'])->name('profile');
        Route::get('/notifikasi', [AdminController::class, 'notifikasi'])->name('notifikasi');
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::post('/assign-ticket', [AdminController::class, 'assignTicket'])->name('assignTicket');
        Route::get('/penugasan', [AdminController::class, 'penugasan'])->name('penugasan');
        Route::get('/status-teknisi', [AdminController::class, 'statusTeknisi'])->name('statusTeknisi');
        Route::get('/status-teknisi/{user}', [AdminController::class, 'statusTeknisiDetail'])->name('statusTeknisi.show');
        Route::get('/kinerja', [AdminController::class, 'kinerja'])->name('kinerja');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::post('/users/{user}/availability', [AdminController::class, 'updateAvailability'])->name('users.availability');
        Route::post('/users/{user}/reset-password', [AdminController::class, 'resetPassword'])->name('users.resetPassword');
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
        Route::get('/kategori', [AdminController::class, 'kategori'])->name('kategori');
        Route::get('/master', [AdminController::class, 'kategori'])->name('master');
        Route::get('/rekap', [AdminController::class, 'rekap'])->name('rekap');
        Route::get('/laporan', [AdminController::class, 'rekap'])->name('laporan');
        Route::post('/divisi', [AdminController::class, 'storeDivisi'])->name('divisi.store');
        Route::put('/divisi/{division}', [AdminController::class, 'updateDivisi'])->name('divisi.update');
        Route::post('/divisi/{division}/toggle', [AdminController::class, 'toggleDivisi'])->name('divisi.toggle');
        Route::delete('/divisi/{division}', [AdminController::class, 'destroyDivisi'])->name('divisi.destroy');
        Route::post('/kategori', [AdminController::class, 'storeKategori'])->name('kategori.store');
        Route::put('/kategori/{deviceCategory}', [AdminController::class, 'updateKategori'])->name('kategori.update');
        Route::post('/kategori/{deviceCategory}/toggle', [AdminController::class, 'toggleKategori'])->name('kategori.toggle');
        Route::delete('/kategori/{deviceCategory}', [AdminController::class, 'destroyKategori'])->name('kategori.destroy');
        Route::post('/lokasi', [AdminController::class, 'storeLokasi'])->name('lokasi.store');
        Route::put('/lokasi/{location}', [AdminController::class, 'updateLokasi'])->name('lokasi.update');
        Route::post('/lokasi/{location}/toggle', [AdminController::class, 'toggleLokasi'])->name('lokasi.toggle');
        Route::delete('/lokasi/{location}', [AdminController::class, 'destroyLokasi'])->name('lokasi.destroy');
        Route::post('/aset', [AdminController::class, 'storeAset'])->name('aset.store');
        Route::put('/aset/{asset}', [AdminController::class, 'updateAset'])->name('aset.update');
        Route::post('/aset/{asset}/toggle', [AdminController::class, 'toggleAset'])->name('aset.toggle');
        Route::delete('/aset/{asset}', [AdminController::class, 'destroyAset'])->name('aset.destroy');
        Route::get('/pemeliharaan', [MaintenanceController::class, 'index'])->name('pemeliharaan');
    });

    // Log Pemeliharaan Aset & Sistem IT (Preventive Maintenance)
    Route::get('/pemeliharaan/cetak', [MaintenanceController::class, 'cetak'])->name('maintenance.cetak');
    Route::get('/pemeliharaan/export', [MaintenanceController::class, 'export'])->name('maintenance.export');
    Route::get('/pemeliharaan/jadwalkan', [MaintenanceController::class, 'createSchedule'])->name('maintenance.jadwalkan.create');
    Route::post('/pemeliharaan/jadwalkan', [MaintenanceController::class, 'storeScheduledTask'])->name('maintenance.jadwalkan');
    Route::post('/pemeliharaan/{maintenanceLog}/reschedule', [MaintenanceController::class, 'reschedule'])->name('maintenance.reschedule');
    Route::post('/pemeliharaan/{maintenanceLog}/tindak-lanjut-tiket', [MaintenanceController::class, 'createFollowUpTicket'])->name('maintenance.followUpTicket');
    Route::get('/pemeliharaan/schedules/create', [MaintenanceController::class, 'createRecurringSchedule'])->name('maintenance.schedules.create');
    Route::post('/pemeliharaan/schedules', [MaintenanceController::class, 'storeRecurringSchedule'])->name('maintenance.schedules.store');
    Route::get('/pemeliharaan/schedules/{maintenanceSchedule}/edit', [MaintenanceController::class, 'editRecurringSchedule'])->name('maintenance.schedules.edit');
    Route::put('/pemeliharaan/schedules/{maintenanceSchedule}', [MaintenanceController::class, 'updateRecurringSchedule'])->name('maintenance.schedules.update');
    Route::post('/pemeliharaan/schedules/{maintenanceSchedule}/deactivate', [MaintenanceController::class, 'deactivateRecurringSchedule'])->name('maintenance.schedules.deactivate');
    Route::delete('/pemeliharaan/schedules/{maintenanceSchedule}', [MaintenanceController::class, 'destroyRecurringSchedule'])->name('maintenance.schedules.destroy');
    Route::get('/pemeliharaan/{maintenanceLog}/cetak', [MaintenanceController::class, 'cetakSingle'])->name('maintenance.cetakSingle');
    Route::resource('pemeliharaan', MaintenanceController::class)->names('maintenance')->parameters(['pemeliharaan' => 'maintenanceLog']);

    // CRUD Tiket Servis
    Route::resource('services', ServiceController::class);
});
