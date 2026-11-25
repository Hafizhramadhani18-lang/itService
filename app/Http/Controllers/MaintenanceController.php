<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\DeviceCategory;
use App\Models\Division;
use App\Models\Location;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceSchedule;
use App\Models\Service;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of preventive maintenance logs with metrics & filters.
     */
    public function index(Request $request)
    {
        // Otomatis sinkronkan master jadwal rutin menjadi log berstatus Terjadwal/Terlambat
        self::syncSchedulesToLogs();

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $currentYear = (int) now()->year;
        $currentMonthNum = (int) now()->month;

        $rawTahun = $request->get('tahun');
        $rawBulan = $request->get('bulan');

        $isAllYears = ($rawTahun === null || $rawTahun === '' || $rawTahun === 'all');
        $selectedTahun = $isAllYears ? 'all' : (int) $rawTahun;

        $isAllMonths = false;
        if ($rawBulan === null || $rawBulan === '' || $rawBulan === 'all') {
            $isAllMonths = true;
            $selectedBulanNum = 0;
        } elseif (is_numeric($rawBulan)) {
            $selectedBulanNum = (int) $rawBulan;
        } else {
            $foundKey = array_search(ucfirst(strtolower(trim($rawBulan))), $monthNames);
            $selectedBulanNum = $foundKey !== false ? $foundKey : 0;
            $isAllMonths = $selectedBulanNum === 0;
        }

        if ($request->filled('periode') && $request->periode !== 'all') {
            $parts = explode('-', $request->periode);
            if (!empty($parts[0]) && is_numeric($parts[0])) {
                $selectedTahun = (int) $parts[0];
                $isAllYears = false;
            }
            if (!empty($parts[1]) && is_numeric($parts[1])) {
                $selectedBulanNum = (int) $parts[1];
                $isAllMonths = false;
            }
        }

        if ($request->get('periode') === 'all' || ($isAllYears && $isAllMonths)) {
            $selectedPeriod = 'all';
        } elseif ($isAllYears && !$isAllMonths) {
            $selectedPeriod = sprintf('all-%02d', $selectedBulanNum);
        } elseif (!$isAllYears && $isAllMonths) {
            $selectedPeriod = sprintf('%04d-all', $selectedTahun);
        } else {
            $selectedPeriod = sprintf('%04d-%02d', $selectedTahun, $selectedBulanNum);
        }

        $isCurrentMonth = !$isAllYears && !$isAllMonths && (int) $selectedTahun === $currentYear && $selectedBulanNum === $currentMonthNum;

        $selectedMinggu = $request->get('minggu', 'all');
        $selectedTim = $request->get('tim', 'all');
        $selectedTeknisi = $request->get('teknisi', 'all');
        $selectedStatus = $request->get('status', 'all');

        $tab = $request->get('tab');
        // Default tab: 'all' untuk admin (sesuai mockup referensi admin), 'my' untuk teknisi
        if (!$tab) {
            $tab = Auth::user()->isAdmin() ? 'all' : 'my';
        }

        // Helper closure untuk filter periode yang konsisten antara tabel & kartu metrik
        $applyPeriodFilter = function ($q) use ($selectedPeriod, $isAllYears, $isAllMonths, $selectedTahun, $selectedBulanNum, $isCurrentMonth) {
            if ($selectedPeriod === 'all') {
                return;
            }
            if ($isAllYears && !$isAllMonths) {
                $q->whereMonth('tanggal_cek', $selectedBulanNum);
                return;
            }
            if (!$isAllYears && $isAllMonths) {
                $q->whereYear('tanggal_cek', (int) $selectedTahun);
                return;
            }
            if ($isCurrentMonth) {
                // Saat melihat bulan berjalan, tetap tampilkan tugas Terlambat dari bulan sebelumnya agar tidak hilang
                $q->where(function ($pq) use ($selectedPeriod) {
                    $pq->where('periode_bulan', $selectedPeriod)
                       ->orWhere(function ($lateQ) {
                           $lateQ->where(function ($stQ) {
                               $stQ->where('status', 'Terlambat')
                                   ->orWhere(function ($pastQ) {
                                       $pastQ->where('status', 'Terjadwal')
                                             ->where('tanggal_cek', '<', now()->startOfDay());
                                   });
                           });
                       });
                });
            } else {
                $q->where('periode_bulan', $selectedPeriod);
            }
        };

        $query = MaintenanceLog::with(['teknisi', 'service'])->latest('tanggal_cek');
        $applyPeriodFilter($query);

        if ($selectedMinggu && $selectedMinggu !== 'all' && is_numeric($selectedMinggu)) {
            $w = (int) $selectedMinggu;
            $startDay = ($w - 1) * 7 + 1;
            $endDay = min(31, $w * 7);
            $query->whereDay('tanggal_cek', '>=', $startDay)->whereDay('tanggal_cek', '<=', $endDay);
        }

        // Tab "Tim saya" / "Pemeliharaan Saya" (log milik teknisi login + tugas terjadwal/terlambat yang belum diambil)
        if ($tab === 'my') {
            $currentUser = Auth::user();
            $query->where(function ($myQ) use ($currentUser) {
                $myQ->ownedByTeknisi($currentUser)
                    ->orWhere(function ($unassignedQ) {
                        $unassignedQ->whereNull('user_id')
                            ->whereIn('status', ['Terjadwal', 'Terlambat']);
                    });
            });
        } elseif ($selectedTeknisi && $selectedTeknisi !== 'all') {
            $techUser = is_numeric($selectedTeknisi) ? User::find($selectedTeknisi) : User::where('name', $selectedTeknisi)->first();
            if ($techUser) {
                $query->ownedByTeknisi($techUser);
            } else {
                $query->where('teknisi_nama', 'like', "%{$selectedTeknisi}%");
            }
        }

        if ($selectedTim && $selectedTim !== 'all') {
            $query->where(function ($tq) use ($selectedTim) {
                $tq->where('tim', 'like', "%{$selectedTim}%")
                   ->orWhereHas('teknisi', fn($u) => $u->where('divisi', 'like', "%{$selectedTim}%"));
            });
        }

        if ($selectedStatus && $selectedStatus !== 'all') {
            $st = strtolower($selectedStatus);
            if ($st === 'terjadwal') {
                $query->where('status', 'Terjadwal')->where('tanggal_cek', '>=', now()->startOfDay());
            } elseif ($st === 'terlambat') {
                $query->where(function($q) {
                    $q->where('status', 'Terlambat')
                      ->orWhere(function($sub) {
                          $sub->where('status', 'Terjadwal')->where('tanggal_cek', '<', now()->startOfDay());
                      });
                });
            } elseif ($st === 'perlu tindak lanjut' || $st === 'tindak lanjut') {
                $query->where('status', 'Selesai')->where(function($sub) {
                    $sub->where('kondisi', '!=', 'Prima')->where('kondisi', '!=', 'Normal')->where('kondisi', '!=', 'Baik');
                });
            } elseif ($st === 'selesai') {
                $query->where('status', 'Selesai');
            }
        }

        if ($request->filled('divisi') && $request->divisi !== 'all') {
            $query->where('divisi', $request->divisi);
        }

        if ($request->filled('kondisi') && $request->kondisi !== 'all') {
            $query->where('kondisi', $request->kondisi);
        }

        if ($request->filled('jenis_perangkat') && $request->jenis_perangkat !== 'all') {
            $query->where('jenis_perangkat', $request->jenis_perangkat);
        }

        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('kode_perangkat', 'like', "%{$keyword}%")
                  ->orWhere('nama_perangkat', 'like', "%{$keyword}%")
                  ->orWhere('lokasi_ruangan', 'like', "%{$keyword}%")
                  ->orWhere('teknisi_nama', 'like', "%{$keyword}%")
                  ->orWhere('kegiatan', 'like', "%{$keyword}%")
                  ->orWhere('tindakan', 'like', "%{$keyword}%")
                  ->orWhere('log_number', 'like', "%{$keyword}%");
            });
        }

        // Filter Frekuensi PM (Sekali, Mingguan, Bulanan, Tahunan, Tim)
        $selectedFrekuensi = strtolower(trim((string) $request->get('frekuensi', 'semua')));
        $applyFreqFilter = function ($q, $freq) {
            if ($freq === 'sekali') {
                $q->where(function ($sub) {
                    $sub->whereHas('jadwal', fn($js) => $js->where('frekuensi', 'like', '%sekali%'))
                        ->orWhere('tindakan', 'like', '%sekali%')
                        ->orWhere('kegiatan', 'like', '%sekali%');
                });
            } elseif ($freq === 'mingguan') {
                $q->where(function ($sub) {
                    $sub->whereHas('jadwal', fn($js) => $js->where('frekuensi', 'like', '%minggu%'))
                        ->orWhere('tindakan', 'like', '%minggu%')
                        ->orWhere('kegiatan', 'like', '%minggu%');
                });
            } elseif ($freq === 'bulanan') {
                $q->where(function ($sub) {
                    $sub->whereHas('jadwal', fn($js) => $js->where('frekuensi', 'like', '%bulan%')->where('frekuensi', 'not like', '%3 bulan%'))
                        ->orWhere(function ($m) {
                            $m->whereDoesntHave('jadwal')
                              ->where('tindakan', 'not like', '%minggu%')
                              ->where('tindakan', 'not like', '%tahun%')
                              ->where('tindakan', 'not like', '%sekali%');
                        });
                });
            } elseif ($freq === 'tahunan') {
                $q->where(function ($sub) {
                    $sub->whereHas('jadwal', fn($js) => $js->where('frekuensi', 'like', '%tahun%'))
                        ->orWhere('tindakan', 'like', '%tahun%')
                        ->orWhere('kegiatan', 'like', '%tahun%');
                });
            } elseif ($freq === 'tim') {
                $q->whereNull('user_id');
            }
        };

        if ($selectedFrekuensi !== 'semua') {
            $applyFreqFilter($query, $selectedFrekuensi);
        }

        $logs = $query->paginate(15)->withQueryString();

        // Query basis statistik aktif (menyesuaikan tab aktif & periode yang sama persis dengan tabel)
        $statsQuery = MaintenanceLog::query();
        $applyPeriodFilter($statsQuery);
        if ($selectedMinggu && $selectedMinggu !== 'all' && is_numeric($selectedMinggu)) {
            $w = (int) $selectedMinggu;
            $startDay = ($w - 1) * 7 + 1;
            $endDay = min(31, $w * 7);
            $statsQuery->whereDay('tanggal_cek', '>=', $startDay)->whereDay('tanggal_cek', '<=', $endDay);
        }

        // Hitung total untuk badge tab
        $allPeriodQuery = clone $statsQuery;
        $totalSemuaTim = (clone $allPeriodQuery)->count();
        $currentUser = Auth::user();
        $totalPMSaya = (clone $allPeriodQuery)->where(function ($myQ) use ($currentUser) {
            $myQ->ownedByTeknisi($currentUser)
                ->orWhere(function ($unassignedQ) {
                    $unassignedQ->whereNull('user_id')
                        ->whereIn('status', ['Terjadwal', 'Terlambat']);
                });
        })->count();

        if ($tab === 'my') {
            $statsQuery->where(function ($myQ) use ($currentUser) {
                $myQ->ownedByTeknisi($currentUser)
                    ->orWhere(function ($unassignedQ) {
                        $unassignedQ->whereNull('user_id')
                            ->whereIn('status', ['Terjadwal', 'Terlambat']);
                    });
            });
        } elseif ($selectedTeknisi && $selectedTeknisi !== 'all') {
            $techUser = is_numeric($selectedTeknisi) ? User::find($selectedTeknisi) : User::where('name', $selectedTeknisi)->first();
            if ($techUser) {
                $statsQuery->ownedByTeknisi($techUser);
            } else {
                $statsQuery->where('teknisi_nama', 'like', "%{$selectedTeknisi}%");
            }
        }

        // Hitung total untuk tombol filter frekuensi PM
        $countFreqSemua = (clone $statsQuery)->count();
        $countFreqSekali = (clone $statsQuery)->tap(fn($q) => $applyFreqFilter($q, 'sekali'))->count();
        $countFreqMingguan = (clone $statsQuery)->tap(fn($q) => $applyFreqFilter($q, 'mingguan'))->count();
        $countFreqBulanan = (clone $statsQuery)->tap(fn($q) => $applyFreqFilter($q, 'bulanan'))->count();
        $countFreqTahunan = (clone $statsQuery)->tap(fn($q) => $applyFreqFilter($q, 'tahunan'))->count();
        $countFreqTim = (clone $statsQuery)->whereNull('user_id')->count();

        // Jika filter frekuensi aktif, sinkronkan juga kartu metrik
        if ($selectedFrekuensi !== 'semua') {
            $applyFreqFilter($statsQuery, $selectedFrekuensi);
        }

        // 4 Kartu Metrik Persis Mockup Referensi Admin:
        // 1. Terjadwal (Minggu ini / bulan ini)
        $countTerjadwal = (clone $statsQuery)->where('status', 'Terjadwal')->where('tanggal_cek', '>=', now()->startOfDay())->count();
        // 2. Selesai (Bulan terpilih)
        $countSelesai = (clone $statsQuery)->where('status', 'Selesai')->count();
        // 3. Terlambat (Perlu segera dikerjakan)
        $countTerlambat = (clone $statsQuery)->where(function($q) {
            $q->where('status', 'Terlambat')
              ->orWhere(function($sub) {
                  $sub->where('status', 'Terjadwal')->where('tanggal_cek', '<', now()->startOfDay());
              });
        })->count();
        // 4. Perlu tindak lanjut (Dari log yang selesai)
        $countPerluTindakLanjut = (clone $statsQuery)->where('status', 'Selesai')->where(function($sub) {
            $sub->where('kondisi', '!=', 'Prima')->where('kondisi', '!=', 'Normal')->where('kondisi', '!=', 'Baik');
        })->count();

        // Backward compatibility for existing view metrics
        $totalAsetDiperiksa = $logs->total();
        $totalPrima = $countSelesai - $countPerluTindakLanjut;
        $totalPerluPerhatian = $countPerluTindakLanjut;
        $totalKritis = $countTerlambat;

        // Master Jadwal Rutin Berkala (Mockup Bottom Section)
        $recurringSchedules = MaintenanceSchedule::where('is_active', true)->orderBy('tanggal_berikutnya')->get();

        // Available periods for dropdown
        $availablePeriods = MaintenanceLog::select('periode_bulan')
            ->distinct()
            ->orderBy('periode_bulan', 'desc')
            ->pluck('periode_bulan')
            ->toArray();

        $currentMonthStr = now()->format('Y-m');
        if (!in_array($currentMonthStr, $availablePeriods)) {
            array_unshift($availablePeriods, $currentMonthStr);
        }

        $divisions = Division::where('is_active', true)->orderBy('nama')->get();
        $categories = DeviceCategory::where('is_active', true)->orderBy('nama')->get();
        $technicians = User::where('role', 'teknisi')
            ->where('name', '!=', 'Tim IT Support')
            ->orderBy('name')
            ->get();
        foreach ($technicians as $tech) {
            $tech->scheduled_pm_count = MaintenanceLog::where('user_id', $tech->id)
                ->whereIn('status', ['Terjadwal', 'Terlambat'])
                ->count();
        }
        $locations = Location::where('is_active', true)->orderBy('nama')->get();

        // Kumpulan data Aset & Lokasi Ruangan yang sudah ada di sistem (untuk dropdown Modal Jadwal)
        $assetMap = [];
        foreach (MaintenanceSchedule::select('nama_aset', 'lokasi_ruangan')->orderBy('nama_aset')->get() as $ms) {
            $name = trim((string) $ms->nama_aset);
            if ($name !== '' && !isset($assetMap[$name])) {
                $assetMap[$name] = trim((string) $ms->lokasi_ruangan);
            }
        }
        foreach (MaintenanceLog::select('nama_perangkat', 'lokasi_ruangan')->orderBy('nama_perangkat')->get() as $ml) {
            $name = trim((string) $ml->nama_perangkat);
            if ($name !== '' && !isset($assetMap[$name])) {
                $assetMap[$name] = trim((string) $ml->lokasi_ruangan);
            }
        }
        foreach ($categories as $cat) {
            $name = trim((string) $cat->nama);
            if ($name !== '' && !isset($assetMap[$name])) {
                $assetMap[$name] = '';
            }
        }
        ksort($assetMap, SORT_NATURAL | SORT_FLAG_CASE);
        $existingAssets = $assetMap;

        $existingLocations = $locations->pluck('nama')
            ->merge(MaintenanceSchedule::whereNotNull('lokasi_ruangan')->pluck('lokasi_ruangan'))
            ->merge(MaintenanceLog::whereNotNull('lokasi_ruangan')->pluck('lokasi_ruangan'))
            ->map(fn ($loc) => trim((string) $loc))
            ->filter()
            ->unique(fn ($loc) => mb_strtolower($loc))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Daftar tahun dinamis dari data PM + rentang 2026 s/d 2030
        $dbYears = MaintenanceLog::whereNotNull('tanggal_cek')
            ->pluck('tanggal_cek')
            ->map(fn ($d) => (int) Carbon::parse($d)->year)
            ->merge(
                MaintenanceSchedule::whereNotNull('tanggal_berikutnya')
                    ->pluck('tanggal_berikutnya')
                    ->map(fn ($d) => (int) Carbon::parse($d)->year)
            )
            ->merge(range(2026, 2030))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $availableYears = $dbYears;
        $selectedBulanName = $isAllMonths ? 'Semua bulan' : ($monthNames[$selectedBulanNum] ?? $monthNames[$currentMonthNum]);

        // Jadwal PM yang ditugaskan khusus untuk teknisi yang sedang login (porsi tugas teknisi)
        $myUpcomingSchedules = collect();
        if ($currentUser && $currentUser->isTeknisi()) {
            $upcomingQ = MaintenanceLog::where(function ($q) use ($currentUser) {
                    $q->ownedByTeknisi($currentUser)
                      ->orWhere(function ($unassigned) use ($currentUser) {
                          $unassigned->whereNull('user_id')
                                     ->where(function ($uq) use ($currentUser) {
                                         $uq->where('tim', 'like', "%{$currentUser->tim_label}%")
                                            ->orWhere('tim', 'like', "%{$currentUser->divisi}%");
                                     });
                      });
                })
                ->whereIn('status', ['Terjadwal', 'Terlambat'])
                ->orderBy('tanggal_cek', 'asc');

            if ($selectedFrekuensi !== 'semua') {
                $applyFreqFilter($upcomingQ, $selectedFrekuensi);
            }

            $myUpcomingSchedules = $upcomingQ->take(6)->get();
        }

        return view('maintenance.index', compact(
            'logs',
            'tab',
            'totalSemuaTim',
            'totalPMSaya',
            'selectedFrekuensi',
            'countFreqSemua',
            'countFreqSekali',
            'countFreqMingguan',
            'countFreqBulanan',
            'countFreqTahunan',
            'countFreqTim',
            'myUpcomingSchedules',
            'countTerjadwal',
            'countSelesai',
            'countTerlambat',
            'countPerluTindakLanjut',
            'totalAsetDiperiksa',
            'totalPrima',
            'totalPerluPerhatian',
            'totalKritis',
            'selectedPeriod',
            'availablePeriods',
            'divisions',
            'categories',
            'technicians',
            'locations',
            'existingAssets',
            'existingLocations',
            'recurringSchedules',
            'availableYears',
            'monthNames',
            'selectedTahun',
            'selectedBulanNum',
            'selectedBulanName',
            'selectedMinggu',
            'selectedTim',
            'selectedTeknisi',
            'selectedStatus'
        ));
    }

    /**
     * Show dedicated page for Admin to schedule a new PM (+ Jadwalkan).
     * Data dropdown Aset & Lokasi bersumber langsung dari Master Jadwal Rutin (+ Tambah jadwal).
     */
    public function createSchedule(Request $request)
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya Admin (Koordinator IT) yang berhak mengatur penjadwalan pemeliharaan.');

        $categories = DeviceCategory::where('is_active', true)->orderBy('nama')->get();
        $technicians = User::where('role', 'teknisi')
            ->where('name', '!=', 'Tim IT Support')
            ->orderBy('name')
            ->get();
        foreach ($technicians as $tech) {
            $tech->scheduled_pm_count = MaintenanceLog::where('user_id', $tech->id)
                ->whereIn('status', ['Terjadwal', 'Terlambat'])
                ->count();
        }

        $defaultActivitiesByKeyword = function (string $name, string $jenis = ''): array {
            $n = strtolower($name . ' ' . $jenis);
            if (str_contains($n, 'cctv') || str_contains($n, 'nvr')) {
                return [
                    'Cek hard disk dan kapasitas storage rekaman CCTV',
                    'Cek suhu dan kipas',
                ];
            }
            if (str_contains($n, 'rack') || str_contains($n, 'ups')) {
                return [
                    'Cek suhu ruangan',
                    'Bersihkan debu filter rack',
                    'Uji UPS',
                ];
            }
            if (str_contains($n, 'switch') || str_contains($n, 'scada') || str_contains($n, 'jaringan')) {
                return [
                    'Cek port, konektor RJ-45 & indikator LED link',
                    'Bersihkan debu industri pada chassis & fan rack',
                ];
            }
            if (str_contains($n, 'printer')) {
                return [
                    'Cek rol kertas & bersihkan pickup roller dari debu',
                    'Cek level toner / tinta & hasil test page',
                ];
            }
            if (str_contains($n, 'database') || str_contains($n, 'server')) {
                return [
                    'Verifikasi backup database & kapasitas storage',
                    'Cek log error sistem & suhu hardware server',
                ];
            }
            return [
                'Bersihkan heatsink, fan & debu batu bara di casing',
                'Cek suhu prosesor, storage & pembaruan antivirus',
            ];
        };

        $mapFreqToPill = function (?string $f): string {
            $fl = strtolower(trim((string) $f));
            if ($fl === 'sekali') return 'Sekali';
            if (str_contains($fl, 'minggu') && !preg_match('/[2-9]/', $fl)) return 'Mingguan';
            if ($fl === 'setiap bulan' || $fl === 'bulanan') return 'Bulanan';
            if (str_contains($fl, '3 bulan') || str_contains($fl, 'tiga bulan')) return 'Tiga bulanan';
            return 'Bulanan';
        };

        $assetCatalog = [];
        $addAsset = function (
            string $nama,
            string $kode,
            string $lokasi,
            string $jenis,
            array $kegiatan = [],
            string $frekuensi = 'Bulanan',
            string $jam = '09.00',
            string $tim = 'Tim IT Lapangan',
            ?int $teknisiId = null,
            string $catatan = ''
        ) use (&$assetCatalog, $defaultActivitiesByKeyword) {
            $key = mb_strtolower(trim($nama)) . '|' . mb_strtolower(trim($lokasi));
            if (trim($nama) === '' || isset($assetCatalog[$key])) {
                return;
            }
            $slug = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $nama), 0, 6)) ?: 'ASET';
            $assetCatalog[$key] = [
                'nama' => trim($nama),
                'kode' => trim($kode) !== '' ? strtoupper(trim($kode)) : "AST-{$slug}-01",
                'lokasi' => trim($lokasi),
                'jenis' => trim($jenis) !== '' ? trim($jenis) : 'Server / UPS',
                'kegiatan' => !empty($kegiatan) ? $kegiatan : $defaultActivitiesByKeyword($nama, $jenis),
                'frekuensi' => $frekuensi,
                'jam' => $jam,
                'tim' => $tim,
                'teknisi_id' => $teknisiId,
                'catatan' => $catatan,
            ];
        };

        // Sumber UTAMA: Data Master Jadwal Rutin yang dibuat lewat "+ Tambah jadwal"
        $masterSchedules = MaintenanceSchedule::where('is_active', true)->orderBy('nama_aset')->get();
        foreach ($masterSchedules as $ms) {
            $schItems = [];
            if (is_array($ms->checklist)) {
                foreach ($ms->checklist as $chk) {
                    if (is_array($chk) && !empty($chk['item'])) {
                        $schItems[] = trim($chk['item']);
                    } elseif (is_string($chk) && trim($chk) !== '') {
                        $schItems[] = trim($chk);
                    }
                }
            } elseif (!empty($ms->kegiatan)) {
                $schItems = array_values(array_filter(array_map('trim', explode(',', $ms->kegiatan))));
            }

            $addAsset(
                (string) $ms->nama_aset,
                (string) $ms->kode_perangkat,
                (string) $ms->lokasi_ruangan,
                'Server / UPS',
                $schItems,
                $mapFreqToPill($ms->frekuensi),
                $ms->jam_mulai ? str_replace(':', '.', substr($ms->jam_mulai, 0, 5)) : '09.00',
                $ms->tim ?: 'Tim IT Lapangan',
                $ms->teknisi_id ? (int) $ms->teknisi_id : null,
                (string) ($ms->catatan_teknisi ?? '')
            );
        }

        // Jika membuka dari tombol "Jadwalkan PM Tindak Lanjut" di Detail Log PM, pastikan aset log tsb ikut masuk
        $prefillLog = $request->filled('from_log') ? MaintenanceLog::find($request->get('from_log')) : null;
        if ($prefillLog) {
            $logItems = [];
            if (is_array($prefillLog->checklist)) {
                foreach ($prefillLog->checklist as $chk) {
                    if (is_array($chk) && !empty($chk['item'])) {
                        $logItems[] = trim($chk['item']);
                    }
                }
            }
            $addAsset(
                (string) $prefillLog->nama_perangkat,
                (string) $prefillLog->kode_perangkat,
                (string) $prefillLog->lokasi_ruangan,
                (string) $prefillLog->jenis_perangkat,
                $logItems,
                'Sekali',
                '09.00',
                $prefillLog->tim_label ?: 'Tim IT Lapangan',
                $prefillLog->user_id ? (int) $prefillLog->user_id : null,
                (string) ($prefillLog->catatan_rekomendasi ?: $prefillLog->tindakan)
            );
        }

        $assetList = collect(array_values($assetCatalog))->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)->values();

        // Daftar lokasi di halaman "+ Jadwalkan" juga bersumber dari Master Jadwal Rutin (+ lokasi prefillLog jika ada)
        $existingLocations = $masterSchedules->pluck('lokasi_ruangan')
            ->when($prefillLog && $prefillLog->lokasi_ruangan, fn ($col) => $col->push($prefillLog->lokasi_ruangan))
            ->map(fn ($loc) => trim((string) $loc))
            ->filter()
            ->unique(fn ($loc) => mb_strtolower($loc))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('maintenance.schedule-create', compact(
            'assetList',
            'existingLocations',
            'technicians',
            'categories',
            'prefillLog'
        ));
    }

    /**
     * Store newly scheduled PM task by Admin (Halaman / Modal + Jadwalkan).
     */
    public function storeScheduledTask(Request $request)
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya Admin (Koordinator IT) yang berhak mengatur penjadwalan pemeliharaan.');

        // Bangun daftar kegiatan / checklist jika berasal dari halaman Jadwalkan Pemeliharaan
        $rawActivities = $request->input('kegiatan_list', []);
        $checklist = [];
        $activeNames = [];
        if (is_array($rawActivities)) {
            foreach ($rawActivities as $item) {
                if (is_array($item) && !empty($item['item'])) {
                    $label = trim((string) $item['item']);
                    $checked = !empty($item['checked']);
                    if ($checked && $label !== '') {
                        $activeNames[] = $label;
                        $checklist[] = ['item' => $label, 'checked' => false];
                    }
                } elseif (is_string($item) && trim($item) !== '') {
                    $label = trim($item);
                    $activeNames[] = $label;
                    $checklist[] = ['item' => $label, 'checked' => false];
                }
            }
        }

        if (!$request->filled('kegiatan') && !empty($activeNames)) {
            $request->merge(['kegiatan' => implode(', ', $activeNames)]);
        }

        $request->validate([
            'tanggal_cek' => 'required|date',
            'nama_perangkat' => 'required|string|max:150',
            'lokasi_ruangan' => 'required|string|max:150',
            'kegiatan' => 'required|string|max:2000',
            'tim' => 'nullable|string|max:100',
            'teknisi_id' => 'nullable',
            'kode_perangkat' => 'nullable|string|max:100',
            'jenis_perangkat' => 'nullable|string|max:100',
            'divisi' => 'nullable|string|max:100',
            'jam_mulai' => 'nullable|string|max:10',
            'frekuensi' => 'nullable|string|max:50',
            'catatan_teknisi' => 'nullable|string|max:1000',
        ]);

        $date = Carbon::parse($request->tanggal_cek);
        $techUser = $request->teknisi_id ? User::find($request->teknisi_id) : null;
        $logNumber = MaintenanceLog::generateLogNumber($date);
        $timLabel = $request->tim ?: ($techUser?->divisi ?? 'Tim IT Lapangan');
        $jamMulai = $request->filled('jam_mulai') ? str_replace('.', ':', trim($request->jam_mulai)) : '09:00';

        // Jika checklist belum terbentuk dari kegiatan_list, pecah dari string kegiatan
        if (empty($checklist) && $request->filled('kegiatan')) {
            $checklist[] = ['item' => trim($request->kegiatan), 'checked' => false];
        }

        $catatanTeknisi = trim((string) $request->input('catatan_teknisi', ''));
        $tindakanText = $catatanTeknisi !== ''
            ? $catatanTeknisi
            : 'Jadwal pemeliharaan baru dibuat oleh ' . Auth::user()->name;
        $rawKodeInput = strtoupper(trim((string) $request->kode_perangkat));
        $kodePerangkat = ($rawKodeInput !== '' && $rawKodeInput !== 'ASET-BARU')
            ? $rawKodeInput
            : Asset::generateKode((string) $request->nama_perangkat, (string) $request->lokasi_ruangan, (string) ($request->jenis_perangkat ?: 'Server / UPS'));

        // Sinkronkan aset & lokasi baru (jika diketik manual) ke Data Master Admin
        Asset::syncFromInput(
            $request->nama_perangkat,
            $kodePerangkat,
            $request->lokasi_ruangan,
            $request->jenis_perangkat ?: 'Server / UPS'
        );

        // Jika frekuensi berulang (selain Sekali), otomatis simpan juga ke Master Jadwal Rutin
        $jadwalId = null;
        $frekuensiInput = trim((string) $request->input('frekuensi', ''));
        if ($frekuensiInput !== '' && strtolower($frekuensiInput) !== 'sekali') {
            $customInterval = max(1, (int) $request->input('custom_interval', 1));
            $customUnit = $request->input('custom_unit', 'bulan');

            $frekuensiLabel = match ($frekuensiInput) {
                'Mingguan' => 'Setiap minggu',
                'Bulanan' => 'Setiap bulan',
                'Tiga bulanan' => 'Setiap 3 bulan',
                'Tahunan' => 'Setiap tahun',
                'Atur sendiri' => "Setiap {$customInterval} {$customUnit}",
                default => $frekuensiInput,
            };

            $nextScheduleDate = match ($frekuensiInput) {
                'Mingguan' => $date->copy()->addWeek(),
                'Bulanan' => $date->copy()->addMonth(),
                'Tiga bulanan' => $date->copy()->addMonths(3),
                'Tahunan' => $date->copy()->addYear(),
                'Atur sendiri' => match ($customUnit) {
                    'hari' => $date->copy()->addDays($customInterval),
                    'minggu' => $date->copy()->addWeeks($customInterval),
                    'tahun' => $date->copy()->addYears($customInterval),
                    default => $date->copy()->addMonths($customInterval),
                },
                default => $date->copy()->addMonth(),
            };

            $schedule = MaintenanceSchedule::create([
                'nama_aset' => $request->nama_perangkat,
                'kode_perangkat' => $kodePerangkat,
                'lokasi_ruangan' => $request->lokasi_ruangan,
                'frekuensi' => $frekuensiLabel,
                'tanggal_berikutnya' => $nextScheduleDate->toDateString(),
                'jam_mulai' => $jamMulai,
                'tim' => $timLabel,
                'teknisi_id' => $techUser?->id,
                'teknisi_nama' => $techUser?->name,
                'kegiatan' => $request->kegiatan,
                'checklist' => $checklist,
                'catatan_teknisi' => $catatanTeknisi ?: null,
                'kirim_pengingat' => $request->boolean('kirim_pengingat', true),
                'is_active' => true,
            ]);
            $jadwalId = $schedule->id;
        }

        MaintenanceLog::create([
            'log_number' => $logNumber,
            'tanggal_cek' => $date->toDateString(),
            'jam_mulai' => $jamMulai,
            'periode_bulan' => $date->format('Y-m'),
            'nama_perangkat' => $request->nama_perangkat,
            'lokasi_ruangan' => $request->lokasi_ruangan,
            'kode_perangkat' => $kodePerangkat,
            'jenis_perangkat' => $request->jenis_perangkat ?: 'Komputer PC',
            'divisi' => $request->divisi ?: 'Operasional PLTU',
            'user_id' => $techUser?->id,
            'teknisi_nama' => $techUser ? $techUser->name : 'Belum ada',
            'tim' => $timLabel,
            'kegiatan' => $request->kegiatan,
            'jenis_pemeliharaan' => 'Rutin',
            'checklist' => $checklist,
            'status' => $date->isPast() && !$date->isToday() ? 'Terlambat' : 'Terjadwal',
            'kondisi' => 'Prima',
            'tindakan' => $tindakanText,
            'catatan_rekomendasi' => $catatanTeknisi ?: null,
            'status_tindak_lanjut' => 'Selesai',
            'jadwal_id' => $jadwalId,
        ]);

        $assignedNote = $techUser ? " dan ditugaskan ke {$techUser->name}" : " untuk semua teknisi {$timLabel}";
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $redirectParams = ($date->year !== (int) now()->year || $date->month !== (int) now()->month)
            ? ['tahun' => $date->year, 'bulan' => $monthNames[$date->month]]
            : [];

        return redirect()->route('maintenance.index', $redirectParams)->with('success', "Pemeliharaan untuk '{$request->nama_perangkat}' berhasil dijadwalkan{$assignedNote}!");
    }

    /**
     * Reschedule maintenance task (Modal Jadwal Ulang).
     */
    public function reschedule(Request $request, MaintenanceLog $maintenanceLog)
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya Admin (Koordinator IT) yang berhak mengubah jadwal pemeliharaan.');

        $request->validate([
            'tanggal_cek' => 'required|date',
            'jam_mulai' => 'nullable|string|max:10',
            'teknisi_id' => 'nullable',
            'alasan' => 'nullable|string|max:1000',
        ]);

        $oldDateStr = $maintenanceLog->tanggal_cek ? $maintenanceLog->tanggal_cek->format('d/m/Y') : '-';
        $newDate = Carbon::parse($request->tanggal_cek);
        $status = $newDate->isPast() && !$newDate->isToday() ? 'Terlambat' : 'Terjadwal';

        $updateData = [
            'tanggal_cek' => $newDate->toDateString(),
            'periode_bulan' => $newDate->format('Y-m'),
            'status' => $status,
        ];

        if ($request->filled('jam_mulai')) {
            $updateData['jam_mulai'] = str_replace('.', ':', trim($request->jam_mulai));
        }

        if ($request->has('teknisi_id') && $request->teknisi_id !== '__keep__') {
            $techUser = $request->teknisi_id ? User::find($request->teknisi_id) : null;
            $updateData['user_id'] = $techUser?->id;
            $updateData['teknisi_nama'] = $techUser ? $techUser->name : 'Belum ada';
        }

        $alasan = trim((string) $request->input('alasan', ''));
        if ($alasan !== '') {
            $noteLine = "Jadwal ulang ({$oldDateStr} → {$newDate->format('d/m/Y')}): {$alasan}";
            $existingNote = trim((string) $maintenanceLog->catatan_rekomendasi);
            $updateData['catatan_rekomendasi'] = $existingNote !== '' ? "{$existingNote}\n{$noteLine}" : $noteLine;
        }

        $maintenanceLog->update($updateData);

        $notifySuffix = $request->boolean('notify_teknisi') && $maintenanceLog->user_id
            ? " Notifikasi telah dikirim ke {$maintenanceLog->teknisi_nama}."
            : '';

        return redirect()->back()->with('success', "Jadwal pemeliharaan '{$maintenanceLog->nama_perangkat}' berhasil diperbarui ke tanggal {$newDate->format('d/m/Y')}.{$notifySuffix}");
    }

    /**
     * Buat tiket perbaikan tindak lanjut langsung dari halaman Detail Log Pemeliharaan.
     */
    public function createFollowUpTicket(Request $request, MaintenanceLog $maintenanceLog)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isTeknisi(), 403, 'Anda tidak memiliki akses untuk membuat tiket tindak lanjut.');

        if ($maintenanceLog->service_id && $existing = Service::find($maintenanceLog->service_id)) {
            return redirect()->route('maintenance.show', $maintenanceLog)
                ->with('success', "Log ini sudah memiliki tiket tindak lanjut {$existing->ticket_number}.");
        }

        $prefix = 'TKT-' . now()->format('ymd') . '-';
        $lastTicket = Service::where('ticket_number', 'like', $prefix . '%')->latest('id')->first();
        $seq = $lastTicket ? ((int) substr($lastTicket->ticket_number, -3) + 1) : 1;
        $newTicketNumber = $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);

        $prioritas = $maintenanceLog->isKritis() ? 'Mendesak' : 'Tinggi';
        $keluhanParts = [
            "Tindak lanjut temuan Pemeliharaan Rutin (PM) log [{$maintenanceLog->log_number}] pada [{$maintenanceLog->kode_perangkat} - {$maintenanceLog->nama_perangkat}]:",
            trim((string) $maintenanceLog->tindakan),
        ];
        if (!empty($maintenanceLog->catatan_rekomendasi)) {
            $keluhanParts[] = "Rekomendasi Tindak Lanjut: " . trim((string) $maintenanceLog->catatan_rekomendasi);
        }

        $ticket = Service::create([
            'ticket_number' => $newTicketNumber,
            'judul_kendala' => 'Tindak Lanjut PM: ' . $maintenanceLog->nama_perangkat,
            'user_id' => Auth::id(),
            'nama_pelapor' => 'Tindak Lanjut PM (' . Auth::user()->name . ')',
            'divisi' => $maintenanceLog->divisi ?: 'Operasional PLTU',
            'lokasi_ruangan' => $maintenanceLog->lokasi_ruangan ?: 'PLTU Karangkandri',
            'jenis_perangkat' => $maintenanceLog->jenis_perangkat ?: 'Komputer PC',
            'kode_perangkat' => $maintenanceLog->kode_perangkat,
            'keluhan' => implode("\n\n", array_filter($keluhanParts)),
            'prioritas' => $prioritas,
            'status' => 'Baru',
            'teknisi_id' => null,
            'teknisi_nama' => null,
            'is_read' => false,
        ]);

        $maintenanceLog->update([
            'service_id' => $ticket->id,
            'status_tindak_lanjut' => 'Eskalasi Tiket',
        ]);

        return redirect()->route('maintenance.show', $maintenanceLog)
            ->with('success', "Tiket perbaikan {$ticket->ticket_number} berhasil dibuat dan masuk ke Penugasan / Antrian Tiket!");
    }

    /**
     * Build shared asset catalog & official locations for Schedule pages (+ Tambah jadwal).
     * Tersinkronisasi langsung dengan Data Master Admin (Asset IT & Area Lokasi aktif).
     */
    private function buildScheduleFormData(?MaintenanceSchedule $editingSchedule = null): array
    {
        $locations = Location::where('is_active', true)->orderBy('nama')->get();
        $technicians = User::where('role', 'teknisi')
            ->where('name', '!=', 'Tim IT Support')
            ->orderBy('name')
            ->get();

        $defaultActivitiesByKeyword = function (string $name, string $jenis = ''): array {
            $n = strtolower($name . ' ' . $jenis);
            if (str_contains($n, 'cctv') || str_contains($n, 'nvr')) {
                return [
                    'Cek hard disk dan kapasitas storage rekaman CCTV',
                    'Cek suhu dan kipas',
                ];
            }
            if (str_contains($n, 'rack') || str_contains($n, 'ups')) {
                return [
                    'Cek suhu ruangan',
                    'Bersihkan debu filter rack',
                    'Uji UPS',
                ];
            }
            if (str_contains($n, 'switch') || str_contains($n, 'scada') || str_contains($n, 'jaringan') || str_contains($n, 'wifi') || str_contains($n, 'lan') || str_contains($n, 'access point')) {
                return [
                    'Cek port, konektor RJ-45 & indikator LED link',
                    'Bersihkan debu industri pada chassis & fan rack',
                ];
            }
            if (str_contains($n, 'printer')) {
                return [
                    'Cek rol kertas & bersihkan pickup roller dari debu',
                    'Cek level toner / tinta & hasil test page',
                ];
            }
            if (str_contains($n, 'database') || str_contains($n, 'server')) {
                return [
                    'Verifikasi backup database & kapasitas storage',
                    'Cek log error sistem & suhu hardware server',
                ];
            }
            return [
                'Bersihkan heatsink, fan & debu batu bara di casing',
                'Cek suhu prosesor, storage & pembaruan antivirus',
            ];
        };

        // Siapkan lookup checklist terakhir dari Master Jadwal Rutin & Log Pemeliharaan berdasarkan kode/nama aset
        $checklistLookup = [];
        $extractChecklist = function ($rawChecklist, ?string $rawKegiatan = null): array {
            $items = [];
            if (is_array($rawChecklist)) {
                foreach ($rawChecklist as $chk) {
                    if (is_array($chk) && !empty($chk['item'])) {
                        $items[] = trim($chk['item']);
                    } elseif (is_string($chk) && trim($chk) !== '') {
                        $items[] = trim($chk);
                    }
                }
            } elseif (!empty($rawKegiatan)) {
                $items = array_values(array_filter(array_map('trim', explode(',', $rawKegiatan))));
            }
            return $items;
        };

        foreach (MaintenanceSchedule::orderByDesc('id')->get() as $ms) {
            $items = $extractChecklist($ms->checklist, $ms->kegiatan);
            if (!empty($items)) {
                $codeKey = strtoupper(trim((string) $ms->kode_perangkat));
                $nameKey = mb_strtolower(trim((string) $ms->nama_aset));
                if ($codeKey !== '' && !isset($checklistLookup['code:' . $codeKey])) {
                    $checklistLookup['code:' . $codeKey] = $items;
                }
                if ($nameKey !== '' && !isset($checklistLookup['name:' . $nameKey])) {
                    $checklistLookup['name:' . $nameKey] = $items;
                }
            }
        }
        foreach (MaintenanceLog::orderByDesc('id')->get() as $ml) {
            $items = $extractChecklist($ml->checklist, null);
            if (!empty($items)) {
                $codeKey = strtoupper(trim((string) $ml->kode_perangkat));
                $nameKey = mb_strtolower(trim((string) $ml->nama_perangkat));
                if ($codeKey !== '' && !isset($checklistLookup['code:' . $codeKey])) {
                    $checklistLookup['code:' . $codeKey] = $items;
                }
                if ($nameKey !== '' && !isset($checklistLookup['name:' . $nameKey])) {
                    $checklistLookup['name:' . $nameKey] = $items;
                }
            }
        }

        $assetCatalog = [];
        $seenCodes = [];
        $addAsset = function (string $nama, string $kode, string $lokasi, string $jenis, array $kegiatan = []) use (&$assetCatalog, &$seenCodes, $checklistLookup, $defaultActivitiesByKeyword) {
            $cleanNama = trim($nama);
            $cleanKode = strtoupper(trim($kode));
            $key = mb_strtolower($cleanNama);
            if ($key === '' || isset($assetCatalog[$key])) {
                return;
            }
            if ($cleanKode !== '' && isset($seenCodes[$cleanKode])) {
                return;
            }

            $slug = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $cleanNama), 0, 6)) ?: 'ASET';
            $finalKode = $cleanKode !== '' ? $cleanKode : "AST-{$slug}-01";
            $resolvedKegiatan = !empty($kegiatan)
                ? $kegiatan
                : ($checklistLookup['code:' . $finalKode]
                    ?? $checklistLookup['name:' . $key]
                    ?? $defaultActivitiesByKeyword($cleanNama, $jenis));

            $assetCatalog[$key] = [
                'nama' => $cleanNama,
                'kode' => $finalKode,
                'lokasi' => trim($lokasi),
                'jenis' => trim($jenis) !== '' ? trim($jenis) : 'Server / UPS',
                'kegiatan' => $resolvedKegiatan,
            ];
            if ($cleanKode !== '') {
                $seenCodes[$cleanKode] = true;
            }
        };

        // 1. Jika sedang edit jadwal rutin, pastikan aset yang sedang diedit selalu masuk pertama
        if ($editingSchedule) {
            $schChecklist = $extractChecklist($editingSchedule->checklist, $editingSchedule->kegiatan);
            $addAsset(
                (string) $editingSchedule->nama_aset,
                (string) $editingSchedule->kode_perangkat,
                (string) $editingSchedule->lokasi_ruangan,
                'Server / UPS',
                $schChecklist
            );
        }

        // 2. Sumber UTAMA: Seluruh Data Master Aset IT aktif dari Admin Data Master
        foreach (Asset::where('is_active', true)->orderBy('nama')->get() as $masterAsset) {
            $addAsset(
                (string) $masterAsset->nama,
                (string) $masterAsset->kode_perangkat,
                (string) ($masterAsset->lokasi_ruangan ?? ''),
                (string) ($masterAsset->jenis_perangkat ?: 'Perangkat IT')
            );
        }

        $assetList = collect(array_values($assetCatalog))->sortBy(function ($item) {
            if (stripos($item['nama'], 'Rack server') !== false) {
                return '0_' . $item['nama'];
            }
            return '1_' . $item['nama'];
        }, SORT_NATURAL | SORT_FLAG_CASE)->values();

        // Sumber UTAMA Lokasi: Seluruh Data Master Area Lokasi aktif dari Admin Data Master (+ lokasi jadwal yang sedang diedit jika nonaktif)
        $existingLocations = $locations->pluck('nama')
            ->when($editingSchedule && $editingSchedule->lokasi_ruangan, fn ($col) => $col->push($editingSchedule->lokasi_ruangan))
            ->map(fn ($loc) => trim((string) $loc))
            ->filter()
            ->unique(fn ($loc) => mb_strtolower($loc))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return compact('assetList', 'existingLocations', 'technicians');
    }

    /**
     * Show dedicated full-page form to create a new Master Recurring Schedule (+ Tambah jadwal).
     */
    public function createRecurringSchedule()
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya Admin (Koordinator IT) yang berhak mengatur jadwal rutin.');

        $data = $this->buildScheduleFormData(null);
        $schedule = null;
        $scheduleHistory = collect();

        return view('maintenance.schedule-form', array_merge($data, compact('schedule', 'scheduleHistory')));
    }

    /**
     * Show dedicated full-page form to edit an existing Master Recurring Schedule (Ubah).
     */
    public function editRecurringSchedule(MaintenanceSchedule $maintenanceSchedule)
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya Admin (Koordinator IT) yang berhak mengubah jadwal rutin.');

        $data = $this->buildScheduleFormData($maintenanceSchedule);
        $schedule = $maintenanceSchedule;

        $scheduleHistory = MaintenanceLog::where(function ($q) use ($maintenanceSchedule) {
            $q->where('jadwal_id', $maintenanceSchedule->id)
              ->orWhere('nama_perangkat', $maintenanceSchedule->nama_aset);
        })
            ->where('status', 'Selesai')
            ->latest('tanggal_cek')
            ->take(5)
            ->get();

        return view('maintenance.schedule-form', array_merge($data, compact('schedule', 'scheduleHistory')));
    }

    /**
     * Store new master recurring schedule (+ Tambah Jadwal).
     */
    public function storeRecurringSchedule(Request $request)
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya Admin (Koordinator IT) yang berhak mengatur jadwal rutin.');

        $request->validate([
            'nama_aset' => 'required|string|max:150',
            'lokasi_ruangan' => 'required|string|max:150',
            'frekuensi' => 'required|string|max:50',
            'tanggal_berikutnya' => 'required|date',
            'tim' => 'nullable|string|max:100',
            'teknisi_id' => 'nullable',
            'jam_mulai' => 'nullable|string|max:10',
            'kode_perangkat' => 'nullable|string|max:100',
            'catatan_teknisi' => 'nullable|string|max:1000',
        ]);

        // Normalisasi frekuensi pill UI -> label standar
        $rawFreq = trim((string) $request->frekuensi);
        $customInterval = max(1, (int) $request->input('custom_interval', 1));
        $customUnit = $request->input('custom_unit', 'bulan');
        $frekuensiLabel = match ($rawFreq) {
            'Sekali' => 'Sekali',
            'Mingguan' => 'Setiap minggu',
            'Bulanan' => 'Setiap bulan',
            'Tiga bulanan' => 'Setiap 3 bulan',
            'Tahunan' => 'Setiap tahun',
            'Atur sendiri' => "Setiap {$customInterval} {$customUnit}",
            default => $rawFreq,
        };

        // Kumpulkan checklist kegiatan
        $checklist = [];
        $activeNames = [];
        foreach ((array) $request->input('kegiatan_list', []) as $item) {
            if (is_array($item) && !empty($item['item']) && !empty($item['checked'])) {
                $lbl = trim((string) $item['item']);
                if ($lbl !== '') {
                    $activeNames[] = $lbl;
                    $checklist[] = ['item' => $lbl, 'checked' => false];
                }
            }
        }
        $kegiatanSummary = !empty($activeNames)
            ? implode(', ', $activeNames)
            : "Pemeliharaan rutin ({$frekuensiLabel}) {$request->nama_aset}";

        $tech = $request->teknisi_id ? User::find($request->teknisi_id) : null;
        $jamMulai = $request->filled('jam_mulai') ? str_replace('.', ':', trim($request->jam_mulai)) : '09:00';
        $rawKodeSch = strtoupper(trim((string) $request->kode_perangkat));
        $kodeAset = $rawKodeSch !== ''
            ? $rawKodeSch
            : Asset::generateKode((string) $request->nama_aset, (string) $request->lokasi_ruangan, 'Server / UPS');

        // Sinkronkan aset & lokasi baru (jika diketik manual) ke Data Master Admin
        Asset::syncFromInput($request->nama_aset, $kodeAset, $request->lokasi_ruangan, 'Server / UPS');

        $schedule = MaintenanceSchedule::create([
            'nama_aset' => $request->nama_aset,
            'lokasi_ruangan' => $request->lokasi_ruangan,
            'kode_perangkat' => $kodeAset,
            'frekuensi' => $frekuensiLabel,
            'tanggal_berikutnya' => $request->tanggal_berikutnya,
            'jam_mulai' => $jamMulai,
            'tim' => $request->tim ?: 'Tim IT Lapangan',
            'teknisi_id' => $tech?->id,
            'teknisi_nama' => $tech?->name,
            'kegiatan' => $kegiatanSummary,
            'checklist' => !empty($checklist) ? $checklist : null,
            'catatan_teknisi' => trim((string) $request->input('catatan_teknisi', '')) ?: null,
            'kirim_pengingat' => $request->boolean('kirim_pengingat', true),
            'is_active' => true,
        ]);

        // Otomatis sinkronkan sebagai tugas Terjadwal ke Log Pemeliharaan teknisi
        $nextDate = Carbon::parse($request->tanggal_berikutnya);
        $alreadyExists = MaintenanceLog::where('nama_perangkat', $request->nama_aset)
            ->whereDate('tanggal_cek', $nextDate->toDateString())
            ->whereIn('status', ['Terjadwal', 'Terlambat'])
            ->exists();

        if (!$alreadyExists) {
            MaintenanceLog::create([
                'log_number' => MaintenanceLog::generateLogNumber($nextDate),
                'tanggal_cek' => $nextDate->toDateString(),
                'jam_mulai' => $jamMulai,
                'periode_bulan' => $nextDate->format('Y-m'),
                'nama_perangkat' => $request->nama_aset,
                'lokasi_ruangan' => $request->lokasi_ruangan,
                'kode_perangkat' => $kodeAset,
                'jenis_perangkat' => 'Server / UPS',
                'divisi' => 'Operasional PLTU',
                'user_id' => $tech?->id,
                'teknisi_nama' => $tech ? $tech->name : 'Belum ada',
                'tim' => $request->tim ?: 'Tim IT Lapangan',
                'kegiatan' => $kegiatanSummary,
                'jenis_pemeliharaan' => 'Rutin',
                'checklist' => !empty($checklist) ? $checklist : null,
                'status' => $nextDate->isPast() && !$nextDate->isToday() ? 'Terlambat' : 'Terjadwal',
                'kondisi' => 'Prima',
                'tindakan' => $schedule->catatan_teknisi ?: ('Dijadwalkan otomatis dari Master Jadwal Rutin oleh ' . Auth::user()->name),
                'catatan_rekomendasi' => $schedule->catatan_teknisi,
                'status_tindak_lanjut' => 'Selesai',
                'jadwal_id' => $schedule->id,
            ]);
        }

        return redirect()->route('maintenance.index')->with('success', "Master jadwal rutin '{$request->nama_aset}' berhasil ditambahkan dan disinkronkan ke Log Pemeliharaan teknisi!");
    }

    /**
     * Update master recurring schedule (Ubah).
     */
    public function updateRecurringSchedule(Request $request, MaintenanceSchedule $maintenanceSchedule)
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya Admin (Koordinator IT) yang berhak mengubah jadwal rutin.');

        $request->validate([
            'nama_aset' => 'required|string|max:150',
            'lokasi_ruangan' => 'required|string|max:150',
            'frekuensi' => 'required|string|max:50',
            'tanggal_berikutnya' => 'required|date',
            'tim' => 'nullable|string|max:100',
            'teknisi_id' => 'nullable',
            'jam_mulai' => 'nullable|string|max:10',
            'kode_perangkat' => 'nullable|string|max:100',
            'catatan_teknisi' => 'nullable|string|max:1000',
        ]);

        $rawFreq = trim((string) $request->frekuensi);
        $customInterval = max(1, (int) $request->input('custom_interval', 1));
        $customUnit = $request->input('custom_unit', 'bulan');
        $frekuensiLabel = match ($rawFreq) {
            'Sekali' => 'Sekali',
            'Mingguan' => 'Setiap minggu',
            'Bulanan' => 'Setiap bulan',
            'Tiga bulanan' => 'Setiap 3 bulan',
            'Tahunan' => 'Setiap tahun',
            'Atur sendiri' => "Setiap {$customInterval} {$customUnit}",
            default => $rawFreq,
        };

        $checklist = [];
        $activeNames = [];
        foreach ((array) $request->input('kegiatan_list', []) as $item) {
            if (is_array($item) && !empty($item['item']) && !empty($item['checked'])) {
                $lbl = trim((string) $item['item']);
                if ($lbl !== '') {
                    $activeNames[] = $lbl;
                    $checklist[] = ['item' => $lbl, 'checked' => false];
                }
            }
        }

        $tech = $request->teknisi_id ? User::find($request->teknisi_id) : null;
        $jamMulai = $request->filled('jam_mulai')
            ? str_replace('.', ':', trim($request->jam_mulai))
            : ($maintenanceSchedule->jam_mulai ?: '09:00');
        $kegiatanSummary = !empty($activeNames) ? implode(', ', $activeNames) : $maintenanceSchedule->kegiatan;
        $catatan = trim((string) $request->input('catatan_teknisi', '')) ?: null;

        $maintenanceSchedule->update([
            'nama_aset' => $request->nama_aset,
            'lokasi_ruangan' => $request->lokasi_ruangan,
            'kode_perangkat' => $request->filled('kode_perangkat') ? strtoupper(trim($request->kode_perangkat)) : $maintenanceSchedule->kode_perangkat,
            'frekuensi' => $frekuensiLabel,
            'tanggal_berikutnya' => $request->tanggal_berikutnya,
            'jam_mulai' => $jamMulai,
            'tim' => $request->tim ?: 'Tim IT Lapangan',
            'teknisi_id' => $tech?->id,
            'teknisi_nama' => $tech?->name,
            'kegiatan' => $kegiatanSummary,
            'checklist' => !empty($checklist) ? $checklist : $maintenanceSchedule->checklist,
            'catatan_teknisi' => $catatan,
            'kirim_pengingat' => $request->boolean('kirim_pengingat', true),
        ]);

        // Jika "Berlaku untuk: Termasuk jadwal aktif saat ini", ikut perbarui tugas Terjadwal yang belum dikerjakan
        if ($request->input('berlaku_untuk') === 'termasuk_aktif') {
            MaintenanceLog::where(function ($q) use ($maintenanceSchedule) {
                $q->where('jadwal_id', $maintenanceSchedule->id)
                  ->orWhere('nama_perangkat', $maintenanceSchedule->nama_aset);
            })
                ->whereIn('status', ['Terjadwal', 'Terlambat'])
                ->update([
                    'jam_mulai' => $jamMulai,
                    'tim' => $maintenanceSchedule->tim,
                    'user_id' => $tech?->id,
                    'teknisi_nama' => $tech ? $tech->name : 'Belum ada',
                    'kegiatan' => $kegiatanSummary ?: $maintenanceSchedule->kegiatan,
                    'checklist' => !empty($checklist) ? $checklist : $maintenanceSchedule->checklist,
                    'catatan_rekomendasi' => $catatan,
                ]);
        }

        return redirect()->route('maintenance.index')->with('success', "Master jadwal rutin '{$maintenanceSchedule->nama_aset}' berhasil diperbarui!");
    }

    /**
     * Deactivate master recurring schedule without deleting old logs.
     */
    public function deactivateRecurringSchedule(MaintenanceSchedule $maintenanceSchedule)
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya Admin (Koordinator IT) yang berhak menonaktifkan jadwal rutin.');

        $maintenanceSchedule->update(['is_active' => false]);

        return redirect()->route('maintenance.index')
            ->with('success', "Jadwal rutin '{$maintenanceSchedule->nama_aset}' telah dinonaktifkan. Seluruh log pemeliharaan lama tetap tersimpan.");
    }

    /**
     * Delete master recurring schedule.
     */
    public function destroyRecurringSchedule(MaintenanceSchedule $maintenanceSchedule)
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya Admin (Koordinator IT) yang berhak menghapus jadwal rutin.');

        $maintenanceSchedule->delete();
        return redirect()->back()->with('success', "Master jadwal rutin berhasil dihapus.");
    }

    /**
     * Show form to record a new preventive maintenance check.
     */
    public function create(Request $request)
    {
        $divisions = Division::where('is_active', true)->orderBy('nama')->get();
        $categories = DeviceCategory::where('is_active', true)->orderBy('nama')->get();
        $locations = Location::where('is_active', true)->orderBy('nama')->get();
        $technicians = User::where('role', 'teknisi')
            ->where('name', '!=', 'Tim IT Support')
            ->orderBy('name')
            ->get();

        // Daftar tugas terjadwal yang siap dicatat oleh teknisi (hanya milik teknisi login & jadwal tim belum diambil)
        $currentUser = Auth::user();
        $scheduledTasksQuery = MaintenanceLog::whereIn('status', ['Terjadwal', 'Terlambat'])
            ->orderBy('tanggal_cek', 'asc');
        if ($currentUser->isTeknisi()) {
            $scheduledTasksQuery->where(function ($q) use ($currentUser) {
                $q->ownedByTeknisi($currentUser)
                  ->orWhereNull('user_id');
            });
        }
        $scheduledTasks = $scheduledTasksQuery
            ->get()
            ->sortBy(function ($task) use ($currentUser) {
                if ((int) $task->user_id === (int) $currentUser->id) {
                    return 0; // Ditugaskan langsung ke teknisi ini
                }
                if (is_null($task->user_id)) {
                    return 1; // Jadwal tim belum ada PIC
                }
                return 2; // Jadwal teknisi lain
            })
            ->values();

        $recurringSchedules = MaintenanceSchedule::where('is_active', true)->orderBy('nama_aset')->get();

        $fromScheduleId = $request->get('from_schedule');
        $selectedSchedule = null;
        if ($fromScheduleId) {
            $selectedSchedule = MaintenanceLog::find($fromScheduleId);
        } elseif ($scheduledTasks->isNotEmpty() && !$request->filled('kode_perangkat')) {
            $selectedSchedule = $scheduledTasks->first();
        }

        // Pre-fill device codes dari Data Master Aset IT + tiket terbaru
        $masterDevices = Asset::where('is_active', true)
            ->orderBy('nama')
            ->get()
            ->map(fn ($a) => (object) [
                'kode_perangkat' => $a->kode_perangkat,
                'jenis_perangkat' => $a->jenis_perangkat ?: 'Perangkat IT',
                'divisi' => 'Operasional PLTU',
                'lokasi_ruangan' => $a->lokasi_ruangan ?: '',
            ]);

        $ticketDevices = Service::whereNotNull('kode_perangkat')
            ->select('id', 'kode_perangkat', 'jenis_perangkat', 'divisi', 'lokasi_ruangan')
            ->latest('id')
            ->take(100)
            ->get();

        $recentDevices = $masterDevices
            ->concat($ticketDevices)
            ->unique('kode_perangkat')
            ->take(50)
            ->values();

        $selectedKode = $request->get('kode_perangkat');
        $prefill = null;
        if ($selectedKode) {
            $prefill = Service::where('kode_perangkat', $selectedKode)->latest('id')->first();
        }

        return view('maintenance.create', compact(
            'divisions',
            'categories',
            'locations',
            'technicians',
            'recentDevices',
            'selectedKode',
            'prefill',
            'scheduledTasks',
            'recurringSchedules',
            'fromScheduleId',
            'selectedSchedule'
        ));
    }

    /**
     * Store newly created maintenance log.
     */
    public function store(Request $request)
    {
        // Normalisasi kondisi dari pill UI (Baik -> Prima, Rusak -> Kritis, Perlu tindak lanjut -> Perlu Perhatian)
        $rawKondisi = $request->input('kondisi', 'Prima');
        $normalizedKondisi = match (strtolower(trim($rawKondisi))) {
            'baik', 'prima' => 'Prima',
            'perlu tindak lanjut', 'perlu perhatian' => 'Perlu Perhatian',
            'rusak', 'kritis' => 'Kritis',
            default => 'Prima',
        };
        $request->merge(['kondisi' => $normalizedKondisi]);

        // Auto fallback jika input dari jadwal tidak memuat kode unik atau catatan singkat
        if (!$request->filled('kode_perangkat')) {
            $slug = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $request->nama_perangkat ?? 'AST'), 0, 8));
            $request->merge(['kode_perangkat' => 'AST-' . ($slug ?: 'DEV') . '-' . rand(100, 999)]);
        }
        if (!$request->filled('jenis_perangkat')) {
            $request->merge(['jenis_perangkat' => 'Komputer PC']);
        }
        if (!$request->filled('divisi')) {
            $request->merge(['divisi' => Auth::user()->divisi ?: 'Operasi']);
        }
        if (!$request->filled('tindakan')) {
            $request->merge(['tindakan' => 'Pemeliharaan rutin dilaksanakan sesuai daftar checklist pemeriksaan.']);
        }
        if (!$request->filled('lokasi_ruangan')) {
            $request->merge(['lokasi_ruangan' => 'Ruang Server / Operasi']);
        }

        $request->validate([
            'tanggal_cek' => 'required|date',
            'kode_perangkat' => 'required|string|max:100',
            'nama_perangkat' => 'required|string|max:150',
            'jenis_perangkat' => 'required|string|max:100',
            'divisi' => 'required|string|max:100',
            'lokasi_ruangan' => 'required|string|max:150',
            'kondisi' => 'required|in:Prima,Perlu Perhatian,Kritis',
            'tindakan' => 'required|string',
            'catatan_rekomendasi' => 'nullable|string',
            'checklist' => 'nullable|array',
            'teknisi_pendamping' => 'nullable|array',
            'foto_dokumentasi' => 'nullable|image|max:5120',
            'foto_sebelum' => 'nullable|image|max:5120',
            'foto_sesudah' => 'nullable|image|max:5120',
        ]);

        $tanggalCek = Carbon::parse($request->tanggal_cek);
        $periodeBulan = $tanggalCek->format('Y-m');

        // Foto upload
        $fotoPath = null;
        if ($request->hasFile('foto_dokumentasi')) {
            $fotoPath = $request->file('foto_dokumentasi')->store('maintenance_docs', 'public');
        } elseif ($request->hasFile('foto_sebelum')) {
            $fotoPath = $request->file('foto_sebelum')->store('maintenance_docs', 'public');
        }

        $fotoSebelum = $request->hasFile('foto_sebelum') ? $request->file('foto_sebelum')->store('maintenance_docs', 'public') : null;
        $fotoSesudah = $request->hasFile('foto_sesudah') ? $request->file('foto_sesudah')->store('maintenance_docs', 'public') : null;

        // Process checklist array with item description and checked status
        $rawChecklist = $request->input('checklist', []);
        $formattedChecklist = [];
        if (is_array($rawChecklist)) {
            foreach ($rawChecklist as $item) {
                if (is_array($item) && isset($item['item'])) {
                    $formattedChecklist[] = [
                        'item' => trim($item['item']),
                        'checked' => !empty($item['checked']),
                    ];
                } elseif (is_string($item) && trim($item) !== '') {
                    $formattedChecklist[] = [
                        'item' => trim($item),
                        'checked' => true,
                    ];
                }
            }
        }

        // Default checklist fallback if empty
        if (empty($formattedChecklist)) {
            $formattedChecklist = MaintenanceLog::defaultChecklist($request->jenis_perangkat);
        }

        $statusTindakLanjut = match ($request->kondisi) {
            'Kritis' => 'Eskalasi Tiket',
            'Perlu Perhatian' => 'Perlu Pantauan',
            default => 'Selesai',
        };

        // Hitung durasi jam jika ada jam_mulai dan jam_selesai
        $jamMulai = $request->input('jam_mulai', '09:00');
        $jamSelesai = $request->input('jam_selesai', '10:00');
        $durasiText = '1 jam';
        if ($jamMulai && $jamSelesai) {
            try {
                $tMulai = Carbon::createFromFormat('H:i', str_replace('.', ':', $jamMulai));
                $tSelesai = Carbon::createFromFormat('H:i', str_replace('.', ':', $jamSelesai));
                $diffMin = abs($tSelesai->diffInMinutes($tMulai));
                if ($diffMin < 60) {
                    $durasiText = "{$diffMin} menit";
                } else {
                    $h = floor($diffMin / 60);
                    $m = $diffMin % 60;
                    $durasiText = $m > 0 ? "{$h} jam {$m} mnt" : "{$h} jam";
                }
            } catch (\Exception $e) {
                $durasiText = '1 jam';
            }
        }

        $finalStatus = $request->input('submit_action') === 'draft' ? 'Draft' : 'Selesai';

        $createdService = null;
        // If critical or user explicitly opted to create repair ticket
        if ($request->kondisi === 'Kritis' || $request->boolean('buat_tiket_servis')) {
            $prefix = 'TKT-' . now()->format('ymd') . '-';
            $lastTicket = Service::where('ticket_number', 'like', $prefix . '%')->latest('id')->first();
            $seq = $lastTicket ? ((int) substr($lastTicket->ticket_number, -3) + 1) : 1;
            $newTicketNumber = $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);

            $createdService = Service::create([
                'ticket_number' => $newTicketNumber,
                'judul_kendala' => 'Temuan Pemeliharaan Rutin: Kerusakan ' . $request->nama_perangkat,
                'user_id' => Auth::id(),
                'nama_pelapor' => 'Preventive Maintenance (' . Auth::user()->name . ')',
                'divisi' => $request->divisi,
                'lokasi_ruangan' => $request->lokasi_ruangan,
                'jenis_perangkat' => $request->jenis_perangkat,
                'kode_perangkat' => $request->kode_perangkat,
                'keluhan' => "Ditemukan kerusakan pada [{$request->kode_perangkat} - {$request->nama_perangkat}] saat pemeliharaan berkala:\n" . 
                             $request->tindakan . 
                             ($request->catatan_rekomendasi ? "\n\nRekomendasi Tindak Lanjut: " . $request->catatan_rekomendasi : ''),
                'prioritas' => 'Mendesak',
                'status' => 'Baru',
                'teknisi_id' => Auth::user()->isTeknisi() ? Auth::id() : null,
                'teknisi_nama' => Auth::user()->isTeknisi() ? Auth::user()->name : null,
                'teknisi_pendamping' => $request->input('teknisi_pendamping'),
                'is_read' => false,
            ]);

            $statusTindakLanjut = 'Eskalasi Tiket';
        }

        // Cek apakah menyelesaikan tugas terjadwal yang sudah ada
        $existingLog = null;
        if ($request->filled('scheduled_log_id')) {
            $existingLog = MaintenanceLog::find($request->scheduled_log_id);
        } elseif ($request->filled('from_schedule')) {
            $existingLog = MaintenanceLog::find($request->from_schedule);
        }

        if ($existingLog) {
            $existingLog->update([
                'tanggal_cek' => $tanggalCek->toDateString(),
                'periode_bulan' => $periodeBulan,
                'kode_perangkat' => strtoupper(trim($request->kode_perangkat)),
                'nama_perangkat' => trim($request->nama_perangkat),
                'jenis_perangkat' => $request->jenis_perangkat,
                'divisi' => $request->divisi,
                'lokasi_ruangan' => $request->lokasi_ruangan,
                'user_id' => Auth::id(),
                'teknisi_nama' => Auth::user()->name,
                'teknisi_pendamping' => $request->input('teknisi_pendamping'),
                'kondisi' => $request->kondisi,
                'status' => $finalStatus,
                'kegiatan' => $request->kegiatan ?: ($request->checklist ? implode(', ', array_slice(array_column($formattedChecklist, 'item'), 0, 2)) : 'Pemeliharaan rutin'),
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'durasi_jam' => $durasiText,
                'jenis_pemeliharaan' => $request->input('jenis_pemeliharaan', 'Rutin'),
                'checklist' => $formattedChecklist,
                'tindakan' => $request->tindakan,
                'catatan_rekomendasi' => $request->catatan_rekomendasi,
                'status_tindak_lanjut' => $statusTindakLanjut,
                'service_id' => $createdService?->id ?: $existingLog->service_id,
                'foto_dokumentasi' => $fotoPath ?: $existingLog->foto_dokumentasi,
                'foto_sebelum' => $fotoSebelum ?: $existingLog->foto_sebelum,
                'foto_sesudah' => $fotoSesudah ?: $existingLog->foto_sesudah,
            ]);

            $savedLog = $existingLog;
            $logNumber = $existingLog->log_number;
        } else {
            $logNumber = MaintenanceLog::generateLogNumber($tanggalCek);
            $savedLog = MaintenanceLog::create([
                'log_number' => $logNumber,
                'tanggal_cek' => $tanggalCek->toDateString(),
                'periode_bulan' => $periodeBulan,
                'kode_perangkat' => strtoupper(trim($request->kode_perangkat)),
                'nama_perangkat' => trim($request->nama_perangkat),
                'jenis_perangkat' => $request->jenis_perangkat,
                'divisi' => $request->divisi,
                'lokasi_ruangan' => $request->lokasi_ruangan,
                'user_id' => Auth::id(),
                'teknisi_nama' => Auth::user()->name,
                'teknisi_pendamping' => $request->input('teknisi_pendamping'),
                'kondisi' => $request->kondisi,
                'status' => $finalStatus,
                'tim' => Auth::user()->divisi ?: 'Tim IT Lapangan',
                'kegiatan' => $request->kegiatan ?: ($request->checklist ? implode(', ', array_slice(array_column($formattedChecklist, 'item'), 0, 2)) : 'Pemeliharaan rutin'),
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'durasi_jam' => $durasiText,
                'jenis_pemeliharaan' => $request->input('jenis_pemeliharaan', 'Rutin'),
                'checklist' => $formattedChecklist,
                'tindakan' => $request->tindakan,
                'catatan_rekomendasi' => $request->catatan_rekomendasi,
                'status_tindak_lanjut' => $statusTindakLanjut,
                'service_id' => $createdService?->id,
                'foto_dokumentasi' => $fotoPath,
                'foto_sebelum' => $fotoSebelum,
                'foto_sesudah' => $fotoSesudah,
            ]);
        }

        // Sinkronkan tanggal_berikutnya pada Master Jadwal Rutin & jadwalkan siklus berikutnya jika log diselesaikan
        $nextLogCreated = null;
        if ($finalStatus === 'Selesai') {
            $masterSch = $savedLog->jadwal_id
                ? MaintenanceSchedule::find($savedLog->jadwal_id)
                : MaintenanceSchedule::where('is_active', true)->where('nama_aset', $savedLog->nama_perangkat)->first();

            if ($masterSch) {
                if (!$savedLog->jadwal_id) {
                    $savedLog->update(['jadwal_id' => $masterSch->id]);
                }

                $freq = strtolower(trim((string) $masterSch->frekuensi));
                if ($freq === 'sekali' || str_contains($freq, 'sekali')) {
                    // Pemeliharaan 1 kali pengerjaan: nonaktifkan jadwal master rutin, tidak ada siklus berikutnya
                    $masterSch->update(['is_active' => false]);
                } else {
                    $nextDue = $tanggalCek->copy();
                    if (preg_match('/(\d+)\s*hari/', $freq, $m)) {
                        $nextDue->addDays((int) $m[1]);
                    } elseif (str_contains($freq, 'minggu')) {
                        $num = preg_match('/(\d+)/', $freq, $m) ? (int) $m[1] : 1;
                        $nextDue->addWeeks($num);
                    } elseif (str_contains($freq, 'tahun')) {
                        $num = preg_match('/(\d+)/', $freq, $m) ? (int) $m[1] : 1;
                        $nextDue->addYears($num);
                    } elseif (str_contains($freq, '3 bulan') || str_contains($freq, 'tiga bulan')) {
                        $nextDue->addMonths(3);
                    } else {
                        $num = preg_match('/(\d+)/', $freq, $m) ? (int) $m[1] : 1;
                        $nextDue->addMonths($num);
                    }

                    $masterSch->update(['tanggal_berikutnya' => $nextDue->toDateString()]);

                    // Otomatis buat tugas Terjadwal untuk siklus berikutnya
                    $nextLogCreated = self::createNextRecurrenceLog($masterSch, $savedLog, $nextDue);
                }
            }
        }

        $message = "Log pemeliharaan {$logNumber} berhasil dicatat!";
        if ($nextLogCreated) {
            $formattedNext = Carbon::parse($nextLogCreated->tanggal_cek)->translatedFormat('d F Y');
            $message .= " Jadwal pemeliharaan berikutnya ({$masterSch->frekuensi}) otomatis dibuat untuk {$formattedNext}.";
        }
        if ($createdService) {
            $message .= " Tiket perbaikan {$createdService->ticket_number} telah dibuat otomatis.";
        }

        return redirect()->route('maintenance.index')->with('success', $message);
    }

    /**
     * Display a specific maintenance log & printable asset card.
     */
    public function show(MaintenanceLog $maintenanceLog)
    {
        $currentUser = Auth::user();
        if (
            $currentUser
            && $currentUser->isTeknisi()
            && !$maintenanceLog->isOwnedByTeknisi($currentUser)
            && !empty($maintenanceLog->user_id)
        ) {
            abort(403, 'Anda hanya dapat melihat detail log pemeliharaan yang Anda kerjakan.');
        }

        $maintenanceLog->load(['teknisi', 'service']);

        // Riwayat pemeliharaan sebelumnya untuk perangkat yang sama
        $deviceHistory = MaintenanceLog::where('kode_perangkat', $maintenanceLog->kode_perangkat)
            ->where('id', '!=', $maintenanceLog->id)
            ->latest('tanggal_cek')
            ->take(5)
            ->get();

        // Cari jadwal rutin terkait (untuk kartu "Jadwal berikutnya")
        $relatedSchedule = null;
        if (!empty($maintenanceLog->jadwal_id)) {
            $relatedSchedule = MaintenanceSchedule::find($maintenanceLog->jadwal_id);
        }
        if (!$relatedSchedule) {
            $relatedSchedule = MaintenanceSchedule::where('is_active', true)
                ->where('nama_aset', $maintenanceLog->nama_perangkat)
                ->first();
        }

        // Nama Admin penjadwal (Koordinator IT)
        $adminUser = User::where('role', 'admin')->orderBy('id')->first();
        $scheduledByLabel = $adminUser ? "{$adminUser->name} (Admin)" : 'Koordinator IT (Admin)';

        // Resolve nama teknisi pendamping jika kerja tim
        $coTechNames = [];
        if (is_array($maintenanceLog->teknisi_pendamping) && count($maintenanceLog->teknisi_pendamping) > 0) {
            foreach ($maintenanceLog->teknisi_pendamping as $item) {
                if (is_numeric($item)) {
                    $u = User::find((int) $item);
                    if ($u) {
                        $coTechNames[] = $u->name;
                    }
                } elseif (is_string($item) && trim($item) !== '') {
                    $coTechNames[] = trim($item);
                }
            }
        }

        return view('maintenance.show', compact(
            'maintenanceLog',
            'deviceHistory',
            'relatedSchedule',
            'scheduledByLabel',
            'coTechNames'
        ));
    }

    /**
     * Show form to edit maintenance log.
     */
    public function edit(MaintenanceLog $maintenanceLog)
    {
        $divisions = Division::where('is_active', true)->orderBy('nama')->get();
        $categories = DeviceCategory::where('is_active', true)->orderBy('nama')->get();
        $locations = Location::where('is_active', true)->orderBy('nama')->get();
        $technicians = User::where('role', 'teknisi')
            ->where('name', '!=', 'Tim IT Support')
            ->orderBy('name')
            ->get();

        return view('maintenance.edit', compact(
            'maintenanceLog',
            'divisions',
            'categories',
            'locations',
            'technicians'
        ));
    }

    /**
     * Update maintenance log.
     */
    public function update(Request $request, MaintenanceLog $maintenanceLog)
    {
        $request->validate([
            'tanggal_cek' => 'required|date',
            'kode_perangkat' => 'required|string|max:100',
            'nama_perangkat' => 'required|string|max:150',
            'jenis_perangkat' => 'required|string|max:100',
            'divisi' => 'required|string|max:100',
            'lokasi_ruangan' => 'required|string|max:150',
            'kondisi' => 'required|in:Prima,Perlu Perhatian,Kritis',
            'tindakan' => 'required|string',
            'catatan_rekomendasi' => 'nullable|string',
            'checklist' => 'nullable|array',
            'teknisi_pendamping' => 'nullable|array',
            'foto_dokumentasi' => 'nullable|image|max:5120',
        ]);

        $tanggalCek = Carbon::parse($request->tanggal_cek);
        $fotoPath = $maintenanceLog->foto_dokumentasi;

        if ($request->hasFile('foto_dokumentasi')) {
            if ($fotoPath) {
                Storage::disk('public')->delete($fotoPath);
            }
            $file = $request->file('foto_dokumentasi');
            $fotoPath = $file->store('maintenance_docs', 'public');
        }

        $rawChecklist = $request->input('checklist', []);
        $formattedChecklist = [];
        if (is_array($rawChecklist)) {
            foreach ($rawChecklist as $item) {
                if (is_array($item) && isset($item['item'])) {
                    $formattedChecklist[] = [
                        'item' => trim($item['item']),
                        'checked' => !empty($item['checked']),
                    ];
                } elseif (is_string($item) && trim($item) !== '') {
                    $formattedChecklist[] = [
                        'item' => trim($item),
                        'checked' => true,
                    ];
                }
            }
        }

        $maintenanceLog->update([
            'tanggal_cek' => $tanggalCek->toDateString(),
            'periode_bulan' => $tanggalCek->format('Y-m'),
            'kode_perangkat' => strtoupper(trim($request->kode_perangkat)),
            'nama_perangkat' => trim($request->nama_perangkat),
            'jenis_perangkat' => $request->jenis_perangkat,
            'divisi' => $request->divisi,
            'lokasi_ruangan' => $request->lokasi_ruangan,
            'teknisi_pendamping' => $request->input('teknisi_pendamping'),
            'kondisi' => $request->kondisi,
            'checklist' => $formattedChecklist ?: $maintenanceLog->checklist,
            'tindakan' => $request->tindakan,
            'catatan_rekomendasi' => $request->catatan_rekomendasi,
            'foto_dokumentasi' => $fotoPath,
        ]);

        return redirect()->route('maintenance.show', $maintenanceLog)->with('success', 'Log pemeliharaan berhasil diperbarui!');
    }

    /**
     * Remove the specified maintenance log.
     */
    public function destroy(MaintenanceLog $maintenanceLog)
    {
        if ($maintenanceLog->foto_dokumentasi) {
            Storage::disk('public')->delete($maintenanceLog->foto_dokumentasi);
        }

        $logNum = $maintenanceLog->log_number;
        $maintenanceLog->delete();

        return redirect()->route('maintenance.index')->with('success', "Log pemeliharaan {$logNum} berhasil dihapus.");
    }

    /**
     * Print preview / export PDF of maintenance report.
     */
    public function cetak(Request $request)
    {
        $currentMonth = now()->format('Y-m');
        $selectedPeriod = $request->get('periode', $currentMonth);

        $query = MaintenanceLog::with(['teknisi', 'service'])->orderBy('tanggal_cek');

        if ($selectedPeriod && $selectedPeriod !== 'all') {
            $query->where('periode_bulan', $selectedPeriod);
        }

        if ($request->filled('divisi') && $request->divisi !== 'all') {
            $query->where('divisi', $request->divisi);
        }

        if ($request->filled('kondisi') && $request->kondisi !== 'all') {
            $query->where('kondisi', $request->kondisi);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('teknisi') && $request->teknisi !== 'all') {
            $selectedTeknisi = $request->teknisi;
            $techUser = is_numeric($selectedTeknisi) ? User::find($selectedTeknisi) : User::where('name', $selectedTeknisi)->first();
            if ($techUser) {
                $query->ownedByTeknisi($techUser);
            } else {
                $query->where('teknisi_nama', 'like', "%{$selectedTeknisi}%");
            }
        }

        if ($request->filled('search')) {
            $keyword = trim((string) $request->search);
            $query->where(function ($q) use ($keyword) {
                $q->where('kode_perangkat', 'like', "%{$keyword}%")
                  ->orWhere('nama_perangkat', 'like', "%{$keyword}%")
                  ->orWhere('lokasi_ruangan', 'like', "%{$keyword}%")
                  ->orWhere('teknisi_nama', 'like', "%{$keyword}%")
                  ->orWhere('kegiatan', 'like', "%{$keyword}%")
                  ->orWhere('tindakan', 'like', "%{$keyword}%")
                  ->orWhere('log_number', 'like', "%{$keyword}%");
            });
        }

        $logs = $query->get();

        $totalAset = $logs->count();
        $totalPrima = $logs->filter(fn($l) => $l->isPrima())->count();
        $totalPerhatian = $logs->filter(fn($l) => $l->isPerluPerhatian())->count();
        $totalKritis = $logs->filter(fn($l) => $l->isKritis())->count();
        $persenKelaikan = $totalAset > 0 ? round(($totalPrima / $totalAset) * 100, 1) : 100;

        // Label teks filter untuk tampilan & dokumen
        $periodeLabel = $selectedPeriod === 'all'
            ? 'Seluruh Periode'
            : (preg_match('/^\d{4}-\d{2}$/', $selectedPeriod)
                ? Carbon::createFromFormat('Y-m', $selectedPeriod)->translatedFormat('F Y')
                : $selectedPeriod);

        $filterDivisiLabel = $request->filled('divisi') && $request->divisi !== 'all' ? $request->divisi : 'Semua Divisi';
        $filterKondisiLabel = $request->filled('kondisi') && $request->kondisi !== 'all' ? $request->kondisi : 'Semua Kondisi';
        $filterStatusLabel = $request->filled('status') && $request->status !== 'all' ? $request->status : 'Semua Status';
        $filterTeknisiLabel = $request->filled('teknisi') && $request->teknisi !== 'all' ? $request->teknisi : 'Seluruh Tim IT';

        $printedAt = now()->translatedFormat('d F Y, H:i') . ' WIB';
        $printedDateOnly = now()->translatedFormat('d F Y');
        $currentUser = Auth::user();
        $printedBy = $currentUser?->name ?? 'Teknisi IT';
        $printedRole = ($currentUser && $currentUser->isTeknisi())
            ? ('Teknisi IT · ' . ($currentUser->label_tim ?? 'Tim IT Support'))
            : 'Koordinator IT Support & Infrastructure';

        $coordinatorName = 'Hendra Wijaya, S.T.';
        $coordinatorTitle = 'Koordinator IT Support & Infrastructure';

        $kondisiDistribution = [
            'Prima' => $totalPrima,
            'Perlu Perhatian' => $totalPerhatian,
            'Kritis' => $totalKritis,
        ];

        $kategoriDistribution = $logs->groupBy(fn($l) => $l->jenis_perangkat ?: 'Lainnya')
            ->map(fn($g) => $g->count())
            ->sortDesc()
            ->take(6);

        $viewData = compact(
            'logs',
            'selectedPeriod',
            'periodeLabel',
            'filterDivisiLabel',
            'filterKondisiLabel',
            'filterStatusLabel',
            'filterTeknisiLabel',
            'totalAset',
            'totalPrima',
            'totalPerhatian',
            'totalKritis',
            'persenKelaikan',
            'kondisiDistribution',
            'kategoriDistribution',
            'printedAt',
            'printedDateOnly',
            'printedBy',
            'printedRole',
            'coordinatorName',
            'coordinatorTitle'
        );

        // Jika dipanggil dengan opsi download PDF (?download=pdf atau ?pdf=1 atau ?format=pdf)
        if ($request->get('download') === 'pdf' || $request->boolean('pdf') || $request->get('format') === 'pdf') {
            $cleanPeriod = Str::slug($selectedPeriod === 'all' ? 'semua-periode' : $selectedPeriod);
            $filename = 'Laporan-PM-Aset-IT-PT-S2P-' . $cleanPeriod . '-' . now()->format('Ymd-His') . '.pdf';

            $orientation = strtolower((string) $request->get('orientasi', 'lanskap')) === 'potret' ? 'portrait' : 'landscape';
            $kertas = strtoupper((string) $request->get('kertas', 'A4'));
            $paperSpec = match ($kertas) {
                'LETTER' => 'letter',
                'F4' => [0, 0, 609.45, 935.43],
                default => 'a4',
            };

            $pdf = Pdf::loadView('maintenance.cetak-pdf', $viewData)->setPaper($paperSpec, $orientation);

            $dompdf = $pdf->getDomPDF();
            $dompdf->render();
            $canvas = $dompdf->getCanvas();
            $canvas->page_script(function (int $pageNumber, int $pageCount, $pageCanvas, $fontMetrics) use ($orientation) {
                $font = $fontMetrics->getFont('DejaVu Sans', 'normal') ?: $fontMetrics->getFont('Helvetica', 'normal');
                $size = 7.5;
                $pageW = $pageCanvas->get_width();
                $pageH = $pageCanvas->get_height();
                $marginX = $orientation === 'landscape' ? 34.02 : 36.85;
                $lineY = $pageH - ($orientation === 'landscape' ? 26.0 : 32.0);
                $textY = $pageH - ($orientation === 'landscape' ? 18.0 : 24.0);

                // Garis footer
                $pageCanvas->line($marginX, $lineY, $pageW - $marginX, $lineY, [0.82, 0.85, 0.90], 0.6);

                // Kiri: Identitas Dokumen S2P
                $docText = "FORM-IT-PM-REV.02 | PT SUMBER SEGARA PRIMADAYA (PLTU KARANGKANDRI CILACAP)";
                $pageCanvas->text($marginX, $textY, $docText, $font, $size, [0.35, 0.40, 0.45]);

                // Kanan: Penomoran Halaman
                $pageText = "Halaman {$pageNumber} dari {$pageCount}";
                $textW = $fontMetrics->getTextWidth($pageText, $font, $size);
                $pageCanvas->text($pageW - $marginX - $textW, $textY, $pageText, $font, $size, [0.35, 0.40, 0.45]);
            });

            return $pdf->download($filename);
        }

        return view('maintenance.cetak', $viewData);
    }

    /**
     * Print preview & PDF download for a single maintenance log (Berita Acara & Lembar Kerja PM).
     */
    public function cetakSingle(Request $request, MaintenanceLog $maintenanceLog)
    {
        $currentUser = Auth::user();
        $printedAt = now()->translatedFormat('d F Y, H:i') . ' WIB';
        $printedDateOnly = now()->translatedFormat('d F Y');
        $printedBy = $maintenanceLog->teknisi_nama && $maintenanceLog->teknisi_nama !== 'Belum ada'
            ? $maintenanceLog->teknisi_nama
            : ($currentUser?->name ?? 'Teknisi IT');
        $printedRole = ($maintenanceLog->teknisi && $maintenanceLog->teknisi->isTeknisi())
            ? ('Teknisi IT · ' . ($maintenanceLog->teknisi->label_tim ?? 'Tim IT Lapangan'))
            : 'Teknisi IT Pelaksana';

        $coordinatorName = 'Hendra Wijaya, S.T.';
        $coordinatorTitle = 'Koordinator IT Support & Infrastructure';

        $viewData = compact(
            'maintenanceLog',
            'printedAt',
            'printedDateOnly',
            'printedBy',
            'printedRole',
            'coordinatorName',
            'coordinatorTitle'
        );

        if ($request->get('download') === 'pdf' || $request->boolean('pdf') || $request->get('format') === 'pdf') {
            $orientation = strtolower((string) $request->get('orientasi', 'potret')) === 'lanskap' ? 'landscape' : 'portrait';
            $kertas = strtoupper((string) $request->get('kertas', 'A4'));
            $paperSpec = match ($kertas) {
                'LETTER' => 'letter',
                'F4' => [0, 0, 609.45, 935.43],
                default => 'a4',
            };
            $filename = 'Berita-Acara-PM-' . Str::slug($maintenanceLog->log_number . '-' . $maintenanceLog->nama_perangkat) . '-' . now()->format('Ymd') . '.pdf';

            $pdf = Pdf::loadView('maintenance.cetak-single-pdf', $viewData)->setPaper($paperSpec, $orientation);

            $dompdf = $pdf->getDomPDF();
            $dompdf->render();
            $canvas = $dompdf->getCanvas();
            $canvas->page_script(function (int $pageNumber, int $pageCount, $pageCanvas, $fontMetrics) use ($orientation, $maintenanceLog) {
                $font = $fontMetrics->getFont('DejaVu Sans', 'normal') ?: $fontMetrics->getFont('Helvetica', 'normal');
                $size = 7.5;
                $pageW = $pageCanvas->get_width();
                $pageH = $pageCanvas->get_height();
                $marginX = $orientation === 'landscape' ? 34.02 : 36.85;
                $lineY = $pageH - ($orientation === 'landscape' ? 26.0 : 32.0);
                $textY = $pageH - ($orientation === 'landscape' ? 18.0 : 24.0);

                // Garis footer
                $pageCanvas->line($marginX, $lineY, $pageW - $marginX, $lineY, [0.82, 0.85, 0.90], 0.6);

                // Kiri: Identitas Dokumen S2P
                $docText = "FORM-IT-PM-BA-REV.02 | BERITA ACARA PEMELIHARAAN ASET IT #{$maintenanceLog->log_number}";
                $pageCanvas->text($marginX, $textY, $docText, $font, $size, [0.35, 0.40, 0.45]);

                // Kanan: Penomoran Halaman
                $pageText = "Halaman {$pageNumber} dari {$pageCount}";
                $textW = $fontMetrics->getTextWidth($pageText, $font, $size);
                $pageCanvas->text($pageW - $marginX - $textW, $textY, $pageText, $font, $size, [0.35, 0.40, 0.45]);
            });

            return $pdf->download($filename);
        }

        return view('maintenance.cetak-single', $viewData);
    }

    /**
     * Export maintenance logs to CSV.
     */
    public function export(Request $request)
    {
        $currentMonth = now()->format('Y-m');
        $selectedPeriod = $request->get('periode', $currentMonth);

        $query = MaintenanceLog::with(['teknisi', 'service'])->orderBy('tanggal_cek');

        if ($selectedPeriod && $selectedPeriod !== 'all') {
            $query->where('periode_bulan', $selectedPeriod);
        }

        if ($request->filled('divisi') && $request->divisi !== 'all') {
            $query->where('divisi', $request->divisi);
        }

        if ($request->filled('kondisi') && $request->kondisi !== 'all') {
            $query->where('kondisi', $request->kondisi);
        }

        $logs = $query->get();

        $filename = 'Laporan_Pemeliharaan_IT_' . ($selectedPeriod === 'all' ? 'Semua' : $selectedPeriod) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // BOM UTF-8 for Excel

            fputcsv($file, [
                'No. Pemeliharaan',
                'Tanggal Cek',
                'Kode Perangkat',
                'Nama Perangkat',
                'Jenis Perangkat',
                'Divisi',
                'Lokasi / Ruangan',
                'Teknisi Pelaksana',
                'Kondisi',
                'Tindakan Pemeliharaan',
                'Rekomendasi',
                'Status Tindak Lanjut',
                'Tiket Terhubung',
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->log_number,
                    $log->tanggal_cek ? $log->tanggal_cek->format('d/m/Y') : '-',
                    $log->kode_perangkat,
                    $log->nama_perangkat,
                    $log->jenis_perangkat,
                    $log->divisi,
                    $log->lokasi_ruangan,
                    $log->teknisi_lengkap,
                    $log->kondisi,
                    $log->tindakan,
                    $log->catatan_rekomendasi ?: '-',
                    $log->status_tindak_lanjut,
                    $log->service ? $log->service->ticket_number : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Otomatis membuat log pemeliharaan Terjadwal untuk siklus berulang berikutnya (mingguan, bulanan, tahunan).
     */
    public static function createNextRecurrenceLog(MaintenanceSchedule $masterSch, MaintenanceLog $completedLog, Carbon $nextDue): ?MaintenanceLog
    {
        // Cegah duplikasi tugas untuk tanggal & aset yang sama
        $alreadyExists = MaintenanceLog::where(function ($q) use ($masterSch) {
                $q->where('jadwal_id', $masterSch->id)
                  ->orWhere(function ($match) use ($masterSch) {
                      $match->where('nama_perangkat', $masterSch->nama_aset)
                            ->where('lokasi_ruangan', $masterSch->lokasi_ruangan);
                  });
            })
            ->whereDate('tanggal_cek', $nextDue->toDateString())
            ->whereIn('status', ['Terjadwal', 'Terlambat'])
            ->exists();

        if ($alreadyExists) {
            return null;
        }

        // Siapkan checklist baru yang belum dicentang (unchecked)
        $freshChecklist = [];
        $sourceChecklist = !empty($masterSch->checklist) ? $masterSch->checklist : $completedLog->checklist;
        if (is_array($sourceChecklist)) {
            foreach ($sourceChecklist as $item) {
                $lbl = is_array($item) ? ($item['item'] ?? '') : (string) $item;
                if (trim($lbl) !== '') {
                    $freshChecklist[] = ['item' => trim($lbl), 'checked' => false];
                }
            }
        }

        $techUser = $masterSch->teknisi_id
            ? User::find($masterSch->teknisi_id)
            : ($completedLog->user_id ? User::find($completedLog->user_id) : null);
        $techNama = $techUser ? $techUser->name : ($masterSch->teknisi_nama ?: ($completedLog->teknisi_nama ?: 'Belum ada'));

        $kodePerangkat = $masterSch->kode_perangkat
            ?: ($completedLog->kode_perangkat ?: Asset::generateKode($masterSch->nama_aset, $masterSch->lokasi_ruangan, 'Server / UPS'));

        return MaintenanceLog::create([
            'log_number' => MaintenanceLog::generateLogNumber($nextDue),
            'tanggal_cek' => $nextDue->toDateString(),
            'jam_mulai' => $masterSch->jam_mulai ?: ($completedLog->jam_mulai ?: '08:00'),
            'periode_bulan' => $nextDue->format('Y-m'),
            'nama_perangkat' => $masterSch->nama_aset,
            'lokasi_ruangan' => $masterSch->lokasi_ruangan,
            'kode_perangkat' => $kodePerangkat,
            'jenis_perangkat' => $completedLog->jenis_perangkat ?: 'Server / UPS',
            'divisi' => $completedLog->divisi ?: 'Operasional PLTU',
            'user_id' => $techUser?->id,
            'teknisi_nama' => $techNama,
            'tim' => $masterSch->tim ?: ($completedLog->tim ?: 'Tim IT Lapangan'),
            'kegiatan' => $masterSch->kegiatan ?: $completedLog->kegiatan,
            'jenis_pemeliharaan' => 'Rutin',
            'checklist' => !empty($freshChecklist) ? $freshChecklist : null,
            'status' => 'Terjadwal',
            'kondisi' => 'Prima',
            'tindakan' => "Dijadwalkan otomatis siklus berikutnya ({$masterSch->frekuensi}) dari Master Jadwal Rutin",
            'catatan_rekomendasi' => $masterSch->catatan_teknisi ?: $completedLog->catatan_rekomendasi,
            'status_tindak_lanjut' => 'Selesai',
            'jadwal_id' => $masterSch->id,
        ]);
    }

    /**
     * Otomatisasi sinkronisasi Master Jadwal Rutin ke Log Pemeliharaan.
     * Membuat tugas Terjadwal untuk teknisi sehingga teknisi tidak perlu input manual dari nol.
     */
    public static function syncSchedulesToLogs(): int
    {
        $activeSchedules = MaintenanceSchedule::where('is_active', true)->get();
        $createdCount = 0;
        $today = now()->startOfDay();

        foreach ($activeSchedules as $schedule) {
            $freq = strtolower(trim((string) $schedule->frekuensi));

            // Jika jadwal sekali dan sudah pernah selesai, nonaktifkan
            if ($freq === 'sekali' || str_contains($freq, 'sekali')) {
                $alreadyDone = MaintenanceLog::where(function ($q) use ($schedule) {
                        $q->where('jadwal_id', $schedule->id)
                          ->orWhere('nama_perangkat', $schedule->nama_aset);
                    })
                    ->where('status', 'Selesai')
                    ->exists();
                if ($alreadyDone) {
                    $schedule->update(['is_active' => false]);
                    continue;
                }
            }

            $due = $schedule->tanggal_berikutnya
                ? Carbon::parse($schedule->tanggal_berikutnya)->startOfDay()
                : $today;

            // Cek apakah ada tugas yang masih aktif (Terjadwal / Terlambat) untuk jadwal ini
            $activeLog = MaintenanceLog::where(function ($q) use ($schedule) {
                    $q->where('jadwal_id', $schedule->id)
                      ->orWhere(function ($match) use ($schedule) {
                          $match->where('nama_perangkat', $schedule->nama_aset)
                                ->where('lokasi_ruangan', $schedule->lokasi_ruangan);
                      });
                })
                ->whereIn('status', ['Terjadwal', 'Terlambat'])
                ->first();

            if ($activeLog) {
                // Update status ke Terlambat jika sudah melewati hari H
                if ($activeLog->status === 'Terjadwal' && $activeLog->tanggal_cek && Carbon::parse($activeLog->tanggal_cek)->lt($today)) {
                    $activeLog->update(['status' => 'Terlambat']);
                }
                continue;
            }

            // Jika tanggal jatuh tempo ini ternyata SUDAH selesai dikerjakan, majukan ke siklus berikutnya
            $alreadyFinished = MaintenanceLog::where(function ($q) use ($schedule) {
                    $q->where('jadwal_id', $schedule->id)
                      ->orWhere('nama_perangkat', $schedule->nama_aset);
                })
                ->whereDate('tanggal_cek', $due->toDateString())
                ->where('status', 'Selesai')
                ->first();

            if ($alreadyFinished) {
                if ($freq === 'sekali' || str_contains($freq, 'sekali')) {
                    $schedule->update(['is_active' => false]);
                    continue;
                }

                $nextDue = $due->copy();
                if (preg_match('/(\d+)\s*hari/', $freq, $m)) {
                    $nextDue->addDays((int) $m[1]);
                } elseif (str_contains($freq, 'minggu')) {
                    $num = preg_match('/(\d+)/', $freq, $m) ? (int) $m[1] : 1;
                    $nextDue->addWeeks($num);
                } elseif (str_contains($freq, 'tahun')) {
                    $num = preg_match('/(\d+)/', $freq, $m) ? (int) $m[1] : 1;
                    $nextDue->addYears($num);
                } elseif (str_contains($freq, '3 bulan') || str_contains($freq, 'tiga bulan')) {
                    $nextDue->addMonths(3);
                } else {
                    $num = preg_match('/(\d+)/', $freq, $m) ? (int) $m[1] : 1;
                    $nextDue->addMonths($num);
                }

                $schedule->update(['tanggal_berikutnya' => $nextDue->toDateString()]);
                $due = $nextDue;
            }

            // Buat log Terjadwal untuk siklus saat ini / berikutnya
            $isPast = $due->lt($today);
            $status = $isPast ? 'Terlambat' : 'Terjadwal';
            $tech = $schedule->teknisi_id ? User::find($schedule->teknisi_id) : null;
            $techNama = $tech ? $tech->name : ($schedule->teknisi_nama ?: 'Belum ada');

            $kodePerangkat = $schedule->kode_perangkat;
            if (empty($kodePerangkat)) {
                $matchedAsset = Asset::where('nama_aset', $schedule->nama_aset)->first();
                $kodePerangkat = $matchedAsset ? $matchedAsset->kode_aset : ('AST-' . strtoupper(Str::random(5)));
            }

            $freshChecklist = [];
            if (!empty($schedule->checklist) && is_array($schedule->checklist)) {
                foreach ($schedule->checklist as $item) {
                    $lbl = is_array($item) ? ($item['item'] ?? '') : (string) $item;
                    if (trim($lbl) !== '') {
                        $freshChecklist[] = ['item' => trim($lbl), 'checked' => false];
                    }
                }
            }

            MaintenanceLog::create([
                'log_number' => MaintenanceLog::generateLogNumber($due),
                'tanggal_cek' => $due->toDateString(),
                'jam_mulai' => $schedule->jam_mulai ?: '08:00',
                'periode_bulan' => $due->format('Y-m'),
                'nama_perangkat' => $schedule->nama_aset,
                'lokasi_ruangan' => $schedule->lokasi_ruangan,
                'kode_perangkat' => $kodePerangkat,
                'jenis_perangkat' => 'Server / UPS',
                'divisi' => 'Operasional PLTU',
                'user_id' => $tech?->id,
                'teknisi_nama' => $techNama,
                'tim' => $schedule->tim ?: 'Tim IT Lapangan',
                'kegiatan' => $schedule->kegiatan ?: 'Pemeriksaan Rutin Berkala',
                'jenis_pemeliharaan' => 'Rutin',
                'checklist' => !empty($freshChecklist) ? $freshChecklist : null,
                'status' => $status,
                'kondisi' => 'Prima',
                'tindakan' => "Dijadwalkan otomatis ({$schedule->frekuensi}) dari Master Jadwal Rutin",
                'catatan_rekomendasi' => $schedule->catatan_teknisi,
                'status_tindak_lanjut' => 'Selesai',
                'jadwal_id' => $schedule->id,
            ]);

            $createdCount++;
        }

        return $createdCount;
    }

    /**
     * Manual Trigger Sinkronisasi Jadwal Pemeliharaan via Tombol.
     */
    public function syncSchedules(Request $request)
    {
        $created = self::syncSchedulesToLogs();
        $msg = $created > 0
            ? "{$created} tugas pemeliharaan rutin berhasil disinkronkan otomatis ke daftar kerja teknisi!"
            : "Semua jadwal pemeliharaan rutin aktif sudah tersinkronisasi di daftar kerja teknisi.";

        return redirect()->back()->with('success', $msg);
    }
}
