@extends('layouts.app')

@section('title', request('filter') === 'my' ? 'Tiket Saya' : (request('filter') === 'report' ? 'Laporan Teknisi' : (request('filter') === 'history' ? 'Riwayat Tiket' : 'Antrian Tiket')))

@section('content')
@if(Auth::user()->isPegawai())
    <!-- PEGAWAI: TIKET SAYA -->
    <div class="space-y-6">
        @include('partials.flash-messages')

        <!-- Header: Title & Buat Tiket CTA -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Tiket Saya</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">Pantau status laporan Anda</p>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ route('services.create') }}" 
                   class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs shadow-sm transition">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Buat Tiket Baru</span>
                </a>
            </div>
        </div>

        <!-- Toolbar: Filter Pills, Search Bar, & View Switcher -->
        <div class="bg-white p-3 sm:p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            @php
                $curStatus = strtolower(request('status', 'semua'));
                $searchVal = request('search', '');
            @endphp

            <!-- Filter Pills -->
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                <a href="{{ route('services.index', array_merge(request()->except(['status', 'page']), ['status' => 'semua'])) }}" 
                   class="px-3 sm:px-3.5 py-1.5 rounded-full text-xs font-semibold transition inline-flex items-center space-x-1.5 {{ $curStatus === 'semua' || empty(request('status')) ? 'bg-[#0e1a2b] text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200/80' }}">
                    <span>Semua</span>
                    @if(isset($pegawaiStatusCounts['semua']))
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold {{ $curStatus === 'semua' || empty(request('status')) ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }}">
                            {{ $pegawaiStatusCounts['semua'] }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('services.index', array_merge(request()->except(['status', 'page']), ['status' => 'aktif'])) }}" 
                   class="px-3 sm:px-3.5 py-1.5 rounded-full text-xs font-semibold transition inline-flex items-center space-x-1.5 {{ $curStatus === 'aktif' ? 'bg-[#0e1a2b] text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200/80' }}">
                    <span>Aktif</span>
                    @if(isset($pegawaiStatusCounts['aktif']))
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold {{ $curStatus === 'aktif' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }}">
                            {{ $pegawaiStatusCounts['aktif'] }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('services.index', array_merge(request()->except(['status', 'page']), ['status' => 'perlu_konfirmasi'])) }}" 
                   class="px-3 sm:px-3.5 py-1.5 rounded-full text-xs font-semibold transition inline-flex items-center space-x-1.5 {{ $curStatus === 'perlu_konfirmasi' ? 'bg-[#0e1a2b] text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200/80' }}">
                    <span>Perlu Konfirmasi</span>
                    @if(($pegawaiStatusCounts['perlu_konfirmasi'] ?? $pegawaiNeedConfirmCount ?? 0) > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold {{ $curStatus === 'perlu_konfirmasi' ? 'bg-amber-400 text-slate-900' : 'bg-amber-100 text-amber-800' }}">
                            {{ $pegawaiStatusCounts['perlu_konfirmasi'] ?? $pegawaiNeedConfirmCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('services.index', array_merge(request()->except(['status', 'page']), ['status' => 'selesai'])) }}" 
                   class="px-3 sm:px-3.5 py-1.5 rounded-full text-xs font-semibold transition inline-flex items-center space-x-1.5 {{ $curStatus === 'selesai' ? 'bg-[#0e1a2b] text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200/80' }}">
                    <span>Selesai</span>
                    @if(isset($pegawaiStatusCounts['selesai']))
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold {{ $curStatus === 'selesai' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }}">
                            {{ $pegawaiStatusCounts['selesai'] }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('services.index', array_merge(request()->except(['status', 'page']), ['status' => 'dibatalkan'])) }}" 
                   class="px-3 sm:px-3.5 py-1.5 rounded-full text-xs font-semibold transition inline-flex items-center space-x-1.5 {{ $curStatus === 'dibatalkan' ? 'bg-[#0e1a2b] text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200/80' }}">
                    <span>Dibatalkan</span>
                    @if(isset($pegawaiStatusCounts['dibatalkan']))
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold {{ $curStatus === 'dibatalkan' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }}">
                            {{ $pegawaiStatusCounts['dibatalkan'] }}
                        </span>
                    @endif
                </a>
            </div>

            <!-- Search Bar & View Mode Toggle -->
            <div class="flex items-center gap-2.5">
                <form action="{{ route('services.index') }}" method="GET" class="relative flex-1 sm:w-64">
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    <input type="text" name="search" value="{{ $searchVal }}" 
                           placeholder="Cari tiket, kendala, lokasi..." 
                           class="w-full pl-8 pr-8 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent placeholder-slate-400">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    @if($searchVal)
                        <a href="{{ route('services.index', request()->except(['search', 'page'])) }}" 
                           class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs" 
                           title="Hapus pencarian">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </a>
                    @endif
                </form>

                <!-- Toggle Grid / Tabel -->
                <div class="flex items-center bg-slate-100 p-0.5 rounded-xl border border-slate-200/80 shrink-0">
                    <button type="button" id="btn-view-grid" onclick="setPegawaiView('grid')" 
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition bg-[#0e1a2b] text-white shadow-xs" 
                            title="Tampilan Kartu">
                        <i class="fa-solid fa-table-cells-large mr-1 text-[11px]"></i>
                        <span class="hidden sm:inline">Kartu</span>
                    </button>
                    <button type="button" id="btn-view-table" onclick="setPegawaiView('table')" 
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition text-slate-600 hover:text-slate-900" 
                            title="Tampilan Tabel">
                        <i class="fa-solid fa-list mr-1 text-[11px]"></i>
                        <span class="hidden sm:inline">Tabel</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 1. TAMPILAN KARTU (GRID 3 KOLOM) -->
        <div id="pegawai-view-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($services as $ticket)
            @php
                $isBatal = ($ticket->status === 'Dibatalkan');
                $iconPerangkat = match(strtolower($ticket->jenis_perangkat ?? '')) {
                    'pc', 'hardware', 'komputer' => 'fa-desktop',
                    'laptop' => 'fa-laptop',
                    'printer' => 'fa-print',
                    'jaringan', 'lan', 'internet' => 'fa-network-wired',
                    'software', 'aplikasi' => 'fa-window-maximize',
                    default => 'fa-screwdriver-wrench'
                };
            @endphp
            <a href="{{ route('services.show', $ticket) }}" 
               class="p-4 sm:p-5 rounded-2xl border transition duration-150 flex flex-col justify-between group relative {{ $isBatal ? 'bg-slate-50/70 border-slate-200/80 hover:border-slate-300' : 'bg-white border-slate-200/80 shadow-xs hover:border-teal-500/50 hover:shadow-md' }}">
                
                <div>
                    <!-- Top Bar: No Tiket & Prioritas (Kiri) + Status Badge (Kanan) -->
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center space-x-2">
                            <span class="font-mono font-bold text-xs sm:text-sm tracking-wide {{ $isBatal ? 'text-slate-400 line-through' : 'text-slate-800 group-hover:text-teal-700' }} transition">
                                {{ $ticket->ticket_number }}
                            </span>

                            @if($ticket->prioritas === 'Mendesak')
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200/80 inline-flex items-center">
                                    <i class="fa-solid fa-fire text-[9px] mr-1 text-rose-500"></i> Mendesak
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200/70">
                                    {{ $ticket->prioritas }}
                                </span>
                            @endif
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="flex items-center space-x-1.5">
                            @if($ticket->status === 'Diproses')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200/70">
                                    Diproses
                                </span>
                            @elseif($ticket->status === 'Menunggu sparepart')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200/70">
                                    Sparepart
                                </span>
                            @elseif($ticket->status === 'Selesai')
                                @if(!$ticket->pelapor_confirmed_at)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        Perlu Konfirmasi
                                    </span>
                                @endif
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200/70">
                                    Selesai
                                </span>
                            @elseif($ticket->status === 'Dibatalkan')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200/80">
                                    Dibatalkan
                                </span>
                            @elseif($ticket->status === 'Baru')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    Baru
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ $ticket->status }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Middle Content: Ikon Perangkat + Judul Kendala + Detail Keluhan -->
                    <div class="mt-3 flex items-start space-x-2.5">
                        <div class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center text-xs {{ $isBatal ? 'bg-slate-100 text-slate-400' : 'bg-slate-100 text-slate-600 border border-slate-200/60' }}">
                            <i class="fa-solid {{ $iconPerangkat }}"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-semibold {{ $isBatal ? 'text-slate-500' : 'text-slate-900 group-hover:text-teal-700' }} leading-snug line-clamp-1 transition">
                                {{ $ticket->display_judul }}
                            </h3>
                            @if($ticket->keluhan && $ticket->keluhan !== $ticket->display_judul)
                                <p class="text-xs text-slate-500 mt-0.5 line-clamp-1 leading-relaxed">
                                    {{ $ticket->keluhan }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <!-- Chips & Metadata: Jenis Perangkat, Lokasi Ruangan & SLA -->
                    <div class="mt-3 flex flex-wrap items-center gap-1.5 text-[11px]">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-50 text-slate-600 font-medium border border-slate-200/60">
                            <i class="fa-solid fa-microchip text-[10px] mr-1 text-slate-400"></i>
                            {{ $ticket->jenis_perangkat ?? 'Perangkat' }}
                        </span>
                        @if($ticket->lokasi_ruangan)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-50 text-slate-600 font-medium border border-slate-200/60">
                            <i class="fa-solid fa-location-dot text-[10px] mr-1 text-slate-400"></i>
                            {{ $ticket->lokasi_ruangan }}
                        </span>
                        @endif

                        @if($ticket->sla_status === 'ontime')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-slate-500 font-medium">
                                <i class="fa-solid fa-check text-[10px] mr-1 text-emerald-600"></i> Tepat Waktu
                            </span>
                        @elseif($ticket->sla_status === 'overdue_finished')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-rose-600 font-medium">
                                <i class="fa-regular fa-clock text-[10px] mr-1"></i> Lewat SLA
                            </span>
                        @elseif($ticket->sla_status === 'breached')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-rose-600 font-semibold">
                                <i class="fa-regular fa-clock text-[10px] mr-1"></i> {{ $ticket->sla_remaining_text }}
                            </span>
                        @elseif($ticket->sla_status === 'warning')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-amber-700 font-medium">
                                <i class="fa-regular fa-hourglass-half text-[10px] mr-1"></i> {{ $ticket->sla_remaining_text }}
                            </span>
                        @elseif($ticket->sla_status === 'safe' && $ticket->status !== 'Dibatalkan')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-slate-500 font-medium">
                                <i class="fa-regular fa-clock text-[10px] mr-1 text-slate-400"></i> {{ $ticket->sla_remaining_text }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Footer: Tanggal/Waktu Lapor (WIB) & Status Teknisi -->
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400 font-medium">
                    <div class="flex items-center space-x-1.5 text-[11px] text-slate-500">
                        <i class="fa-regular fa-clock text-[10px] text-slate-400"></i>
                        <span>{{ $ticket->created_at->format('d M Y, H:i') }} WIB</span>
                    </div>

                    <div class="flex items-center space-x-2">
                        @if($ticket->is_team)
                            <div class="text-slate-700 font-semibold text-[11px] flex items-center space-x-1" title="{{ $ticket->teknisi_tim_label }}">
                                <i class="fa-solid fa-users text-[10px] text-slate-400"></i>
                                <span>{{ $ticket->teknisi_tim_label }}</span>
                            </div>
                        @elseif($ticket->teknisi_nama)
                            <div class="text-slate-700 font-semibold text-[11px] flex items-center space-x-1">
                                <i class="fa-solid fa-user-check text-[10px] text-slate-400"></i>
                                <span>{{ $ticket->teknisi_nama }}</span>
                            </div>
                        @elseif($ticket->status === 'Baru')
                            <span class="text-slate-400 text-[10px] flex items-center space-x-1">
                                <i class="fa-solid fa-hourglass-start text-[9px]"></i>
                                <span>Menunggu</span>
                            </span>
                        @endif

                        <span class="text-slate-400 group-hover:text-teal-600 group-hover:translate-x-1 transition duration-150 text-xs ml-1">
                            <i class="fa-solid fa-arrow-right"></i>
                        </span>
                    </div>
                </div>
            </a>
            @empty
            <div class="col-span-full bg-white p-10 rounded-2xl border border-dashed border-slate-300 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-teal-50 text-teal-700 mx-auto flex items-center justify-center text-lg">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <div class="text-sm font-bold text-slate-800">Tidak ada tiket pada kategori ini</div>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                    Laporkan keluhan atau perbaikan perangkat IT divisi Anda dengan menekan tombol Buat Tiket.
                </p>
                <a href="{{ route('services.create') }}" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-teal-700 text-white text-xs font-bold shadow-xs">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Buat Tiket Baru</span>
                </a>
            </div>
            @endforelse
        </div>

        <!-- 2. TAMPILAN TABEL / LIST VIEW -->
        <div id="pegawai-view-table" class="hidden bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-4">No. Tiket</th>
                            <th class="py-3 px-4">Kendala &amp; Lokasi</th>
                            <th class="py-3 px-4">Perangkat</th>
                            <th class="py-3 px-4">Prioritas</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Teknisi</th>
                            <th class="py-3 px-4">Waktu Lapor (WIB)</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($services as $ticket)
                        @php
                            $isBatal = ($ticket->status === 'Dibatalkan');
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition {{ $isBatal ? 'bg-slate-50/40 text-slate-500' : '' }}">
                            <td class="py-3 px-4 font-mono font-bold {{ $isBatal ? 'text-slate-400 line-through' : 'text-slate-900' }}">
                                {{ $ticket->ticket_number }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold {{ $isBatal ? 'text-slate-600' : 'text-slate-800' }}">{{ $ticket->display_judul }}</div>
                                @if($ticket->lokasi_ruangan)
                                    <div class="text-[11px] text-slate-400 flex items-center space-x-1 mt-0.5">
                                        <i class="fa-solid fa-location-dot text-[9px]"></i>
                                        <span>{{ $ticket->lokasi_ruangan }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                {{ $ticket->jenis_perangkat ?? '-' }}
                            </td>
                            <td class="py-3 px-4">
                                @if($ticket->prioritas === 'Mendesak')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200/80 inline-flex items-center">
                                        <i class="fa-solid fa-fire text-[9px] mr-1 text-rose-500"></i> Mendesak
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200/70">
                                        {{ $ticket->prioritas }}
                                    </span>
                                @endif

                                @if($ticket->sla_status === 'ontime')
                                    <div class="text-[10px] text-slate-500 font-medium mt-0.5 flex items-center">
                                        <i class="fa-solid fa-check text-[9px] mr-1 text-emerald-600"></i> Tepat Waktu
                                    </div>
                                @elseif($ticket->sla_status === 'overdue_finished')
                                    <div class="text-[10px] text-rose-600 font-medium mt-0.5 flex items-center">
                                        <i class="fa-regular fa-clock text-[9px] mr-1"></i> Lewat SLA
                                    </div>
                                @elseif($ticket->sla_status === 'breached')
                                    <div class="text-[10px] text-rose-600 font-semibold mt-0.5 flex items-center">
                                        <i class="fa-regular fa-clock text-[9px] mr-1"></i> {{ $ticket->sla_remaining_text }}
                                    </div>
                                @elseif($ticket->sla_status === 'warning')
                                    <div class="text-[10px] text-amber-700 font-medium mt-0.5 flex items-center">
                                        <i class="fa-regular fa-hourglass-half text-[9px] mr-1"></i> {{ $ticket->sla_remaining_text }}
                                    </div>
                                @elseif($ticket->sla_status === 'safe' && $ticket->status !== 'Dibatalkan')
                                    <div class="text-[10px] text-slate-400 mt-0.5 flex items-center">
                                        <i class="fa-regular fa-clock text-[9px] mr-1"></i> {{ $ticket->sla_remaining_text }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($ticket->status === 'Diproses')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200/70">
                                        Diproses
                                    </span>
                                @elseif($ticket->status === 'Menunggu sparepart')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200/70">
                                        Sparepart
                                    </span>
                                @elseif($ticket->status === 'Selesai')
                                    <div class="flex items-center space-x-1">
                                        @if(!$ticket->pelapor_confirmed_at)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                                Konfirmasi
                                            </span>
                                        @endif
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200/70">
                                            Selesai
                                        </span>
                                    </div>
                                @elseif($ticket->status === 'Dibatalkan')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200/80">
                                        Dibatalkan
                                    </span>
                                @elseif($ticket->status === 'Baru')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                        Baru
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $ticket->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($ticket->is_team)
                                    <span class="text-slate-700 font-semibold text-xs flex items-center space-x-1" title="{{ $ticket->teknisi_tim_label }}">
                                        <i class="fa-solid fa-users text-[10px] text-slate-400"></i>
                                        <span>{{ $ticket->teknisi_tim_label }}</span>
                                    </span>
                                @elseif($ticket->teknisi_nama)
                                    <span class="text-slate-700 font-semibold text-xs flex items-center space-x-1">
                                        <i class="fa-solid fa-user-check text-[10px] text-slate-400"></i>
                                        <span>{{ $ticket->teknisi_nama }}</span>
                                    </span>
                                @elseif($ticket->status === 'Baru')
                                    <span class="text-slate-400 text-[11px]">Menunggu teknisi</span>
                                @else
                                    <span class="text-slate-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                                {{ $ticket->created_at->format('d/m/Y H:i') }} WIB
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('services.show', $ticket) }}" 
                                   class="inline-flex items-center space-x-1 px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200/80 text-slate-700 font-semibold text-xs transition border border-slate-200/60">
                                    <span>Detail</span>
                                    <i class="fa-solid fa-chevron-right text-[9px] text-slate-400"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                Tidak ada tiket pada kategori ini
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pt-2">
            {{ $services->links() }}
        </div>
    </div>

    <!-- Script View Toggle Grid/Table -->
    <script>
        function setPegawaiView(mode) {
            const gridView = document.getElementById('pegawai-view-grid');
            const tableView = document.getElementById('pegawai-view-table');
            const btnGrid = document.getElementById('btn-view-grid');
            const btnTable = document.getElementById('btn-view-table');

            if (!gridView || !tableView) return;

            if (mode === 'table') {
                gridView.classList.add('hidden');
                tableView.classList.remove('hidden');
                if (btnTable && btnGrid) {
                    btnTable.classList.add('bg-[#0e1a2b]', 'text-white', 'shadow-xs');
                    btnTable.classList.remove('text-slate-600', 'hover:text-slate-900');
                    btnGrid.classList.remove('bg-[#0e1a2b]', 'text-white', 'shadow-xs');
                    btnGrid.classList.add('text-slate-600', 'hover:text-slate-900');
                }
                try { localStorage.setItem('pegawai_view_pref', 'table'); } catch (e) {}
            } else {
                tableView.classList.add('hidden');
                gridView.classList.remove('hidden');
                if (btnTable && btnGrid) {
                    btnGrid.classList.add('bg-[#0e1a2b]', 'text-white', 'shadow-xs');
                    btnGrid.classList.remove('text-slate-600', 'hover:text-slate-900');
                    btnTable.classList.remove('bg-[#0e1a2b]', 'text-white', 'shadow-xs');
                    btnTable.classList.add('text-slate-600', 'hover:text-slate-900');
                }
                try { localStorage.setItem('pegawai_view_pref', 'grid'); } catch (e) {}
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            try {
                const savedPref = localStorage.getItem('pegawai_view_pref');
                if (savedPref === 'table') {
                    setPegawaiView('table');
                }
            } catch (e) {}
        });
    </script>
@else
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            @if(request('filter') === 'report')
                <div class="flex items-center space-x-2.5">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Laporan Saya</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200/60">
                        Hanya milik Anda
                    </span>
                    <span class="sr-only">Laporan Teknisi</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                    Laporan penanganan dan rekap pekerjaan milik Anda sendiri
                </p>
            @else
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                    @if(request('filter') === 'my')
                        @if(request('teknisi') && request('teknisi') !== 'semua')
                            Tiket Penanganan {{ request('teknisi') }}
                        @elseif(!request('teknisi') && Auth::user()->isTeknisi())
                            Tiket Saya
                        @else
                            Seluruh Tugas Aktif Tim Teknisi
                        @endif
                    @elseif(request('filter') === 'all')
                        Semua Tiket IT Service Desk
                    @elseif(request('filter') === 'history')
                        Riwayat Tiket Selesai (Histori Servis)
                    @else
                        Antrian Tiket IT Service Desk
                    @endif
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    @if(request('filter') === 'my')
                        @if(request('teknisi') && request('teknisi') !== 'semua')
                            Daftar penanganan troubleshooting aktif yang sedang dikerjakan oleh {{ request('teknisi') }}
                        @elseif(!request('teknisi') && Auth::user()->isTeknisi())
                            Daftar penanganan troubleshooting aktif yang sedang Anda tangani
                        @else
                            Daftar seluruh troubleshooting aktif yang sedang dikerjakan oleh seluruh personil tim IT Support
                        @endif
                    @elseif(request('filter') === 'all')
                        Master seluruh data tiket keluhan dan servis perangkat IT lintas divisi PLTU Karangkandri
                    @elseif(request('filter') === 'history')
                        Arsip seluruh perbaikan hardware dan pengkabelan jaringan yang telah tuntas diselesaikan oleh tim IT Support
                    @else
                        Daftar keluhan baru dari divisi PLTU yang siap diambil dan ditangani teknisi
                    @endif
                </p>
            @endif
        </div>

        @if(Auth::user()->isPegawai())
        <div>
            <!-- Tombol Khusus Pegawai -->
            <a href="{{ route('services.create') }}" class="px-4 py-2 bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg text-xs shadow-xs transition flex items-center space-x-1.5">
                <i class="fa-solid fa-plus text-[10px]"></i>
                <span>Ajukan Perbaikan</span>
            </a>
        </div>
        @elseif(request('filter') === 'report')
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Tombol Ekspor Excel -->
            <a href="{{ route('services.export', array_merge(request()->query(), ['filter' => 'report'])) }}" 
               class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs flex items-center space-x-1.5"
               title="Unduh rekap data Excel (.csv) sesuai filter">
                <span>Ekspor Excel</span>
            </a>

            <!-- Tombol Cetak / PDF -->
            <a href="{{ route('services.cetak', array_merge(request()->query(), ['filter' => 'report'])) }}" target="_blank"
               class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs flex items-center space-x-1.5"
               title="Cetak Laporan">
                <span>Cetak / PDF</span>
                <span class="sr-only">Cetak Laporan</span>
            </a>

            <!-- Tanggal & Bell Notification Widget -->
            @include('partials.header-widget')
        </div>
        @elseif(Auth::user()->isTeknisi() || Auth::user()->isAdmin())
        <div class="flex items-center space-x-2.5">
            @include('partials.header-widget')
        </div>
        @endif
    </div>

    <!-- Alert Notifikasi Flash (Ramping & Auto-Dismiss) -->
    @include('partials.flash-messages')

    <!-- Ringkasan Metrik (Khusus Tiket Saya - Usulan A) -->
    @if(request('filter') === 'my' && !empty($myMetrics))
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Card 1: Total Tiket Ditangani -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Ditangani</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-0.5">{{ $myMetrics['total'] }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">
                    @if(request('teknisi') && request('teknisi') !== 'semua')
                        Penugasan servis {{ request('teknisi') }}
                    @elseif(!request('teknisi') && Auth::user()->isTeknisi())
                        Tugas aktif Anda
                    @else
                        Seluruh personil tim IT Support
                    @endif
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center text-sm shadow-xs border border-teal-100">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
        </div>

        <!-- Card 2: Sedang Dikerjakan -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Sedang Dikerjakan</div>
                <div class="text-2xl font-extrabold text-amber-600 mt-0.5">{{ $myMetrics['proses'] }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Dalam tahap investigasi & servis</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm shadow-xs border border-amber-100">
                <i class="fa-solid fa-screwdriver-wrench"></i>
            </div>
        </div>

        <!-- Card 3: Perbaikan Selesai -->
        <a href="{{ route('services.index', ['filter' => 'history']) }}" 
           class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between hover:border-emerald-300 hover:shadow-sm transition group">
            <div>
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider group-hover:text-emerald-700">Perbaikan Selesai</div>
                <div class="text-2xl font-extrabold text-emerald-600 mt-0.5">{{ $myMetrics['selesai'] }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Tuntas &bull; Buka Riwayat Tiket &rarr;</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm shadow-xs border border-emerald-100 group-hover:bg-emerald-600 group-hover:text-white transition">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </a>
    </div>
    @endif

    <!-- Ringkasan Metrik (Khusus Riwayat Tiket Selesai) -->
    @if(request('filter') === 'history' && !empty($myMetrics))
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Card 1: Total Tiket Selesai -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Tiket Tuntas</div>
                <div class="text-2xl font-extrabold text-emerald-600 mt-0.5">{{ $myMetrics['total_selesai'] }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Seluruh unit PC & jaringan beres</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-sm shadow-xs border border-emerald-100">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 2: Dinamis (Selesai Bulan Ini / Teknisi Terpilih) -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                @if(!empty($myMetrics['selected_teknisi']))
                    <div class="text-[11px] font-semibold text-teal-700 uppercase tracking-wider">
                        Diselesaikan {{ Str::limit($myMetrics['selected_teknisi'], 16) }}
                    </div>
                    <div class="text-2xl font-extrabold text-teal-700 mt-0.5">{{ $myMetrics['selesai_selected'] }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Kontribusi teknisi terpilih</div>
                @else
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Selesai Bulan Ini</div>
                    <div class="text-2xl font-extrabold text-teal-700 mt-0.5">{{ $myMetrics['selesai_bulan_ini'] }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Periode {{ \Carbon\Carbon::now()->locale('id')->isoFormat('MMMM Y') }}</div>
                @endif
            </div>
            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center text-sm shadow-xs border border-teal-100">
                @if(!empty($myMetrics['selected_teknisi']))
                    <i class="fa-solid fa-user-check"></i>
                @else
                    <i class="fa-solid fa-calendar-days"></i>
                @endif
            </div>
        </div>

        <!-- Card 3: Selesai Minggu Ini -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Selesai Minggu Ini</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-0.5">{{ $myMetrics['selesai_minggu_ini'] }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Performa respon cepat PLTU</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-700 flex items-center justify-center text-sm shadow-xs border border-slate-200">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
        </div>
    </div>
    @endif

    <!-- KHUSUS LAPORAN SAYA (ROLE TEKNISI) -->
    @if(request('filter') === 'report')
    <!-- Filter Card Khusus Laporan Saya (Periode) -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
        <form method="GET" action="{{ route('services.index') }}" id="filterReportForm" class="flex flex-wrap items-center justify-between gap-3">
            <input type="hidden" name="filter" value="report">

            <!-- Left: Periode (Tahun, Bulan, Minggu) -->
            <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
                <span class="text-xs font-bold text-slate-800 self-center mr-1">Periode</span>

                <!-- Dropdown Tahun -->
                <div class="relative">
                    <label class="block text-[10px] text-slate-400 font-semibold mb-0.5">Tahun</label>
                    @php $selRepTahun = request('tahun', 'all'); @endphp
                    <select name="tahun" onchange="document.getElementById('filterReportForm').submit()" 
                            class="bg-white border border-slate-200 text-xs rounded-xl px-3 py-1.5 font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-teal-700">
                        <option value="all" {{ ($selRepTahun === 'all' || $selRepTahun === '') ? 'selected' : '' }}>Semua tahun</option>
                        @foreach($years ?? range(2026, 2030) as $yr)
                            <option value="{{ $yr }}" {{ (string) $selRepTahun === (string) $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Dropdown Bulan -->
                <div class="relative">
                    <label class="block text-[10px] text-slate-400 font-semibold mb-0.5">Bulan</label>
                    @php $selRepBulan = request('bulan', 'all'); @endphp
                    <select name="bulan" onchange="document.getElementById('filterReportForm').submit()" 
                            class="bg-white border border-slate-200 text-xs rounded-xl px-3 py-1.5 font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-teal-700">
                        <option value="all" {{ ($selRepBulan === 'all' || $selRepBulan === '') ? 'selected' : '' }}>Semua bulan</option>
                        @foreach($months ?? [] as $num => $namaBulan)
                            <option value="{{ $num }}" {{ (string) $selRepBulan === (string) $num ? 'selected' : '' }}>{{ $namaBulan }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Dropdown Minggu -->
                <div class="relative">
                    <label class="block text-[10px] text-slate-400 font-semibold mb-0.5">Minggu</label>
                    <select name="minggu" onchange="document.getElementById('filterReportForm').submit()" 
                            class="bg-white border border-slate-200 text-xs rounded-xl px-3 py-1.5 font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-teal-700">
                        <option value="">Semua minggu</option>
                        @foreach($weeks ?? [] as $wNum => $wLabel)
                            <option value="{{ $wNum }}" {{ request('minggu') == $wNum ? 'selected' : '' }}>{{ $wLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Right: Reset Filter if active -->
            @if(($selRepTahun !== 'all' && $selRepTahun !== '') || ($selRepBulan !== 'all' && $selRepBulan !== '') || request('minggu') || request('search') || request('quick'))
            <div class="flex items-center self-center">
                <a href="{{ route('services.index', ['filter' => 'report']) }}" class="text-rose-600 hover:text-rose-700 hover:underline font-semibold text-xs flex items-center space-x-1.5" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left text-[10px]"></i>
                    <span>Reset Filter</span>
                </a>
            </div>
            @endif
        </form>
    </div>

    <!-- 3 KPI Cards Laporan Saya -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Card 1: Tiket Selesai -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div class="w-7 h-7 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-check"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-xs font-semibold text-slate-600">Tiket selesai</div>
                <div class="text-3xl font-extrabold text-slate-900 mt-1">{{ $myMetrics['tiket_selesai'] ?? 0 }}</div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100/80 flex items-center justify-between text-xs text-slate-400">
                <span>Sudah ada laporan terkirim</span>
                <span class="text-emerald-500 font-bold">&rarr;</span>
            </div>
        </div>

        <!-- Card 2: Laporan Draft -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div class="w-7 h-7 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-xs font-semibold text-slate-600">Laporan draft</div>
                <div class="text-3xl font-extrabold text-slate-900 mt-1">{{ $myMetrics['laporan_draft'] ?? 0 }}</div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100/80 flex items-center justify-between text-xs text-slate-400">
                <span>Belum dikirim</span>
                <span class="text-amber-500 font-bold">&rarr;</span>
            </div>
        </div>

        <!-- Card 3: Rata-rata Waktu Saya (Bisa diklik untuk lihat rincian durasi) -->
        <button type="button" onclick="openAvgDurationModal()"
                class="text-left bg-white p-5 rounded-2xl border border-slate-200/80 hover:border-blue-300 hover:shadow-sm shadow-xs flex flex-col justify-between transition cursor-pointer group">
            <div class="flex items-center justify-between w-full">
                <div class="w-8 h-8 rounded-lg bg-blue-500 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div class="w-7 h-7 rounded-full bg-blue-50 group-hover:bg-blue-100 text-blue-600 flex items-center justify-center text-xs transition">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div class="mt-4 w-full">
                <div class="text-xs font-semibold text-slate-600">Rata-rata waktu saya</div>
                <div class="text-3xl font-extrabold text-slate-900 mt-1">{{ $myMetrics['avg_hours'] ?? '—' }}</div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100/80 flex items-center justify-between w-full text-xs text-slate-400 gap-2">
                <span class="truncate">{{ $myMetrics['period_label'] ?? 'Dari tiket yang selesai' }}</span>
                <span class="text-blue-600 font-bold shrink-0 group-hover:translate-x-0.5 transition">Rincian &rarr;</span>
            </div>
        </button>
    </div>
    @elseif(request('filter') === 'history')
    <!-- Filter Card Khusus Riwayat Tiket (Tahun, Bulan, Minggu, Teknisi, Prioritas, Pencarian) -->
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('services.index') }}" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-12 gap-2 text-xs">
            <input type="hidden" name="filter" value="history">

            <!-- Pencarian Kata Kunci (3 cols on lg) -->
            <div class="col-span-2 sm:col-span-3 lg:col-span-3 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari tiket, keluhan, pelapor..."
                    class="w-full pl-3 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                </span>
            </div>

            <!-- Dropdown Tahun (1 col on lg) -->
            <div class="col-span-1 lg:col-span-1">
                <select name="tahun" class="w-full py-2 px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                    <option value="">Semua tahun</option>
                    @foreach($years ?? range(2026, 2030) as $yr)
                        <option value="{{ $yr }}" {{ request('tahun') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Dropdown Bulan (2 cols on lg) -->
            <div class="col-span-1 lg:col-span-2">
                <select name="bulan" class="w-full py-2 px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                    <option value="">Semua bulan</option>
                    @foreach($months ?? [] as $num => $namaBulan)
                        <option value="{{ $num }}" {{ request('bulan') == $num ? 'selected' : '' }}>{{ $namaBulan }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Dropdown Minggu (2 cols on lg) -->
            <div class="col-span-1 lg:col-span-2">
                <select name="minggu" class="w-full py-2 px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                    <option value="">Semua minggu</option>
                    @foreach($weeks ?? [] as $wNum => $wLabel)
                        <option value="{{ $wNum }}" {{ request('minggu') == $wNum ? 'selected' : '' }}>{{ $wLabel }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Dropdown Teknisi (2 cols on lg) -->
            <div class="col-span-1 lg:col-span-2">
                <select name="teknisi" class="w-full py-2 px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                    <option value="">-- Teknisi --</option>
                    @foreach($teknisiList ?? [] as $tek)
                        <option value="{{ $tek }}" {{ request('teknisi') === $tek ? 'selected' : '' }}>
                            {{ $tek }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Prioritas & Submit (2 cols on lg) -->
            <div class="col-span-2 sm:col-span-2 lg:col-span-2 flex space-x-1.5 items-center">
                <select name="prioritas" class="w-full py-2 px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                    <option value="">-- Prioritas --</option>
                    <option value="Rendah" {{ request('prioritas') === 'Rendah' ? 'selected' : '' }}>Rendah</option>
                    <option value="Sedang" {{ request('prioritas') === 'Sedang' ? 'selected' : '' }}>Sedang</option>
                    <option value="Mendesak" {{ request('prioritas') === 'Mendesak' ? 'selected' : '' }}>Mendesak</option>
                </select>

                <button type="submit" class="px-3.5 py-2 bg-[#0e1a2b] hover:bg-[#152438] text-white font-semibold rounded-lg transition flex items-center space-x-1 flex-shrink-0" title="Terapkan Filter">
                    <i class="fa-solid fa-filter text-[11px]"></i>
                    <span class="hidden sm:inline">Filter</span>
                </button>

                @if(request('search') || request('tahun') || request('bulan') || request('minggu') || request('teknisi') || request('prioritas'))
                    <a href="{{ route('services.index', ['filter' => 'history']) }}" class="p-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition flex-shrink-0" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
    @else
    <!-- Filter & Search Bar Standar (Untuk Tiket Saya & Antrian Tiket) -->
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs space-y-3">
        @if(request('filter') === 'my' && Auth::user()->isTeknisi())
        <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-slate-100">
            @php
                $isMyOnly = !request('teknisi') || (Auth::user()->isTeknisi() && mb_strtolower(trim(request('teknisi'))) === mb_strtolower(trim(Auth::user()->name)));
            @endphp
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                <a href="{{ route('services.index', array_filter(['filter' => 'my', 'search' => request('search'), 'status' => request('status'), 'prioritas' => request('prioritas')])) }}"
                   class="px-3 py-1.5 rounded-lg font-semibold transition {{ $isMyOnly ? 'bg-teal-700 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Tugas Saya ({{ $myMetrics['own_active'] ?? 0 }})
                </a>
                <a href="{{ route('services.index', array_filter(['filter' => 'my', 'teknisi' => 'semua', 'search' => request('search'), 'status' => request('status'), 'prioritas' => request('prioritas')])) }}"
                   class="px-3 py-1.5 rounded-lg font-semibold transition {{ !$isMyOnly ? 'bg-[#0e1a2b] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Semua Tim ({{ $myMetrics['team_active'] ?? ($myMetrics['total'] ?? 0) }})
                </a>
            </div>
            <span class="text-[11px] text-slate-400">Menampilkan {{ $isMyOnly ? 'tugas aktif Anda' : 'seluruh tugas aktif tim teknisi' }}</span>
        </div>
        @endif
        <form method="GET" action="{{ route('services.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
            @if(request('filter'))
                <input type="hidden" name="filter" value="{{ request('filter') }}">
            @endif

            @if(request('filter') === 'my')
                @if(Auth::user()->isAdmin())
                <!-- Input Pencarian (Admin: 4 cols) -->
                <div class="sm:col-span-4 relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari no. tiket, nama pelapor, divisi, keluhan..."
                        class="w-full pl-3 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                    </span>
                </div>

                <!-- Dropdown Teknisi Penanggung Jawab (Khusus Admin: 3 cols) -->
                <div class="sm:col-span-3">
                    <select name="teknisi" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700 font-medium">
                        <option value="semua" {{ (!request('teknisi') || request('teknisi') === 'semua') ? 'selected' : '' }}>-- Semua Teknisi Bertugas --</option>
                        @foreach($teknisiList ?? [] as $tek)
                            <option value="{{ $tek }}" {{ request('teknisi') === $tek ? 'selected' : '' }}>
                                {{ $tek }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Dropdown Status Tugas (Admin: 2 cols) -->
                <div class="sm:col-span-2">
                    <select name="status" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                        <option value="">-- Status --</option>
                        <option value="Diproses" {{ request('status') === 'Diproses' ? 'selected' : '' }}>Diproses</option>
                        <option value="Menunggu sparepart" {{ request('status') === 'Menunggu sparepart' ? 'selected' : '' }}>Menunggu sparepart</option>
                    </select>
                </div>
                @else
                <!-- Input Pencarian (Teknisi: 6 cols - Bersih & Rapi tanpa dropdown teknisi lain) -->
                <div class="sm:col-span-6 relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari no. tiket, nama pelapor, divisi, keluhan..."
                        class="w-full pl-3 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                    </span>
                </div>

                <!-- Dropdown Status Tugas (Teknisi: 3 cols) -->
                <div class="sm:col-span-3">
                    <select name="status" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                        <option value="">-- Status --</option>
                        <option value="Diproses" {{ request('status') === 'Diproses' ? 'selected' : '' }}>Diproses</option>
                        <option value="Menunggu sparepart" {{ request('status') === 'Menunggu sparepart' ? 'selected' : '' }}>Menunggu sparepart</option>
                    </select>
                </div>
                @endif
            @else
            <!-- Input Pencarian Standar (6 cols) -->
            <div class="sm:col-span-6 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari no. tiket, nama pelapor, divisi, atau keluhan..."
                    class="w-full pl-3 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                </span>
            </div>

            <!-- Dropdown Status Standar (3 cols) -->
            <div class="sm:col-span-3">
                <select name="status" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                    <option value="">-- Semua Status --</option>
                    <option value="Baru" {{ request('status') === 'Baru' ? 'selected' : '' }}>Baru</option>
                    <option value="Diproses" {{ request('status') === 'Diproses' ? 'selected' : '' }}>Diproses</option>
                    <option value="Menunggu sparepart" {{ request('status') === 'Menunggu sparepart' ? 'selected' : '' }}>Menunggu sparepart</option>
                    <option value="Selesai" {{ request('status') === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="Dibatalkan" {{ request('status') === 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            @endif

            <div class="sm:col-span-3 flex space-x-2 items-center">
                <select name="prioritas" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                    <option value="">-- Prioritas --</option>
                    <option value="Rendah" {{ request('prioritas') === 'Rendah' ? 'selected' : '' }}>Rendah</option>
                    <option value="Sedang" {{ request('prioritas') === 'Sedang' ? 'selected' : '' }}>Sedang</option>
                    <option value="Mendesak" {{ request('prioritas') === 'Mendesak' ? 'selected' : '' }}>Mendesak</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-[#0e1a2b] hover:bg-[#152438] text-white font-semibold rounded-lg transition flex-shrink-0">
                    Filter
                </button>

                @if(request('search') || request('teknisi') || request('status') || request('prioritas'))
                    <a href="{{ route('services.index', ['filter' => request('filter')]) }}" class="p-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition flex-shrink-0" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
    @endif

    @if(request('filter') === 'report')
    <!-- Table Card: DAFTAR LAPORAN SAYA (ROLE TEKNISI) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-100">
            <div>
                <h2 class="text-base sm:text-lg font-bold text-slate-900">Daftar laporan saya</h2>
            </div>
            <div class="text-xs text-slate-400 font-medium">
                Laporan baru dibuat dari tombol Buat Laporan di Tiket Saya
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="text-slate-400 text-xs font-semibold border-b border-slate-100 pb-3">
                        <th class="py-3 px-4 font-semibold">No.</th>
                        <th class="py-3 px-4 font-semibold">Kendala &amp; Lokasi</th>
                        <th class="py-3 px-4 font-semibold">Status laporan</th>
                        <th class="py-3 px-4 font-semibold">Durasi</th>
                        <th class="py-3 px-4 font-semibold">Waktu</th>
                        <th class="py-3 px-4 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($services as $ticket)
                    @php
                        $isSelesai = $ticket->status === 'Selesai';
                        $durasiText = $ticket->formatted_durasi;
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3.5 px-4 font-bold text-slate-800 whitespace-nowrap">
                            <a href="{{ route('services.show', $ticket) }}" class="hover:text-teal-700">
                                {{ $ticket->ticket_number }}
                            </a>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-800 text-xs line-clamp-1" title="{{ $ticket->keluhan }}">
                                {{ Str::limit($ticket->keluhan, 45) }}
                            </div>
                            <div class="text-slate-400 text-[11px] mt-0.5">
                                {{ $ticket->lokasi_ruangan }}
                            </div>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($isSelesai)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                    Terkirim
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">
                                    Draft
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($durasiText !== '—')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold text-xs" title="Durasi riil dari tiket dilaporkan hingga selesai">
                                    <i class="fa-regular fa-clock text-[10px] text-teal-600 mr-1.5"></i>
                                    {{ $durasiText }}
                                </span>
                            @else
                                <span class="text-slate-400 font-medium">—</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($isSelesai && $ticket->tanggal_selesai)
                                <div class="font-bold text-slate-800 text-xs flex items-center space-x-1.5" title="Waktu perbaikan selesai">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>{{ $ticket->tanggal_selesai->format('d/m/Y · H:i') }} WIB</span>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5 pl-3" title="Waktu tiket dilaporkan masuk">
                                    Masuk: {{ $ticket->created_at->format('d/m/Y · H:i') }} WIB
                                </div>
                            @else
                                <div class="font-semibold text-slate-700 text-xs">
                                    {{ $ticket->updated_at->format('d/m/Y · H:i') }} WIB
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    Masuk: {{ $ticket->created_at->format('d/m/Y · H:i') }} WIB
                                </div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                            @if($isSelesai)
                                <a href="{{ route('services.show', $ticket) }}" 
                                   class="inline-block px-3.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                                    Lihat
                                </a>
                            @else
                                <a href="{{ route('services.show', $ticket) }}" 
                                   class="inline-block px-3 py-1 rounded-lg bg-[#0d6e6e] hover:bg-teal-800 text-white font-semibold text-xs transition shadow-xs">
                                    Lanjutkan
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center mx-auto mb-3 text-lg">
                                <i class="fa-solid fa-file-circle-check"></i>
                            </div>
                            <div class="font-bold text-slate-800 text-sm">Belum ada laporan pada periode ini</div>
                            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                Selesaikan tiket di menu Tiket Saya untuk otomatis menghasilkan laporan servis baru.
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($services->hasPages())
        <div class="pt-2">
            {{ $services->links() }}
        </div>
        @endif
    </div>
    @else
    <!-- Table Card Standar (Antrian, Tiket Saya, Riwayat, Semua Tiket) -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/60 text-slate-500 font-semibold text-[11px]">
                        <th class="py-3 px-4">No.</th>
                        <th class="py-3 px-4">{{ in_array(request('filter'), ['history', 'report']) ? 'Tgl Selesai' : 'Tanggal' }}</th>
                        <th class="py-3 px-4">Divisi & Pelapor</th>
                        <th class="py-3 px-4">Perangkat & Lokasi</th>
                        <th class="py-3 px-4">Kendala Kerusakan</th>
                        <th class="py-3 px-4">Prioritas</th>
                        <th class="py-3 px-4">Status</th>
                        @if(request('filter') === 'my')
                            <th class="py-3 px-4">Teknisi Bertugas</th>
                            <th class="py-3 px-4">Progres Checklist</th>
                        @else
                            <th class="py-3 px-4">{{ in_array(request('filter'), ['history', 'report']) ? 'Teknisi Pelaksana' : 'Teknisi' }}</th>
                        @endif
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($services as $ticket)
                    @php
                        $canOpen = $ticket->canBeOpenedBy(Auth::user());
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3 px-4 font-semibold text-slate-900 whitespace-nowrap">
                            @if($canOpen)
                                <a href="{{ route('services.show', $ticket) }}" class="hover:underline text-teal-800">
                                    {{ $ticket->ticket_number }}
                                </a>
                            @else
                                <span class="text-slate-500 font-medium cursor-not-allowed" title="Hanya dapat dibuka oleh {{ $ticket->teknisi_nama ?? 'teknisi penanggung jawab' }}">
                                    {{ $ticket->ticket_number }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-slate-500 text-[11px]">
                            @if(in_array(request('filter'), ['history', 'report']) && $ticket->tanggal_selesai)
                                <div class="font-semibold text-slate-800">{{ $ticket->tanggal_selesai->format('d/m/Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $ticket->tanggal_selesai->format('H:i') }} WIB</div>
                            @else
                                <div class="font-medium text-slate-700">{{ $ticket->created_at->format('d/m/Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $ticket->created_at->format('H:i') }} WIB</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="font-bold text-slate-900">{{ $ticket->divisi }}</div>
                            <div class="text-slate-400 text-[11px]">{{ $ticket->nama_pelapor }}</div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-medium text-slate-800">{{ $ticket->jenis_perangkat }}</div>
                            <div class="text-slate-400 text-[10px]">{{ $ticket->lokasi_ruangan }}</div>
                        </td>
                        <td class="py-3 px-4 max-w-xs">
                            <div class="text-slate-700 truncate font-medium" title="{{ $ticket->keluhan }}">{{ $ticket->keluhan }}</div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($ticket->prioritas === 'Mendesak')
                                <span class="px-2.5 py-0.5 rounded-full font-semibold bg-rose-50 text-rose-700 text-[10px] border border-rose-200/60">
                                    Mendesak
                                </span>
                            @elseif($ticket->prioritas === 'Sedang' || $ticket->prioritas === 'Tinggi')
                                <span class="px-2.5 py-0.5 rounded-full font-semibold bg-amber-50 text-amber-700 text-[10px] border border-amber-200/60">
                                    Sedang
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full font-semibold bg-sky-50 text-sky-700 text-[10px] border border-sky-200/60">
                                    Rendah
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($ticket->status === 'Diproses')
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-amber-50 text-amber-700 border border-amber-200 text-[10px]">
                                    Diproses
                                </span>
                            @elseif($ticket->status === 'Menunggu sparepart')
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-purple-50 text-purple-700 border border-purple-200 text-[10px]">
                                    Menunggu sparepart
                                </span>
                            @elseif($ticket->status === 'Selesai')
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px]">
                                    Selesai
                                </span>
                            @elseif($ticket->status === 'Baru')
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-blue-50 text-blue-700 border border-blue-200 text-[10px]">
                                    Baru
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-slate-100 text-slate-600 text-[10px]">
                                    Dibatalkan
                                </span>
                            @endif
                        </td>
                        @if(request('filter') === 'my')
                        <!-- Kolom Teknisi Bertugas -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <div class="w-7 h-7 rounded-lg bg-teal-50 border border-teal-200/80 text-teal-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                    <i class="fa-solid fa-user-gear text-[10px]"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 text-xs">
                                        {{ $ticket->teknisi_nama ?? 'Belum Ditugaskan' }}
                                    </div>
                                    <div class="text-[10px] text-teal-600 font-medium">
                                        Sedang menangani
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Kolom Progres Checklist -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            @php
                                $chk = $ticket->checklist ?? [];
                                $totalChk = count($chk);
                                $doneChk = count(array_filter($chk, fn($c) => !empty($c['done'])));
                                $percent = $totalChk > 0 ? round(($doneChk / $totalChk) * 100) : 0;
                            @endphp

                            @if($ticket->status === 'Selesai')
                                <div class="flex items-center space-x-1.5 text-emerald-700 font-bold text-xs">
                                    <i class="fa-solid fa-circle-check text-[11px]"></i>
                                    <span>Selesai ({{ $doneChk }}/{{ $totalChk }})</span>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    {{ $ticket->tanggal_selesai ? $ticket->tanggal_selesai->format('d/m/Y H:i') : '100% tuntas' }}
                                </div>
                            @else
                                <div class="w-32">
                                    <div class="flex justify-between items-center text-[10px] font-semibold text-slate-600 mb-1">
                                        <span class="flex items-center space-x-1">
                                            <i class="fa-solid fa-list-check text-teal-600 text-[9px]"></i>
                                            <span>{{ $doneChk }}/{{ $totalChk }} Cek</span>
                                        </span>
                                        <span class="font-bold text-teal-700">{{ $percent }}%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200/60">
                                        <div class="h-full bg-teal-600 rounded-full transition-all duration-300" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </td>
                        @else
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($ticket->teknisi_nama)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $ticket->teknisi_nama === Auth::user()->name ? 'bg-teal-50 text-teal-800 border border-teal-200' : 'bg-slate-100 text-slate-700' }}">
                                    @if($ticket->teknisi_nama === Auth::user()->name)
                                        <i class="fa-solid fa-user-check mr-1 text-[9px]"></i>
                                    @endif
                                    {{ $ticket->teknisi_nama }}
                                </span>
                            @elseif($ticket->teknisi)
                                <span class="text-slate-700 font-medium">{{ $ticket->teknisi->name }}</span>
                            @else
                                <span class="text-slate-400 italic">Belum ada</span>
                            @endif
                        </td>
                        @endif
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center space-x-1.5">
                                @if(Auth::user()->isTeknisi() && $ticket->status === 'Baru' && empty($ticket->teknisi_id))
                                <button type="button" 
                                        onclick="openClaimModal({{ json_encode([
                                            'id' => $ticket->id,
                                            'number' => $ticket->ticket_number,
                                            'divisi' => $ticket->divisi,
                                            'lokasi' => $ticket->lokasi_ruangan,
                                            'perangkat' => $ticket->jenis_perangkat,
                                            'kode' => $ticket->kode_perangkat,
                                            'prioritas' => $ticket->prioritas,
                                            'keluhan' => $ticket->keluhan,
                                            'pelapor' => $ticket->nama_pelapor,
                                            'url' => route('services.claim', $ticket)
                                        ]) }})"
                                        title="Pilih teknisi dan ambil tiket mandiri"
                                        class="px-2.5 py-1 rounded-lg bg-teal-700 hover:bg-teal-800 text-white font-semibold text-[11px] shadow-xs transition flex items-center space-x-1 cursor-pointer">
                                    <i class="fa-solid fa-hand-holding-hand text-[10px]"></i>
                                    <span>Ambil</span>
                                </button>

                                <!-- Tombol Aksi Tim (Multi-Teknisi) -->
                                <button type="button" 
                                        onclick="openTeamClaimModal({{ json_encode([
                                            'id' => $ticket->id,
                                            'number' => $ticket->ticket_number,
                                            'divisi' => $ticket->divisi,
                                            'lokasi' => $ticket->lokasi_ruangan,
                                            'perangkat' => $ticket->jenis_perangkat,
                                            'kode' => $ticket->kode_perangkat,
                                            'prioritas' => $ticket->prioritas,
                                            'keluhan' => Str::limit($ticket->keluhan, 90),
                                            'pelapor' => $ticket->nama_pelapor,
                                            'url' => route('services.claim', $ticket)
                                        ]) }})"
                                        title="Ambil sebagai Kerja Tim (Multi-Teknisi)"
                                        class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg text-[11px] border border-indigo-200/80 transition flex items-center space-x-1 cursor-pointer">
                                    <i class="fa-solid fa-users text-[10px]"></i>
                                    <span>Tim</span>
                                </button>
                                @endif
                                @if($canOpen)
                                <a href="{{ route('services.show', $ticket) }}" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition inline-block text-[11px] font-semibold">
                                    {{ $ticket->status === 'Baru' && empty($ticket->teknisi_id) ? 'Detail' : 'Buka Tiket' }}
                                </a>
                                @else
                                <span class="text-slate-300 font-semibold text-xs px-2 select-none" title="Hanya dapat dibuka oleh {{ $ticket->teknisi_nama ?? 'teknisi yang menangani' }}">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center mx-auto mb-3 text-lg">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div class="font-bold text-slate-800 text-sm">
                                @if(request('filter') === 'history')
                                    Belum ada riwayat tiket yang berstatus selesai
                                @elseif(request('filter') === 'my')
                                    Tidak ada tugas servis aktif saat ini
                                @else
                                    Tidak ada tiket dalam antrian
                                @endif
                            </div>
                            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                @if(request('filter') === 'history')
                                    Seluruh tiket yang telah selesai diperbaiki akan diarsipkan di halaman riwayat ini.
                                @elseif(request('filter') === 'my')
                                    Semua tugas troubleshooting Anda telah selesai dan otomatis tersimpan di menu Riwayat Tiket. Ambil tiket baru di menu Antrian Tiket jika ada keluhan masuk.
                                @else
                                    Semua tiket baru telah diambil teknisi atau belum ada laporan kerusakan baru dari divisi PLTU.
                                @endif
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($services->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $services->links() }}
        </div>
        @endif
    </div>
    @endif

</div>

<!-- MODAL POP-UP: KONFIRMASI PENUGASAN TEKNISI (OPSI 1) -->
<div id="claimModal" class="fixed inset-0 z-50 hidden transition-opacity duration-200">
    <!-- Backdrop Blur -->
    <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs" onclick="closeClaimModal()"></div>

    <!-- Modal Dialog Box -->
    <div class="fixed inset-0 flex items-center justify-center p-4 z-10 pointer-events-none">
        <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200/90 shadow-2xl p-6 pointer-events-auto transform transition-all duration-200 scale-95 opacity-0 space-y-4" id="claimModalBox">
            
            <!-- Modal Header -->
            <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 border border-teal-200/70 flex items-center justify-center text-sm shadow-xs flex-shrink-0">
                        <i class="fa-solid fa-hand-holding-hand"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 leading-tight">Ambil & Tugaskan Tiket</h3>
                        <div class="text-xs text-slate-500 font-medium mt-0.5">
                            Nomor Tiket: <span id="modalTicketNumber" class="font-mono font-bold text-teal-700">TKT-XXXX</span>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="closeClaimModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Ticket Problem Summary Box -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs space-y-2">
                <div class="flex items-center justify-between">
                    <span id="modalTicketDivisi" class="font-bold text-slate-800">Divisi</span>
                    <span id="modalTicketPriority" class="px-2 py-0.5 rounded-full font-bold text-[10px]">Prioritas</span>
                </div>
                <div class="text-[11px] text-slate-500 flex items-center gap-1.5">
                    <i class="fa-solid fa-desktop text-slate-400"></i>
                    <span id="modalTicketDevice" class="font-medium text-slate-700">Perangkat</span>
                    <span>&bull;</span>
                    <span id="modalTicketLocation">Lokasi</span>
                </div>
                <div class="bg-white p-2.5 rounded-lg border border-slate-200/70 text-slate-700 italic text-[11px] leading-relaxed" id="modalTicketComplaint">
                    Keluhan kerusakan...
                </div>
            </div>

            <!-- Form Penugasan Teknisi -->
            <form id="claimForm" method="POST" action="" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                        <span>Pilih Teknisi Pelaksana</span>
                        <span class="text-[10px] text-teal-700 font-semibold bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200/50">Wajib dipilih</span>
                    </label>
                    <div class="relative">
                        <select name="teknisi_nama" required class="w-full py-2.5 pl-3 pr-8 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-teal-700 focus:border-teal-700 shadow-xs text-slate-800 cursor-pointer">
                            @if(Auth::user()->isTeknisi())
                                <option value="{{ Auth::user()->name }}" selected>👤 {{ Auth::user()->name }} &bull; [Saya Sendiri]</option>
                            @endif
                            @foreach($teknisiList ?? [] as $tek)
                                @if($tek !== Auth::user()->name)
                                    <option value="{{ $tek }}" {{ (Auth::user()->isAdmin() && $loop->first) ? 'selected' : '' }}>👤 {{ $tek }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1.5 leading-snug">
                        Tiket ini akan otomatis <strong>keluar dari antrian umum</strong> dan masuk ke daftar kerja teknisi yang dipilih.
                    </p>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeClaimModal()" 
                            class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-teal-700 hover:bg-teal-800 shadow-md shadow-teal-900/20 transition flex items-center space-x-1.5">
                        <i class="fa-solid fa-check text-[11px]"></i>
                        <span>Konfirmasi Ambil Tiket</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- MODAL POP-UP: AKSI PENUGASAN / AMBIL BERSAMA TIM IT (MULTI-TEKNISI) -->
<div id="modal-team-claim" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-lg w-full overflow-hidden animate-in fade-in duration-200">
        <!-- Header Modal -->
        <div class="px-6 py-4 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-300">
                    <i class="fa-solid fa-users text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-white">Kerja Tim IT (Multi-Teknisi)</h3>
                    <p class="text-[11px] text-slate-300">Ambil tiket dan kerjakan bersama rekan teknisi lapangan</p>
                </div>
            </div>
            <button type="button" onclick="closeTeamClaimModal()" class="text-slate-400 hover:text-white transition cursor-pointer p-1">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <form id="teamClaimForm" method="POST" action="" class="p-6 space-y-4">
            @csrf

            <!-- Detail Tiket Ringkas -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-teal-800 font-mono text-[11px]" id="team-claim-ticket-number">TKT-XXXX</span>
                    <span class="text-slate-500 font-semibold text-[11px]" id="team-claim-divisi">Divisi</span>
                </div>
                <div class="font-semibold text-slate-700 text-xs" id="team-claim-perangkat">Perangkat</div>
                <div class="text-slate-500 text-[11px] line-clamp-2 italic" id="team-claim-keluhan">Keluhan...</div>
            </div>

            <!-- 1. Pilih Teknisi Utama (Lead / PIC) -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5 flex items-center justify-between">
                    <span>Teknisi Penanggung Jawab (Lead / PIC) <span class="text-rose-500">*</span></span>
                    <span class="text-[10px] text-indigo-700 font-semibold bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200/50">Wajib dipilih</span>
                </label>
                <select name="teknisi_id" id="team-claim-pic" required onchange="updateTeamClaimCoTechAvailability()"
                        class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-600/30 focus:bg-white text-slate-800 font-medium">
                    @foreach($teknisiUsers ?? [] as $tek)
                        @php
                            $tasks = $tek->active_tasks_count ?? 0;
                            $eff = $tek->effective_availability;
                            $isBlocked = $tek->isCuti() || in_array(strtolower((string) $tek->status_ketersediaan), ['offline', 'off'], true);
                            $badge = match ($eff) {
                                'cuti' => '⛔ ' . $tek->availability_short_note,
                                'offline' => '⚫ ' . $tek->availability_short_note,
                                'on_task' => "🟡 On Task • {$tasks} tugas",
                                default => $tasks === 0 ? '🟢 Available • 0 tugas' : "🟢 Available • {$tasks} tugas",
                            };
                            $isSelf = $tek->id === Auth::id();
                        @endphp
                        <option value="{{ $tek->id }}" {{ $isSelf ? 'selected' : '' }} {{ $isBlocked ? 'disabled' : '' }}>
                            {{ $tek->name }} {{ $isSelf ? '(Saya Sendiri)' : '' }} — {{ $badge }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1">PIC bertanggung jawab mengkoordinasikan pekerjaan dan input laporan akhir.</p>
            </div>

            <!-- 2. Pilih Anggota Tim Pendamping -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5 flex items-center justify-between">
                    <span>Rekan Teknisi Pendamping (Pilih 1 atau lebih) <span class="text-rose-500">*</span></span>
                    <span class="text-[10px] text-slate-400 font-normal">Minimal pilih 1 rekan</span>
                </label>
                <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50 max-h-44 overflow-y-auto space-y-2">
                    @foreach($teknisiUsers ?? [] as $tek)
                        @php
                            $tasks = $tek->active_tasks_count ?? 0;
                            $eff = $tek->effective_availability;
                            $isBlocked = $tek->isCuti() || in_array(strtolower((string) $tek->status_ketersediaan), ['offline', 'off'], true);
                            $isSelf = $tek->id === Auth::id();
                        @endphp
                        <label class="flex items-center justify-between p-2 rounded-lg {{ $isBlocked ? 'bg-slate-50 opacity-60 cursor-not-allowed' : 'bg-white hover:border-indigo-300 cursor-pointer' }} border border-slate-200/70 transition select-none team-claim-co-option" data-tech-id="{{ $tek->id }}">
                            <div class="flex items-center space-x-2.5">
                                <input type="checkbox" name="teknisi_pendamping[]" value="{{ $tek->id }}" {{ $isBlocked ? 'disabled' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 co-tech-claim-checkbox cursor-pointer">
                                <span class="text-xs font-bold text-slate-800">{{ $tek->name }} {{ $isSelf ? '(Saya)' : '' }}</span>
                            </div>
                            <span class="inline-flex items-center space-x-1 text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $tek->availability_badge_class }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $tek->availability_dot_class }}"></span>
                                <span>{{ $tek->availability_label }}{{ in_array($eff, ['available', 'on_task'], true) ? " ({$tasks})" : '' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Tiket ini otomatis akan muncul di menu <strong>Tiket Saya</strong> pada seluruh akun teknisi yang dicentang.</p>
            </div>

            <!-- 3. Catatan Penugasan / Instruksi Kerja -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">
                    Instruksi / Catatan Kerja Tim Lapangan (Opsional)
                </label>
                <textarea name="catatan_penugasan" rows="2" placeholder="Contoh: Penarikan kabel LAN 4 titik di Control Room bersama Hafizh & Bagas..."
                          class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-600/30 focus:bg-white text-slate-800"></textarea>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-2 flex items-center justify-end space-x-2 border-t border-slate-100">
                <button type="button" onclick="closeTeamClaimModal()" class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-users-gear text-xs"></i>
                    <span>Ambil Bersama Tim IT</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@if(request('filter') === 'report' && !empty($myMetrics))
<!-- Modal Rincian Rata-rata Waktu Penanganan -->
<div id="modal-avg-duration" class="fixed inset-0 z-50 hidden bg-slate-950/50 backdrop-blur-xs flex items-center justify-center p-4"
     onclick="if(event.target === this) closeAvgDurationModal()">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Rincian Rata-rata Waktu Penanganan</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ $myMetrics['period_label'] ?? 'Periode aktif' }}</p>
            </div>
            <button type="button" onclick="closeAvgDurationModal()" class="text-slate-400 hover:text-slate-700 p-1 cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <div class="p-6 space-y-5">
            <!-- 3 Ringkasan Statistik -->
            <div class="grid grid-cols-3 gap-3 text-xs">
                <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-200/70">
                    <div class="text-[11px] font-semibold text-blue-700">Rata-rata</div>
                    <div class="text-lg font-black text-slate-900 mt-0.5">{{ $myMetrics['avg_hours'] ?? '—' }}</div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Dari {{ count($myMetrics['duration_breakdown'] ?? []) }} tiket selesai</div>
                </div>
                <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-200/70">
                    <div class="text-[11px] font-semibold text-emerald-700">Tercepat</div>
                    <div class="text-lg font-black text-slate-900 mt-0.5">{{ $myMetrics['fastest_duration'] ?? '—' }}</div>
                    <div class="text-[10px] text-slate-500 mt-0.5 font-mono">{{ $myMetrics['fastest_ticket'] ?? '-' }}</div>
                </div>
                <div class="p-3.5 rounded-xl bg-amber-50/60 border border-amber-200/70">
                    <div class="text-[11px] font-semibold text-amber-700">Terlama</div>
                    <div class="text-lg font-black text-slate-900 mt-0.5">{{ $myMetrics['slowest_duration'] ?? '—' }}</div>
                    <div class="text-[10px] text-slate-500 mt-0.5 font-mono">{{ $myMetrics['slowest_ticket'] ?? '-' }}</div>
                </div>
            </div>

            <!-- Daftar Rincian Tiket -->
            <div class="max-h-72 overflow-y-auto border border-slate-200/80 rounded-xl divide-y divide-slate-100 text-xs">
                @forelse(($myMetrics['duration_breakdown'] ?? []) as $item)
                    <a href="{{ $item['url'] }}" class="px-4 py-3 flex items-center justify-between gap-3 hover:bg-slate-50/80 transition">
                        <div class="min-w-0">
                            <div class="flex items-center space-x-2">
                                <span class="font-mono font-extrabold text-teal-800">{{ $item['ticket_number'] }}</span>
                                <span class="font-bold text-slate-800 truncate">{{ $item['judul'] }}</span>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                Masuk: {{ $item['created_label'] }} &rarr; Selesai: {{ $item['finished_label'] }}
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold shrink-0 {{ $item['duration_mins'] >= 1440 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                            {{ $item['duration_label'] }}
                        </span>
                    </a>
                @empty
                    <div class="py-8 text-center text-slate-400">
                        Belum ada tiket selesai pada periode ini.
                    </div>
                @endforelse
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <span class="text-[11px] text-slate-400">Durasi dihitung otomatis dari tiket dibuat hingga diselesaikan &amp; reset tiap ganti periode.</span>
                <button type="button" onclick="closeAvgDurationModal()"
                        class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<script>
    function openAvgDurationModal() {
        const el = document.getElementById('modal-avg-duration');
        if (el) el.classList.remove('hidden');
    }
    function closeAvgDurationModal() {
        const el = document.getElementById('modal-avg-duration');
        if (el) el.classList.add('hidden');
    }

    function openClaimModal(ticket) {
        document.getElementById('claimForm').action = ticket.url;
        document.getElementById('modalTicketNumber').innerText = '#' + ticket.number;
        document.getElementById('modalTicketDivisi').innerText = ticket.divisi + ' (Pelapor: ' + ticket.pelapor + ')';
        document.getElementById('modalTicketDevice').innerText = ticket.perangkat + (ticket.kode ? ' [' + ticket.kode + ']' : '');
        document.getElementById('modalTicketLocation').innerText = ticket.lokasi;
        document.getElementById('modalTicketComplaint').innerText = '"' + ticket.keluhan + '"';
        
        const priorityEl = document.getElementById('modalTicketPriority');
        priorityEl.innerText = ticket.prioritas;
        if (ticket.prioritas === 'Mendesak') {
            priorityEl.className = 'px-2 py-0.5 rounded-full font-bold text-[10px] bg-rose-50 text-rose-700 border border-rose-200/60';
        } else if (ticket.prioritas === 'Sedang' || ticket.prioritas === 'Tinggi') {
            priorityEl.className = 'px-2 py-0.5 rounded-full font-bold text-[10px] bg-amber-50 text-amber-700 border border-amber-200/60';
        } else {
            priorityEl.className = 'px-2 py-0.5 rounded-full font-bold text-[10px] bg-sky-50 text-sky-700 border border-sky-200/60';
        }

        const modal = document.getElementById('claimModal');
        const box = document.getElementById('claimModalBox');
        modal.classList.remove('hidden');
        setTimeout(() => {
            box.classList.remove('scale-95', 'opacity-0');
            box.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeClaimModal() {
        const modal = document.getElementById('claimModal');
        const box = document.getElementById('claimModalBox');
        box.classList.remove('scale-100', 'opacity-100');
        box.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 150);
    }

    function openTeamClaimModal(ticket) {
        const form = document.getElementById('teamClaimForm');
        if (!form) return;
        form.action = ticket.url;

        const numEl = document.getElementById('team-claim-ticket-number');
        if (numEl) numEl.textContent = '#' + ticket.number;

        const divEl = document.getElementById('team-claim-divisi');
        if (divEl) divEl.textContent = ticket.divisi + (ticket.pelapor ? ' (Pelapor: ' + ticket.pelapor + ')' : '');

        const devEl = document.getElementById('team-claim-perangkat');
        if (devEl) devEl.textContent = ticket.perangkat + (ticket.kode ? ' [' + ticket.kode + ']' : '') + ' • ' + ticket.lokasi;

        const kelEl = document.getElementById('team-claim-keluhan');
        if (kelEl) kelEl.textContent = ticket.keluhan;

        // Reset co-technician checkboxes
        document.querySelectorAll('.co-tech-claim-checkbox').forEach(cb => {
            cb.checked = false;
        });

        // Set default PIC to logged in user if available
        const defaultPicId = '{{ Auth::id() }}';
        const picSelect = document.getElementById('team-claim-pic');
        if (picSelect && defaultPicId) {
            picSelect.value = defaultPicId;
        }

        updateTeamClaimCoTechAvailability();
        const modal = document.getElementById('modal-team-claim');
        if (modal) modal.classList.remove('hidden');
    }

    function closeTeamClaimModal() {
        const modal = document.getElementById('modal-team-claim');
        if (modal) modal.classList.add('hidden');
    }

    function updateTeamClaimCoTechAvailability() {
        const picSelect = document.getElementById('team-claim-pic');
        if (!picSelect) return;
        const selectedPic = picSelect.value;
        document.querySelectorAll('.team-claim-co-option').forEach(el => {
            const techId = el.getAttribute('data-tech-id');
            const cb = el.querySelector('.co-tech-claim-checkbox');
            if (!cb) return;
            if (selectedPic && techId === selectedPic) {
                cb.checked = false;
                cb.disabled = true;
                el.classList.add('opacity-40', 'pointer-events-none');
            } else {
                cb.disabled = false;
                el.classList.remove('opacity-40', 'pointer-events-none');
            }
        });
    }
</script>
@endsection
