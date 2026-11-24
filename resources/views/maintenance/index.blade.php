@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Flash Notification -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-xs animate-fade-in">
            <div class="flex items-center space-x-2.5">
                <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-check text-xs"></i>
                </div>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- Top Header Sesuai Mockup Referensi Admin & Teknisi -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Log Pemeliharaan</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                @if(Auth::user()->isTeknisi())
                    Catatan pemeliharaan rutin perangkat dan jaringan, sinkron dengan admin
                @else
                    Pantau jadwal dan hasil pemeliharaan seluruh tim, sinkron dengan teknisi
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Tombol Cetak / PDF Laporan PM Resmi (Admin & Teknisi) -->
            <a href="{{ route('maintenance.cetak', request()->query()) }}" target="_blank"
               class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold transition shadow-2xs">
                <i class="fa-solid fa-print text-slate-500"></i>
                <span>Cetak / PDF</span>
            </a>

            <!-- Tombol Sinkronkan Jadwal Otomatis (Rutin Harian, Mingguan, Bulanan, Tahunan) -->
            <form method="POST" action="{{ route('maintenance.syncSchedules') }}" class="inline">
                @csrf
                <button type="submit" 
                        class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer"
                        title="Otomatis sinkronkan jadwal rutin menjadi log terjadwal di daftar kerja teknisi">
                    <i class="fa-solid fa-arrows-rotate text-teal-600 text-xs"></i>
                    <span class="hidden sm:inline">Sinkronkan Jadwal</span>
                </button>
            </form>

            @if(Auth::user()->isTeknisi())
                <!-- Tombol + Catat Pemeliharaan Khusus Teknisi -->
                <a href="{{ route('maintenance.create') }}" 
                   class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white text-xs font-extrabold transition shadow-xs">
                    <i class="fa-solid fa-plus text-[10px]"></i>
                    <span>Catat Pemeliharaan</span>
                </a>
            @else
                <!-- Ekspor Excel untuk Admin -->
                <a href="{{ route('maintenance.export', request()->all()) }}" 
                   class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs">
                    <span>Ekspor Excel</span>
                </a>

                <!-- Tombol + Jadwalkan untuk Admin (Halaman Form Jadwalkan Pemeliharaan) -->
                <a href="{{ route('maintenance.jadwalkan.create') }}" 
                   class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-[#0e1a2b] hover:bg-[#152438] text-white text-xs font-extrabold transition shadow-xs">
                    <i class="fa-solid fa-plus text-[10px]"></i>
                    <span>Jadwalkan</span>
                </a>
            @endif

            <!-- Widget Tanggal & Lonceng Notifikasi -->
            <div class="flex items-center space-x-2">
                @include('partials.header-widget')
            </div>
        </div>
    </div>

    @if(!Auth::user()->isAdmin())
    <!-- Tab Selector Khusus Teknisi: Saya vs Semua tim -->
    <div class="flex items-center space-x-2">
        <a href="{{ route('maintenance.index', array_merge(request()->except('tab'), ['tab' => 'my'])) }}" 
           class="inline-flex items-center space-x-1.5 px-4 py-1.5 rounded-full text-xs font-bold transition {{ $tab === 'my' ? 'bg-[#0e1a2b] text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-slate-600 hover:bg-slate-50' }}"
           title="Pemeliharaan Saya">
            <span>Saya</span>
            <span class="sr-only">Pemeliharaan Saya</span>
            <span class="text-[11px] font-semibold opacity-85">&bull; {{ $totalPMSaya }}</span>
        </a>
        <a href="{{ route('maintenance.index', array_merge(request()->except('tab'), ['tab' => 'all'])) }}" 
           class="inline-flex items-center space-x-1.5 px-4 py-1.5 rounded-full text-xs font-bold transition {{ $tab === 'all' ? 'bg-[#0e1a2b] text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-slate-600 hover:bg-slate-50' }}"
           title="Semua Tim IT">
            <span>Semua tim</span>
            <span class="sr-only">Semua Tim IT</span>
            <span class="text-[11px] font-semibold opacity-85">&bull; {{ $totalSemuaTim }}</span>
        </a>
    </div>
    @endif

    @if(Auth::user()->isTeknisi() && isset($myUpcomingSchedules))
    <!-- SEKSI AGENDA & JADWAL PM TEKNISI (PORSI PENUGASAN DARI ADMIN) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-[#0f766e] flex items-center justify-center text-lg shrink-0 border border-teal-100/70">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <h2 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Jadwal PM Anda (Porsi Penugasan)</h2>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $myUpcomingSchedules->isNotEmpty() ? 'bg-teal-50 text-teal-800 border border-teal-200' : 'bg-slate-100 text-slate-500' }}">
                            {{ $myUpcomingSchedules->count() }} Tugas
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Daftar pemeliharaan berkala yang dijadwalkan oleh Koordinator IT untuk Anda kerjakan.
                    </p>
                </div>
            </div>

            @if($myUpcomingSchedules->isNotEmpty())
            <a href="{{ route('maintenance.create') }}" 
               class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200/80 text-slate-700 text-xs font-bold transition self-start sm:self-auto border border-slate-200/70">
                <i class="fa-solid fa-list-check text-slate-500 text-xs"></i>
                <span>Buka Form Checklist</span>
            </a>
            @endif
        </div>

        @if($myUpcomingSchedules->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($myUpcomingSchedules as $sched)
                @php
                    $isToday = $sched->tanggal_cek ? $sched->tanggal_cek->isToday() : false;
                    $isLate = $sched->isTerlambat();
                    $dateText = $sched->tanggal_cek ? $sched->tanggal_cek->format('d M Y') : 'Jadwal Rutin';
                    $jamText = $sched->jam_mulai ? str_replace(':', '.', substr($sched->jam_mulai, 0, 5)) . ' WIB' : '09.00 WIB';
                    $checklistCount = is_array($sched->checklist) ? count($sched->checklist) : 0;
                @endphp
                <div class="p-4 rounded-xl border transition flex flex-col justify-between group {{ $isLate ? 'bg-rose-50/40 border-rose-200/80 hover:border-rose-300' : ($isToday ? 'bg-teal-50/30 border-teal-200/90 hover:border-teal-400' : 'bg-slate-50/50 border-slate-200/70 hover:border-slate-300') }}">
                    <div class="space-y-2.5">
                        <!-- Header Kartu: Status Jadwal & Waktu -->
                        <div class="flex items-center justify-between gap-2">
                            @if($isLate)
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200 inline-flex items-center">
                                    <i class="fa-regular fa-clock text-[9px] mr-1"></i> Terlambat
                                </span>
                            @elseif($isToday)
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center">
                                    <i class="fa-solid fa-calendar-day text-[9px] mr-1"></i> Hari Ini
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-white text-slate-600 border border-slate-200 inline-flex items-center shadow-2xs">
                                    <i class="fa-regular fa-calendar text-[9px] mr-1 text-slate-400"></i> Mendatang
                                </span>
                            @endif

                            <span class="text-[11px] font-mono font-bold text-slate-600">
                                {{ $dateText }} &bull; {{ $jamText }}
                            </span>
                        </div>

                        <!-- Info Aset & Lokasi -->
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 leading-snug group-hover:text-teal-800 transition line-clamp-1">
                                {{ $sched->nama_perangkat }}
                            </h3>
                            <div class="flex items-center space-x-1.5 text-[11px] text-slate-500 mt-1">
                                <i class="fa-solid fa-location-dot text-[10px] text-slate-400"></i>
                                <span class="line-clamp-1">{{ $sched->lokasi_ruangan }}</span>
                                @if($sched->kode_perangkat)
                                    <span class="text-slate-300">&bull;</span>
                                    <span class="font-mono text-slate-500">{{ $sched->kode_perangkat }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Kegiatan & Checklist Count -->
                        <div class="text-[11px] text-slate-600 bg-white/80 p-2.5 rounded-lg border border-slate-200/60 space-y-1">
                            <div class="font-semibold text-slate-800 line-clamp-1">
                                {{ $sched->display_kegiatan }}
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-slate-400 font-medium">
                                <span><i class="fa-solid fa-list-check mr-1 text-teal-600"></i>{{ $checklistCount > 0 ? $checklistCount . ' item checklist' : 'Pemeriksaan rutin' }}</span>
                                <span class="text-slate-500">{{ $sched->tim_label }}</span>
                            </div>
                            @if($sched->catatan_rekomendasi && $sched->catatan_rekomendasi !== $sched->tindakan)
                                <div class="text-[10px] text-amber-700 bg-amber-50/80 p-1.5 rounded border border-amber-200/50 mt-1 line-clamp-1" title="{{ $sched->catatan_rekomendasi }}">
                                    <i class="fa-solid fa-note-sticky mr-1"></i>{{ $sched->catatan_rekomendasi }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Footer Kartu: Tombol Mulai Pemeliharaan -->
                    <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex items-center justify-between">
                        <span class="text-[10px] font-medium text-slate-400">
                            {{ $sched->log_number }}
                        </span>
                        <a href="{{ route('maintenance.create', ['from_schedule' => $sched->id, 'kode_perangkat' => $sched->kode_perangkat]) }}" 
                           class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-[#0f766e] hover:bg-[#115e59] text-white text-xs font-bold transition shadow-2xs group-hover:shadow-xs"
                           title="Kerjakan dan isi checklist pemeliharaan">
                            <i class="fa-solid fa-clipboard-check text-[11px]"></i>
                            <span>Kerjakan / Isi Checklist</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
        @else
        <div class="py-6 text-center text-slate-400 bg-slate-50/50 rounded-xl border border-dashed border-slate-200 space-y-1">
            <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-base mx-auto">
                <i class="fa-solid fa-check"></i>
            </div>
            <div class="text-xs font-bold text-slate-700">Semua Porsi PM Anda Up-to-Date</div>
            <p class="text-[11px] text-slate-400 max-w-md mx-auto">
                Tidak ada jadwal pemeliharaan berkala yang tertunda atau terlambat untuk Anda saat ini.
            </p>
        </div>
        @endif
    </div>
    @endif

    <!-- Filter Bar Card Sesuai Mockup Referensi -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('maintenance.index') }}" id="form-filter-pm">
            <input type="hidden" name="tab" value="{{ $tab }}">
            
            <div class="flex flex-wrap items-center gap-2.5">
                
                <!-- Group Periode: Label + Tahun + Bulan + Minggu -->
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-800 pr-1 shrink-0">Periode</span>
                    
                    <!-- Tahun -->
                    <div class="w-32 shrink-0">
                        <select name="tahun" onchange="this.form.submit()" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                            <option value="all" {{ (string) $selectedTahun === 'all' ? 'selected' : '' }}>Semua tahun</option>
                            @foreach($availableYears as $yr)
                                <option value="{{ $yr }}" {{ (string) $selectedTahun === (string) $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Bulan -->
                    <div class="w-36 shrink-0">
                        <select name="bulan" onchange="this.form.submit()" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                            <option value="all" {{ (int) $selectedBulanNum === 0 ? 'selected' : '' }}>Semua bulan</option>
                            @foreach($monthNames as $num => $name)
                                <option value="{{ $name }}" {{ (int) $selectedBulanNum === (int) $num ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Minggu -->
                    <div class="w-36 shrink-0">
                        <select name="minggu" onchange="this.form.submit()" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                            <option value="all" {{ $selectedMinggu === 'all' ? 'selected' : '' }}>Semua minggu</option>
                            <option value="1" {{ $selectedMinggu === '1' ? 'selected' : '' }}>Minggu 1</option>
                            <option value="2" {{ $selectedMinggu === '2' ? 'selected' : '' }}>Minggu 2</option>
                            <option value="3" {{ $selectedMinggu === '3' ? 'selected' : '' }}>Minggu 3</option>
                            <option value="4" {{ $selectedMinggu === '4' ? 'selected' : '' }}>Minggu 4</option>
                            <option value="5" {{ $selectedMinggu === '5' ? 'selected' : '' }}>Minggu 5</option>
                        </select>
                    </div>
                </div>

                @if(Auth::user()->isAdmin())
                    <!-- Tim (Khusus Admin) -->
                    <div class="flex-1 min-w-[130px]">
                        <select name="tim" onchange="this.form.submit()" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                            <option value="all" {{ $selectedTim === 'all' ? 'selected' : '' }}>Semua tim</option>
                            <option value="Tim IT Lapangan" {{ $selectedTim === 'Tim IT Lapangan' ? 'selected' : '' }}>Tim IT Lapangan</option>
                            <option value="Tim IT Support" {{ $selectedTim === 'Tim IT Support' ? 'selected' : '' }}>Tim IT Support</option>
                        </select>
                    </div>

                    <!-- Teknisi (Khusus Admin) -->
                    <div class="flex-1 min-w-[140px]">
                        <select name="teknisi" onchange="this.form.submit()" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                            <option value="all" {{ $selectedTeknisi === 'all' ? 'selected' : '' }}>Semua teknisi</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->name }}" {{ (string) $selectedTeknisi === (string) $tech->id || $selectedTeknisi === $tech->name ? 'selected' : '' }}>
                                    {{ $tech->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Status -->
                <div class="{{ Auth::user()->isAdmin() ? 'flex-1 min-w-[130px]' : 'w-48 ml-auto' }}">
                    <select name="status" onchange="this.form.submit()" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                        <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua status</option>
                        <option value="Terjadwal" {{ $selectedStatus === 'Terjadwal' ? 'selected' : '' }}>Terjadwal</option>
                        <option value="Selesai" {{ $selectedStatus === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="Terlambat" {{ $selectedStatus === 'Terlambat' ? 'selected' : '' }}>Terlambat</option>
                        <option value="Perlu tindak lanjut" {{ $selectedStatus === 'Perlu tindak lanjut' ? 'selected' : '' }}>Perlu tindak lanjut</option>
                    </select>
                </div>

                <!-- Reset Filter jika ada filter aktif -->
                @if((string) $selectedTahun !== 'all' || (int) $selectedBulanNum !== 0 || $selectedMinggu !== 'all' || $selectedTim !== 'all' || $selectedTeknisi !== 'all' || $selectedStatus !== 'all' || request('q'))
                    <a href="{{ route('maintenance.index') }}" class="px-2.5 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs shrink-0" title="Reset Filter">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif

            </div>
        </form>
    </div>

    <!-- 4 KARTU METRIK PERSIS SESUAI MOCKUP REFERENSI ADMIN -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Terjadwal (Biru) -->
        <a href="{{ route('maintenance.index', array_merge(request()->all(), ['status' => 'Terjadwal'])) }}" 
           class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:border-sky-300 transition group flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        <span class="text-xs font-bold text-slate-700">Terjadwal</span>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
                <div class="text-3xl font-black text-slate-900 mt-2">{{ $countTerjadwal }}</div>
            </div>
            <div class="text-xs text-blue-600 font-bold mt-3 flex items-center justify-between">
                <span>Minggu ini</span>
                <span class="group-hover:translate-x-0.5 transition">&rarr;</span>
            </div>
        </a>

        <!-- Card 2: Selesai (Hijau) -->
        <a href="{{ route('maintenance.index', array_merge(request()->all(), ['status' => 'Selesai'])) }}" 
           class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:border-emerald-300 transition group flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-bold text-slate-700">Selesai</span>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-check"></i>
                    </div>
                </div>
                <div class="text-3xl font-black text-slate-900 mt-2">{{ $countSelesai }}</div>
            </div>
            <div class="text-xs text-emerald-600 font-bold mt-3 flex items-center justify-between">
                <span>
                    @if((string) $selectedTahun === 'all' && (int) $selectedBulanNum === 0)
                        Semua periode
                    @elseif((int) $selectedBulanNum === 0)
                        Tahun {{ $selectedTahun }}
                    @else
                        Bulan {{ $selectedBulanName }}{{ (string) $selectedTahun !== 'all' ? ' ' . $selectedTahun : '' }}
                    @endif
                </span>
                <span class="group-hover:translate-x-0.5 transition">&rarr;</span>
            </div>
        </a>

        <!-- Card 3: Terlambat (Merah / Rose) -->
        <a href="{{ route('maintenance.index', array_merge(request()->all(), ['status' => 'Terlambat'])) }}" 
           class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:border-rose-300 transition group flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        <span class="text-xs font-bold text-slate-700">Terlambat</span>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                </div>
                <div class="text-3xl font-black text-slate-900 mt-2">{{ $countTerlambat }}</div>
            </div>
            <div class="text-xs text-rose-600 font-bold mt-3 flex items-center justify-between">
                <span>Perlu segera dikerjakan</span>
                <span class="group-hover:translate-x-0.5 transition">&rarr;</span>
            </div>
        </a>

        <!-- Card 4: Perlu tindak lanjut (Kuning / Oranye) -->
        <a href="{{ route('maintenance.index', array_merge(request()->all(), ['status' => 'Perlu tindak lanjut'])) }}" 
           class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:border-amber-300 transition group flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <span class="text-xs font-bold text-slate-700">Perlu tindak lanjut</span>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-wrench"></i>
                    </div>
                </div>
                <div class="text-3xl font-black text-slate-900 mt-2">{{ $countPerluTindakLanjut }}</div>
            </div>
            <div class="text-xs text-amber-600 font-bold mt-3 flex items-center justify-between">
                <span>Dari log yang selesai</span>
                <span class="group-hover:translate-x-0.5 transition">&rarr;</span>
            </div>
        </a>

    </div>

    <!-- TABEL DATA LOG & JADWAL PEMELIHARAAN SESUAI MOCKUP -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-extrabold text-slate-500 tracking-wider">
                        <th class="py-3 px-4 font-bold">Tanggal</th>
                        <th class="py-3 px-4 font-bold">Aset & Lokasi</th>
                        <th class="py-3 px-4 font-bold">Kegiatan</th>
                        <th class="py-3 px-4 font-bold">Teknisi</th>
                        <th class="py-3 px-4 font-bold">Status</th>
                        <th class="py-3 px-4 font-bold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($logs as $log)
                        @php
                            $computedStatus = $log->computed_status;
                            $isLate = $log->isTerlambat();
                            $isDone = $log->isSelesai();
                            $isScheduled = $log->isTerjadwal();
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <!-- Tanggal & Jam -->
                            <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                <div class="text-slate-700 font-semibold">
                                    {{ $log->tanggal_cek ? $log->tanggal_cek->format('d/m/Y') : '-' }}
                                </div>
                                @if($log->formatted_jam)
                                    <div class="text-[11px] text-slate-400 font-medium mt-0.5">
                                        {{ $log->formatted_jam }}
                                    </div>
                                @endif
                            </td>

                            <!-- Aset & Lokasi -->
                            <td class="py-3.5 px-4 align-top">
                                <div class="font-extrabold text-slate-900 text-xs leading-snug">
                                    {{ $log->nama_perangkat }}
                                    @if($log->kode_perangkat && !str_contains($log->nama_perangkat, $log->kode_perangkat))
                                        <span class="text-[10px] font-mono text-slate-400 font-normal">({{ $log->kode_perangkat }})</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-400 font-medium mt-0.5">{{ $log->lokasi_ruangan }}</div>
                            </td>

                            <!-- Kegiatan & Catatan Status -->
                            <td class="py-3.5 px-4 align-top max-w-xs">
                                <div class="text-slate-800 font-medium text-xs line-clamp-1">
                                    {{ $log->display_kegiatan }}
                                </div>
                                <div class="text-[11px] mt-0.5">
                                    @if($isLate)
                                        <span class="text-rose-500 font-semibold">{{ $log->late_days_text ?: 'Lewat jadwal' }}</span>
                                    @elseif($isScheduled)
                                        <span class="text-slate-400 font-medium">Dijadwalkan</span>
                                    @elseif($isDone)
                                        @if($log->isPrima())
                                            <span class="text-slate-500 font-medium">Kondisi: Baik</span>
                                        @else
                                            <span class="text-amber-600 font-semibold">Kondisi: Perlu tindak lanjut</span>
                                        @endif
                                    @endif
                                </div>
                            </td>

                            <!-- Teknisi & Tim -->
                            @php
                                $authUser = Auth::user();
                                $isMainOwner = $authUser && (
                                    ((int) $log->user_id === (int) $authUser->id)
                                    || (!empty($log->teknisi_nama) && strcasecmp(trim($log->teknisi_nama), trim($authUser->name)) === 0)
                                );
                                $isOwnedByMe = $authUser && $log->isOwnedByTeknisi($authUser);
                                $isCoTechOnly = $isOwnedByMe && !$isMainOwner;
                                $isUnassignedSchedule = empty($log->user_id) || empty($log->teknisi_nama) || $log->teknisi_nama === 'Belum ada';
                            @endphp
                            <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                @if($log->teknisi_nama && $log->teknisi_nama !== 'Belum ada')
                                    <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200/70">
                                        <i class="fa-regular fa-user text-[10px] text-teal-600"></i>
                                        <span>{{ $log->teknisi_nama }}</span>
                                    </div>
                                    @if($isMainOwner)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-extrabold bg-teal-100 text-teal-800 border border-teal-300 ml-1">
                                            Anda
                                        </span>
                                    @elseif($isCoTechOnly)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-extrabold bg-sky-100 text-sky-800 border border-sky-200 ml-1" title="Anda tercatat sebagai teknisi pendamping">
                                            + Anda (Tim)
                                        </span>
                                    @endif
                                @else
                                    <div class="text-slate-400 italic text-xs">Belum ada</div>
                                @endif
                                <div class="text-[11px] text-slate-400 font-medium mt-0.5">{{ $log->tim_label }}</div>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold border {{ $log->status_badge_class }}">
                                    {{ $computedStatus }}
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 align-top text-center whitespace-nowrap">
                                @if($isScheduled || $isLate)
                                    @if($authUser->isTeknisi())
                                        @if($isOwnedByMe || $isUnassignedSchedule)
                                            <a href="{{ route('maintenance.create', ['from_schedule' => $log->id]) }}" 
                                               class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white text-xs font-bold transition shadow-2xs"
                                               title="Kerjakan dan isi checklist pemeliharaan">
                                                <i class="fa-solid fa-clipboard-check text-[11px]"></i>
                                                <span>Kerjakan / Isi Checklist</span>
                                            </a>
                                        @else
                                            <span title="Jadwal ini ditugaskan ke {{ $log->teknisi_nama }}"
                                                  class="inline-flex items-center justify-center space-x-1 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-400 border border-slate-200 text-xs font-semibold cursor-not-allowed select-none">
                                                <i class="fa-solid fa-lock text-[10px]"></i>
                                                <span>Kerjakan</span>
                                            </span>
                                        @endif
                                    @else
                                        <button type="button"
                                                onclick="openModalReschedule(this)"
                                                data-id="{{ $log->id }}"
                                                data-nama="{{ $log->nama_perangkat }}"
                                                data-kode="{{ $log->kode_perangkat }}"
                                                data-lokasi="{{ $log->lokasi_ruangan }}"
                                                data-status="{{ $computedStatus }}"
                                                data-is-late="{{ $isLate ? '1' : '0' }}"
                                                data-late-days="{{ $isLate && $log->tanggal_cek ? (int) abs($log->tanggal_cek->diffInDays(now()->startOfDay())) : 0 }}"
                                                data-date-dmy="{{ $log->tanggal_cek ? $log->tanggal_cek->format('d/m/Y') : '-' }}"
                                                data-date-ymd="{{ $log->tanggal_cek ? $log->tanggal_cek->format('Y-m-d') : '' }}"
                                                data-jam="{{ $log->jam_mulai ? str_replace(':', '.', substr($log->jam_mulai, 0, 5)) : '09.00' }}"
                                                data-tech-id="{{ $log->user_id ?? '' }}"
                                                data-tech-name="{{ $log->teknisi_nama && $log->teknisi_nama !== 'Belum ada' ? $log->teknisi_nama : '' }}"
                                                data-tim="{{ $log->tim_label }}"
                                                class="px-3 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-2xs cursor-pointer">
                                            Jadwal ulang
                                        </button>
                                    @endif
                                @else
                                    @if($authUser->isAdmin() || $isOwnedByMe)
                                        <a href="{{ route('maintenance.show', $log) }}" 
                                           class="px-3 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-2xs inline-block">
                                            Lihat
                                        </a>
                                    @else
                                        <span title="Hanya dapat dilihat oleh teknisi pelaksana ({{ $log->teknisi_nama }}) & Koordinator IT"
                                              class="inline-flex items-center space-x-1 px-3 py-1 rounded-lg border border-slate-200/80 bg-slate-50 text-slate-400 text-xs font-semibold cursor-not-allowed select-none">
                                            <i class="fa-solid fa-lock text-[10px]"></i>
                                            <span>Lihat</span>
                                        </span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mx-auto mb-2">
                                    <i class="fa-regular fa-calendar-xmark"></i>
                                </div>
                                <h3 class="text-sm font-bold text-slate-700">Tidak Ada Log Pemeliharaan</h3>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">
                                    Tidak ditemukan data log atau jadwal pemeliharaan untuk filter yang dipilih.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between text-xs text-slate-400 gap-2">
            <div>
                Data sama dengan yang dilihat teknisi. Admin melihat semua tim.
            </div>
            @if($logs->hasPages())
                <div>
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>

    @if(Auth::user()->isAdmin())
    <!-- CARD BAWAH: JADWAL RUTIN (MOCKUP REFERENSI BOTTOM SECTION - KHUSUS ADMIN) -->
    <div id="jadwal-rutin-section" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-black text-slate-900 tracking-tight">Jadwal rutin</h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Jadwal yang dibuat admin otomatis muncul di Log Pemeliharaan teknisi
                </p>
            </div>

            <a href="{{ route('maintenance.schedules.create') }}"
               class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold transition shadow-xs">
                <i class="fa-solid fa-plus text-[10px]"></i>
                <span>Tambah jadwal</span>
            </a>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($recurringSchedules as $sch)
                <div class="py-3.5 flex items-center justify-between gap-4 text-xs">
                    <div>
                        <div class="font-extrabold text-slate-900">
                            {{ $sch->nama_aset }} &bull; <span class="text-slate-600 font-medium">{{ $sch->lokasi_ruangan }}</span>
                        </div>
                        <div class="text-slate-400 font-medium text-[11px] mt-0.5">
                            {{ $sch->frekuensi }} &bull; {{ $sch->tim }} &bull; berikutnya {{ $sch->tanggal_berikutnya ? $sch->tanggal_berikutnya->format('d/m/Y') : '-' }}
                            @if($sch->jam_mulai)
                                &bull; {{ str_replace(':', '.', substr($sch->jam_mulai, 0, 5)) }} WIB
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button type="button"
                                onclick="openModalEditJadwalRutin(this)"
                                data-id="{{ $sch->id }}"
                                data-nama="{{ $sch->nama_aset }}"
                                data-kode="{{ $sch->kode_perangkat ?: '' }}"
                                data-lokasi="{{ $sch->lokasi_ruangan }}"
                                data-frekuensi="{{ $sch->frekuensi }}"
                                data-jam="{{ $sch->jam_mulai ? str_replace(':', '.', substr($sch->jam_mulai, 0, 5)) : '09.00' }}"
                                data-tim="{{ $sch->tim ?: 'Tim IT Lapangan' }}"
                                data-teknisi-id="{{ $sch->teknisi_id ?? '' }}"
                                data-date-dmy="{{ $sch->tanggal_berikutnya ? $sch->tanggal_berikutnya->format('d/m/Y') : '-' }}"
                                data-date-ymd="{{ $sch->tanggal_berikutnya ? $sch->tanggal_berikutnya->format('Y-m-d') : '' }}"
                                data-pengingat="{{ ($sch->kirim_pengingat ?? true) ? '1' : '0' }}"
                                class="text-teal-700 hover:text-teal-900 font-bold text-xs hover:underline cursor-pointer">
                            Ubah
                        </button>
                    </div>
                </div>
            @empty
                <div class="py-6 text-center text-slate-400 text-xs">
                    Belum ada master jadwal rutin berkala yang dibuat. Klik tombol "+ Tambah jadwal" untuk membuat baru.
                </div>
            @endforelse
        </div>
    </div>
    @endif

</div>

<!-- MODAL 1: + JADWALKAN PEMELIHARAAN -->
<div id="modal-jadwalkan" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 animate-fade-in space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold text-xs">
                    <i class="fa-solid fa-calendar-plus"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-sm text-slate-900">Jadwalkan Pemeliharaan Aset</h3>
                    <p class="text-[11px] text-slate-400">Sinkron langsung ke daftar tugas teknisi</p>
                </div>
            </div>
            <button onclick="closeModalJadwalkan()" class="text-slate-400 hover:text-slate-600 text-xs">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('maintenance.jadwalkan') }}" class="space-y-4 text-xs">
            @csrf
            
            <div>
                <label class="block font-bold text-slate-700 mb-1">Tanggal Jadwal <span class="text-rose-500">*</span></label>
                <input type="date" name="tanggal_cek" value="{{ date('Y-m-d', strtotime('+1 day')) }}" required 
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 font-semibold text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Aset / Perangkat <span class="text-rose-500">*</span></label>
                    <select id="jad-nama-aset-select" onchange="handleAssetDropdownChange('jad')" required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                        <option value="">-- Pilih Aset / Perangkat --</option>
                        @foreach($existingAssets as $assetName => $assetLoc)
                            <option value="{{ $assetName }}" data-lokasi="{{ $assetLoc }}">{{ $assetName }}</option>
                        @endforeach
                        <option value="__custom__">+ Lainnya (Ketik manual...)</option>
                    </select>
                    <input type="text" id="jad-nama-aset" name="nama_perangkat" placeholder="Ketik nama aset baru..."
                           class="hidden mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Lokasi Ruangan <span class="text-rose-500">*</span></label>
                    <select id="jad-lokasi-select" onchange="handleLocationDropdownChange('jad')" required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                        <option value="">-- Pilih Lokasi Ruangan --</option>
                        @foreach($existingLocations as $locName)
                            <option value="{{ $locName }}">{{ $locName }}</option>
                        @endforeach
                        <option value="__custom__">+ Lokasi lainnya (Ketik manual...)</option>
                    </select>
                    <input type="text" id="jad-lokasi" name="lokasi_ruangan" placeholder="Ketik lokasi ruangan baru..."
                           class="hidden mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Kegiatan Pemeliharaan <span class="text-rose-500">*</span></label>
                <input type="text" name="kegiatan" placeholder="Contoh: Cek hard disk dan kapasitas storage rekaman CCTV" required 
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tim Penanggung Jawab</label>
                    <select name="tim" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                        <option value="Tim IT Lapangan">Tim IT Lapangan</option>
                        <option value="Tim IT Support">Tim IT Support</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tugaskan Teknisi (Opsional)</label>
                    <select name="teknisi_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                        <option value="">-- Belum Ditentukan --</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="closeModalJadwalkan()" class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-extrabold shadow-xs">
                    Simpan Jadwal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: JADWAL ULANG PEMELIHARAAN (SESUAI MOCKUP REFERENSI) -->
<div id="modal-reschedule" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200/80 animate-fade-in space-y-5">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h3 class="font-extrabold text-lg text-slate-900 tracking-tight">Jadwal ulang pemeliharaan</h3>
            <button type="button" onclick="closeModalReschedule()" class="text-slate-400 hover:text-slate-600 p-1 transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Ringkasan Aset & Keterlambatan (Kotak Abu Lembut) -->
        <div class="rounded-2xl bg-slate-50/90 border border-slate-100 p-4 space-y-3 text-xs">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-baseline gap-1.5">
                        <span id="reschedule-asset-name" class="font-extrabold text-slate-900 text-sm"></span>
                        <span id="reschedule-asset-code" class="text-[11px] font-mono font-semibold text-slate-400"></span>
                    </div>
                    <div id="reschedule-asset-location" class="text-[11px] text-slate-400 font-medium mt-0.5"></div>
                </div>
                <span id="reschedule-status-badge" class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-bold border shrink-0"></span>
            </div>

            <div class="divide-y divide-slate-200/60">
                <div class="py-2 flex items-center justify-between">
                    <span class="text-slate-400 font-medium">Jadwal semula</span>
                    <span id="reschedule-old-date" class="font-bold text-slate-800"></span>
                </div>
                <div id="reschedule-late-row" class="py-2 flex items-center justify-between hidden">
                    <span class="text-slate-400 font-medium">Keterlambatan</span>
                    <span id="reschedule-late-days" class="font-extrabold text-rose-600"></span>
                </div>
            </div>

            <div class="flex items-center space-x-2 pt-0.5">
                <span id="reschedule-tech-pill" class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-teal-50 text-teal-800 border border-teal-200/70">
                    <i class="fa-regular fa-user text-[10px] text-teal-600"></i>
                    <span id="reschedule-tech-name"></span>
                </span>
                <span id="reschedule-team-label" class="text-[11px] text-slate-400 font-medium"></span>
            </div>
        </div>

        <!-- Form Jadwal Ulang -->
        <form id="form-reschedule" method="POST" action="" class="space-y-4 text-xs">
            @csrf

            <!-- Tanggal baru & Jam -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Tanggal baru</label>
                    <input type="date" id="reschedule-date" name="tanggal_cek" required
                           class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-semibold text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 shadow-2xs">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Jam</label>
                    <select id="reschedule-jam" name="jam_mulai"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-semibold text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 shadow-2xs">
                        @foreach(['07.00', '08.00', '09.00', '10.00', '11.00', '13.00', '14.00', '15.00', '16.00'] as $jam)
                            <option value="{{ $jam }}" {{ $jam === '09.00' ? 'selected' : '' }}>{{ $jam }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Ganti teknisi (opsional) -->
            <div>
                <label class="block font-bold text-slate-700 mb-1.5">Ganti teknisi (opsional)</label>
                <select id="reschedule-teknisi" name="teknisi_id"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-semibold text-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 shadow-2xs">
                    <option id="reschedule-keep-tech-opt" value="__keep__">Tetap: Belum ditentukan</option>
                    <option value="">Siapa saja di tim (kosongkan PIC)</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ $tech->isUnavailable() ? 'disabled' : '' }}>
                            {{ $tech->name }}{{ $tech->isUnavailable() ? ' — ⛔ ' . $tech->availability_short_note : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Alasan (wajib diisi) -->
            <div>
                <label class="block font-bold text-slate-700 mb-1.5">Alasan (wajib diisi)</label>
                <textarea id="reschedule-alasan" name="alasan" rows="3" required
                          placeholder="Contoh: menunggu izin masuk area atau teknisi sedang cuti"
                          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 shadow-2xs leading-relaxed"></textarea>
            </div>

            <!-- Checkbox Notifikasi -->
            <div class="pt-0.5 pb-2 border-b border-slate-100">
                <label class="inline-flex items-center space-x-2.5 cursor-pointer">
                    <input type="checkbox" name="notify_teknisi" value="1" checked
                           class="w-4 h-4 rounded border-slate-300 text-[#0f766e] focus:ring-[#0f766e] accent-[#0f766e]">
                    <span class="text-xs font-medium text-slate-700">Beri tahu teknisi lewat notifikasi</span>
                </label>
            </div>

            <!-- Tombol Footer -->
            <div class="flex items-center justify-end space-x-2.5 pt-1">
                <button type="button" onclick="closeModalReschedule()"
                        class="px-5 py-2.5 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 font-bold transition cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white font-extrabold shadow-xs transition cursor-pointer">
                    Simpan Jadwal Baru
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: UBAH JADWAL RUTIN (SESUAI MOCKUP REFERENSI) -->
<div id="modal-jadwal-rutin" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200/80 animate-fade-in space-y-5">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h3 class="font-extrabold text-lg text-slate-900 tracking-tight">Ubah jadwal rutin</h3>
            <button type="button" onclick="closeModalJadwalRutin()" class="text-slate-400 hover:text-slate-600 p-1 transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Ringkasan Aset & Jadwal Berikutnya (Kotak Abu Lembut) -->
        <div class="rounded-2xl bg-slate-50/90 border border-slate-100 p-4 space-y-2.5 text-xs">
            <div>
                <div class="flex flex-wrap items-baseline gap-1.5">
                    <span id="edit-sch-asset-name" class="font-extrabold text-slate-900 text-sm"></span>
                    <span id="edit-sch-asset-code" class="text-[11px] font-mono font-semibold text-slate-400"></span>
                </div>
                <div id="edit-sch-asset-location" class="text-[11px] text-slate-400 font-medium mt-0.5"></div>
            </div>
            <div class="flex items-center justify-between pt-1">
                <span class="text-slate-400 font-medium">Jadwal berikutnya</span>
                <span id="edit-sch-next-date" class="font-extrabold text-slate-900"></span>
            </div>
        </div>

        <!-- Form Ubah Jadwal Rutin -->
        <form id="form-jadwal-rutin" method="POST" action="" class="space-y-4 text-xs">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit-sch-nama-hidden" name="nama_aset" value="">
            <input type="hidden" id="edit-sch-lokasi-hidden" name="lokasi_ruangan" value="">
            <input type="hidden" id="edit-sch-kode-hidden" name="kode_perangkat" value="">
            <input type="hidden" id="edit-sch-tanggal-hidden" name="tanggal_berikutnya" value="">
            <input type="hidden" id="edit-sch-tim-hidden" name="tim" value="Tim IT Lapangan">

            <!-- Frekuensi & Jam -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Frekuensi</label>
                    <select id="edit-sch-frekuensi" name="frekuensi"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-semibold text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 shadow-2xs">
                        <option value="Setiap minggu">Setiap minggu</option>
                        <option value="Setiap bulan">Setiap bulan</option>
                        <option value="Setiap 2 bulan">Setiap 2 bulan</option>
                        <option value="Setiap 3 bulan">Setiap 3 bulan</option>
                        <option value="Setiap 6 bulan">Setiap 6 bulan</option>
                        <option value="Setiap tahun">Setiap tahun</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Jam</label>
                    <select id="edit-sch-jam" name="jam_mulai"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-semibold text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 shadow-2xs">
                        @foreach(['07.00', '07.30', '08.00', '08.30', '09.00', '09.30', '10.00', '10.30', '11.00', '13.00', '13.30', '14.00', '14.30', '15.00', '15.30', '16.00'] as $jam)
                            <option value="{{ $jam }}">{{ $jam }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Tim (Pill Buttons) -->
            <div>
                <label class="block font-bold text-slate-700 mb-2">Tim</label>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="setEditSchTim('Tim IT Lapangan')" data-edit-tim="Tim IT Lapangan"
                            class="edit-sch-tim-pill px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs">
                        Tim IT Lapangan
                    </button>
                    <button type="button" onclick="setEditSchTim('Tim IT Support')" data-edit-tim="Tim IT Support"
                            class="edit-sch-tim-pill px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-600 hover:bg-slate-200/80">
                        Tim IT Support
                    </button>
                </div>
            </div>

            <!-- Teknisi -->
            <div>
                <label class="block font-bold text-slate-700 mb-1.5">Teknisi</label>
                <select id="edit-sch-teknisi" name="teknisi_id"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-semibold text-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 shadow-2xs">
                    <option value="">Siapa saja di tim (yang pertama mengambil)</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ $tech->isUnavailable() ? 'disabled' : '' }}>
                            {{ $tech->name }}{{ $tech->isUnavailable() ? ' — ⛔ ' . $tech->availability_short_note : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Checkbox Pengingat -->
            <div class="pt-0.5 pb-2 border-b border-slate-100">
                <label class="inline-flex items-center space-x-2.5 cursor-pointer">
                    <input type="checkbox" id="edit-sch-pengingat" name="kirim_pengingat" value="1" checked
                           class="w-4 h-4 rounded border-slate-300 text-[#0f766e] focus:ring-[#0f766e] accent-[#0f766e]">
                    <span class="text-xs font-medium text-slate-700">Kirim pengingat ke teknisi satu hari sebelumnya</span>
                </label>
            </div>

            <!-- Kotak Peringatan Kuning Lembut -->
            <div class="rounded-xl bg-amber-50/80 border border-amber-300/80 px-4 py-3 text-xs text-amber-800 leading-relaxed">
                Perubahan berlaku untuk jadwal berikutnya. Log yang sudah selesai tidak berubah.
            </div>

            <!-- Footer: Nonaktifkan Jadwal (Kiri) + Batal & Simpan Perubahan (Kanan) -->
            <div class="flex items-center justify-between pt-2">
                <button type="button" onclick="confirmDeactivateModalSchedule()"
                        class="text-rose-600 hover:text-rose-700 font-bold text-xs hover:underline transition cursor-pointer">
                    Nonaktifkan jadwal
                </button>

                <div class="flex items-center space-x-2.5">
                    <button type="button" onclick="closeModalJadwalRutin()"
                            class="px-5 py-2.5 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 font-bold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white font-extrabold shadow-xs transition cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>

        <!-- Form Terpisah untuk Nonaktifkan Jadwal -->
        <form id="form-modal-deactivate-schedule" method="POST" action="" class="hidden">
            @csrf
        </form>
    </div>
</div>

@push('scripts')
<script>
    function handleAssetDropdownChange(prefix) {
        const sel = document.getElementById(`${prefix}-nama-aset-select`);
        const inp = document.getElementById(`${prefix}-nama-aset`);
        if (!sel || !inp) return;

        if (sel.value === '__custom__') {
            inp.classList.remove('hidden');
            inp.required = true;
            inp.value = '';
            inp.focus();
        } else {
            inp.classList.add('hidden');
            inp.required = false;
            inp.value = sel.value;

            // Auto-fill lokasi ruangan jika aset sudah punya lokasi tercatat
            const opt = sel.options[sel.selectedIndex];
            const defaultLoc = opt ? (opt.getAttribute('data-lokasi') || '') : '';
            if (defaultLoc) {
                setDropdownOrCustom(`${prefix}-lokasi`, defaultLoc);
            }
        }
    }

    function handleLocationDropdownChange(prefix) {
        const sel = document.getElementById(`${prefix}-lokasi-select`);
        const inp = document.getElementById(`${prefix}-lokasi`);
        if (!sel || !inp) return;

        if (sel.value === '__custom__') {
            inp.classList.remove('hidden');
            inp.required = true;
            inp.value = '';
            inp.focus();
        } else {
            inp.classList.add('hidden');
            inp.required = false;
            inp.value = sel.value;
        }
    }

    function setDropdownOrCustom(fieldId, val) {
        const sel = document.getElementById(`${fieldId}-select`);
        const inp = document.getElementById(fieldId);
        if (!sel || !inp) return;

        if (!val) {
            sel.value = '';
            inp.value = '';
            inp.classList.add('hidden');
            inp.required = false;
            return;
        }

        const matchedOpt = Array.from(sel.options).find(o => o.value.toLowerCase() === val.toLowerCase() && o.value !== '__custom__');
        if (matchedOpt) {
            sel.value = matchedOpt.value;
            inp.value = matchedOpt.value;
            inp.classList.add('hidden');
            inp.required = false;
        } else {
            sel.value = '__custom__';
            inp.value = val;
            inp.classList.remove('hidden');
            inp.required = true;
        }
    }

    function openModalJadwalkan() {
        setDropdownOrCustom('jad-nama-aset', '');
        setDropdownOrCustom('jad-lokasi', '');
        document.getElementById('modal-jadwalkan').classList.remove('hidden');
    }
    function closeModalJadwalkan() {
        document.getElementById('modal-jadwalkan').classList.add('hidden');
    }

    function openModalReschedule(btn) {
        const logId = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama') || '';
        const kode = btn.getAttribute('data-kode') || '';
        const lokasi = btn.getAttribute('data-lokasi') || '';
        const status = btn.getAttribute('data-status') || 'Terjadwal';
        const isLate = btn.getAttribute('data-is-late') === '1';
        const lateDays = parseInt(btn.getAttribute('data-late-days') || '0', 10);
        const dateDmy = btn.getAttribute('data-date-dmy') || '-';
        const dateYmd = btn.getAttribute('data-date-ymd') || '';
        const jam = btn.getAttribute('data-jam') || '09.00';
        const techName = btn.getAttribute('data-tech-name') || '';
        const tim = btn.getAttribute('data-tim') || 'Tim IT Lapangan';

        document.getElementById('reschedule-asset-name').innerText = nama;
        document.getElementById('reschedule-asset-code').innerText = kode ? `(${kode})` : '';
        document.getElementById('reschedule-asset-location').innerText = lokasi;
        document.getElementById('reschedule-old-date').innerText = dateDmy;

        const badge = document.getElementById('reschedule-status-badge');
        badge.innerText = status;
        if (isLate) {
            badge.className = 'inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-bold border shrink-0 bg-rose-50 text-rose-600 border-rose-200/70';
        } else {
            badge.className = 'inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-bold border shrink-0 bg-sky-50 text-sky-700 border-sky-200/70';
        }

        const lateRow = document.getElementById('reschedule-late-row');
        if (isLate && lateDays > 0) {
            lateRow.classList.remove('hidden');
            document.getElementById('reschedule-late-days').innerText = `${lateDays} hari`;
        } else {
            lateRow.classList.add('hidden');
        }

        const techPill = document.getElementById('reschedule-tech-pill');
        const techLabel = document.getElementById('reschedule-tech-name');
        if (techName) {
            techPill.className = 'inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-teal-50 text-teal-800 border border-teal-200/70';
            techLabel.innerText = techName;
        } else {
            techPill.className = 'inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500 border border-slate-200';
            techLabel.innerText = 'Belum ada';
        }
        document.getElementById('reschedule-team-label').innerText = tim;

        // Tanggal baru: jika terlambat, sarankan besok; jika belum, tampilkan tanggal saat ini
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowYmd = `${tomorrow.getFullYear()}-${String(tomorrow.getMonth() + 1).padStart(2, '0')}-${String(tomorrow.getDate()).padStart(2, '0')}`;
        document.getElementById('reschedule-date').value = isLate ? tomorrowYmd : (dateYmd || tomorrowYmd);

        const jamEl = document.getElementById('reschedule-jam');
        if (jamEl) {
            const hasJam = Array.from(jamEl.options).some(o => o.value === jam);
            jamEl.value = hasJam ? jam : '09.00';
        }

        const keepOpt = document.getElementById('reschedule-keep-tech-opt');
        if (keepOpt) {
            keepOpt.textContent = techName ? `Tetap: ${techName}` : 'Tetap: Belum ditentukan';
        }
        const techEl = document.getElementById('reschedule-teknisi');
        if (techEl) techEl.value = '__keep__';

        const alasanEl = document.getElementById('reschedule-alasan');
        if (alasanEl) alasanEl.value = '';

        document.getElementById('form-reschedule').action = `/pemeliharaan/${logId}/reschedule`;
        document.getElementById('modal-reschedule').classList.remove('hidden');
    }
    function closeModalReschedule() {
        document.getElementById('modal-reschedule').classList.add('hidden');
    }

    function setEditSchTim(tim) {
        document.getElementById('edit-sch-tim-hidden').value = tim;
        document.querySelectorAll('.edit-sch-tim-pill').forEach(btn => {
            btn.className = btn.getAttribute('data-edit-tim') === tim
                ? 'edit-sch-tim-pill px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs'
                : 'edit-sch-tim-pill px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-600 hover:bg-slate-200/80';
        });
    }

    function openModalEditJadwalRutin(btn) {
        const id = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama') || '';
        const kode = btn.getAttribute('data-kode') || '';
        const lokasi = btn.getAttribute('data-lokasi') || '';
        const frekuensi = btn.getAttribute('data-frekuensi') || 'Setiap bulan';
        const jam = btn.getAttribute('data-jam') || '09.00';
        const tim = btn.getAttribute('data-tim') || 'Tim IT Lapangan';
        const techId = btn.getAttribute('data-teknisi-id') || '';
        const dateDmy = btn.getAttribute('data-date-dmy') || '-';
        const dateYmd = btn.getAttribute('data-date-ymd') || '';
        const pengingat = btn.getAttribute('data-pengingat') === '1';

        document.getElementById('edit-sch-asset-name').innerText = nama;
        document.getElementById('edit-sch-asset-code').innerText = kode ? `(${kode})` : '';
        document.getElementById('edit-sch-asset-location').innerText = lokasi;
        document.getElementById('edit-sch-next-date').innerText = dateDmy;

        document.getElementById('edit-sch-nama-hidden').value = nama;
        document.getElementById('edit-sch-lokasi-hidden').value = lokasi;
        document.getElementById('edit-sch-kode-hidden').value = kode;
        document.getElementById('edit-sch-tanggal-hidden').value = dateYmd;

        const freqEl = document.getElementById('edit-sch-frekuensi');
        if (freqEl) {
            const hasFreq = Array.from(freqEl.options).some(o => o.value === frekuensi);
            if (!hasFreq && frekuensi) {
                const opt = document.createElement('option');
                opt.value = frekuensi;
                opt.textContent = frekuensi;
                freqEl.appendChild(opt);
            }
            freqEl.value = frekuensi;
        }

        const jamEl = document.getElementById('edit-sch-jam');
        if (jamEl) {
            const hasJam = Array.from(jamEl.options).some(o => o.value === jam);
            jamEl.value = hasJam ? jam : '09.00';
        }

        setEditSchTim(tim);

        const techEl = document.getElementById('edit-sch-teknisi');
        if (techEl) techEl.value = techId || '';

        const pengEl = document.getElementById('edit-sch-pengingat');
        if (pengEl) pengEl.checked = pengingat;

        document.getElementById('form-jadwal-rutin').action = `/pemeliharaan/schedules/${id}`;
        document.getElementById('form-modal-deactivate-schedule').action = `/pemeliharaan/schedules/${id}/deactivate`;
        document.getElementById('modal-jadwal-rutin').classList.remove('hidden');
    }

    function confirmDeactivateModalSchedule() {
        if (confirm('Nonaktifkan jadwal rutin ini? Jadwal akan berhenti membuat log baru, sedangkan log yang sudah selesai tetap tersimpan.')) {
            document.getElementById('form-modal-deactivate-schedule').submit();
        }
    }

    function closeModalJadwalRutin() {
        document.getElementById('modal-jadwal-rutin').classList.add('hidden');
    }
</script>
@endpush
@endsection
