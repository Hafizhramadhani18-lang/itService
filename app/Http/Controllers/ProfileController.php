<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceLog;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman Pengaturan Profil
     */
    public function edit()
    {
        $user = Auth::user();

        return view('admin.profile', compact('user'));
    }

    /**
     * Tampilkan halaman khusus Status Kerja Teknisi (Available / On Task / Cuti / Offline)
     */
    public function statusKerja()
    {
        $user = Auth::user();
        abort_unless($user->isTeknisi(), 403, 'Halaman Status Kerja khusus untuk akun Teknisi.');

        $user->syncTaskAvailability();
        $user = $user->fresh();

        $activeTickets = Service::ownedByTeknisi($user)
            ->whereIn('status', ['Diproses', 'Menunggu sparepart'])
            ->latest()
            ->get();

        $activeTicketCount = $activeTickets->count();
        $scheduledPmCount = MaintenanceLog::where('user_id', $user->id)
            ->whereIn('status', ['Terjadwal', 'Terlambat'])
            ->count();

        $teammates = User::where('role', 'teknisi')
            ->where('name', '!=', 'Tim IT Support')
            ->orderBy('name')
            ->get()
            ->map(function ($t) {
                $t->syncTaskAvailability();
                $t->active_ticket_count = Service::ownedByTeknisi($t)
                    ->whereIn('status', ['Diproses', 'Menunggu sparepart'])
                    ->count();
                $t->top_active_ticket = Service::ownedByTeknisi($t)
                    ->whereIn('status', ['Diproses', 'Menunggu sparepart'])
                    ->latest()
                    ->first();
                return $t;
            });

        $teamAvailableCount = $teammates->filter(fn($t) => $t->effective_availability === 'available')->count();
        $teamOnTaskCount = $teammates->filter(fn($t) => $t->effective_availability === 'on_task')->count();
        $teamCutiCount = $teammates->filter(fn($t) => $t->effective_availability === 'cuti')->count();
        $teamOfflineCount = $teammates->filter(fn($t) => $t->effective_availability === 'offline')->count();

        $withinWorkHours = User::isWithinWorkHours();
        $nowWib = now()->timezone('Asia/Jakarta');
        $topActiveTicket = $activeTickets->first();

        // Riwayat status hari ini untuk kartu kanan
        $statusTimeline = [];

        $checkinTimeStr = ($user->status_ketersediaan_updated_at && $user->status_ketersediaan_updated_at->copy()->timezone('Asia/Jakarta')->isToday())
            ? $user->status_ketersediaan_updated_at->copy()->timezone('Asia/Jakarta')->format('H.i')
            : '07.30';

        // 1. Check-in & status aktif hari ini
        if ($user->isWorkingNow() || ($user->status_ketersediaan_updated_at && $user->status_ketersediaan_updated_at->copy()->timezone('Asia/Jakarta')->isToday())) {
            $userCheckinTime = ($user->status_ketersediaan_updated_at && $user->status_ketersediaan_updated_at->copy()->timezone('Asia/Jakarta')->isToday())
                ? $user->status_ketersediaan_updated_at->copy()->timezone('Asia/Jakarta')->format('H.i')
                : '08.00';

            if ($user->isOnTask()) {
                $checkinDisplayTime = ($user->status_ketersediaan_updated_at && $user->status_ketersediaan_updated_at->copy()->timezone('Asia/Jakarta')->hour < 8)
                    ? $userCheckinTime
                    : '07.30';

                $statusTimeline[] = [
                    'time' => $checkinDisplayTime,
                    'status' => 'Available',
                    'dot' => 'bg-emerald-500',
                    'title' => 'Available',
                    'desc' => 'Check-in kehadiran',
                ];

                $ticketNumber = $topActiveTicket ? $topActiveTicket->ticket_number : 'tiket tugas';
                $statusTimeline[] = [
                    'time' => $userCheckinTime,
                    'status' => 'On Task',
                    'dot' => 'bg-amber-500',
                    'title' => 'On Task',
                    'desc' => $topActiveTicket
                        ? "Otomatis · mengambil {$ticketNumber}"
                        : ($user->catatan_ketersediaan ?: 'Sedang mengerjakan perbaikan perangkat'),
                ];
            } elseif ($user->isCuti()) {
                $statusTimeline[] = [
                    'time' => $userCheckinTime,
                    'status' => 'Cuti / Izin',
                    'dot' => 'bg-rose-500',
                    'title' => 'Cuti / Izin',
                    'desc' => $user->cuti_selesai ? 'Sedang cuti s/d ' . $user->cuti_selesai->locale('id')->isoFormat('D MMM Y') : 'Pengajuan cuti',
                ];
            } else {
                // Saat ini Available
                $statusTimeline[] = [
                    'time' => $userCheckinTime,
                    'status' => 'Available',
                    'dot' => 'bg-emerald-500',
                    'title' => 'Available',
                    'desc' => 'Check-in kehadiran',
                ];
            }
        }

        // 3. Tiket yang diselesaikan hari ini
        $completedToday = Service::where('teknisi_id', $user->id)
            ->where('status', 'Selesai')
            ->whereDate('tanggal_selesai', now()->toDateString())
            ->orderBy('tanggal_selesai', 'asc')
            ->get();

        foreach ($completedToday as $tDone) {
            $doneTime = $tDone->tanggal_selesai ? $tDone->tanggal_selesai->timezone('Asia/Jakarta')->format('H.i') : '14.00';
            $statusTimeline[] = [
                'time' => $doneTime,
                'status' => 'Available',
                'dot' => 'bg-emerald-500',
                'title' => 'Available',
                'desc' => "{$tDone->ticket_number} selesai, laporan terkirim",
            ];
        }

        // 4. Jam pulang 17:00 jika waktu sudah >= 17:00
        if ($nowWib->hour >= 17) {
            $statusTimeline[] = [
                'time' => '17.00',
                'status' => 'Offline',
                'dot' => 'bg-slate-400',
                'title' => 'Offline',
                'desc' => 'Terjadwal otomatis (jam pulang)',
            ];
        }

        // 5. Fallback jika offline & belum check-in
        if (empty($statusTimeline)) {
            $statusTimeline[] = [
                'time' => 'Pagi ini',
                'status' => 'Offline',
                'dot' => 'bg-slate-400',
                'title' => 'Offline',
                'desc' => 'Belum check-in hari ini',
            ];
        }

        // Urutkan timeline berdasarkan jam kronologis
        usort($statusTimeline, function ($a, $b) {
            return strcmp($a['time'], $b['time']);
        });

        return view('teknisi.status', compact(
            'user',
            'activeTickets',
            'activeTicketCount',
            'scheduledPmCount',
            'topActiveTicket',
            'statusTimeline',
            'teammates',
            'teamAvailableCount',
            'teamOnTaskCount',
            'teamCutiCount',
            'teamOfflineCount',
            'withinWorkHours',
            'nowWib'
        ));
    }

    /**
     * Perbarui data profil (Nama, Email, No. HP, Jabatan, Foto)
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name' => 'required|string|max:255',
            'no_hp' => 'nullable|string|max:50',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

        // Khusus Admin Koordinator IT yang diizinkan mengubah email dan jabatan mandiri
        if ($user->isAdmin()) {
            $rules['email'] = 'required|string|email|max:255|unique:users,email,' . $user->id;
            $rules['jabatan'] = 'nullable|string|max:100';
        }

        $validated = $request->validate($rules, [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'foto.image' => 'File foto harus berupa gambar.',
            'foto.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        $user->name = $validated['name'];
        $user->no_hp = $validated['no_hp'] ?? $user->no_hp;

        if ($user->isAdmin()) {
            $user->email = $validated['email'];
            $user->jabatan = $validated['jabatan'] ?? $user->jabatan;
        }

        // Handle upload foto profil jika ada
        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }
            $oldFilename = basename($user->foto ?? '');
            if ($oldFilename && file_exists(public_path('uploads/avatars/' . $oldFilename))) {
                @unlink(public_path('uploads/avatars/' . $oldFilename));
            }

            $path = $request->file('foto')->store('avatars', 'public');
            $user->foto = $path;

            // Pastikan juga tersimpan di public/uploads/avatars sebagai fallback kuat di sistem lokal/Windows
            $destinationDir = public_path('uploads/avatars');
            if (!file_exists($destinationDir)) {
                @mkdir($destinationDir, 0777, true);
            }
            @copy(storage_path('app/public/' . $path), $destinationDir . '/' . basename($path));
        }

        $user->save();

        return back()->with('success', 'Data profil Anda berhasil diperbarui.');
    }

    /**
     * Perbarui Kata Sandi Akun
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Kata sandi saat ini tidak sesuai dengan akun Anda.',
            ])->withInput();
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success', 'Kata sandi akun Anda berhasil diperbarui.');
    }

    /**
     * Perbarui Status Kerja / Ketersediaan Mandiri oleh Teknisi
     */
    public function updateAvailability(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->isTeknisi(), 403, 'Pengaturan status kerja khusus untuk akun Teknisi.');

        $validated = $request->validate([
            'status_ketersediaan' => 'required|in:available,on_task,cuti,offline,off',
            'cuti_mulai' => 'nullable|date',
            'cuti_selesai' => 'nullable|date|after_or_equal:cuti_mulai',
            'catatan_ketersediaan' => 'nullable|string|max:255',
            'release_pm_to_team' => 'nullable|boolean',
        ], [
            'cuti_selesai.after_or_equal' => 'Tanggal selesai cuti/izin harus sama atau setelah tanggal mulai.',
        ]);

        $status = $validated['status_ketersediaan'] === 'off' ? 'offline' : $validated['status_ketersediaan'];

        // Di luar jam kerja (sebelum 08:00 atau >= 17:00 WIB), tidak bisa mengaktifkan status Available/On Task
        if (in_array($status, ['available', 'on_task'], true) && !User::isWithinWorkHours()) {
            return back()->with('error', 'Status kerja hanya dapat diaktifkan pada jam operasional 08.00 – 17.00 WIB. Saat ini di luar jam kerja sehingga status otomatis Offline.');
        }

        $isCuti = $status === 'cuti';
        $user->update([
            'status_ketersediaan' => $status,
            'cuti_mulai' => $isCuti ? ($validated['cuti_mulai'] ?? now()->toDateString()) : null,
            'cuti_selesai' => $isCuti ? ($validated['cuti_selesai'] ?? null) : null,
            'catatan_ketersediaan' => in_array($status, ['cuti', 'offline', 'on_task'], true)
                ? (trim((string) ($validated['catatan_ketersediaan'] ?? '')) ?: null)
                : null,
            'status_ketersediaan_updated_at' => now(),
        ]);

        if (in_array($status, ['available', 'on_task'], true)) {
            $user->syncTaskAvailability();
        }

        $releasedPm = 0;
        if (in_array($status, ['cuti', 'offline'], true) && $request->boolean('release_pm_to_team')) {
            $releasedPm = MaintenanceLog::where('user_id', $user->id)
                ->whereIn('status', ['Terjadwal', 'Terlambat'])
                ->update([
                    'user_id' => null,
                    'teknisi_nama' => 'Belum ada',
                ]);
        }

        $label = $user->fresh()->availability_short_note;
        $extra = '';
        $activeCount = Service::ownedByTeknisi($user)
            ->whereIn('status', ['Diproses', 'Menunggu sparepart'])
            ->count();

        if ($releasedPm > 0) {
            $extra = " {$releasedPm} jadwal PM terjadwal telah dilepas ke tim agar dapat diambil rekan.";
        } elseif ($status === 'cuti') {
            $extra = ' Koordinator IT (Admin) telah menerima notifikasi untuk penyesuaian penugasan.';
        } elseif ($status === 'available' && $activeCount > 0) {
            $extra = " Karena Anda saat ini masih memiliki {$activeCount} tiket yang sedang dikerjakan, status otomatis disesuaikan menjadi On Task.";
        }

        return back()->with('success', "Status kerja Anda berhasil diperbarui menjadi '{$label}'.{$extra}");
    }
}
