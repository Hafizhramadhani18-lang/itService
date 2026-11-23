@extends('layouts.app')

@section('title', 'Rekap Laporan - Koordinator IT PLTU Karangkandri')

@section('content')
<div class="space-y-5">

    <!-- ============================================== -->
    <!-- HEADER HALAMAN SESUAI MOCKUP REFERENSI         -->
    <!-- ============================================== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2.5">
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                    Rekap Laporan
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                    Seluruh tim
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Ringkasan seluruh tiket dan laporan penanganan tim IT
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            <!-- Tombol Ekspor Excel -->
            <a href="{{ route('services.export', array_merge(request()->query(), ['filter' => 'report'])) }}" 
               class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs flex items-center space-x-1.5 cursor-pointer">
                <span>Ekspor Excel</span>
            </a>

            <!-- Tombol Cetak / PDF -->
            <a href="{{ route('services.cetak', array_merge(request()->query(), ['filter' => 'report'])) }}" target="_blank"
               class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs flex items-center space-x-1.5 cursor-pointer">
                <span>Cetak / PDF</span>
            </a>

            <!-- Header Date & Notification Widget -->
            @include('partials.header-widget')
        </div>
    </div>

    <!-- Alert Flash -->
    @include('partials.flash-messages')

    <!-- ============================================== -->
    <!-- FILTER BAR                                     -->
    <!-- ============================================== -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 sm:p-5">
        <form id="filterForm" action="{{ route('admin.rekap') }}" method="GET" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            
            <!-- Grup Filter Periode -->
            <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                <span class="text-xs font-bold text-slate-700 self-end mb-2 mr-1">
                    Periode
                </span>

                <!-- Tahun -->
                <div>
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">Tahun</label>
                    <div class="relative">
                        <select name="tahun" onchange="this.form.submit()" 
                                class="appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold rounded-lg px-3 py-1.5 pr-8 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition cursor-pointer">
                            <option value="all" {{ $tahun === 'all' ? 'selected' : '' }}>Semua tahun</option>
                            @foreach($distinctYears as $y)
                                <option value="{{ $y }}" {{ (string)$tahun === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Bulan -->
                <div>
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">Bulan</label>
                    <div class="relative">
                        <select name="bulan" onchange="this.form.submit()" 
                                class="appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold rounded-lg px-3 py-1.5 pr-8 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition cursor-pointer">
                            <option value="all" {{ $bulan === 'all' ? 'selected' : '' }}>Semua bulan</option>
                            @foreach($namaBulanIndo as $num => $nama)
                                <option value="{{ $num }}" {{ (string)$bulan === (string)$num ? 'selected' : '' }}>{{ $nama }}</option>
                            @endforeach
                        </select>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Minggu -->
                <div>
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">Minggu</label>
                    <div class="relative">
                        <select name="minggu" onchange="this.form.submit()" 
                                class="appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold rounded-lg px-3 py-1.5 pr-8 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition cursor-pointer">
                            <option value="all" {{ $minggu === 'all' ? 'selected' : '' }}>Semua minggu</option>
                            <option value="1" {{ $minggu === '1' ? 'selected' : '' }}>Minggu 1 </option>
                            <option value="2" {{ $minggu === '2' ? 'selected' : '' }}>Minggu 2 </option>
                            <option value="3" {{ $minggu === '3' ? 'selected' : '' }}>Minggu 3 </option>
                            <option value="4" {{ $minggu === '4' ? 'selected' : '' }}>Minggu 4 </option>
                           
                        </select>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <!-- Grup Filter Divisi & Teknisi -->
            <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                <!-- Divisi -->
                <div>
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">Divisi</label>
                    <div class="relative">
                        <select name="divisi" onchange="this.form.submit()" 
                                class="appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold rounded-lg px-3 py-1.5 pr-8 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition cursor-pointer min-w-[140px]">
                            <option value="all" {{ $divisi === 'all' ? 'selected' : '' }}>Semua divisi</option>
                            @foreach($masterDivisions as $d)
                                <option value="{{ $d->nama }}" {{ $divisi === $d->nama ? 'selected' : '' }}>{{ $d->nama }}</option>
                            @endforeach
                        </select>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Teknisi -->
                <div>
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">Teknisi</label>
                    <div class="relative">
                        <select name="teknisi" onchange="this.form.submit()" 
                                class="appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold rounded-lg px-3 py-1.5 pr-8 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition cursor-pointer min-w-[140px]">
                            <option value="all" {{ $teknisi === 'all' ? 'selected' : '' }}>Semua teknisi</option>
                            @foreach($masterTeknisi as $t)
                                <option value="{{ $t->name }}" {{ $teknisi === $t->name ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>

                @if($tahun !== 'all' || $bulan !== 'all' || $minggu !== 'all' || $divisi !== 'all' || $teknisi !== 'all')
                <div class="self-end mb-1">
                    <a href="{{ route('admin.rekap') }}" class="text-[11px] text-rose-500 hover:text-rose-700 font-semibold underline">
                        Reset Filter
                    </a>
                </div>
                @endif
            </div>

        </form>
    </div>

    <!-- ============================================== -->
    <!-- 4 KARTU METRIK UTAMA                           -->
    <!-- ============================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- Kartu 1: Tiket Masuk -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">Tiket masuk</span>
                    <span class="w-7 h-7 rounded-lg bg-blue-500 text-white flex items-center justify-center text-xs shadow-xs">
                        <i class="fa-solid fa-inbox"></i>
                    </span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">
                    {{ $tiketMasuk }}
                </div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-400 mt-3 pt-2 border-t border-slate-50">
                <span>{{ Str::startsWith($periodeLabel, ['Semua', 'Periode', 'Tahun', 'Bulan']) ? $periodeLabel : 'Periode ' . $periodeLabel }}</span>
                <i class="fa-solid fa-arrow-right text-[10px] text-blue-500"></i>
            </div>
        </div>

        <!-- Kartu 2: Tiket Selesai -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">Tiket selesai</span>
                    <span class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-xs shadow-xs">
                        <i class="fa-solid fa-check"></i>
                    </span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">
                    {{ $tiketSelesai }}
                </div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-400 mt-3 pt-2 border-t border-slate-50">
                <span>Sudah ada laporan</span>
                <i class="fa-solid fa-arrow-right text-[10px] text-emerald-500"></i>
            </div>
        </div>

        <!-- Kartu 3: Masih Terbuka -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">Masih terbuka</span>
                    <span class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs shadow-xs">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">
                    {{ $masihTerbuka }}
                </div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-400 mt-3 pt-2 border-t border-slate-50">
                <span>{{ $belumDiambil }} belum diambil</span>
                <i class="fa-solid fa-arrow-right text-[10px] text-amber-500"></i>
            </div>
        </div>

        <!-- Kartu 4: Rata-rata Waktu Penanganan -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">Rata-rata waktu penanganan</span>
                    <span class="w-7 h-7 rounded-lg bg-teal-700 text-white flex items-center justify-center text-xs shadow-xs">
                        <i class="fa-regular fa-calendar-check"></i>
                    </span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">
                    {{ $avgHours }}
                </div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-400 mt-3 pt-2 border-t border-slate-50">
                <span>Dari diambil sampai selesai</span>
                <i class="fa-solid fa-arrow-right text-[10px] text-teal-600"></i>
            </div>
        </div>

    </div>

    <!-- ============================================== -->
    <!-- 2 KOLOM GRID: GRAFIK, KINERJA, DIVISI, KATEGORI -->
    <!-- ============================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        <!-- ============================================== -->
        <!-- KOLOM KIRI (7/12): GRAFIK & KINERJA TEKNISI    -->
        <!-- ============================================== -->
        <div class="lg:col-span-7 space-y-5">

            <!-- Card A: Tiket Masuk dan Selesai per Hari (Live 7 Hari / 1 Minggu) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-sm font-extrabold text-slate-900">
                                Tiket masuk dan selesai per hari
                            </h3>
                            <span class="inline-flex items-center space-x-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60" title="Rentang 7 hari (1 minggu) berurutan dan diperbarui setiap hari">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Live 7 Hari</span>
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                            Rentang 7 hari (1 minggu) berurutan &bull; Otomatis sinkron dengan hari dan jam sistem
                        </p>
                    </div>

                    <div class="flex items-center space-x-4 text-xs flex-shrink-0">
                        <div class="flex items-center space-x-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                            <span class="text-slate-500 font-medium text-[11px]">Masuk</span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-700"></span>
                            <span class="text-slate-500 font-medium text-[11px]">Selesai</span>
                        </div>
                    </div>
                </div>

                <!-- Visual Bar Chart (7 Hari Proporsional) -->
                <div class="pt-4 pb-2">
                    <div class="h-48 flex items-end justify-between gap-1 sm:gap-3 px-1 border-b border-slate-100">
                        @foreach($dailyData as $day)
                            @php
                                $maxVal = max($maxDailyCount, 1);
                                $heightMasuk = ($day['masuk'] > 0) ? max(14, round(($day['masuk'] / $maxVal) * 120)) : 4;
                                $heightSelesai = ($day['selesai'] > 0) ? max(14, round(($day['selesai'] / $maxVal) * 120)) : 4;
                                $isToday = !empty($day['is_today']);
                            @endphp
                            <div class="flex flex-col items-center flex-1 transition-all {{ $isToday ? 'bg-teal-50/70 border border-teal-200/60 rounded-xl px-1 py-1.5 shadow-xs' : 'py-1.5' }}">
                                <div class="flex items-end justify-center space-x-1 sm:space-x-1.5 w-full h-34">
                                    <!-- Bar Masuk -->
                                    <div class="flex flex-col items-center justify-end group">
                                        <span class="text-[11px] font-bold text-slate-600 mb-1 {{ $day['masuk'] == 0 ? 'text-slate-300' : '' }}">
                                            {{ $day['masuk'] }}
                                        </span>
                                        <div class="w-4 sm:w-6 bg-slate-400 rounded-t-sm transition-all duration-300 {{ $day['masuk'] == 0 ? 'opacity-20' : '' }}" 
                                             style="height: {{ $heightMasuk }}px;"
                                             title="{{ $day['nama_hari'] ?? '' }}, {{ $day['label'] }} - Masuk: {{ $day['masuk'] }} tiket"></div>
                                    </div>

                                    <!-- Bar Selesai -->
                                    <div class="flex flex-col items-center justify-end group">
                                        <span class="text-[11px] font-bold text-teal-800 mb-1 {{ $day['selesai'] == 0 ? 'text-teal-300' : '' }}">
                                            {{ $day['selesai'] }}
                                        </span>
                                        <div class="w-4 sm:w-6 bg-teal-700 rounded-t-sm transition-all duration-300 {{ $day['selesai'] == 0 ? 'opacity-20' : '' }}" 
                                             style="height: {{ $heightSelesai }}px;"
                                             title="{{ $day['nama_hari'] ?? '' }}, {{ $day['label'] }} - Selesai: {{ $day['selesai'] }} tiket"></div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-center mt-2">
                                    @if(!empty($day['nama_hari']))
                                        <span class="text-[10px] font-semibold leading-tight {{ $isToday ? 'text-teal-700' : 'text-slate-400' }}">
                                            {{ $day['nama_hari'] }}
                                        </span>
                                    @endif
                                    <span class="text-[11px] font-bold whitespace-nowrap leading-tight {{ $isToday ? 'text-teal-900 font-extrabold' : 'text-slate-700' }}">
                                        {{ $day['label'] }}
                                    </span>
                                </div>
                                @if($isToday)
                                    <span class="text-[9px] font-extrabold text-teal-700 bg-teal-100 px-1.5 py-0.2 rounded-full mt-1 whitespace-nowrap">
                                        Hari ini
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Card B: Daftar Laporan (Sesuai Referensi Rekap Laporan Admin) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-2">
                        <h3 class="text-sm font-extrabold text-slate-900">
                            Daftar laporan
                        </h3>
                        <span class="sr-only">Kinerja teknisi</span>
                    </div>
                    @php
                        $totalLaporanCount = $daftarLaporan->count();
                        $displayedCount = min($totalLaporanCount, 5);
                    @endphp
                    <span class="text-xs text-slate-400 font-medium">
                        @if($totalLaporanCount > 0)
                            Menampilkan {{ $displayedCount }} dari {{ $totalLaporanCount }} laporan
                        @else
                            0 laporan
                        @endif
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-[11px] font-bold text-slate-400 border-b border-slate-100">
                                <th class="pb-2.5 font-bold">No.</th>
                                <th class="pb-2.5 font-bold">Kendala &amp; Lokasi</th>
                                <th class="pb-2.5 font-bold">Teknisi</th>
                                <th class="pb-2.5 font-bold">Durasi</th>
                                <th class="pb-2.5 font-bold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($daftarLaporan->take(5) as $laporan)
                            @php
                                $durationStr = $laporan->formatted_durasi;
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 font-bold text-slate-800 whitespace-nowrap">
                                    <a href="{{ route('services.show', $laporan) }}" class="hover:text-teal-700">
                                        {{ $laporan->ticket_number }}
                                    </a>
                                </td>
                                <td class="py-3.5 pr-2">
                                    <div class="font-bold text-slate-800 text-xs line-clamp-1" title="{{ $laporan->keluhan }}">
                                        {{ Str::limit($laporan->keluhan, 35) }}
                                    </div>
                                    <div class="text-slate-400 text-[11px] mt-0.5 truncate max-w-[200px]" title="{{ $laporan->lokasi_ruangan }}">
                                        {{ $laporan->lokasi_ruangan }}
                                    </div>
                                </td>
                                <td class="py-3.5 whitespace-nowrap">
                                    @if($laporan->teknisi_nama)
                                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full bg-teal-50 border border-teal-200/70 text-teal-800 text-xs font-semibold">
                                            <i class="fa-solid fa-user text-[10px] text-teal-600"></i>
                                            <span>{{ $laporan->teknisi_nama }}</span>
                                        </div>
                                    @elseif($laporan->teknisi)
                                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full bg-teal-50 border border-teal-200/70 text-teal-800 text-xs font-semibold">
                                            <i class="fa-solid fa-user text-[10px] text-teal-600"></i>
                                            <span>{{ $laporan->teknisi->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">Belum ada</span>
                                    @endif
                                </td>
                                <td class="py-3.5 font-semibold text-slate-700 whitespace-nowrap">
                                    {{ $durationStr }}
                                </td>
                                <td class="py-3.5 text-center whitespace-nowrap">
                                    <a href="{{ route('services.show', $laporan) }}" 
                                       class="text-teal-700 hover:text-teal-900 font-bold hover:underline text-xs">
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                    Belum ada laporan selesai pada periode ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($totalLaporanCount > 5)
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400 text-[11px]">Ada {{ $totalLaporanCount - 5 }} laporan lainnya pada periode ini</span>
                    <a href="{{ route('services.index', ['filter' => 'history']) }}" class="text-teal-700 hover:text-teal-900 font-semibold hover:underline flex items-center space-x-1">
                        <span>Buka Riwayat Tiket</span>
                        <span>&rarr;</span>
                    </a>
                </div>
                @endif
            </div>

        </div>

        <!-- ============================================== -->
        <!-- KOLOM KANAN (5/12): TIKET PER DIVISI & KATEGORI-->
        <!-- ============================================== -->
        <div class="lg:col-span-5 space-y-5">

            <!-- Card C: Tiket per Divisi -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
                <h3 class="text-sm font-extrabold text-slate-900 mb-4">
                    Tiket per divisi
                </h3>

                <div class="space-y-3.5">
                    @forelse($divisiGroup as $div)
                        @php
                            $pctDiv = round(($div['count'] / max($maxDivisiCount, 1)) * 100);
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                <span class="text-slate-800 truncate pr-2">{{ $div['nama'] }}</span>
                                <span class="text-slate-900 flex-shrink-0">{{ $div['count'] }}</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-teal-700 h-1.5 rounded-full transition-all duration-300" style="width: {{ $pctDiv }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-slate-400 text-xs py-4 text-center">Belum ada data divisi.</p>
                    @endforelse
                </div>
            </div>

            <!-- Card D: Tiket per Kategori -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
                <h3 class="text-sm font-extrabold text-slate-900 mb-4">
                    Tiket per kategori
                </h3>

                <div class="space-y-3.5">
                    @forelse($kategoriGroup as $kat)
                        @php
                            $pctKat = round(($kat['count'] / max($maxKategoriCount, 1)) * 100);
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                <span class="text-slate-800 truncate pr-2">{{ $kat['nama'] }}</span>
                                <span class="text-slate-900 flex-shrink-0">{{ $kat['count'] }}</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-teal-700 h-1.5 rounded-full transition-all duration-300" style="width: {{ $pctKat }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-slate-400 text-xs py-4 text-center">Belum ada data kategori.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
