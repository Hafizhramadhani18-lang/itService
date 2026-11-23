<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Service::with(['user', 'teknisi'])->latest();

        $myMetrics = null;
        $pegawaiStatusCounts = null;

        // Pegawai only sees their own
        if ($user->isPegawai()) {
            $query->where('user_id', $user->id);

            $basePegawai = Service::where('user_id', $user->id);
            $pegawaiStatusCounts = [
                'semua' => (clone $basePegawai)->count(),
                'aktif' => (clone $basePegawai)->whereIn('status', ['Baru', 'Diproses', 'Menunggu sparepart'])->count(),
                'perlu_konfirmasi' => (clone $basePegawai)->where('status', 'Selesai')->whereNull('pelapor_confirmed_at')->count(),
                'selesai' => (clone $basePegawai)->where('status', 'Selesai')->count(),
                'dibatalkan' => (clone $basePegawai)->where('status', 'Dibatalkan')->count(),
            ];
        } else {
            // Logika Khusus Teknisi
            if ($request->get('filter') === 'my') {
                // Halaman "Tiket Saya" (Khusus tugas aktif yang SEDANG DIKERJAKAN oleh teknisi)
                $selectedTeknisi = $request->get('teknisi');
                
                $query->whereIn('status', ['Diproses', 'Menunggu sparepart']);

                $myBase = Service::whereIn('status', ['Diproses', 'Menunggu sparepart']);
                if ($selectedTeknisi && $selectedTeknisi !== 'semua') {
                    $this->applySelectedTeknisiFilter($query, $selectedTeknisi);
                    $this->applySelectedTeknisiFilter($myBase, $selectedTeknisi);
                } elseif (!$selectedTeknisi && $user->isTeknisi()) {
                    $this->applyTeknisiOwnershipScope($query, $user);
                    $this->applyTeknisiOwnershipScope($myBase, $user);
                }

                $selesaiQuery = Service::where('status', 'Selesai');
                if ($selectedTeknisi && $selectedTeknisi !== 'semua') {
                    $this->applySelectedTeknisiFilter($selesaiQuery, $selectedTeknisi);
                } elseif (!$selectedTeknisi && $user->isTeknisi()) {
                    $this->applyTeknisiOwnershipScope($selesaiQuery, $user);
                }

                $myMetrics = [
                    'total' => (clone $myBase)->count(),
                    'proses' => (clone $myBase)->where('status', 'Diproses')->count(),
                    'selesai' => $selesaiQuery->count(),
                    'own_active' => $user->isTeknisi()
                        ? Service::ownedByTeknisi($user)->whereIn('status', ['Diproses', 'Menunggu sparepart'])->count()
                        : 0,
                    'team_active' => Service::whereIn('status', ['Diproses', 'Menunggu sparepart'])->count(),
                ];

                // Filter status spesifik dalam tugas aktif
                if ($request->filled('status') && in_array($request->status, ['Diproses', 'Menunggu sparepart'])) {
                    $query->where('status', $request->status);
                }
            } elseif ($request->get('filter') === 'history') {
                // Halaman "Riwayat Tiket" (Seluruh tiket yang sudah selesai)
                $historyBase = Service::where('status', 'Selesai');
                
                $selectedTeknisi = $request->get('teknisi');
                $selesaiSelected = 0;
                if ($selectedTeknisi) {
                    $selectedHistoryQuery = (clone $historyBase);
                    $this->applySelectedTeknisiFilter($selectedHistoryQuery, $selectedTeknisi);
                    $selesaiSelected = $selectedHistoryQuery->count();
                }

                $myMetrics = [
                    'total_selesai' => (clone $historyBase)->count(),
                    'selesai_bulan_ini' => (clone $historyBase)->whereMonth('tanggal_selesai', now()->month)->whereYear('tanggal_selesai', now()->year)->count(),
                    'selesai_minggu_ini' => (clone $historyBase)->where('tanggal_selesai', '>=', now()->startOfWeek())->count(),
                    'selected_teknisi' => $selectedTeknisi,
                    'selesai_selected' => $selesaiSelected,
                ];
                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                } else {
                    $query->whereIn('status', ['Selesai', 'Dibatalkan']);
                }

                // Filter Spesifik: Tahun, Bulan, Minggu
                if ($request->filled('tahun')) {
                    $query->where(function ($q) use ($request) {
                        $q->whereYear('tanggal_selesai', $request->tahun)
                          ->orWhere(function ($sq) use ($request) {
                              $sq->whereNull('tanggal_selesai')->whereYear('updated_at', $request->tahun);
                          });
                    });
                }

                if ($request->filled('bulan')) {
                    $query->where(function ($q) use ($request) {
                        $q->whereMonth('tanggal_selesai', $request->bulan)
                          ->orWhere(function ($sq) use ($request) {
                              $sq->whereNull('tanggal_selesai')->whereMonth('updated_at', $request->bulan);
                          });
                    });
                }

                if ($request->filled('minggu')) {
                    $week = (int) $request->minggu;
                    if ($week === 1) {
                        $query->whereDay('tanggal_selesai', '>=', 1)->whereDay('tanggal_selesai', '<=', 7);
                    } elseif ($week === 2) {
                        $query->whereDay('tanggal_selesai', '>=', 8)->whereDay('tanggal_selesai', '<=', 14);
                    } elseif ($week === 3) {
                        $query->whereDay('tanggal_selesai', '>=', 15)->whereDay('tanggal_selesai', '<=', 21);
                    } elseif ($week === 4) {
                        $query->whereDay('tanggal_selesai', '>=', 22);
                    }
                }

                if ($request->filled('teknisi')) {
                    $this->applySelectedTeknisiFilter($query, $request->teknisi);
                }
            } elseif ($request->get('filter') === 'report') {
                // Halaman "Laporan Saya" (Role Teknisi) / Rekap Laporan
                $isTeknisiUser = $user->isTeknisi();
                
                // Base query: tiket yang ditangani oleh user ini (jika teknisi) atau teknisi terpilih
                $reportBase = Service::query();
                if ($isTeknisiUser) {
                    $this->applyTeknisiOwnershipScope($reportBase, $user);
                } elseif ($request->filled('teknisi') && $request->teknisi !== 'semua') {
                    $this->applySelectedTeknisiFilter($reportBase, $request->teknisi);
                }

                // Query Tiket Selesai (terkirim) mengikuti filter periode
                $selesaiQuery = (clone $reportBase)->where('status', 'Selesai');
                $periodMeta = $this->applyReportPeriodFilter($selesaiQuery, $request, 'tanggal_selesai');

                $tiketSelesaiCount = (clone $selesaiQuery)->count();

                // Laporan Draft: tiket yang masih berstatus Diproses atau Menunggu sparepart
                $laporanDraftCount = (clone $reportBase)->whereIn('status', ['Diproses', 'Menunggu sparepart'])->count();

                // Rata-rata waktu pengerjaan & rincian per tiket dari tiket selesai
                $selesaiTickets = (clone $selesaiQuery)->whereNotNull('tanggal_selesai')->orderByDesc('tanggal_selesai')->get();
                $totalMins = 0.0;
                $countWithDuration = 0;
                $durationBreakdown = [];

                foreach ($selesaiTickets as $st) {
                    if ($st->created_at && $st->tanggal_selesai) {
                        $diffM = (float) $st->created_at->diffInMinutes($st->tanggal_selesai);
                        if ($diffM > 0) {
                            $totalMins += $diffM;
                            $countWithDuration++;
                            $durationBreakdown[] = [
                                'id' => $st->id,
                                'ticket_number' => $st->ticket_number,
                                'judul' => $st->display_judul,
                                'divisi' => $st->divisi ?: '-',
                                'created_label' => $st->created_at->locale('id')->isoFormat('D MMM Y, HH:mm'),
                                'finished_label' => $st->tanggal_selesai->locale('id')->isoFormat('D MMM Y, HH:mm'),
                                'duration_mins' => $diffM,
                                'duration_label' => $this->formatDurationAdaptive($diffM),
                                'url' => route('services.show', $st),
                            ];
                        }
                    }
                }

                $avgMinsVal = $countWithDuration > 0 ? ($totalMins / $countWithDuration) : 0.0;
                $avgHoursStr = $countWithDuration > 0 ? $this->formatDurationAdaptive($avgMinsVal) : '—';

                $sortedByDuration = collect($durationBreakdown)->sortBy('duration_mins')->values();
                $fastestItem = $sortedByDuration->first();
                $slowestItem = $sortedByDuration->last();

                $monthNamesId = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                ];
                $quickKey = $periodMeta['quick'] ?? null;
                if ($quickKey === 'bulan_ini') {
                    $periodLabel = ($monthNamesId[(int) now()->month] ?? '') . ' ' . now()->year . ' · Reset otomatis tiap bulan';
                } elseif ($quickKey === 'minggu_ini') {
                    $periodLabel = 'Minggu ini · Reset otomatis tiap minggu';
                } elseif ($quickKey === 'bulan_lalu') {
                    $lm = now()->subMonth();
                    $periodLabel = ($monthNamesId[(int) $lm->month] ?? '') . ' ' . $lm->year;
                } elseif ($quickKey === 'minggu_lalu') {
                    $periodLabel = 'Minggu lalu';
                } elseif (!empty($periodMeta['bulan']) && !empty($periodMeta['tahun'])) {
                    $periodLabel = ($monthNamesId[(int) $periodMeta['bulan']] ?? '') . ' ' . $periodMeta['tahun'];
                } elseif (!empty($periodMeta['tahun'])) {
                    $periodLabel = 'Tahun ' . $periodMeta['tahun'];
                } else {
                    $periodLabel = 'Semua periode · Dari tiket selesai';
                }

                $myMetrics = [
                    'tiket_selesai' => $tiketSelesaiCount,
                    'laporan_draft' => $laporanDraftCount,
                    'avg_hours' => $avgHoursStr,
                    'period_label' => $periodLabel,
                    'fastest_duration' => $fastestItem['duration_label'] ?? '—',
                    'fastest_ticket' => $fastestItem['ticket_number'] ?? null,
                    'slowest_duration' => $slowestItem['duration_label'] ?? '—',
                    'slowest_ticket' => $slowestItem['ticket_number'] ?? null,
                    'duration_breakdown' => $sortedByDuration->reverse()->values()->all(),
                    'total_selesai' => $tiketSelesaiCount,
                    'selesai_bulan_ini' => $tiketSelesaiCount,
                    'selesai_minggu_ini' => (clone $selesaiQuery)->where('tanggal_selesai', '>=', now()->startOfWeek())->count(),
                    'selected_teknisi' => $user->name,
                    'selesai_selected' => $tiketSelesaiCount,
                ];

                // Table Rows: tiket selesai yang sesuai periode, PLUS tiket draft aktif
                $selesaiIds = (clone $selesaiQuery)->pluck('id')->toArray();
                $draftIds = (clone $reportBase)->whereIn('status', ['Diproses', 'Menunggu sparepart'])->pluck('id')->toArray();
                $allIds = array_merge($selesaiIds, $draftIds);

                $query->whereIn('id', $allIds);
            } elseif ($request->get('filter') === 'all') {
                // Halaman "Semua Tiket" (Master Seluruh Tiket untuk Admin & Teknisi)
                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }
                if ($request->filled('teknisi')) {
                    $this->applySelectedTeknisiFilter($query, $request->teknisi);
                }
            } elseif ($request->filled('teknisi')) {
                $this->applySelectedTeknisiFilter($query, $request->teknisi);
            } else {
                // Halaman "Antrian Tiket" (Murni tiket baru yang belum diambil teknisi)
                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                } else {
                    $query->where('status', 'Baru')->whereNull('teknisi_id');
                }
            }
        }

        if ($user->isPegawai() && $request->filled('status')) {
            $statusVal = strtolower($request->status);
            if ($statusVal === 'aktif') {
                $query->whereIn('status', ['Baru', 'Diproses', 'Menunggu sparepart']);
            } elseif ($statusVal === 'perlu_konfirmasi') {
                $query->where('status', 'Selesai')->whereNull('pelapor_confirmed_at');
            } elseif ($statusVal === 'dibatalkan') {
                $query->where('status', 'Dibatalkan');
            } elseif ($statusVal === 'selesai') {
                $query->where('status', 'Selesai');
            } elseif ($statusVal !== 'semua') {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('nama_pelapor', 'like', "%{$search}%")
                  ->orWhere('divisi', 'like', "%{$search}%")
                  ->orWhere('jenis_perangkat', 'like', "%{$search}%")
                  ->orWhere('lokasi_ruangan', 'like', "%{$search}%")
                  ->orWhere('judul_kendala', 'like', "%{$search}%")
                  ->orWhere('keluhan', 'like', "%{$search}%");
            });
        }

        $services = $query->paginate(12)->withQueryString();

        $teknisiList = $this->getTechnicianFilterOptions();

        $teknisiUsers = User::where('role', 'teknisi')
            ->where('name', '!=', 'Tim IT Support')
            ->orderBy('name')
            ->get();

        foreach ($teknisiUsers as $tek) {
            $tek->syncTaskAvailability();
            $tek->active_tasks_count = Service::ownedByTeknisi($tek)
                ->whereIn('status', ['Diproses', 'Menunggu sparepart'])
                ->count();
        }

        $startYear = 2026;
        $maxYear = 2030;
        $years = range($startYear, $maxYear);

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $weeks = [
            1 => 'Minggu 1',
            2 => 'Minggu 2',
            3 => 'Minggu 3',
            4 => 'Minggu 4',
        ];

        return view('services.index', compact(
            'services',
            'myMetrics',
            'pegawaiStatusCounts',
            'teknisiList',
            'teknisiUsers',
            'years',
            'months',
            'weeks'
        ));
    }

    public function create()
    {
        $user = Auth::user();
        $hasMasterCategories = \App\Models\DeviceCategory::exists();
        $dbCategories = \App\Models\DeviceCategory::where('is_active', true)->orderBy('id')->get();
        $dbLocations = \App\Models\Location::where('is_active', true)->orderBy('nama')->get();
        return view('services.create', compact('user', 'dbCategories', 'dbLocations', 'hasMasterCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lokasi_ruangan' => 'required|string|max:255',
            'jenis_perangkat' => 'required|string|max:100',
            'kode_perangkat' => 'nullable|string|max:50',
            'prioritas' => 'required|in:Rendah,Sedang,Tinggi,Mendesak',
            'judul_kendala' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
            'keluhan' => 'nullable|string',
            'foto' => 'nullable|image|max:5120',
        ]);

        $user = Auth::user();

        // Tentukan judul dan keluhan
        $judul = $request->filled('judul_kendala') ? trim($request->judul_kendala) : null;
        $keluhan = $request->filled('deskripsi')
            ? trim($request->deskripsi)
            : ($request->filled('keluhan') ? trim($request->keluhan) : ($judul ?? 'Keluhan perbaikan'));

        if ($judul && empty($request->deskripsi) && empty($request->keluhan)) {
            $keluhan = $judul;
        }

        // Handle upload foto lampiran jika ada
        $lampiranFoto = null;
        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = 'ticket_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('uploads/tickets');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $file->move($destinationPath, $filename);
            $lampiranFoto = 'uploads/tickets/' . $filename;
        }

        // Generate Ticket Number: TKT-XXXX (Aman dari duplikasi)
        $allTickets = Service::pluck('ticket_number');
        $maxNum = 143; // Base default dari referensi TKT-0143
        foreach ($allTickets as $t) {
            if (preg_match('/TKT-(\d+)/', $t, $m)) {
                $val = (int)$m[1];
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            }
        }
        $ticketNumber = 'TKT-' . str_pad($maxNum + 1, 4, '0', STR_PAD_LEFT);

        $checklist = Service::getDefaultChecklist($validated['jenis_perangkat']);

        $service = Service::create([
            'ticket_number' => $ticketNumber,
            'judul_kendala' => $judul,
            'user_id' => $user->id,
            'nama_pelapor' => $user->name,
            'divisi' => $user->divisi ?? 'Operasional',
            'lokasi_ruangan' => $validated['lokasi_ruangan'],
            'jenis_perangkat' => $validated['jenis_perangkat'],
            'kode_perangkat' => $validated['kode_perangkat'] ?? null,
            'keluhan' => $keluhan,
            'lampiran_foto' => $lampiranFoto,
            'prioritas' => $validated['prioritas'],
            'status' => 'Baru',
            'checklist' => $checklist,
        ]);

        return redirect()->route('services.show', $service)
            ->with('success', "Tiket perbaikan #{$ticketNumber} berhasil diajukan!")
            ->with('ticket_created_broadcast', $ticketNumber);
    }

    public function show(Service $service)
    {
        $user = Auth::user();

        if (!$service->canBeOpenedBy($user)) {
            abort(403, 'Akses ditolak. Anda hanya dapat membuka dan memproses tiket yang ditugaskan kepada Anda.');
        }

        // Tandai notifikasi tiket sudah dibaca saat teknisi atau admin membuka tiket
        if ($user->isTeknisi() || $user->isAdmin()) {
            if (!$service->is_read) {
                $service->update(['is_read' => true]);
            }
            \App\Services\NotificationCenterService::markServiceAsReadForUser($user, $service->id);
        }

        $service->load(['user', 'teknisi', 'comments.user']);
        $teknisiList = $this->getAssignableTechnicians($service->teknisi_nama);

        $teknisiUsers = User::where('role', 'teknisi')
            ->where('name', '!=', 'Tim IT Support')
            ->orderBy('name')
            ->get();

        foreach ($teknisiUsers as $tek) {
            $tek->syncTaskAvailability();
            $tek->active_tasks_count = Service::ownedByTeknisi($tek)
                ->whereIn('status', ['Diproses', 'Menunggu sparepart'])
                ->count();
        }

        return view('services.show', compact('service', 'teknisiList', 'teknisiUsers'));
    }

    public function storeComment(Request $request, Service $service)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = Auth::user();

        if (!$service->canBeOpenedBy($user)) {
            abort(403, 'Akses ditolak.');
        }

        $authorName = $user->isAdmin()
            ? ($user->name . ' (Koordinator IT)')
            : ($user->isTeknisi() ? ($service->teknisi_nama ?: $user->name) : $user->name);

        $authorRole = $user->isAdmin()
            ? 'admin'
            : ($user->isTeknisi() ? 'teknisi' : 'pegawai');

        $service->comments()->create([
            'user_id' => $user->id,
            'author_name' => $authorName,
            'author_role' => $authorRole,
            'message' => trim($request->message),
        ]);

        return back()->with('success', 'Pesan / komentar berhasil dikirim.');
    }

    public function edit(Service $service)
    {
        if (!Auth::user()->isTeknisi() && !Auth::user()->isAdmin()) {
            abort(403, 'Hanya teknisi IT dan Admin yang berhak memperbarui tindakan perbaikan.');
        }

        $service->load(['user', 'teknisi']);
        $teknisiList = $this->getAssignableTechnicians($service->teknisi_nama);

        return view('services.edit', compact('service', 'teknisiList'));
    }

    public function update(Request $request, Service $service)
    {
        $user = Auth::user();
        if (!$user->isTeknisi() && !$user->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        if (!$service->canBeOpenedBy($user)) {
            abort(403, 'Akses ditolak. Anda hanya dapat mengubah tiket yang ditugaskan kepada Anda.');
        }

        $validated = $request->validate([
            'status' => 'required|in:Baru,Diproses,Menunggu sparepart,Selesai,Dibatalkan',
            'teknisi_nama' => 'nullable|string|max:100',
            'tindakan_perbaikan' => 'nullable|string',
            'sparepart' => 'nullable|string|max:255',
            'prioritas' => 'sometimes|in:Rendah,Sedang,Tinggi,Mendesak',
            'foto_selesai' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('foto_selesai')) {
            $file = $request->file('foto_selesai');
            $filename = 'done_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $dest = public_path('uploads/tickets');
            if (!file_exists($dest)) {
                mkdir($dest, 0755, true);
            }
            $file->move($dest, $filename);
            $validated['foto_selesai'] = 'uploads/tickets/' . $filename;
        }

        if ($validated['status'] === 'Selesai') {
            if (!$service->tanggal_selesai) {
                $validated['tanggal_selesai'] = now();
            }
            if ($service->status !== 'Selesai') {
                $validated['pelapor_confirmed_at'] = null;
            }
        } else {
            $validated['tanggal_selesai'] = null;
            $validated['pelapor_confirmed_at'] = null;
        }

        // Jika status aktif, asosiasikan ke akun teknisi penanggung jawab
        if ($validated['status'] !== 'Baru') {
            if (!empty($validated['teknisi_nama'])) {
                $tekUser = User::where('name', $validated['teknisi_nama'])
                    ->orWhere('name', 'like', "%{$validated['teknisi_nama']}%")
                    ->first();
                $validated['teknisi_id'] = $tekUser ? $tekUser->id : ($service->teknisi_id ?: Auth::id());
            } else {
                $validated['teknisi_id'] = $service->teknisi_id ?: Auth::id();
                $validated['teknisi_nama'] = $service->teknisi_nama ?: Auth::user()->name;
            }
        } else {
            // Jika dikembalikan ke status Baru
            $validated['teknisi_id'] = null;
            $validated['teknisi_nama'] = null;
        }

        $oldTeknisiId = $service->teknisi_id;
        $service->update($validated);

        // Sinkronisasi otomatis status Available <-> On Task untuk teknisi terkait
        foreach (array_unique(array_filter([$oldTeknisiId, $service->teknisi_id])) as $syncTekId) {
            if ($syncUser = User::find($syncTekId)) {
                $syncUser->syncTaskAvailability();
            }
        }

        $pesan = $validated['status'] === 'Selesai'
            ? "Tiket #{$service->ticket_number} telah tuntas dan otomatis dipindahkan ke menu 'Riwayat Tiket'!"
            : "Perubahan tiket #{$service->ticket_number} berhasil disimpan!";

        return redirect()->route('services.show', $service)
            ->with('success', $pesan);
    }

    public function claim(Request $request, Service $service)
    {
        if (!Auth::user()->isTeknisi() && !Auth::user()->isAdmin()) {
            abort(403, 'Hanya teknisi IT dan Admin yang berhak mengambil tiket.');
        }

        $validated = $request->validate([
            'teknisi_id' => 'nullable|exists:users,id',
            'teknisi_nama' => 'nullable|string|max:100',
            'teknisi_pendamping' => 'nullable|array',
            'teknisi_pendamping.*' => 'exists:users,id',
            'catatan_penugasan' => 'nullable|string|max:500',
        ]);

        $teknisiId = $validated['teknisi_id'] ?? null;
        $teknisiNama = $validated['teknisi_nama'] ?? null;

        if ($teknisiId) {
            $tekUser = User::find($teknisiId);
            if ($tekUser) {
                $teknisiNama = $tekUser->name;
            }
        } elseif ($teknisiNama) {
            $tekUser = User::where('name', $teknisiNama)
                ->orWhere('name', 'like', "%{$teknisiNama}%")
                ->first();
            $teknisiId = $tekUser ? $tekUser->id : Auth::id();
        } else {
            $tekUser = Auth::user();
            $teknisiId = $tekUser->id;
            $teknisiNama = $tekUser->name;
        }

        // Kumpulkan data anggota tim pendamping (jika aksi tim / multi-teknisi)
        $pendampingData = [];
        $coNames = [];
        if ($request->filled('teknisi_pendamping')) {
            $coIds = array_diff((array) $request->teknisi_pendamping, [$teknisiId]);
            if (!empty($coIds)) {
                $coUsers = User::whereIn('id', $coIds)->get();
                foreach ($coUsers as $cu) {
                    $pendampingData[] = [
                        'id' => $cu->id,
                        'name' => $cu->name,
                    ];
                    $coNames[] = $cu->name;
                    $cu->syncTaskAvailability();
                }
            }
        }

        $service->update([
            'teknisi_id' => $teknisiId,
            'teknisi_nama' => $teknisiNama,
            'teknisi_pendamping' => !empty($pendampingData) ? $pendampingData : null,
            'status' => 'Diproses',
            'is_read' => true,
        ]);

        // Mengambil tiket adalah aktivitas kerja langsung hari ini -> aktifkan teknisi menjadi On Task
        if ($syncUser = User::find($teknisiId)) {
            $syncUser->update([
                'status_ketersediaan' => 'on_task',
                'status_ketersediaan_updated_at' => now(),
            ]);
        }
        if (!empty($coUsers)) {
            foreach ($coUsers as $cu) {
                $cu->update([
                    'status_ketersediaan' => 'on_task',
                    'status_ketersediaan_updated_at' => now(),
                ]);
            }
        }

        // Catat komentar kronologi klaim / penugasan tim
        if (class_exists(\App\Models\ServiceComment::class)) {
            $author = Auth::user();
            if (!empty($coNames)) {
                $msg = $teknisiId === $author->id
                    ? "Tiket diambil oleh {$author->name} bersama rekan tim: " . implode(', ', $coNames) . "."
                    : "Tiket ditugaskan kepada Tim IT dengan PIC {$teknisiNama} dan pendamping " . implode(', ', $coNames) . " oleh {$author->name}.";
            } else {
                $msg = $teknisiId === $author->id
                    ? "Tiket diambil oleh teknisi {$author->name}."
                    : "Tiket ditugaskan kepada teknisi {$teknisiNama} oleh {$author->name}.";
            }

            if ($request->filled('catatan_penugasan')) {
                $msg .= " Catatan tim: " . trim($request->catatan_penugasan);
            }

            \App\Models\ServiceComment::create([
                'service_id' => $service->id,
                'user_id' => $author->id,
                'author_name' => $author->name,
                'author_role' => $author->role,
                'message' => $msg,
            ]);
        }

        if (!empty($coNames)) {
            $pesan = $teknisiId === Auth::id()
                ? "Tiket #{$service->ticket_number} berhasil Anda ambil bersama tim (" . implode(', ', $coNames) . ")! Tiket telah masuk ke menu 'Tiket Saya'."
                : "Tiket #{$service->ticket_number} berhasil didelegasikan kepada Tim IT (PIC: {$teknisiNama}, Pendamping: " . implode(', ', $coNames) . ").";
        } else {
            $pesan = $teknisiNama === Auth::user()->name
                ? "Tiket #{$service->ticket_number} berhasil Anda ambil! Tiket telah berpindah ke menu 'Tiket Saya'."
                : "Tiket #{$service->ticket_number} berhasil ditugaskan kepada {$teknisiNama}.";
        }

        return back()->with('success', $pesan);
    }

    public function toggleChecklist(Request $request, Service $service)
    {
        $user = Auth::user();
        if (!$user->isTeknisi() && !$user->isAdmin()) {
            abort(403);
        }

        if (!$service->canBeOpenedBy($user)) {
            abort(403, 'Akses ditolak.');
        }

        $index = $request->input('index');
        $checklist = $service->checklist ?? [];

        if (isset($checklist[$index])) {
            $checklist[$index]['done'] = !$checklist[$index]['done'];
            $service->update(['checklist' => $checklist]);
        }

        return back()->with('success', 'Checklist troubleshooting berhasil diperbarui.');
    }

    public function markDone(Service $service)
    {
        $user = Auth::user();
        if (!$user->isTeknisi() && !$user->isAdmin()) {
            abort(403);
        }

        if (!$service->canBeOpenedBy($user)) {
            abort(403, 'Akses ditolak.');
        }

        $service->update([
            'status' => 'Selesai',
            'tanggal_selesai' => now(),
            'pelapor_confirmed_at' => null,
            'teknisi_id' => $service->teknisi_id ?: Auth::id(),
            'teknisi_nama' => $service->teknisi_nama ?: Auth::user()->name,
        ]);

        // Otomatis kembalikan status On Task -> Available jika sudah tidak ada tiket aktif tersisa
        if ($service->teknisi_id && ($syncUser = User::find($service->teknisi_id))) {
            $syncUser->syncTaskAvailability();
        }

        return back()->with('success', "Tiket #{$service->ticket_number} telah ditandai Selesai dan otomatis dipindahkan ke menu 'Riwayat Tiket'!");
    }

    /**
     * Konfirmasi penyelesaian tiket atau ajukan penanganan ulang oleh Pegawai (Pelapor)
     */
    public function confirm(Request $request, Service $service)
    {
        $user = Auth::user();
        if (!$user->isPegawai() || (int) $service->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak memiliki hak untuk mengonfirmasi tiket ini.');
        }

        if ($service->status !== 'Selesai') {
            return back()->withErrors(['error' => 'Konfirmasi hanya dapat dilakukan pada tiket yang berstatus Selesai.']);
        }

        $validated = $request->validate([
            'action' => 'required|in:confirm,reopen',
            'note' => 'required_if:action,reopen|nullable|string|max:1000',
        ], [
            'note.required_if' => 'Mohon jelaskan kendala yang masih terjadi agar teknisi dapat menindaklanjuti.',
        ]);

        $note = trim((string) ($validated['note'] ?? ''));

        if ($validated['action'] === 'reopen') {
            $service->update([
                'status' => 'Diproses',
                'tanggal_selesai' => null,
                'pelapor_confirmed_at' => null,
            ]);

            $service->comments()->create([
                'user_id' => $user->id,
                'author_name' => $user->name,
                'author_role' => 'pegawai',
                'message' => 'Pelapor melaporkan kendala belum teratasi: ' . $note,
            ]);

            return back()->with('info', "Tiket #{$service->ticket_number} telah dibuka kembali untuk ditindaklanjuti oleh teknisi.");
        }

        $service->update([
            'pelapor_confirmed_at' => now(),
        ]);

        $service->comments()->create([
            'user_id' => $user->id,
            'author_name' => $user->name,
            'author_role' => 'pegawai',
            'message' => $note !== ''
                ? "Pelapor mengonfirmasi selesai: {$note}"
                : 'Pelapor mengonfirmasi perbaikan telah selesai dan perangkat berfungsi normal.',
        ]);

        return back()->with('success', "Terima kasih! Tiket #{$service->ticket_number} telah Anda konfirmasi selesai.");
    }

    /**
     * Pembatalan tiket oleh Pegawai sebelum tiket diambil/ditangani teknisi
     */
    public function cancel(Request $request, Service $service)
    {
        $user = Auth::user();
        if (!$user->isPegawai() || (int) $service->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak memiliki hak untuk membatalkan tiket ini.');
        }

        if ($service->status !== 'Baru' || $service->teknisi_id !== null) {
            return back()->withErrors(['error' => 'Tiket yang sudah diambil atau ditangani teknisi tidak dapat dibatalkan secara mandiri.']);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $service->update([
            'status' => 'Dibatalkan',
        ]);

        $reason = trim((string) ($validated['reason'] ?? ''));
        $service->comments()->create([
            'user_id' => $user->id,
            'author_name' => $user->name,
            'author_role' => 'pegawai',
            'message' => $reason !== ''
                ? "Tiket dibatalkan oleh pelapor: {$reason}"
                : 'Tiket dibatalkan secara mandiri oleh pelapor.',
        ]);

        return back()
            ->with('info', "Tiket #{$service->ticket_number} telah dibatalkan.")
            ->with('ticket_cancelled_broadcast', [
                'ticket_number' => $service->ticket_number,
                'pelapor' => $user->name,
                'reason' => $reason,
            ]);
    }

    public function destroy(Service $service)
    {
        if (!Auth::user()->isTeknisi()) {
            abort(403, 'Akses ditolak.');
        }

        $service->delete();

        return redirect()->route('services.index')
            ->with('info', "Tiket #{$service->ticket_number} berhasil dihapus.");
    }

    public function cetak(Request $request)
    {
        $user = Auth::user();
        if (!$user->isTeknisi() && !$user->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $namaBulanIndo = [
            '1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April',
            '5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Agustus',
            '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
        ];

        // Mode Khusus Role Teknisi vs Rekap Admin
        $data = $user->isTeknisi()
            ? $this->buildTeknisiReportData($request, $user, $namaBulanIndo)
            : $this->buildAdminReportData($request, $namaBulanIndo);

        if ($request->get('download') === 'pdf') {
            return $this->streamReportPdf($request, $data);
        }

        return view('services.cetak', $data);
    }

    /**
     * Siapkan data Rekap Laporan IT untuk role Admin.
     */
    private function buildAdminReportData(Request $request, array $namaBulanIndo): array
    {
        $isTeknisiReport = false;
        $tahun = $request->get('tahun', 'all');
        $bulan = $request->get('bulan', 'all');
        $minggu = $request->get('minggu', 'all');
        $divisi = $request->get('divisi', 'all');
        $tim = $request->get('tim', 'all');
        $teknisi = $request->get('teknisi', 'all');

        $baseQuery = Service::with(['user', 'teknisi'])->latest();

        // Quick Period support
        if ($request->get('quick') === 'bulan_ini') {
            $tahun = now()->year;
            $bulan = now()->month;
            $baseQuery->whereYear('created_at', $tahun)->whereMonth('created_at', $bulan);
        } elseif ($request->get('quick') === 'bulan_lalu') {
            $lm = now()->subMonth();
            $tahun = $lm->year;
            $bulan = $lm->month;
            $baseQuery->whereYear('created_at', $tahun)->whereMonth('created_at', $bulan);
        } elseif ($request->get('quick') === 'minggu_ini') {
            $baseQuery->where('created_at', '>=', now()->startOfWeek())->where('created_at', '<=', now()->endOfWeek());
        } elseif ($request->get('quick') === 'minggu_lalu') {
            $baseQuery->where('created_at', '>=', now()->subWeek()->startOfWeek())->where('created_at', '<=', now()->subWeek()->endOfWeek());
        } else {
            if ($tahun !== 'all' && !empty($tahun)) {
                $baseQuery->whereYear('created_at', $tahun);
            }
            if ($bulan !== 'all' && !empty($bulan)) {
                $baseQuery->whereMonth('created_at', $bulan);
            }
            if ($minggu !== 'all' && !empty($minggu)) {
                $weekNum = (int) $minggu;
                $startDay = ($weekNum - 1) * 7 + 1;
                $endDay = min($weekNum * 7, 31);
                $baseQuery->whereDay('created_at', '>=', $startDay)->whereDay('created_at', '<=', $endDay);
            }
        }

        if ($divisi !== 'all' && !empty($divisi)) {
            $baseQuery->where('divisi', $divisi);
        }

        if ($tim !== 'all' && !empty($tim)) {
            $timLower = strtolower((string) $tim);
            $baseQuery->where(function ($q) use ($timLower) {
                $q->whereHas('teknisi', fn($uq) => $uq->where('spesialisasi', $timLower))
                  ->orWhere('teknisi_nama', 'like', "%{$timLower}%");
            });
        }

        if ($teknisi !== 'all' && !empty($teknisi) && $teknisi !== 'semua') {
            $this->applySelectedTeknisiFilter($baseQuery, (string) $teknisi);
        }

        $allPeriodTickets = $baseQuery->get();

        $totalMasuk = $allPeriodTickets->count();
        $totalSelesai = $allPeriodTickets->where('status', 'Selesai')->count();
        $totalTerbuka = $allPeriodTickets->whereIn('status', ['Baru', 'Diproses', 'Menunggu sparepart'])->count();
        $belumDiambil = $allPeriodTickets->where('status', 'Baru')->whereNull('teknisi_id')->count();

        // Rata-rata jam penanganan tiket selesai
        $selesaiList = $allPeriodTickets->where('status', 'Selesai')->filter(fn($t) => $t->created_at !== null && $t->tanggal_selesai !== null);
        if ($selesaiList->count() > 0) {
            $totalMins = $selesaiList->reduce(fn($c, $t) => $c + $t->created_at->diffInMinutes($t->tanggal_selesai), 0);
            $avgHours = number_format(round(($totalMins / $selesaiList->count()) / 60, 1), 1, ',', '.') . ' jam';
        } else {
            $avgHours = '—';
        }

        // Tiket per divisi & kategori
        $perDivisi = $allPeriodTickets
            ->groupBy(fn($t) => trim((string) $t->divisi) !== '' ? $t->divisi : 'Umum / Lainnya')
            ->map->count()
            ->sortDesc();

        $perKategori = $allPeriodTickets
            ->groupBy(fn($t) => trim((string) $t->jenis_perangkat) !== '' ? $t->jenis_perangkat : 'Lainnya')
            ->map->count()
            ->sortDesc();

        // Daftar tiket untuk tabel laporan
        if ($request->filled('status') && $request->status !== 'all') {
            $services = $allPeriodTickets->where('status', $request->status)->values();
        } else {
            $services = $allPeriodTickets->sortByDesc(fn($t) => $t->status === 'Selesai' ? 1 : 0)->values();
        }

        $divisiList = Service::select('divisi')->distinct()->pluck('divisi');

        // Label Periode & Filter Aktif
        $namaBulan = ($bulan !== 'all' && isset($namaBulanIndo[(string)$bulan])) ? $namaBulanIndo[(string)$bulan] : 'Semua bulan';
        $mingguLabel = ($minggu !== 'all') ? "Minggu ke-{$minggu}" : 'Semua minggu';
        $tahunLabel = ($tahun !== 'all') ? (string)$tahun : 'Semua tahun';

        $filterAktifTahunBulan = "{$tahunLabel} · {$namaBulan} · {$mingguLabel}";
        $divisiText = ($divisi !== 'all' && !empty($divisi)) ? "Divisi {$divisi}" : 'Semua divisi';
        $timText = ($tim !== 'all' && !empty($tim)) ? "Tim " . ucfirst($tim) : 'Semua tim';
        $teknisiText = ($teknisi !== 'all' && !empty($teknisi) && $teknisi !== 'semua') ? "Teknisi {$teknisi}" : 'Semua teknisi';
        $filterAktifDivisiTeknisi = "{$divisiText} · {$timText} · {$teknisiText}";

        if ($tahun === 'all' && $bulan === 'all') {
            $periodeText = "Semua Periode · {$divisiText} · {$timText}";
        } elseif ($tahun === 'all' && $bulan !== 'all') {
            $periodeText = "Bulan {$namaBulan} (Semua Tahun) · {$divisiText} · {$timText}";
        } elseif ($tahun !== 'all' && $bulan === 'all') {
            $periodeText = "Tahun {$tahunLabel} (Semua Bulan) · {$divisiText} · {$timText}";
        } else {
            $periodeText = "Periode {$namaBulan} {$tahunLabel} · {$divisiText} · {$timText}";
        }

        // Data Tren 7 Hari selaras dengan filter periode aktif
        $trendDates = collect();
        $quickMode = $request->get('quick');
        if ($quickMode === 'minggu_ini') {
            $ws = now()->startOfWeek();
            for ($i = 0; $i < 7; $i++) {
                $trendDates->push($ws->copy()->addDays($i));
            }
        } elseif ($quickMode === 'minggu_lalu') {
            $ws = now()->subWeek()->startOfWeek();
            for ($i = 0; $i < 7; $i++) {
                $trendDates->push($ws->copy()->addDays($i));
            }
        } else {
            $targetYearNum = ($tahun !== 'all' && !empty($tahun)) ? (int) $tahun : (int) now()->year;
            $targetMonthNum = ($bulan !== 'all' && !empty($bulan)) ? (int) $bulan : (int) now()->month;
            $isCurrentMonth = ($targetYearNum === (int) now()->year && $targetMonthNum === (int) now()->month);

            if ($minggu !== 'all' && !empty($minggu)) {
                $weekNum = max(1, min(5, (int) $minggu));
                $startDay = ($weekNum - 1) * 7 + 1;
                $daysInMonth = \Carbon\Carbon::create($targetYearNum, $targetMonthNum, 1)->daysInMonth;
                $endDay = min($startDay + 6, $daysInMonth);
                for ($d = $startDay; $d <= $endDay; $d++) {
                    $trendDates->push(\Carbon\Carbon::create($targetYearNum, $targetMonthNum, $d)->startOfDay());
                }
            } elseif ($isCurrentMonth || ($tahun === 'all' && $bulan === 'all')) {
                for ($i = 6; $i >= 0; $i--) {
                    $trendDates->push(now()->subDays($i)->startOfDay());
                }
            } else {
                $daysInMonth = \Carbon\Carbon::create($targetYearNum, $targetMonthNum, 1)->daysInMonth;
                $endDay = min(7, $daysInMonth);
                for ($d = 1; $d <= $endDay; $d++) {
                    $trendDates->push(\Carbon\Carbon::create($targetYearNum, $targetMonthNum, $d)->startOfDay());
                }
            }
        }

        $todayStart = now()->startOfDay();
        $dailyTrend = $trendDates->map(function ($tgl) use ($allPeriodTickets, $todayStart) {
            $masuk = $allPeriodTickets->filter(fn($t) => $t->created_at && $t->created_at->isSameDay($tgl))->count();
            $selesai = $allPeriodTickets->filter(function ($t) use ($tgl) {
                if ($t->status !== 'Selesai') {
                    return false;
                }
                $refDate = $t->tanggal_selesai ?: $t->created_at;
                return $refDate && $refDate->isSameDay($tgl);
            })->count();

            return [
                'day_short' => $tgl->locale('id')->isoFormat('dd'),
                'date_short' => $tgl->format('d/m'),
                'date_full' => $tgl->locale('id')->isoFormat('dd, D MMM Y'),
                'masuk' => $masuk,
                'selesai' => $selesai,
                'is_today' => $tgl->isSameDay($todayStart),
            ];
        })->values();
        $maxDailyTrend = max(1, (int) $dailyTrend->max(fn($d) => max($d['masuk'], $d['selesai'])));

        // Kinerja teknisi
        $teknisiUsersQuery = User::where('role', 'teknisi')
            ->where('name', '!=', 'Tim IT Support')
            ->orderBy('name');
        $teknisiUsers = $teknisiUsersQuery->get();
        if ($teknisiUsers->isEmpty()) {
            $teknisiUsers = User::where('role', 'teknisi')->orderBy('name')->get();
        }
        $kinerjaTeknisi = $teknisiUsers->map(function ($tek) use ($allPeriodTickets) {
            $tekTickets = $allPeriodTickets->filter(fn ($t) => $t->isOwnedByTeknisi($tek));
            $diambil = $tekTickets->count();
            $selesaiTickets = $tekTickets->where('status', 'Selesai');
            $selesai = $selesaiTickets->count();

            $finished = $selesaiTickets->filter(fn($t) => $t->created_at !== null && $t->tanggal_selesai !== null);
            if ($finished->count() > 0) {
                $mins = $finished->reduce(fn($c, $t) => $c + $t->created_at->diffInMinutes($t->tanggal_selesai), 0);
                $avgTime = number_format(round(($mins / $finished->count()) / 60, 1), 1, ',', '.') . ' jam';
            } else {
                $avgTime = '—';
            }

            return [
                'user' => $tek,
                'spesialisasi' => $tek->label_tim ?? 'Tim IT Support',
                'diambil' => $diambil,
                'selesai' => $selesai,
                'avg_time' => $avgTime,
                'rate' => $diambil > 0 ? round(($selesai / $diambil) * 100) : 0,
            ];
        });

        return compact(
            'isTeknisiReport',
            'services',
            'allPeriodTickets',
            'divisiList',
            'totalMasuk',
            'totalSelesai',
            'totalTerbuka',
            'belumDiambil',
            'avgHours',
            'perDivisi',
            'perKategori',
            'dailyTrend',
            'maxDailyTrend',
            'kinerjaTeknisi',
            'filterAktifTahunBulan',
            'filterAktifDivisiTeknisi',
            'periodeText',
            'namaBulan',
            'tahunLabel',
            'mingguLabel'
        );
    }

    /**
     * Siapkan data Pratinjau Cetak / PDF khusus Laporan Kerja Teknisi ("Laporan Saya").
     */
    private function buildTeknisiReportData(Request $request, User $user, array $namaBulanIndo): array
    {
        $isTeknisiReport = true;

        $myBase = Service::with(['user', 'teknisi']);
        $this->applyTeknisiOwnershipScope($myBase, $user);

        // Tiket selesai (status Terkirim) mengikuti filter periode aktif
        $selesaiQuery = (clone $myBase)->where('status', 'Selesai');
        $periodMeta = $this->applyReportPeriodFilter($selesaiQuery, $request, 'tanggal_selesai');

        if ($request->filled('search')) {
            $search = $request->search;
            $selesaiQuery->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('nama_pelapor', 'like', "%{$search}%")
                  ->orWhere('divisi', 'like', "%{$search}%")
                  ->orWhere('jenis_perangkat', 'like', "%{$search}%")
                  ->orWhere('keluhan', 'like', "%{$search}%");
            });
        }

        $services = $selesaiQuery->orderByDesc('tanggal_selesai')->orderByDesc('id')->get();
        $draftTickets = (clone $myBase)->whereIn('status', ['Diproses', 'Menunggu sparepart'])->get();

        $totalSelesai = $services->count();
        $totalTerbuka = $draftTickets->count(); // Aktif (Draft)
        $totalDiambil = $totalSelesai + $totalTerbuka;

        // Rata-rata waktu penanganan dari tiket selesai
        $totalMins = 0;
        $counted = 0;
        foreach ($services as $st) {
            if ($st->created_at && $st->tanggal_selesai) {
                $diffM = $st->created_at->diffInMinutes($st->tanggal_selesai);
                if ($diffM > 0) {
                    $totalMins += $diffM;
                    $counted++;
                }
            }
        }
        $avgHours = $counted > 0
            ? $this->formatDurationAdaptive($totalMins / $counted)
            : '—';

        // Rincian per prioritas dan kategori
        $perPrioritas = collect([
            'Mendesak' => $services->where('prioritas', 'Mendesak')->count(),
            'Sedang' => $services->where('prioritas', 'Sedang')->count(),
            'Rendah' => $services->where('prioritas', 'Rendah')->count(),
        ]);
        $tinggiCount = $services->where('prioritas', 'Tinggi')->count();
        if ($tinggiCount > 0) {
            $perPrioritas = collect([
                'Mendesak' => $perPrioritas['Mendesak'],
                'Tinggi' => $tinggiCount,
                'Sedang' => $perPrioritas['Sedang'],
                'Rendah' => $perPrioritas['Rendah'],
            ]);
        }

        $perKategori = $services->groupBy('jenis_perangkat')->map->count()->sortDesc();

        // Label periode & filter aktif
        $tahunLabel = $periodMeta['tahun'] ? (string) $periodMeta['tahun'] : 'Semua tahun';
        $namaBulan = ($periodMeta['bulan'] && isset($namaBulanIndo[(string) $periodMeta['bulan']]))
            ? $namaBulanIndo[(string) $periodMeta['bulan']]
            : 'Semua bulan';
        $mingguLabel = $periodMeta['minggu'] ? "Minggu ke-{$periodMeta['minggu']}" : 'Semua minggu';

        if ($periodMeta['quick'] === 'minggu_ini') {
            $filterAktifTahunBulan = 'Minggu Ini · ' . now()->startOfWeek()->locale('id')->isoFormat('D MMM Y');
            $periodeShort = 'Minggu Ini';
        } elseif ($periodMeta['quick'] === 'minggu_lalu') {
            $filterAktifTahunBulan = 'Minggu Lalu · ' . now()->subWeek()->startOfWeek()->locale('id')->isoFormat('D MMM Y');
            $periodeShort = 'Minggu Lalu';
        } else {
            $filterAktifTahunBulan = "{$tahunLabel} · {$namaBulan} · {$mingguLabel}";
            $periodeShort = "Periode {$namaBulan} {$tahunLabel}";
        }

        $filterAktifDivisiTeknisi = 'Hanya laporan milik Anda · Status Terkirim';
        $periodeText = "{$user->name} · {$user->label_tim} · {$periodeShort}";

        return compact(
            'isTeknisiReport',
            'user',
            'services',
            'totalDiambil',
            'totalSelesai',
            'totalTerbuka',
            'avgHours',
            'perPrioritas',
            'perKategori',
            'filterAktifTahunBulan',
            'filterAktifDivisiTeknisi',
            'periodeText'
        );
    }

    /**
     * Render dan unduh langsung file PDF laporan menggunakan DomPDF sesuai opsi pratinjau.
     */
    private function streamReportPdf(Request $request, array $data)
    {
        $isTeknisiMode = !empty($data['isTeknisiReport']);
        $defaultSigner = Auth::user()->name ?? ($isTeknisiMode ? 'Teknisi IT' : 'Hendra Wijaya, S.T.');

        $dibuatInput = trim((string) $request->get('dibuat_oleh', ''));
        $mengetahuiInput = trim((string) $request->get('mengetahui', ''));

        $showFoto = $request->boolean('show_foto', false);
        $pdfOptions = [
            'show_ringkasan' => $request->boolean('show_ringkasan', true),
            'show_rincian_pk' => $request->boolean('show_rincian_pk', true),
            'show_daftar' => $request->boolean('show_daftar', true),
            'show_sparepart' => $request->boolean('show_sparepart', false),
            'show_foto' => $showFoto,
            'show_grafik' => $request->boolean('show_grafik', false),
            'show_divisi_kategori' => $request->boolean('show_divisi_kategori', true),
            'show_kinerja' => $request->boolean('show_kinerja', false),
            'show_ttd' => $request->boolean('show_ttd', true),
            'dibuat_oleh' => $dibuatInput !== '' ? $dibuatInput : $defaultSigner,
            'mengetahui_display' => ($mengetahuiInput === '' || $mengetahuiInput === 'Nama atasan') ? '[Nama atasan]' : $mengetahuiInput,
        ];

        $photoAttachments = [];
        if ($isTeknisiMode && $showFoto) {
            foreach ($data['services'] ?? [] as $item) {
                if (!empty($item->lampiran_foto)) {
                    $photoAttachments[] = [
                        'ticket_number' => $item->ticket_number,
                        'judul' => $item->display_judul,
                        'data_uri' => $this->resolvePhotoDataUri($item->lampiran_foto),
                    ];
                }
            }
        }

        $orientation = strtolower((string) $request->get('orientasi', 'potret')) === 'lanskap' ? 'landscape' : 'portrait';
        $kertas = strtoupper((string) $request->get('kertas', 'A4'));
        $paperSpec = match ($kertas) {
            'LETTER' => 'letter',
            'F4' => [0, 0, 609.45, 935.43], // 215mm x 330mm dalam satuan point PDF
            default => 'a4',
        };
        $orientationLabel = $orientation === 'landscape' ? 'Lanskap' : 'Potret';
        $paperShortLabel = match ($kertas) {
            'LETTER' => 'Letter',
            'F4' => 'F4 / Folio',
            default => 'A4',
        } . ' · ' . $orientationLabel;

        $filename = $isTeknisiMode
            ? 'Laporan-Kerja-Teknisi-' . Str::slug($data['user']->name ?? 'teknisi') . '-' . now()->format('Ymd') . '.pdf'
            : 'Laporan-Rekapitulasi-Servis-IT-' . now()->format('Ymd') . '.pdf';

        $pdf = Pdf::loadView('services.cetak-pdf', array_merge($data, [
            'pdfOptions' => $pdfOptions,
            'photoAttachments' => $photoAttachments,
            'isLandscapePdf' => $orientation === 'landscape',
            'paperShortLabel' => $paperShortLabel,
        ]))->setPaper($paperSpec, $orientation);

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $marginX = $orientation === 'landscape' ? 34.02 : 36.85; // 12mm / 13mm dalam point
        $footerYear = date('Y');
        $isLandscape = ($orientation === 'landscape');

        $canvas->page_script(function (int $pageNumber, int $pageCount, $pageCanvas, $fontMetrics) use ($marginX, $footerYear, $paperShortLabel, $isLandscape) {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal') ?: $fontMetrics->getFont('Helvetica', 'normal');
            $size = 7.5;
            $pageW = $pageCanvas->get_width();
            $pageH = $pageCanvas->get_height();
            $lineY = $pageH - ($isLandscape ? 30.0 : 34.0);
            $textY = $pageH - ($isLandscape ? 21.5 : 25.0);

            $pageCanvas->line($marginX, $lineY, $pageW - $marginX, $lineY, [0.82, 0.85, 0.90], 0.6);

            $leftText = "© {$footerYear} PT Sumber Segara Primadaya (S2P) · {$paperShortLabel}";
            $pageCanvas->text($marginX, $textY, $leftText, $font, $size, [0.45, 0.51, 0.59]);

            $rightText = "Halaman {$pageNumber} dari {$pageCount}";
            $rightWidth = $fontMetrics->getTextWidth($rightText, $font, $size);
            $pageCanvas->text($pageW - $marginX - $rightWidth, $textY, $rightText, $font, $size, [0.28, 0.33, 0.41]);
        });

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Ubah path foto lokal menjadi base64 data URI agar tampil aman di DomPDF tanpa network fetch.
     */
    private function resolvePhotoDataUri(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        $candidates = [
            public_path($normalized),
            storage_path('app/public/' . preg_replace('#^storage/#', '', $normalized)),
        ];

        foreach ($candidates as $fullPath) {
            if (is_file($fullPath) && is_readable($fullPath)) {
                $mime = mime_content_type($fullPath) ?: 'image/jpeg';
                if (str_starts_with($mime, 'image/')) {
                    $raw = file_get_contents($fullPath);
                    if ($raw !== false) {
                        return 'data:' . $mime . ';base64,' . base64_encode($raw);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Scope query tiket agar hanya mengambil milik akun teknisi tertentu.
     */
    private function applyTeknisiOwnershipScope($query, User $user): void
    {
        $query->ownedByTeknisi($user);
    }

    /**
     * Daftar opsi nama teknisi untuk filter dan modal klaim di daftar tiket.
     * Bersumber dari akun aktif database serta nama teknisi historis pada tiket.
     */
    private function getTechnicianFilterOptions(): array
    {
        $dbUsers = User::whereIn('role', ['teknisi', 'admin'])
            ->where('name', '!=', 'Tim IT Support')
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values();

        if ($dbUsers->isEmpty()) {
            $dbUsers = User::whereIn('role', ['teknisi', 'admin'])
                ->orderBy('name')
                ->pluck('name')
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->values();
        }

        $ticketNames = Service::whereNotNull('teknisi_nama')
            ->where('teknisi_nama', '!=', '')
            ->where('teknisi_nama', '!=', 'Tim IT Support')
            ->distinct()
            ->orderBy('teknisi_nama')
            ->pluck('teknisi_nama')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values();

        $merged = $dbUsers->merge($ticketNames)
            ->unique(fn ($name) => mb_strtolower($name))
            ->values()
            ->all();

        return !empty($merged) ? $merged : array_keys(Service::TEKNISI_PERSONEL);
    }

    /**
     * Daftar pasangan [nama => label] teknisi aktif di database untuk penugasan tiket,
     * sambil mempertahankan teknisi historis yang sedang terpasang pada tiket.
     */
    private function getAssignableTechnicians(?string $currentAssignedName = null): array
    {
        $users = User::whereIn('role', ['teknisi', 'admin'])
            ->where('name', '!=', 'Tim IT Support')
            ->orderBy('name')
            ->get();

        if ($users->isEmpty()) {
            $users = User::whereIn('role', ['teknisi', 'admin'])
                ->orderBy('name')
                ->get();
        }

        $list = [];
        foreach ($users as $tech) {
            $spec = trim((string) ($tech->spesialisasi ?: ($tech->divisi ?: 'IT Support')));
            $list[$tech->name] = $tech->name . ' (' . $spec . ')';
        }

        if (empty($list)) {
            $list = Service::TEKNISI_PERSONEL;
        }

        $current = trim((string) $currentAssignedName);
        if ($current !== '' && !array_key_exists($current, $list)) {
            $list[$current] = $current . ' (Penugasan saat ini)';
        }

        return $list;
    }

    /**
     * Terapkan filter teknisi yang aman dari tabrakan prefix nama (mis. Arif vs Arifin).
     */
    private function applySelectedTeknisiFilter($query, string $selectedTeknisi): void
    {
        $selected = trim($selectedTeknisi);
        if ($selected === '' || in_array(mb_strtolower($selected), ['all', 'semua'], true)) {
            return;
        }

        if (ctype_digit($selected)) {
            $userById = User::whereIn('role', ['teknisi', 'admin'])->find((int) $selected);
            if ($userById) {
                $query->ownedByTeknisi($userById);
                return;
            }
        }

        $exactUser = User::whereIn('role', ['teknisi', 'admin'])
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($selected)])
            ->first();

        if (!$exactUser && strcasecmp($selected, 'Tim IT Support') !== 0) {
            $firstWord = explode(' ', $selected)[0];
            if (mb_strlen($firstWord) >= 3) {
                $firstWordMatches = User::whereIn('role', ['teknisi', 'admin'])
                    ->where('name', '!=', 'Tim IT Support')
                    ->get()
                    ->filter(fn ($u) => strcasecmp(explode(' ', trim($u->name))[0], $firstWord) === 0);

                if ($firstWordMatches->count() === 1) {
                    $exactUser = $firstWordMatches->first();
                }
            }
        }

        if ($exactUser) {
            $query->ownedByTeknisi($exactUser);
            return;
        }

        $lowerSelected = mb_strtolower($selected);
        $escaped = addcslashes($lowerSelected, '%_\\');
        $query->where(function ($q) use ($lowerSelected, $escaped) {
            $q->whereRaw('LOWER(TRIM(COALESCE(teknisi_nama, ""))) = ?', [$lowerSelected])
              ->orWhereRaw('LOWER(TRIM(COALESCE(teknisi_nama, ""))) LIKE ?', ["{$escaped} %"]);
        });
    }

    /**
     * Terapkan filter periode quick / tahun / bulan / minggu untuk Laporan Teknisi.
     */
    private function applyReportPeriodFilter($query, Request $request, string $dateColumn = 'tanggal_selesai'): array
    {
        $quick = $request->get('quick');

        $tahun = $request->filled('tahun') && $request->tahun !== 'all' ? $request->tahun : null;
        $bulan = $request->filled('bulan') && $request->bulan !== 'all' ? $request->bulan : null;
        $minggu = $request->filled('minggu') && $request->minggu !== 'all' ? $request->minggu : null;

        if ($quick === 'bulan_ini') {
            $tahun = now()->year;
            $bulan = now()->month;
            $query->whereYear($dateColumn, $tahun)->whereMonth($dateColumn, $bulan);
        } elseif ($quick === 'bulan_lalu') {
            $lm = now()->subMonth();
            $tahun = $lm->year;
            $bulan = $lm->month;
            $query->whereYear($dateColumn, $tahun)->whereMonth($dateColumn, $bulan);
        } elseif ($quick === 'minggu_ini') {
            $query->where($dateColumn, '>=', now()->startOfWeek())
                  ->where($dateColumn, '<=', now()->endOfWeek());
        } elseif ($quick === 'minggu_lalu') {
            $query->where($dateColumn, '>=', now()->subWeek()->startOfWeek())
                  ->where($dateColumn, '<=', now()->subWeek()->endOfWeek());
        } else {
            if ($tahun) {
                $query->whereYear($dateColumn, $tahun);
            }
            if ($bulan) {
                $query->whereMonth($dateColumn, $bulan);
            }
            if ($minggu) {
                $week = (int) $minggu;
                if ($week === 1) $query->whereDay($dateColumn, '>=', 1)->whereDay($dateColumn, '<=', 7);
                elseif ($week === 2) $query->whereDay($dateColumn, '>=', 8)->whereDay($dateColumn, '<=', 14);
                elseif ($week === 3) $query->whereDay($dateColumn, '>=', 15)->whereDay($dateColumn, '<=', 21);
                elseif ($week === 4) $query->whereDay($dateColumn, '>=', 22);
            }
        }

        return [
            'quick' => $quick,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'minggu' => $minggu,
        ];
    }

    /**
     * Format durasi menit menjadi label adaptif yang mudah dibaca:
     * < 60 mnt -> "35 mnt"
     * 1-24 jam -> "2,4 jam"
     * >= 24 jam -> "3 hr 6 jam"
     */
    private function formatDurationAdaptive(float $minutes): string
    {
        if ($minutes <= 0) {
            return '—';
        }
        if ($minutes < 60) {
            return max(1, (int) round($minutes)) . ' mnt';
        }
        $hours = $minutes / 60;
        if ($hours < 24) {
            return number_format(round($hours, 1), 1, ',', '.') . ' jam';
        }
        $days = (int) floor($hours / 24);
        $remHours = (int) round(fmod($hours, 24));
        if ($remHours >= 24) {
            $days++;
            $remHours = 0;
        }
        return $remHours > 0 ? "{$days} hr {$remHours} jam" : "{$days} hr";
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $query = Service::with(['user', 'teknisi'])->latest();
        $filter = $request->get('filter');
        $isTeknisiReport = $user->isTeknisi() && $filter === 'report';
        $isAdminReport = $user->isAdmin() && $filter === 'report';

        if ($user->isPegawai()) {
            $query->where('user_id', $user->id);
        } elseif ($isTeknisiReport) {
            $this->applyTeknisiOwnershipScope($query, $user);
        }

        if ($filter === 'my') {
            $query->whereIn('status', ['Diproses', 'Menunggu sparepart']);
            if ($user->isTeknisi() && !$request->filled('teknisi')) {
                $this->applyTeknisiOwnershipScope($query, $user);
            }
        } elseif ($filter === 'history' || $isTeknisiReport) {
            $query->where('status', 'Selesai');
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }

        if ($request->filled('divisi') && $request->divisi !== 'all') {
            $query->where('divisi', $request->divisi);
        }

        if ($request->filled('tim') && $request->tim !== 'all') {
            $timLower = strtolower((string) $request->tim);
            $teamUsers = User::whereIn('role', ['teknisi', 'admin'])
                ->where('name', '!=', 'Tim IT Support')
                ->where('spesialisasi', $timLower)
                ->get();
            $query->where(function ($q) use ($teamUsers, $timLower) {
                if ($teamUsers->isNotEmpty()) {
                    foreach ($teamUsers as $tu) {
                        $q->orWhere(fn ($sub) => $sub->ownedByTeknisi($tu));
                    }
                } else {
                    $q->whereHas('teknisi', fn ($uq) => $uq->where('spesialisasi', $timLower));
                }
            });
        }

        if (!$isTeknisiReport && $request->filled('teknisi') && !in_array($request->teknisi, ['all', 'semua'], true)) {
            $this->applySelectedTeknisiFilter($query, (string) $request->teknisi);
        }

        if ($isTeknisiReport) {
            $this->applyReportPeriodFilter($query, $request, 'tanggal_selesai');
        } else {
            $dateCol = $isAdminReport ? 'created_at' : 'tanggal_selesai';
            // Quick Period
            if ($request->get('quick') === 'bulan_ini') {
                $query->whereYear($dateCol, now()->year)->whereMonth($dateCol, now()->month);
            } elseif ($request->get('quick') === 'bulan_lalu') {
                $lm = now()->subMonth();
                $query->whereYear($dateCol, $lm->year)->whereMonth($dateCol, $lm->month);
            } elseif ($request->get('quick') === 'minggu_ini') {
                $query->where($dateCol, '>=', now()->startOfWeek())->where($dateCol, '<=', now()->endOfWeek());
            } elseif ($request->get('quick') === 'minggu_lalu') {
                $query->where($dateCol, '>=', now()->subWeek()->startOfWeek())->where($dateCol, '<=', now()->subWeek()->endOfWeek());
            }

            if ($request->filled('tahun') && $request->tahun !== 'all') {
                $query->whereYear($dateCol, $request->tahun);
            }
            if ($request->filled('bulan') && $request->bulan !== 'all') {
                $query->whereMonth($dateCol, $request->bulan);
            }
            if ($request->filled('minggu') && $request->minggu !== 'all') {
                $week = (int) $request->minggu;
                if ($isAdminReport) {
                    $startDay = ($week - 1) * 7 + 1;
                    $endDay = min($week * 7, 31);
                    $query->whereDay($dateCol, '>=', $startDay)->whereDay($dateCol, '<=', $endDay);
                } else {
                    if ($week === 1) $query->whereDay($dateCol, '>=', 1)->whereDay($dateCol, '<=', 7);
                    elseif ($week === 2) $query->whereDay($dateCol, '>=', 8)->whereDay($dateCol, '<=', 14);
                    elseif ($week === 3) $query->whereDay($dateCol, '>=', 15)->whereDay($dateCol, '<=', 21);
                    elseif ($week === 4) $query->whereDay($dateCol, '>=', 22);
                }
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('nama_pelapor', 'like', "%{$search}%")
                  ->orWhere('divisi', 'like', "%{$search}%")
                  ->orWhere('jenis_perangkat', 'like', "%{$search}%")
                  ->orWhere('keluhan', 'like', "%{$search}%");
            });
        }

        $services = $query->get();

        if ($request->get('filter') === 'report') {
            $parts = [$isAdminReport ? 'Rekap_Laporan_IT_PT_S2P' : 'Laporan_Teknisi_PT_S2P'];
            if ($request->filled('quick')) $parts[] = ucfirst(str_replace('_', '', $request->quick));
            if ($request->filled('tahun')) $parts[] = 'Thn_' . $request->tahun;
            if ($request->filled('bulan')) $parts[] = 'Bln_' . $request->bulan;
            if ($request->filled('minggu')) $parts[] = 'Mgu_' . $request->minggu;
            if ($request->filled('teknisi')) $parts[] = str_replace([' ', '.', ','], '_', $request->teknisi);
            $filenamePrefix = implode('_', $parts);
        } elseif ($request->get('filter') === 'history') {
            $parts = ['Rekap_Riwayat_Tiket_Selesai'];
            if ($request->filled('tahun')) $parts[] = 'Thn_' . $request->tahun;
            if ($request->filled('bulan')) $parts[] = 'Bln_' . $request->bulan;
            if ($request->filled('minggu')) $parts[] = 'Mgu_' . $request->minggu;
            if ($request->filled('teknisi')) $parts[] = str_replace([' ', '.', ','], '_', $request->teknisi);
            $filenamePrefix = implode('_', $parts);
        } elseif ($request->get('filter') === 'my') {
            if ($request->filled('teknisi') && $request->teknisi !== 'semua') {
                $filenamePrefix = 'Rekap_Tugas_Aktif_' . str_replace([' ', '.', ','], '_', $request->teknisi);
            } else {
                $filenamePrefix = 'Rekap_Seluruh_Tugas_Aktif_Tim_Teknisi';
            }
        } elseif ($request->get('filter') === 'all') {
            $filenamePrefix = 'Rekap_Semua_Tiket_IT_PT_S2P';
        } else {
            $filenamePrefix = 'Rekap_Antrian_Tiket_IT_PT_S2P';
        }

        $filename = $filenamePrefix . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($services) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM agar terbaca sempurna di Microsoft Excel Windows
            fputs($file, "\xEF\xBB\xBF");

            // Header Kolom Excel
            fputcsv($file, [
                'No.',
                'Nomor Tiket',
                'Tanggal Masuk',
                'Divisi',
                'Nama Pelapor',
                'Lokasi Ruangan',
                'Jenis Perangkat',
                'Kode Perangkat',
                'Kendala / Keluhan',
                'Prioritas',
                'Status Pengerjaan',
                'Teknisi Penanggung Jawab',
                'Tindakan Perbaikan / Solusi',
                'Sparepart / Material Digunakan',
                'Waktu Penyelesaian',
            ], ';'); // Gunakan delimiter titik koma (;) standar Excel regional Indonesia

            foreach ($services as $index => $item) {
                fputcsv($file, [
                    $index + 1,
                    $item->ticket_number,
                    $item->created_at->format('d/m/Y H:i'),
                    $item->divisi,
                    $item->nama_pelapor,
                    $item->lokasi_ruangan,
                    $item->jenis_perangkat,
                    $item->kode_perangkat ?: '-',
                    $item->keluhan,
                    $item->prioritas,
                    $item->status,
                    $item->teknisi_tim_label,
                    $item->tindakan_perbaikan ?: '-',
                    $item->sparepart ?: '-',
                    $item->tanggal_selesai ? $item->tanggal_selesai->format('d/m/Y H:i') : '-',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function knowledgeBase()
    {
        return view('pages.knowledge-base');
    }
}
