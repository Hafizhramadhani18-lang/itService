@extends('layouts.app')

@section('title', 'Kinerja Teknisi - IT Service Desk PLTU Karangkandri')

@section('content')
<div class="space-y-5">

    <!-- ============================================== -->
    <!-- HEADER HALAMAN SESUAI MOCKUP REFERENSI         -->
    <!-- ============================================== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2.5">
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                    Kinerja Teknisi
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                    Monitoring Kinerja Teknisi
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Fokus per orang: beban kerja dan penyelesaian tiap teknisi
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            <!-- Tombol Ekspor Excel -->
            <a href="{{ route('services.export', array_merge(request()->query(), ['filter' => 'report'])) }}" 
               class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs flex items-center space-x-1.5 cursor-pointer">
                <span>Ekspor Excel</span>
            </a>

            <!-- Header Date & Notification Widget -->
            @include('partials.header-widget')
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @include('partials.flash-messages')

    <!-- ============================================== -->
    <!-- FILTER BAR                                     -->
    <!-- ============================================== -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 sm:p-5">
        <form id="filterKinerjaForm" action="{{ route('admin.kinerja') }}" method="GET" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            
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
                            <option value="1" {{ $minggu === '1' ? 'selected' : '' }}>Minggu 1 (Tgl 1-7)</option>
                            <option value="2" {{ $minggu === '2' ? 'selected' : '' }}>Minggu 2 (Tgl 8-14)</option>
                            <option value="3" {{ $minggu === '3' ? 'selected' : '' }}>Minggu 3 (Tgl 15-21)</option>
                            <option value="4" {{ $minggu === '4' ? 'selected' : '' }}>Minggu 4 (Tgl 22-28)</option>
                            <option value="5" {{ $minggu === '5' ? 'selected' : '' }}>Minggu 5 (Tgl 29-31)</option>
                        </select>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <!-- Grup Filter Tim & Teknisi -->
            <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                <!-- Tim -->
                <div>
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">Tim</label>
                    <div class="relative">
                        <select name="tim" onchange="this.form.submit()" 
                                class="appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold rounded-lg px-3 py-1.5 pr-8 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition cursor-pointer min-w-[130px]">
                            <option value="all" {{ $tim === 'all' ? 'selected' : '' }}>Semua tim</option>
                            <option value="lapangan" {{ $tim === 'lapangan' ? 'selected' : '' }}>Tim IT Lapangan</option>
                            <option value="aplikasi" {{ $tim === 'aplikasi' ? 'selected' : '' }}>Tim IT Support</option>
                        </select>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Teknisi -->
                <div>
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">Teknisi</label>
                    <div class="relative">
                        <select name="teknisi" onchange="this.form.submit()" 
                                class="appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold rounded-lg px-3 py-1.5 pr-8 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition cursor-pointer min-w-[165px]">
                            <option value="all" {{ $teknisi === 'all' ? 'selected' : '' }}>Semua teknisi</option>
                            @foreach($personelUsers as $person)
                                <option value="{{ $person->id }}" {{ ($selectedFilterTeknisiUser && $selectedFilterTeknisiUser->id === $person->id) ? 'selected' : '' }}>
                                    {{ $person->name }}
                                </option>
                            @endforeach
                        </select>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </div>

                @if($tahun !== (string)now()->year || $bulan !== (string)now()->month || $minggu !== 'all' || $tim !== 'all' || $teknisi !== 'all' || $selectedTeknisi !== 'all')
                <div class="self-end mb-1">
                    <a href="{{ route('admin.kinerja') }}" class="text-[11px] text-rose-500 hover:text-rose-700 font-semibold underline">
                        Reset Filter
                    </a>
                </div>
                @endif
            </div>

        </form>
    </div>

    <!-- ============================================== -->
    <!-- 4 KARTU METRIK UTAMA (KPI)                     -->
    <!-- ============================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- Kartu 1: Teknisi Aktif -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">Teknisi aktif</span>
                    <span class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-xs shadow-xs">
                        <i class="fa-solid fa-user"></i>
                    </span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">
                    {{ $teknisiAktifCount }}
                </div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-400 mt-3 pt-2 border-t border-slate-50">
                <span>Sedang bertugas</span>
                <i class="fa-solid fa-arrow-right text-[10px] text-emerald-500"></i>
            </div>
        </div>

        <!-- Kartu 2: Tiket Aktif -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">Tiket aktif</span>
                    <span class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs shadow-xs">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">
                    {{ $tiketAktifCount }}
                </div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-400 mt-3 pt-2 border-t border-slate-50">
                <span>Sedang dikerjakan</span>
                <i class="fa-solid fa-arrow-right text-[10px] text-amber-500"></i>
            </div>
        </div>

        <!-- Kartu 3: Slot Tersedia -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">Slot tersedia</span>
                    <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs shadow-xs">
                        <i class="fa-solid fa-check"></i>
                    </span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">
                    {{ $slotTersedia }}
                </div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-400 mt-3 pt-2 border-t border-slate-50">
                <span>Dari batas 3 tiket per teknisi</span>
                <i class="fa-solid fa-arrow-right text-[10px] text-emerald-600"></i>
            </div>
        </div>

        <!-- Kartu 4: Belum Diambil -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">Belum diambil</span>
                    <span class="w-7 h-7 rounded-lg bg-rose-500 text-white flex items-center justify-center text-xs shadow-xs">
                        <i class="fa-solid fa-inbox"></i>
                    </span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">
                    {{ $belumDiambilCount }}
                </div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-400 mt-3 pt-2 border-t border-slate-50">
                <span>{{ $mendesakBelumDiambil }} di antaranya mendesak</span>
                <i class="fa-solid fa-arrow-right text-[10px] text-rose-500"></i>
            </div>
        </div>

    </div>

    <!-- ============================================== -->
    <!-- 2 KOLOM GRID: PERBANDINGAN & BEBAN KERJA       -->
    <!-- ============================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        <!-- ============================================== -->
        <!-- KOLOM KIRI (8/12): TABEL PERBANDINGAN TEKNISI  -->
        <!-- ============================================== -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-4">
            <h3 class="text-sm font-extrabold text-slate-900">
                Perbandingan teknisi
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-[11px] font-bold text-slate-400 border-b border-slate-100">
                            <th class="pb-2.5 font-bold">Teknisi</th>
                            <th class="pb-2.5 font-bold text-center">Diambil</th>
                            <th class="pb-2.5 font-bold text-center">Selesai</th>
                            <th class="pb-2.5 font-bold text-center">Aktif</th>
                            <th class="pb-2.5 font-bold text-center">Rata-rata waktu</th>
                            <th class="pb-2.5 font-bold">Tingkat selesai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php
                            $showLapanganGroup = ($tim === 'all' || $tim === 'lapangan') && $visibleLapanganData->isNotEmpty();
                            $showSupportGroup = ($tim === 'all' || $tim === 'aplikasi') && $visibleSupportData->isNotEmpty();
                        @endphp

                        <!-- ========================= -->
                        <!-- HEADER GRUP 1: TIM LAPANGAN -->
                        <!-- ========================= -->
                        @if($showLapanganGroup)
                        <tr class="bg-slate-50/70">
                            <td colspan="6" class="py-2 px-1 text-[11px] font-bold text-teal-800">
                                Tim IT Lapangan &bull; {{ $visibleLapanganData->count() }} teknisi
                            </td>
                        </tr>
                        @foreach($visibleLapanganData as $tek)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3">
                                    <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full bg-slate-50 border border-slate-200">
                                        <i class="fa-solid fa-user text-[10px] text-teal-600"></i>
                                        <span class="font-bold text-slate-800 text-xs">{{ $tek['name'] }}</span>
                                    </div>
                                </td>
                                <td class="py-3 font-semibold text-slate-700 text-center">{{ $tek['diambil'] }}</td>
                                <td class="py-3 font-semibold text-slate-700 text-center">{{ $tek['selesai'] }}</td>
                                <td class="py-3 font-semibold text-slate-700 text-center">{{ $tek['aktif'] }}</td>
                                <td class="py-3 font-semibold text-slate-700 text-center">{{ $tek['rata_rata_waktu'] }}</td>
                                <td class="py-3">
                                    @if($tek['tingkat_selesai'] !== null)
                                        <span class="font-extrabold text-slate-900 block text-xs">{{ $tek['tingkat_selesai'] }}%</span>
                                        <div class="w-20 bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                            <div class="bg-teal-700 h-1.5 rounded-full" style="width: {{ $tek['tingkat_selesai'] }}%"></div>
                                        </div>
                                    @else
                                        <span class="text-slate-400 font-bold">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @endif

                        <!-- ========================= -->
                        <!-- HEADER GRUP 2: TIM SUPPORT -->
                        <!-- ========================= -->
                        @if($showSupportGroup)
                        <tr class="bg-slate-50/70">
                            <td colspan="6" class="py-2 px-1 text-[11px] font-bold text-indigo-900 {{ $showLapanganGroup ? 'pt-4' : '' }}">
                                Tim IT Support &bull; {{ $visibleSupportData->count() }} teknisi
                            </td>
                        </tr>
                        @foreach($visibleSupportData as $tek)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3">
                                    <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full bg-slate-50 border border-slate-200">
                                        <i class="fa-solid fa-user text-[10px] text-indigo-600"></i>
                                        <span class="font-bold text-slate-800 text-xs">{{ $tek['name'] }}</span>
                                    </div>
                                </td>
                                <td class="py-3 font-semibold text-slate-700 text-center">{{ $tek['diambil'] }}</td>
                                <td class="py-3 font-semibold text-slate-700 text-center">{{ $tek['selesai'] }}</td>
                                <td class="py-3 font-semibold text-slate-700 text-center">{{ $tek['aktif'] }}</td>
                                <td class="py-3 font-semibold text-slate-700 text-center">{{ $tek['rata_rata_waktu'] }}</td>
                                <td class="py-3">
                                    @if($tek['tingkat_selesai'] !== null)
                                        <span class="font-extrabold text-slate-900 block text-xs">{{ $tek['tingkat_selesai'] }}%</span>
                                        <div class="w-20 bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                            <div class="bg-teal-700 h-1.5 rounded-full" style="width: {{ $tek['tingkat_selesai'] }}%"></div>
                                        </div>
                                    @else
                                        <span class="text-slate-400 font-bold">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @endif

                        @if(!$showLapanganGroup && !$showSupportGroup)
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400 text-xs">
                                Tidak ada data teknisi yang sesuai dengan filter yang dipilih.
                            </td>
                        </tr>
                        @endif

                    </tbody>
                </table>
            </div>

            <!-- Catatan Kaki Sesuai Mockup -->
            <p class="text-[11px] text-slate-400 font-medium pt-2">
                Tingkat selesai = tiket selesai dibagi tiket yang diambil pada periode ini.
            </p>
        </div>

        <!-- ============================================== -->
        <!-- KOLOM KANAN (4/12): BEBAN KERJA SAAT INI       -->
        <!-- ============================================== -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-4">
            <h3 class="text-sm font-extrabold text-slate-900">
                Beban kerja saat ini
            </h3>

            <!-- Tim Lapangan -->
            <div class="space-y-3">
                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    Tim IT Lapangan
                </h4>

                @foreach($timLapanganData as $tek)
                <div>
                    <div class="flex items-center justify-between text-xs font-bold mb-1">
                        <span class="text-slate-800">{{ $tek['name'] }}</span>
                        <span class="text-slate-700">{{ $tek['beban_str'] }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-teal-700 h-1.5 rounded-full transition-all duration-300" style="width: {{ $tek['beban_pct'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Tim Support -->
            <div class="space-y-3 pt-3 border-t border-slate-100">
                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    Tim IT Support
                </h4>

                @foreach($timSupportData as $tek)
                <div>
                    <div class="flex items-center justify-between text-xs font-bold mb-1">
                        <span class="text-slate-800">{{ $tek['name'] }}</span>
                        <span class="text-slate-700">{{ $tek['beban_str'] }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-teal-700 h-1.5 rounded-full transition-all duration-300" style="width: {{ $tek['beban_pct'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Catatan Batas Maksimal -->
            <p class="text-[11px] text-slate-400 font-medium pt-2 border-t border-slate-50">
                Batas tiket aktif per teknisi: 3.
            </p>
        </div>

    </div>

    <!-- ============================================== -->
    <!-- BAGIAN BAWAH: TIKET TERAKHIR DISELESAIKAN      -->
    <!-- ============================================== -->
    <div id="tiket-terakhir-diselesaikan" class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-4">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">
                    Tiket terakhir diselesaikan
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    @if($selectedTeknisiUser)
                        Menampilkan riwayat penyelesaian oleh <span class="font-semibold text-slate-600">{{ $selectedTeknisiUser->name }}</span>
                    @elseif($selectedTeknisi !== 'all')
                        Menampilkan riwayat penyelesaian untuk <span class="font-semibold text-slate-600">{{ $selectedTeknisi }}</span>
                    @else
                        Menampilkan riwayat penyelesaian terbaru dari semua teknisi
                    @endif
                </p>
            </div>

            <!-- Dropdown Pilih Personil Teknisi -->
            <form action="{{ route('admin.kinerja') }}#tiket-terakhir-diselesaikan" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="bulan" value="{{ $bulan }}">
                <input type="hidden" name="minggu" value="{{ $minggu }}">
                <input type="hidden" name="tim" value="{{ $tim }}">
                <input type="hidden" name="teknisi" value="{{ $teknisi }}">

                <label for="tabTeknisiSelect" class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">Teknisi</label>
                <div class="relative">
                    <select id="tabTeknisiSelect" name="tab_teknisi" onchange="this.form.submit()"
                            class="appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold rounded-lg px-3 py-1.5 pr-8 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition cursor-pointer min-w-[190px]">
                        <option value="all" {{ $selectedTeknisi === 'all' ? 'selected' : '' }}>Semua teknisi</option>
                        @foreach($personelUsers as $person)
                            <option value="{{ $person->id }}" {{ ($selectedTeknisiUser && $selectedTeknisiUser->id === $person->id) ? 'selected' : '' }}>
                                {{ $person->name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-[11px] font-bold text-slate-400 border-b border-slate-100">
                        <th class="pb-2.5 font-bold">No.</th>
                        <th class="pb-2.5 font-bold">Kendala &amp; Lokasi</th>
                        <th class="pb-2.5 font-bold">Teknisi</th>
                        <th class="pb-2.5 font-bold">Prioritas</th>
                        <th class="pb-2.5 font-bold">Durasi</th>
                        <th class="pb-2.5 font-bold">Selesai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tiketTerakhirSelesai as $tkt)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="py-3 font-bold text-slate-900 whitespace-nowrap">
                            <a href="{{ route('services.show', $tkt) }}" class="text-teal-700 hover:underline">
                                {{ $tkt->ticket_number }}
                            </a>
                        </td>
                        <td class="py-3">
                            <div class="font-bold text-slate-800">{{ $tkt->display_judul }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                <i class="fa-solid fa-location-dot text-[9px] mr-1 text-slate-400"></i>{{ $tkt->lokasi_ruangan }}
                            </div>
                        </td>
                        <td class="py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold text-[11px]">
                                {{ $tkt->teknisi_nama ?? $tkt->teknisi?->name ?? '—' }}
                            </span>
                        </td>
                        <td class="py-3 whitespace-nowrap">
                            @if($tkt->prioritas === 'Mendesak')
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-rose-50 text-rose-700 text-[10px] border border-rose-200/60">
                                    Mendesak
                                </span>
                            @elseif($tkt->prioritas === 'Sedang' || $tkt->prioritas === 'Tinggi')
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-amber-50 text-amber-700 text-[10px] border border-amber-200/60">
                                    Sedang
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-sky-50 text-sky-700 text-[10px] border border-sky-200/60">
                                    Rendah
                                </span>
                            @endif
                        </td>
                        <td class="py-3 whitespace-nowrap font-medium text-slate-700">
                            {{ $tkt->formatted_durasi }}
                        </td>
                        <td class="py-3 whitespace-nowrap text-slate-600 font-medium">
                            @if($tkt->tanggal_selesai)
                                {{ $tkt->tanggal_selesai->format('d/m/Y') }} &bull; {{ $tkt->tanggal_selesai->format('H:i') }} WIB
                            @else
                                &mdash;
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-slate-400 text-xs">
                            Belum ada riwayat tiket selesai untuk teknisi ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection
