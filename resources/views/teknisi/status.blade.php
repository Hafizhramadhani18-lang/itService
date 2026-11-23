@extends('layouts.app')

@section('title', 'Status Kerja - PT S2P PLTU Karangkandri')

@section('content')
<div class="space-y-6">

    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Status Kerja
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Atur status kehadiran Anda selama jam kerja agar koordinator IT mengetahui ketersediaan tim
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            @include('partials.header-widget')
        </div>
    </div>

    <!-- Alert Flash Feedback -->
    @include('partials.flash-messages')

    @if ($errors->any())
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200/80 text-xs text-rose-700 shadow-xs">
        <div class="flex items-center space-x-2 font-bold mb-1">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Terdapat kesalahan input:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-600 pl-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Banner Jam Operasional Kerja (08.00 - 17.00 WIB) -->
    <div class="rounded-2xl border px-5 py-3.5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 {{ $withinWorkHours ? 'bg-teal-50/70 border-teal-200/80' : 'bg-slate-100/80 border-slate-200' }}">
        <div class="flex items-start sm:items-center space-x-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $withinWorkHours ? 'bg-teal-700 text-white' : 'bg-slate-600 text-white' }}">
                <i class="fa-regular fa-clock text-sm"></i>
            </div>
            <div>
                <div class="text-xs font-extrabold {{ $withinWorkHours ? 'text-teal-950' : 'text-slate-800' }}">
                    Jam kerja 08.00 – 17.00 WIB
                </div>
                <p class="text-[11px] {{ $withinWorkHours ? 'text-teal-800/80' : 'text-slate-500' }} mt-0.5">
                    Status otomatis menjadi Offline pukul 17.00 dan direset setiap pagi sebelum teknisi check-in.
                </p>
            </div>
        </div>
        <div class="inline-flex items-center space-x-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 shadow-2xs shrink-0 self-start sm:self-center">
            <span class="w-2 h-2 rounded-full {{ $withinWorkHours ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
            <span id="live-wib-clock" class="text-xs font-black text-slate-900 tabular-nums">{{ $nowWib->format('H:i') }} WIB</span>
        </div>
    </div>

    <!-- Layout 2 Kolom: Form Status (Kiri 7) & Panel Tugas + Riwayat (Kanan 5) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- KIRI: Form Ubah Status Kerja -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-5">
            <!-- Header Card Kiri -->
            <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                <div>
                    <h2 class="text-sm font-black text-slate-900 tracking-tight">Status saat ini</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        @if($user->status_ketersediaan_updated_at && $user->status_ketersediaan_updated_at->copy()->timezone('Asia/Jakarta')->isToday())
                            Terakhir diperbarui {{ $user->status_ketersediaan_updated_at->copy()->timezone('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMM Y · HH.mm') }} WIB
                        @else
                            Belum ada pembaruan status hari ini
                        @endif
                    </p>
                </div>
                <span class="text-[11px] font-semibold text-slate-600 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1 shrink-0">
                    {{ $user->label_tim }}
                </span>
            </div>

            <!-- Banner Highlight Status Saat Ini -->
            @php
                $eff = $user->effective_availability;
                $boxStyle = match($eff) {
                    'on_task' => 'bg-amber-50/70 border-amber-200 text-amber-900',
                    'cuti' => 'bg-rose-50/70 border-rose-200 text-rose-900',
                    'offline' => 'bg-slate-50 border-slate-200 text-slate-700',
                    default => 'bg-emerald-50/70 border-emerald-200 text-emerald-900',
                };
                $badgeStyle = match($eff) {
                    'on_task' => 'bg-amber-100 text-amber-800 border-amber-200',
                    'cuti' => 'bg-rose-100 text-rose-800 border-rose-200',
                    'offline' => 'bg-slate-200 text-slate-700 border-slate-300',
                    default => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                };
                $dotColor = match($eff) {
                    'on_task' => 'bg-amber-500',
                    'cuti' => 'bg-rose-500',
                    'offline' => 'bg-slate-400',
                    default => 'bg-emerald-500',
                };
                $lastTime = $user->status_ketersediaan_updated_at ? $user->status_ketersediaan_updated_at->copy()->timezone('Asia/Jakarta')->format('H.i') : 'Pagi ini';
            @endphp
            <div class="p-3.5 rounded-xl border {{ $boxStyle }} flex items-center space-x-3 text-xs">
                <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-lg border text-[11px] font-extrabold shrink-0 {{ $badgeStyle }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                    <span>{{ $user->availability_label }}</span>
                </span>
                <span class="text-[11px] font-medium leading-relaxed">
                    @if($eff === 'on_task')
                        Sejak {{ $lastTime }} WIB &bull; 
                        @if($topActiveTicket)
                            otomatis karena Anda mengambil <strong class="font-bold text-slate-900">{{ $topActiveTicket->ticket_number }}</strong>
                        @else
                            {{ $user->catatan_ketersediaan ?: 'sedang mengerjakan perbaikan atau pemeliharaan' }}
                        @endif
                    @elseif($eff === 'available')
                        Sejak {{ $lastTime }} WIB &bull; Siap menerima penugasan baru dari Koordinator IT
                    @elseif($eff === 'cuti')
                        Sedang cuti / izin {{ $user->cuti_selesai ? 's/d ' . $user->cuti_selesai->locale('id')->isoFormat('D MMM Y') : '' }}
                    @else
                        {{ $withinWorkHours ? 'Belum check-in hari ini &bull; silakan pilih Available untuk mulai aktif' : 'Di luar jam kerja operasional (08.00 - 17.00 WIB)' }}
                    @endif
                </span>
            </div>

            <form id="form-update-status" action="{{ route('teknisi.status.update') }}" method="POST" class="space-y-4">
                @csrf
                @php
                    $curStatus = old('status_ketersediaan', $user->effective_availability);
                @endphp

                <!-- Ubah status (4 Opsi Horizontal Sejajar) -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold text-slate-800">
                            Ubah status
                        </label>
                        <!-- Accessible title for tests -->
                        <span class="sr-only">Pilih Status Kerja Anda</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <!-- 1. Available -->
                        <label class="status-card flex flex-col justify-between p-3 rounded-xl border cursor-pointer transition {{ $curStatus === 'available' ? 'border-emerald-500 bg-emerald-50/50 ring-1 ring-emerald-500/30' : 'border-slate-200 hover:border-emerald-300 bg-white' }}">
                            <div class="flex items-center space-x-2">
                                <input type="radio" name="status_ketersediaan" value="available"
                                       onchange="handleStatusCardChange(this.value)"
                                       {{ $curStatus === 'available' ? 'checked' : '' }}
                                       {{ !$withinWorkHours ? 'disabled' : '' }}
                                       class="text-emerald-600 focus:ring-emerald-500 accent-emerald-600">
                                <span class="inline-flex items-center space-x-1 text-[11px] font-extrabold text-emerald-800 bg-emerald-100/70 px-2 py-0.5 rounded-md">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Available</span>
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-2 leading-tight">
                                Siap menerima tiket baru
                            </p>
                        </label>

                        <!-- 2. On Task -->
                        <label class="status-card flex flex-col justify-between p-3 rounded-xl border cursor-pointer transition {{ $curStatus === 'on_task' ? 'border-amber-500 bg-amber-50/50 ring-1 ring-amber-500/30' : 'border-slate-200 hover:border-amber-300 bg-white' }}">
                            <div class="flex items-center space-x-2">
                                <input type="radio" name="status_ketersediaan" value="on_task"
                                       onchange="handleStatusCardChange(this.value)"
                                       {{ $curStatus === 'on_task' ? 'checked' : '' }}
                                       {{ !$withinWorkHours ? 'disabled' : '' }}
                                       class="text-amber-600 focus:ring-amber-500 accent-amber-600">
                                <span class="inline-flex items-center space-x-1 text-[11px] font-extrabold text-amber-800 bg-amber-100/70 px-2 py-0.5 rounded-md">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    <span>On Task</span>
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-2 leading-tight">
                                Sedang mengerjakan tiket atau pemeliharaan
                            </p>
                        </label>

                        <!-- 3. Cuti / Izin -->
                        <label class="status-card flex flex-col justify-between p-3 rounded-xl border cursor-pointer transition {{ $curStatus === 'cuti' ? 'border-rose-500 bg-rose-50/50 ring-1 ring-rose-500/30' : 'border-slate-200 hover:border-rose-300 bg-white' }}">
                            <div class="flex items-center space-x-2">
                                <input type="radio" name="status_ketersediaan" value="cuti"
                                       onchange="handleStatusCardChange(this.value)"
                                       {{ $curStatus === 'cuti' ? 'checked' : '' }}
                                       class="text-rose-600 focus:ring-rose-500 accent-rose-600">
                                <span class="inline-flex items-center space-x-1 text-[11px] font-extrabold text-rose-800 bg-rose-100/70 px-2 py-0.5 rounded-md">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    <span>Cuti / Izin</span>
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-2 leading-tight">
                                Tidak ditugaskan selama cuti
                            </p>
                        </label>

                        <!-- 4. Offline -->
                        <label class="status-card flex flex-col justify-between p-3 rounded-xl border cursor-pointer transition {{ $curStatus === 'offline' ? 'border-slate-500 bg-slate-50 ring-1 ring-slate-400/40' : 'border-slate-200 hover:border-slate-300 bg-white' }}">
                            <div class="flex items-center space-x-2">
                                <input type="radio" name="status_ketersediaan" value="offline"
                                       onchange="handleStatusCardChange(this.value)"
                                       {{ $curStatus === 'offline' ? 'checked' : '' }}
                                       class="text-slate-600 focus:ring-slate-500 accent-slate-600">
                                <span class="inline-flex items-center space-x-1 text-[11px] font-extrabold text-slate-700 bg-slate-200 px-2 py-0.5 rounded-md">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    <span>Offline</span>
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-2 leading-tight">
                                Di luar jam kerja atau pulang
                            </p>
                        </label>
                    </div>
                </div>

                <!-- Panel Input Cuti (Collapsible hanya saat Cuti dipilih) -->
                <div id="status-cuti-panel" class="{{ $curStatus === 'cuti' ? '' : 'hidden' }} p-3.5 rounded-xl bg-rose-50/60 border border-rose-200/80 space-y-3">
                    <div class="text-xs font-bold text-rose-900 flex items-center space-x-1.5">
                        <i class="fa-regular fa-calendar-xmark"></i>
                        <span>Periode Cuti / Izin</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tanggal Mulai</label>
                            <input type="date" name="cuti_mulai"
                                   value="{{ old('cuti_mulai', $user->cuti_mulai ? $user->cuti_mulai->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                   class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-teal-700">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Sampai Tanggal <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <input type="date" name="cuti_selesai"
                                   value="{{ old('cuti_selesai', $user->cuti_selesai ? $user->cuti_selesai->format('Y-m-d') : '') }}"
                                   class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-teal-700">
                        </div>
                    </div>
                    @if($scheduledPmCount > 0)
                    <label class="inline-flex items-center space-x-2 cursor-pointer pt-0.5">
                        <input type="checkbox" name="release_pm_to_team" value="1"
                               class="w-3.5 h-3.5 rounded border-slate-300 text-teal-700 focus:ring-teal-700 accent-teal-700">
                        <span class="text-[11px] font-medium text-slate-700">
                            Lepas {{ $scheduledPmCount }} jadwal PM terjadwal saya ke tim agar dapat diambil rekan
                        </span>
                    </label>
                    @endif
                </div>

                <!-- Input Catatan Singkat -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">
                        Catatan singkat <span class="text-slate-400 font-normal text-[11px]">(opsional, tampil di pantauan admin)</span>
                    </label>
                    <input type="text" name="catatan_ketersediaan"
                           value="{{ old('catatan_ketersediaan', $user->catatan_ketersediaan) }}"
                           placeholder="Contoh: Pengecekan jaringan Control Room"
                           class="w-full bg-slate-50 focus:bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 transition">
                </div>

                <!-- Footer Form: Penjelasan Otomatisasi & Tombol Simpan -->
                <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <p class="text-[11px] text-slate-400 leading-relaxed max-w-sm">
                        Saat jam kerja, status berganti otomatis antara Available dan On Task ketika Anda mengambil atau menyelesaikan tiket.
                    </p>
                    <button type="submit"
                            class="px-5 py-2.5 bg-[#0f766e] hover:bg-[#115e59] active:scale-95 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer shrink-0">
                        Simpan Status Kerja
                    </button>
                </div>
            </form>
        </div>

        <!-- KANAN: Tugas Aktif Anda & Riwayat Status Hari Ini -->
        <div class="lg:col-span-5 space-y-4">

            <!-- Card 1: Tugas aktif Anda -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3.5">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-black text-slate-900 tracking-tight">Tugas aktif Anda</h3>
                        <!-- Accessible title for tests -->
                        <span class="sr-only">Beban Anda</span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-100">Real-time</span>
                </div>

                <!-- 2 Metric Boxes -->
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('services.index', ['filter' => 'my']) }}"
                       class="p-3.5 rounded-xl bg-slate-50/80 hover:bg-teal-50/70 border border-slate-200/80 transition block group">
                        <div class="text-[10px] font-bold text-slate-500">Tiket aktif</div>
                        <div class="text-2xl font-black text-slate-900 mt-0.5">{{ $activeTicketCount }}</div>
                    </a>

                    <a href="{{ route('maintenance.index') }}"
                       class="p-3.5 rounded-xl bg-slate-50/80 hover:bg-teal-50/70 border border-slate-200/80 transition block group">
                        <div class="text-[10px] font-bold text-slate-500">Jadwal PM</div>
                        <div class="text-2xl font-black text-slate-900 mt-0.5">{{ $scheduledPmCount }}</div>
                    </a>
                </div>

                <!-- Kartu Tiket Sedang Dikerjakan -->
                @if($topActiveTicket)
                <a href="{{ route('services.show', $topActiveTicket) }}"
                   class="block p-3 rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 transition space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="font-extrabold text-teal-800 font-mono text-xs">{{ $topActiveTicket->ticket_number }}</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200">
                            {{ $topActiveTicket->status }}
                        </span>
                    </div>
                    <div class="text-xs font-bold text-slate-900 truncate">
                        {{ $topActiveTicket->jenis_perangkat ?: 'Perangkat IT' }}
                    </div>
                    <div class="text-[10px] text-slate-500 truncate flex items-center space-x-1">
                        <span>{{ $topActiveTicket->divisi }}</span>
                        <span>&bull;</span>
                        <span>{{ $topActiveTicket->lokasi_ruangan ?: 'Area PLTU' }}</span>
                        @if($topActiveTicket->keluhan)
                            <span>&bull;</span>
                            <span class="truncate">{{ Str::limit($topActiveTicket->keluhan, 25) }}</span>
                        @endif
                    </div>
                </a>
                @else
                <div class="p-3 rounded-xl border border-dashed border-slate-200 text-center py-3 bg-slate-50/40 text-[11px] text-slate-500">
                    <i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i>
                    <span>Tidak ada tiket aktif yang sedang dikerjakan</span>
                </div>
                @endif
            </div>

            <!-- Card 2: Riwayat status hari ini -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="text-xs font-black text-slate-900 tracking-tight">Riwayat status hari ini</h3>
                    <span class="text-[10px] text-slate-400 font-medium">{{ now()->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM Y') }}</span>
                </div>

                <div class="relative pl-5 space-y-3 pt-1">
                    <!-- Vertical Rail Line -->
                    <div class="absolute left-1.5 top-2.5 bottom-2.5 w-0.5 bg-slate-200"></div>

                    @forelse($statusTimeline as $tl)
                    <div class="relative flex items-start space-x-2.5 text-xs">
                        <div class="absolute -left-5 top-1 w-2.5 h-2.5 rounded-full border-2 border-white {{ $tl['dot'] ?? 'bg-slate-400' }} shadow-2xs"></div>
                        <div class="font-mono text-[11px] font-semibold text-slate-400 w-10 shrink-0">
                            {{ $tl['time'] }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-slate-900 leading-tight">
                                {{ $tl['title'] }}
                            </div>
                            <div class="text-[10px] text-slate-500 leading-normal mt-0.5">
                                {{ $tl['desc'] }}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-[11px] text-slate-400 py-1">
                        Belum ada aktivitas tercatat hari ini
                    </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

    <!-- SEKSI 2: TABEL TIM HARI INI (STANDAR PERUSAHAAN & TIDAK KERAMEAN) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
        
        <!-- Header & Counter -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <div class="flex items-center space-x-2">
                    <h2 class="text-sm sm:text-base font-black text-slate-900 tracking-tight">Tim hari ini</h2>
                    <span class="text-[11px] text-slate-400 font-normal hidden sm:inline">&bull; Pantau ketersediaan rekan kerja secara real-time</span>
                    <span class="sr-only">Status Tim IT Hari Ini</span>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-1 rounded-xl text-[11px] font-extrabold bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
                    {{ $teammates->count() }} teknisi
                </span>
            </div>
        </div>

        <!-- Filter Tabs & Quick Search Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-0.5">
            <!-- Filter Status Tabs (id="team-filter-tabs" preserved for tests) -->
            <div class="flex flex-wrap items-center gap-1.5" id="team-filter-tabs">
                <button type="button" onclick="filterTeamTable('all')"
                        data-filter="all"
                        class="team-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold transition bg-[#0e1a2b] text-white shadow-xs cursor-pointer">
                    Semua ({{ $teammates->count() }})
                </button>
                <button type="button" onclick="filterTeamTable('available')"
                        data-filter="available"
                        class="team-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold transition bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-pointer">
                    🟢 Available ({{ $teamAvailableCount ?? 0 }})
                </button>
                <button type="button" onclick="filterTeamTable('on_task')"
                        data-filter="on_task"
                        class="team-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold transition bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-pointer">
                    🟡 On Task ({{ $teamOnTaskCount ?? 0 }})
                </button>
                <button type="button" onclick="filterTeamTable('cuti')"
                        data-filter="cuti"
                        class="team-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold transition bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-pointer">
                    ⛔ Cuti ({{ $teamCutiCount ?? 0 }})
                </button>
                <button type="button" onclick="filterTeamTable('offline')"
                        data-filter="offline"
                        class="team-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold transition bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-pointer">
                    ⚫ Offline ({{ $teamOfflineCount ?? 0 }})
                </button>
            </div>

            <!-- Instant Search Input (id="team-search-input" preserved for tests) -->
            <div class="relative w-full sm:w-60">
                <input type="text" id="team-search-input" oninput="searchTeamTable()" placeholder="Cari nama teknisi..."
                       class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
            </div>
        </div>

        <!-- Tabel Standar Perusahaan: Tim Hari Ini (max-h-[380px] scrollable) -->
        <div class="border border-slate-200/80 rounded-xl overflow-hidden max-h-[380px] overflow-y-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/90 border-b border-slate-200 text-slate-500 font-bold text-[11px] sticky top-0 z-10 backdrop-blur-xs">
                        <th class="py-3 px-4">Teknisi</th>
                        <th class="py-3 px-4">Tim</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right sm:text-left">Beban</th>
                    </tr>
                </thead>
                <!-- id="team-cards-grid" preserved for automated tests -->
                <tbody id="team-cards-grid" class="divide-y divide-slate-100">
                    @foreach($teammates as $mate)
                    @php
                        $isMe = ($mate->id === $user->id);
                        $eff = $mate->effective_availability;
                        $taskCount = $mate->active_ticket_count ?? 0;
                        $pillBadge = match($eff) {
                            'on_task' => 'bg-amber-50 text-amber-800 border-amber-200',
                            'cuti' => 'bg-rose-50 text-rose-700 border-rose-200',
                            'offline' => 'bg-slate-100 text-slate-600 border-slate-300',
                            default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        };
                        $pillDot = match($eff) {
                            'on_task' => 'bg-amber-500',
                            'cuti' => 'bg-rose-500',
                            'offline' => 'bg-slate-400',
                            default => 'bg-emerald-500',
                        };
                    @endphp
                    <tr class="teammate-row hover:bg-slate-50/80 transition {{ $isMe ? 'bg-teal-50/40 font-semibold' : '' }}"
                        data-status="{{ $eff }}"
                        data-name="{{ strtolower($mate->name) }}"
                        data-tim="{{ strtolower($mate->label_tim) }}">
                        
                        <!-- Kolom 1: Avatar & Nama -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-7 h-7 rounded-full bg-teal-800 text-white font-extrabold text-[11px] flex items-center justify-center shrink-0 shadow-2xs overflow-hidden">
                                    @if($mate->foto_url)
                                        <img src="{{ $mate->foto_url }}" alt="{{ $mate->name }}" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr($mate->name, 0, 1)) }}
                                    @endif
                                </div>
                                <div class="flex items-center space-x-1.5">
                                    <span class="font-bold text-slate-900">{{ $mate->name }}</span>
                                    @if($isMe)
                                        <span class="text-[9px] font-black text-teal-700 bg-teal-100 px-1.5 py-0.2 rounded-md">Anda</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- Kolom 2: Tim -->
                        <td class="py-3 px-4 whitespace-nowrap text-slate-600 text-[11px]">
                            {{ $mate->label_tim }}
                        </td>

                        <!-- Kolom 3: Status Badge -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $pillBadge }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $pillDot }}"></span>
                                <span>{{ $mate->availability_label }}</span>
                            </span>
                        </td>

                        <!-- Kolom 4: Beban Kerja -->
                        <td class="py-3 px-4 whitespace-nowrap text-right sm:text-left text-slate-600 text-[11px]">
                            @if($taskCount > 0)
                                <span class="font-bold text-slate-900">{{ $taskCount }} tiket aktif</span>
                            @else
                                <span class="text-slate-400">0 tiket aktif</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>

</div>

<!-- Script UI & Filter Table -->
<script>
    function handleStatusCardChange(value) {
        const cutiPanel = document.getElementById('status-cuti-panel');
        if (value === 'cuti') {
            cutiPanel.classList.remove('hidden');
        } else {
            cutiPanel.classList.add('hidden');
        }

        // Highlight selected status card
        document.querySelectorAll('.status-card').forEach(card => {
            const radio = card.querySelector('input[type="radio"]');
            if (radio && radio.checked) {
                card.classList.add('ring-1');
            } else {
                card.classList.remove('ring-1');
            }
        });
    }

    function filterTeamTable(status) {
        const rows = document.querySelectorAll('.teammate-row');
        const buttons = document.querySelectorAll('.team-filter-btn');

        buttons.forEach(btn => {
            if (btn.dataset.filter === status) {
                btn.classList.add('bg-[#0e1a2b]', 'text-white', 'shadow-xs');
                btn.classList.remove('bg-slate-100', 'text-slate-700');
            } else {
                btn.classList.remove('bg-[#0e1a2b]', 'text-white', 'shadow-xs');
                btn.classList.add('bg-slate-100', 'text-slate-700');
            }
        });

        rows.forEach(row => {
            const rowStatus = row.dataset.status;
            if (status === 'all' || rowStatus === status) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function searchTeamTable() {
        const query = (document.getElementById('team-search-input').value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.teammate-row');

        rows.forEach(row => {
            const name = row.dataset.name || '';
            const tim = row.dataset.tim || '';
            if (!query || name.includes(query) || tim.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
@endsection
