@extends('layouts.app')

@section('content')
@php
    $tglStr = $maintenanceLog->tanggal_cek ? $maintenanceLog->tanggal_cek->format('d/m/Y') : '-';
    $hasStart = !empty($maintenanceLog->jam_mulai);
    $hasEnd = !empty($maintenanceLog->jam_selesai);

    $fallbackTime = ($maintenanceLog->updated_at ?: $maintenanceLog->created_at)?->format('H.i');
    $jamMulaiDisp = $hasStart ? str_replace(':', '.', substr($maintenanceLog->jam_mulai, 0, 5)) : ($fallbackTime ?: '—');
    $jamSelesaiDisp = $hasEnd ? str_replace(':', '.', substr($maintenanceLog->jam_selesai, 0, 5)) : ($fallbackTime ?: '—');

    // Hitung durasi tampilan hanya dari jam riil
    $durasiDisp = $maintenanceLog->durasi_jam;
    if (empty($durasiDisp) && $hasStart && $hasEnd) {
        try {
            $t1 = \Carbon\Carbon::createFromFormat('H:i', str_replace('.', ':', substr($maintenanceLog->jam_mulai, 0, 5)));
            $t2 = \Carbon\Carbon::createFromFormat('H:i', str_replace('.', ':', substr($maintenanceLog->jam_selesai, 0, 5)));
            $mins = (int) abs($t2->diffInMinutes($t1));
            if ($mins < 60) {
                $durasiDisp = "{$mins} menit";
            } else {
                $h = (int) floor($mins / 60);
                $m = $mins % 60;
                $durasiDisp = $m > 0 ? "{$h} jam {$m} menit" : "{$h} jam";
            }
        } catch (\Exception $e) {
            $durasiDisp = null;
        }
    }

    if ($hasStart && $hasEnd) {
        $jamRangeText = "{$jamMulaiDisp} - {$jamSelesaiDisp} WIB" . ($durasiDisp ? " ({$durasiDisp})" : '');
    } elseif ($hasStart) {
        $jamRangeText = "{$jamMulaiDisp} WIB" . ($maintenanceLog->isSelesai() ? '' : ' (Jadwal)');
    } elseif ($fallbackTime) {
        $jamRangeText = "{$fallbackTime} WIB";
    } else {
        $jamRangeText = '—';
    }

    $jenisRaw = $maintenanceLog->jenis_pemeliharaan ?: 'Rutin';
    $jenisDisp = strtolower($jenisRaw) === 'rutin' ? 'Pemeliharaan rutin' : $jenisRaw;

    // Checklist / Kegiatan yang dikerjakan
    $activities = [];
    if (is_array($maintenanceLog->checklist) && count($maintenanceLog->checklist) > 0) {
        foreach ($maintenanceLog->checklist as $chk) {
            if (is_array($chk) && !empty($chk['item'])) {
                $activities[] = [
                    'item' => trim($chk['item']),
                    'checked' => array_key_exists('checked', $chk) ? (bool) $chk['checked'] : true,
                ];
            } elseif (is_string($chk) && trim($chk) !== '') {
                $activities[] = ['item' => trim($chk), 'checked' => true];
            }
        }
    }
    if (empty($activities)) {
        $rawKeg = $maintenanceLog->display_kegiatan;
        foreach (array_filter(array_map('trim', explode(',', $rawKeg))) as $part) {
            $activities[] = ['item' => ucfirst($part), 'checked' => true];
        }
    }

    // Kondisi badge sesuai mockup
    if ($maintenanceLog->isPrima()) {
        $kondisiLabel = 'Baik';
        $kondisiClass = 'bg-emerald-50 text-emerald-700 border-emerald-200/70';
    } elseif ($maintenanceLog->isPerluPerhatian()) {
        $kondisiLabel = 'Perlu tindak lanjut';
        $kondisiClass = 'bg-amber-50 text-amber-700 border-amber-200/70';
    } else {
        $kondisiLabel = 'Rusak';
        $kondisiClass = 'bg-rose-50 text-rose-700 border-rose-200/70';
    }

    // Jadwal berikutnya
    $nextScheduleDate = $relatedSchedule?->tanggal_berikutnya
        ? $relatedSchedule->tanggal_berikutnya->format('d/m/Y')
        : ($maintenanceLog->tanggal_cek ? $maintenanceLog->tanggal_cek->copy()->addMonth()->format('d/m/Y') : now()->addMonth()->format('d/m/Y'));
    $nextFrekuensi = $relatedSchedule?->frekuensi ?: 'Setiap bulan';

    // Tanggal dijadwalkan untuk timeline Riwayat
    $scheduledAtStr = $maintenanceLog->created_at
        ? $maintenanceLog->created_at->format('d/m/Y')
        : ($maintenanceLog->tanggal_cek ? $maintenanceLog->tanggal_cek->copy()->subDays(14)->format('d/m/Y') : '-');

    // Foto sebelum & sesudah
    $fotoSebelum = $maintenanceLog->foto_sebelum ?: $maintenanceLog->foto_dokumentasi;
    $fotoSesudah = $maintenanceLog->foto_sesudah;
@endphp

<div class="space-y-6">

    <!-- Flash Notification -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-xs print:hidden">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- Top Header Sesuai Mockup Referensi Detail Log Pemeliharaan -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Detail Log Pemeliharaan</h1>
            <nav class="flex flex-wrap items-center space-x-1.5 text-xs text-slate-400 mt-1">
                <a href="{{ route('maintenance.index') }}" class="hover:text-teal-700 transition font-medium">Log Pemeliharaan</a>
                <span>&rsaquo;</span>
                <span class="text-slate-600 font-semibold">{{ $maintenanceLog->nama_perangkat }}</span>
                <span>&bull;</span>
                <span class="text-slate-500 font-medium">{{ $tglStr }}</span>
                <span class="sr-only">{{ $maintenanceLog->log_number }}</span>
            </nav>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 print:hidden">
            <!-- Tombol Cetak / PDF Resmi -->
            <a href="{{ route('maintenance.cetakSingle', $maintenanceLog) }}" target="_blank"
               class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs">
                <i class="fa-solid fa-print text-slate-500"></i>
                <span>Cetak / PDF</span>
            </a>

            <!-- Tombol Kembali -->
            <a href="{{ route('maintenance.index') }}"
               class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs">
                Kembali
            </a>

            <!-- Widget Tanggal & Lonceng Notifikasi -->
            <div class="flex items-center space-x-2">
                @include('partials.header-widget')
            </div>
        </div>
    </div>

    <!-- Layout 2 Kolom Sesuai Mockup -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        <!-- KOLOM KIRI (2 SPAN) -->
        <div class="lg:col-span-2 space-y-5">

            <!-- KARTU 1: INFORMASI ASET & PELAKSANAAN -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
                <!-- Judul Aset + Kode + Status Badge -->
                <div class="flex items-start justify-between gap-3 pb-4 border-b border-slate-100">
                    <div class="flex flex-wrap items-baseline gap-1.5">
                        <h2 class="text-base font-extrabold text-slate-900 tracking-tight">{{ $maintenanceLog->nama_perangkat }}</h2>
                        @if($maintenanceLog->kode_perangkat)
                            <span class="text-xs font-mono font-semibold text-slate-400">({{ $maintenanceLog->kode_perangkat }})</span>
                        @endif
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold border shrink-0 {{ $maintenanceLog->status_badge_class }}">
                        {{ $maintenanceLog->computed_status }}
                    </span>
                </div>

                <!-- Baris Detail Key-Value -->
                <div class="divide-y divide-slate-100 text-xs">
                    <div class="py-3 flex items-center justify-between gap-4">
                        <span class="text-slate-400 font-medium">Lokasi</span>
                        <span class="font-semibold text-slate-800 text-right">{{ $maintenanceLog->lokasi_ruangan }}</span>
                    </div>

                    <div class="py-3 flex items-center justify-between gap-4">
                        <span class="text-slate-400 font-medium">Tanggal</span>
                        <span class="font-semibold text-slate-800 text-right">{{ $tglStr }}</span>
                    </div>

                    <div class="py-3 flex items-center justify-between gap-4">
                        <span class="text-slate-400 font-medium">Jam</span>
                        <span class="font-semibold text-slate-800 text-right">{{ $jamRangeText }}</span>
                    </div>

                    <div class="py-3 flex items-center justify-between gap-4">
                        <span class="text-slate-400 font-medium">Jenis</span>
                        <span class="font-semibold text-slate-800 text-right">{{ $jenisDisp }}</span>
                    </div>

                    <div class="py-3 flex items-start justify-between gap-4">
                        <span class="text-slate-400 font-medium pt-0.5">Teknisi</span>
                        <div class="text-right">
                            @if($maintenanceLog->teknisi_nama && $maintenanceLog->teknisi_nama !== 'Belum ada')
                                <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200/70">
                                    <i class="fa-regular fa-user text-[10px] text-teal-600"></i>
                                    <span>{{ $maintenanceLog->teknisi_nama }}</span>
                                </div>
                            @else
                                <span class="text-slate-400 italic">Belum ada</span>
                            @endif
                            <div class="text-[11px] text-slate-400 font-medium mt-0.5">{{ $maintenanceLog->tim_label }}</div>
                            @if(!empty($coTechNames))
                                <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                                    Pendamping: {{ implode(', ', $coTechNames) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="py-3 flex items-center justify-between gap-4">
                        <span class="text-slate-400 font-medium">Dijadwalkan oleh</span>
                        <span class="font-semibold text-slate-800 text-right">{{ $scheduledByLabel }}</span>
                    </div>
                </div>
            </div>

            <!-- KARTU 2: KEGIATAN YANG DIKERJAKAN -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-3">
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Kegiatan yang dikerjakan</h3>

                <div class="divide-y divide-slate-100">
                    @foreach($activities as $act)
                        <div class="py-3 flex items-center space-x-3">
                            @if($act['checked'])
                                <span class="w-4 h-4 rounded bg-[#0f766e] text-white flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-check text-[9px]"></i>
                                </span>
                                <span class="text-xs font-medium text-slate-800">{{ $act['item'] }}</span>
                            @else
                                <span class="w-4 h-4 rounded border-2 border-slate-300 bg-white shrink-0"></span>
                                <span class="text-xs font-medium text-slate-400">{{ $act['item'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- KARTU 3: HASIL & DOKUMENTASI FOTO -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Hasil</h3>

                <div class="flex items-center space-x-2.5">
                    <span class="text-xs text-slate-500 font-medium">Kondisi setelah pemeliharaan</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $kondisiClass }}">
                        {{ $kondisiLabel }}
                    </span>
                </div>

                @if($maintenanceLog->tindakan)
                    <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">{{ $maintenanceLog->tindakan }}</p>
                @endif

                @if($maintenanceLog->catatan_rekomendasi && trim($maintenanceLog->catatan_rekomendasi) !== trim((string) $maintenanceLog->tindakan))
                    <p class="text-xs text-slate-500 leading-relaxed whitespace-pre-line pt-1 border-t border-slate-100">
                        <span class="font-semibold text-slate-600">Catatan tambahan:</span> {{ $maintenanceLog->catatan_rekomendasi }}
                    </p>
                @endif

                <!-- Foto Sebelum & Foto Sesudah -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <!-- Box Foto Sebelum -->
                    @if($fotoSebelum)
                        <a href="{{ asset('storage/' . $fotoSebelum) }}" target="_blank"
                           class="group relative block rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 h-36">
                            <img src="{{ asset('storage/' . $fotoSebelum) }}" alt="Foto sebelum"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            <span class="absolute bottom-2 left-2 px-2.5 py-0.5 rounded-lg bg-slate-900/75 text-white text-[11px] font-semibold">
                                Foto sebelum
                            </span>
                        </a>
                    @else
                        <div class="border-2 border-dashed border-slate-200 rounded-2xl h-28 flex items-center justify-center bg-slate-50/30">
                            <span class="text-xs font-semibold text-slate-400">Foto sebelum</span>
                        </div>
                    @endif

                    <!-- Box Foto Sesudah -->
                    @if($fotoSesudah)
                        <a href="{{ asset('storage/' . $fotoSesudah) }}" target="_blank"
                           class="group relative block rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 h-36">
                            <img src="{{ asset('storage/' . $fotoSesudah) }}" alt="Foto sesudah"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            <span class="absolute bottom-2 left-2 px-2.5 py-0.5 rounded-lg bg-slate-900/75 text-white text-[11px] font-semibold">
                                Foto sesudah
                            </span>
                        </a>
                    @else
                        <div class="border-2 border-dashed border-slate-200 rounded-2xl h-28 flex items-center justify-center bg-slate-50/30">
                            <span class="text-xs font-semibold text-slate-400">Foto sesudah</span>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- KOLOM KANAN (1 SPAN) -->
        <div class="lg:col-span-1 space-y-4 lg:sticky lg:top-6">

            <!-- KARTU 1: RIWAYAT TIMELINE -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Riwayat</h3>

                <div class="border-l-2 border-slate-200 ml-1 pl-4 space-y-4">
                    <div>
                        <div class="text-xs font-bold text-slate-900">Dijadwalkan</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">
                            Admin &bull; {{ $scheduledAtStr }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold text-slate-900">Dikerjakan</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">
                            @if($maintenanceLog->isSelesai())
                                {{ $maintenanceLog->teknisi_nama ?: 'Teknisi IT' }} &bull; {{ $tglStr }} {{ $jamMulaiDisp }}
                            @else
                                Menunggu pelaksanaan teknisi
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold text-slate-900">
                            {{ $maintenanceLog->isSelesai() ? 'Selesai' : $maintenanceLog->computed_status }}
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">
                            @if($maintenanceLog->isSelesai())
                                {{ $tglStr }} {{ $jamSelesaiDisp }}
                            @else
                                Target jadwal {{ $tglStr }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- KARTU 2: JADWAL BERIKUTNYA -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3">
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Jadwal berikutnya</h3>

                <div class="flex items-center justify-between pt-1">
                    <span class="text-xs font-semibold text-slate-700">{{ $nextScheduleDate }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200/70">
                        Terjadwal
                    </span>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        Dibuat otomatis dari jadwal rutin {{ $nextFrekuensi }}.
                    </p>
                </div>
            </div>

            <!-- KARTU 3: TINDAK LANJUT -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3">
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Tindak lanjut</h3>

                @if($maintenanceLog->service)
                    <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200/70 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-mono font-bold text-slate-900">{{ $maintenanceLog->service->ticket_number }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                {{ $maintenanceLog->service->status }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 line-clamp-2">{{ $maintenanceLog->service->display_judul }}</p>
                        <a href="{{ route('services.show', $maintenanceLog->service) }}"
                           class="inline-flex items-center space-x-1 text-xs font-bold text-teal-700 hover:text-teal-900 hover:underline">
                            <span>Buka tiket tindak lanjut</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                @elseif($maintenanceLog->isPerluPerhatian() || $maintenanceLog->isKritis())
                    <div class="p-3 rounded-xl {{ $maintenanceLog->isKritis() ? 'bg-rose-50/70 border-rose-200/70 text-rose-800' : 'bg-amber-50/70 border-amber-200/70 text-amber-800' }} border text-[11px] leading-relaxed">
                        Aset ini memerlukan tindak lanjut namun belum dibuatkan tiket perbaikan.
                    </div>

                    <div class="space-y-2 pt-1 print:hidden">
                        <!-- Tombol 1: Buat Tiket Perbaikan (Admin & Teknisi) -->
                        <form method="POST" action="{{ route('maintenance.followUpTicket', $maintenanceLog) }}">
                            @csrf
                            <button type="submit"
                                    class="w-full py-2.5 px-4 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white text-xs font-extrabold transition shadow-xs cursor-pointer flex items-center justify-center space-x-1.5">
                                <i class="fa-solid fa-plus text-[10px]"></i>
                                <span>Buat Tiket Perbaikan</span>
                            </button>
                        </form>

                        @if(Auth::user()->isAdmin())
                            <!-- Tombol 2: Jadwalkan PM Tindak Lanjut (Khusus Admin) -->
                            <a href="{{ route('maintenance.jadwalkan.create', ['from_log' => $maintenanceLog->id]) }}"
                               class="w-full py-2.5 px-4 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition text-center flex items-center justify-center space-x-1.5">
                                <i class="fa-regular fa-calendar-plus text-[11px]"></i>
                                <span>Jadwalkan PM Tindak Lanjut</span>
                            </a>
                        @endif
                    </div>
                @else
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Tidak ada tiket tindak lanjut untuk log ini.
                    </p>
                @endif
            </div>

        </div>

    </div>

</div>

<style>
@media print {
    #sidebar, nav, .print\:hidden {
        display: none !important;
    }
    #main-content-wrapper {
        margin-left: 0 !important;
    }
    main {
        padding: 0 !important;
        max-width: 100% !important;
    }
}
</style>
@endsection
