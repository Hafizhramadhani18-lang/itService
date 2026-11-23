@extends('layouts.app')

@section('title', 'Dashboard Teknisi')

@section('content')
<div class="space-y-6">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Dashboard Teknisi</h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200/60">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-pulse"></span>
                    Live Service Desk
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1 font-medium">
                Sistem Informasi Manajemen Pelayanan Servis & Troubleshooting IT &bull; PT S2P PLTU Karangkandri
            </p>
        </div>

        <!-- Header Right: Tanggal & Lonceng Notifikasi Tiket Masuk -->
        <div class="flex items-center space-x-2.5">
            @include('partials.header-widget')
        </div>
    </div>

    <!-- Alert Notifikasi Flash (Ramping & Auto-Dismiss) -->
    @include('partials.flash-messages')

    <!-- 4 Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Tiket Baru -->
        <a href="{{ route('services.index') }}" 
           class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-blue-400 hover:shadow-md transition duration-150 block group cursor-pointer">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 text-xs font-semibold text-slate-600 group-hover:text-blue-600 transition">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span>Tiket baru</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition flex items-center justify-center text-xs">
                    <i class="fa-solid fa-inbox"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900 mt-3">{{ $baruCount }}</div>
            <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                <span>Perlu ditindaklanjuti</span>
                <span class="text-blue-600 font-bold group-hover:translate-x-0.5 transition">&rarr;</span>
            </div>
        </a>

        <!-- Sedang diproses -->
        <a href="{{ route('services.index', ['filter' => 'my', 'teknisi' => Auth::user()->name]) }}" 
           class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-amber-400 hover:shadow-md transition duration-150 block group cursor-pointer">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 text-xs font-semibold text-slate-600 group-hover:text-amber-600 transition">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Sedang diproses</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition flex items-center justify-center text-xs">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900 mt-3">{{ $prosesCount }}</div>
            <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                <span>Tugas aktif Anda</span>
                <span class="text-amber-600 font-bold group-hover:translate-x-0.5 transition">&rarr;</span>
            </div>
        </a>

        <!-- Menunggu sparepart -->
        <a href="{{ route('services.index', ['status' => 'Menunggu sparepart']) }}" 
           class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-purple-400 hover:shadow-md transition duration-150 block group cursor-pointer">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 text-xs font-semibold text-slate-600 group-hover:text-purple-600 transition">
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    <span>Menunggu sparepart</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition flex items-center justify-center text-xs">
                    <i class="fa-solid fa-microchip"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900 mt-3">{{ $sparepartCount }}</div>
            <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                <span>Pengadaan vendor</span>
                <span class="text-purple-600 font-bold group-hover:translate-x-0.5 transition">&rarr;</span>
            </div>
        </a>

        <!-- Selesai hari ini -->
        <a href="{{ route('services.index', ['filter' => 'history']) }}" 
           class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-emerald-400 hover:shadow-md transition duration-150 block group cursor-pointer">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 text-xs font-semibold text-slate-600 group-hover:text-emerald-600 transition">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Perbaikan selesai</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition flex items-center justify-center text-xs">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900 mt-3">{{ $selesaiHariIniCount }}</div>
            <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                <span>{{ $selesaiTodayCount ?? 0 }} hari ini &bull; Riwayat</span>
                <span class="text-emerald-600 font-bold group-hover:translate-x-0.5 transition">&rarr;</span>
            </div>
        </a>
    </div>

    <!-- Main Grid: Left Table (8 cols), Right Widgets (4 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left: Antrian Tiket Card (8 cols) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
            
            <!-- Table Header & Search -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <h2 class="font-bold text-slate-900 text-sm">Antrian Tiket</h2>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                        {{ $antrianTiket->count() }} Tiket
                    </span>
                </div>

                <div class="flex items-center space-x-2 w-full sm:w-auto">
                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center space-x-1.5 w-full sm:w-auto">
                        @if(request('filter'))
                            <input type="hidden" name="filter" value="{{ request('filter') }}">
                        @endif
                        @if(request('tim'))
                            <input type="hidden" name="tim" value="{{ request('tim') }}">
                        @endif
                        <div class="relative w-full sm:w-64">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari tiket, pelapor, divisi..."
                                   class="w-full pl-3 pr-8 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                            <button type="submit" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600">
                                <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                            </button>
                        </div>
                        @if(request()->filled('search'))
                            <a href="{{ route('dashboard', request()->except('search')) }}"
                               class="p-1.5 text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition flex-shrink-0"
                               title="Reset pencarian">
                                <i class="fa-solid fa-rotate-left text-xs"></i>
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Filter Pills Row -->
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                @php
                    $currentFilter = request('filter');
                    $currentTim = request('tim');
                @endphp
                <div class="flex flex-wrap items-center gap-1.5">
                    <a href="{{ route('dashboard', array_merge(request()->except(['filter', 'tim']), [])) }}" 
                       class="px-3 py-1 rounded-full text-xs font-semibold transition {{ empty($currentFilter) && empty($currentTim) ? 'bg-[#0e1a2b] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Semua
                    </a>
                    <a href="{{ route('dashboard', array_merge(request()->except('tim'), ['tim' => 'lapangan'])) }}" 
                       class="px-3 py-1 rounded-full text-xs font-semibold transition flex items-center space-x-1 {{ $currentTim === 'lapangan' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200/60' }}">
                        <i class="fa-solid fa-wrench text-[10px]"></i>
                        <span>Tim Lapangan</span>
                    </a>
                    <a href="{{ route('dashboard', array_merge(request()->except('tim'), ['tim' => 'aplikasi'])) }}" 
                       class="px-3 py-1 rounded-full text-xs font-semibold transition flex items-center space-x-1 {{ $currentTim === 'aplikasi' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-indigo-50 text-indigo-800 hover:bg-indigo-100 border border-indigo-200/60' }}">
                        <i class="fa-solid fa-laptop-code text-[10px]"></i>
                        <span>Tim Support & Aplikasi</span>
                    </a>
                    <a href="{{ route('dashboard', array_merge(request()->except('filter'), ['filter' => 'Mendesak'])) }}" 
                       class="px-3 py-1 rounded-full text-xs font-semibold transition flex items-center space-x-1 {{ $currentFilter === 'Mendesak' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200/60' }}">
                        <i class="fa-solid fa-fire text-[10px]"></i>
                        <span>Mendesak</span>
                    </a>
                </div>

                <a href="{{ route('services.index') }}" class="text-[11px] font-semibold text-teal-700 hover:text-teal-800 hover:underline flex items-center space-x-1">
                    <span>Lihat Antrian Lengkap</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <!-- Table of Tickets -->
            <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/70 text-slate-600 font-bold text-[11px]">
                            <th class="py-2.5 px-3">No.</th>
                            <th class="py-2.5 px-3">Divisi & Pelapor</th>
                            <th class="py-2.5 px-3">Kendala & Unit Target</th>
                            <th class="py-2.5 px-3">Prioritas</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3">Teknisi</th>
                            <th class="py-2.5 px-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($antrianTiket as $ticket)
                        @php
                            $canOpen = $ticket->canBeOpenedBy(Auth::user());
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition {{ $canOpen ? 'cursor-pointer group' : '' }}" @if($canOpen) onclick="window.location='{{ route('services.show', $ticket) }}'" @endif>
                            <td class="py-3 px-3 font-bold text-slate-900 whitespace-nowrap">
                                @if($canOpen)
                                <a href="{{ route('services.show', $ticket) }}" class="group-hover:text-teal-700 transition">
                                    {{ $ticket->ticket_number }}
                                </a>
                                @else
                                <span class="text-slate-500 font-medium cursor-not-allowed" title="Hanya dapat dibuka oleh {{ $ticket->teknisi_nama ?? 'teknisi yang bertugas' }}">{{ $ticket->ticket_number }}</span>
                                @endif
                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">
                                    {{ $ticket->created_at ? $ticket->created_at->format('d/m H:i') : '—' }} WIB
                                </div>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="font-bold text-slate-900">{{ $ticket->divisi }}</div>
                                <div class="text-[10px] text-slate-500 font-medium flex items-center space-x-1.5 mt-0.5">
                                    <span>{{ $ticket->nama_pelapor }}</span>
                                    @if(!empty($ticket->user?->no_hp))
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $ticket->user->no_hp) }}" 
                                           target="_blank" 
                                           onclick="event.stopPropagation()"
                                           class="text-emerald-600 hover:text-emerald-700" 
                                           title="WhatsApp Pelapor ({{ $ticket->user->no_hp }})">
                                            <i class="fa-brands fa-whatsapp text-xs"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-3 text-slate-700 max-w-xs">
                                <div class="truncate font-semibold text-slate-800 text-xs" title="{{ $ticket->keluhan }}">
                                    {{ $ticket->keluhan }}
                                </div>
                                <div class="flex items-center space-x-1.5 text-[10px] text-slate-400 mt-1 flex-wrap gap-y-1">
                                    <span class="font-medium text-slate-600">{{ $ticket->jenis_perangkat }}</span>
                                    <span>&bull;</span>
                                    <span class="inline-flex items-center text-slate-400 max-w-[150px] truncate" title="{{ $ticket->lokasi_ruangan }}">
                                        <i class="fa-solid fa-location-dot text-[9px] mr-1 text-slate-400"></i>{{ $ticket->lokasi_ruangan }}
                                    </span>
                                    @php $tb = $ticket->badge_target; @endphp
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold border whitespace-nowrap {{ $tb['bg'] }}">
                                        <i class="{{ $tb['icon'] }} mr-0.5"></i>{{ $tb['short'] }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                @if($ticket->prioritas === 'Mendesak')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full font-bold bg-rose-50 text-rose-700 text-[10px] border border-rose-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                        Mendesak
                                    </span>
                                @elseif($ticket->prioritas === 'Sedang' || $ticket->prioritas === 'Tinggi')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-bold bg-amber-50 text-amber-700 text-[10px] border border-amber-200/60">
                                        {{ $ticket->prioritas }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-bold bg-sky-50 text-sky-700 text-[10px] border border-sky-200/60">
                                        Rendah
                                    </span>
                                @endif

                                @if($ticket->sla_status === 'breached')
                                    <div class="text-[9px] font-bold text-rose-700 mt-0.5 animate-pulse">
                                        <i class="fa-solid fa-clock text-[8px] mr-0.5"></i> {{ $ticket->sla_remaining_text }}
                                    </div>
                                @elseif($ticket->sla_status === 'warning')
                                    <div class="text-[9px] font-bold text-amber-800 mt-0.5">
                                        <i class="fa-solid fa-hourglass-half text-[8px] mr-0.5"></i> {{ $ticket->sla_remaining_text }}
                                    </div>
                                @elseif($ticket->sla_status === 'safe' && $ticket->status !== 'Dibatalkan')
                                    <div class="text-[9px] font-medium text-slate-400 mt-0.5">
                                        <i class="fa-solid fa-stopwatch text-[8px] mr-0.5"></i> {{ $ticket->sla_remaining_text }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                @if($ticket->status === 'Baru')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200/70">
                                        Baru
                                    </span>
                                @elseif($ticket->status === 'Diproses')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/70">
                                        Diproses
                                    </span>
                                @elseif($ticket->status === 'Menunggu sparepart')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200/70">
                                        Sparepart
                                    </span>
                                @elseif($ticket->status === 'Selesai')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                                        Selesai
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $ticket->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                @if($ticket->teknisi_nama || $ticket->teknisi)
                                    @php 
                                        $tekName = $ticket->teknisi_nama ?? $ticket->teknisi?->name;
                                        $isOwnedByMe = $ticket->isOwnedByTeknisi(Auth::user()); 
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold {{ $isOwnedByMe ? 'bg-teal-50 text-teal-800 border border-teal-200/90' : 'bg-slate-100 text-slate-700 border border-slate-200/60' }}">
                                        <i class="{{ $isOwnedByMe ? 'fa-solid fa-user-check text-teal-700' : 'fa-regular fa-user text-slate-400' }} mr-1 text-[8px]"></i>
                                        <span>{{ $tekName }}</span>
                                        @if($isOwnedByMe)
                                            <span class="ml-1 text-[9px] font-bold text-teal-700">(Anda)</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">
                                        <i class="fa-regular fa-clock mr-1 text-[8px]"></i>
                                        <span>Belum ada</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                @if($canOpen)
                                <a href="{{ route('services.show', $ticket) }}" 
                                   class="inline-flex items-center space-x-1 px-3 py-1 rounded-lg bg-slate-100 hover:bg-teal-700 hover:text-white text-slate-700 font-semibold text-[11px] transition shadow-2xs">
                                    <span>{{ $ticket->status === 'Baru' && empty($ticket->teknisi_id) ? 'Detail' : 'Buka' }}</span>
                                    <i class="fa-solid fa-chevron-right text-[8px]"></i>
                                </a>
                                @else
                                <span class="text-slate-300 font-semibold text-xs px-2 select-none" title="Hanya dapat dibuka oleh {{ $ticket->teknisi_nama ?? 'teknisi yang bertugas' }}">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400">
                                <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center mx-auto mb-2 text-sm border border-slate-200/60">
                                    <i class="fa-solid fa-inbox"></i>
                                </div>
                                <span class="font-semibold text-slate-600 block text-xs">Tidak ada tiket dalam antrian</span>
                                <span class="text-[11px] text-slate-400 mt-0.5 block">Semua tiket telah diproses atau sesuai dengan kriteria filter.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Right: Side Widgets (4 cols) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Widget 1: Tiket per divisi -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3.5">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-xs tracking-tight">Tiket per divisi</h3>
                    <span class="text-[10px] text-slate-400 font-medium">Beban Kerja</span>
                </div>
                
                <div class="space-y-3 pt-1 text-xs">
                    @forelse($divisiCounts as $row)
                        @php
                            $percentage = min(100, round(($row->total / $maxDivisiCount) * 100));
                        @endphp
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-700 font-semibold truncate">{{ $row->divisi }}</span>
                                <span class="font-extrabold text-slate-900">{{ $row->total }}</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-gradient-to-r from-teal-700 to-teal-500 h-2 rounded-full transition-all duration-300" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-slate-400 text-xs">Belum ada data tiket per divisi.</p>
                    @endforelse
                </div>
            </div>

            <!-- Widget 2: Perlu perhatian -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3.5">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-xs tracking-tight">Perlu perhatian</h3>
                    @if($perluPerhatian->isNotEmpty())
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                    @endif
                </div>

                <div class="space-y-2.5 text-xs">
                    @forelse($perluPerhatian as $alert)
                    @php
                        $canOpenAlert = $alert->canBeOpenedBy(Auth::user());
                        $timeAgo = $alert->created_at ? $alert->created_at->locale('id')->diffForHumans(null, true) : '—';
                    @endphp
                    <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-200/60 {{ $canOpenAlert ? 'hover:bg-slate-100/70 hover:border-slate-300 transition cursor-pointer' : '' }}" @if($canOpenAlert) onclick="window.location='{{ route('services.show', $alert) }}'" @endif>
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900 font-mono text-[11px]">{{ $alert->ticket_number }} &bull; {{ $alert->divisi }}</span>
                            <span class="text-[10px] text-slate-500 font-medium">{{ $timeAgo }}</span>
                        </div>
                        <p class="text-[11px] mt-1 leading-snug">
                            @if($alert->status === 'Baru' && $alert->prioritas === 'Mendesak')
                                <span class="text-rose-600 font-bold">Mendesak, belum ada teknisi</span>
                            @elseif($alert->status === 'Menunggu sparepart')
                                <span class="text-purple-600 font-bold">Menunggu sparepart lebih dari 2 hari</span>
                            @elseif($alert->status === 'Baru')
                                @php
                                    $hoursOpen = (int) $alert->created_at->diffInHours(now());
                                @endphp
                                <span class="text-amber-600 font-bold">Antrian belum diambil {{ $hoursOpen >= 24 ? (int)($hoursOpen/24) . ' hari' : "{$hoursOpen} jam" }}</span>
                            @else
                                <span class="text-slate-600">{{ Str::limit($alert->keluhan, 45) }}</span>
                            @endif
                        </p>
                    </div>
                    @empty
                    <p class="text-slate-400 text-xs py-2">Tidak ada tiket yang memerlukan eskalasi darurat.</p>
                    @endforelse
                </div>
            </div>

            <!-- Widget 3: Pemeliharaan Rutin Bulan Ini -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>
                        <h3 class="font-bold text-slate-900 text-xs tracking-tight">Pemeliharaan Rutin (PM)</h3>
                    </div>
                    <a href="{{ route('maintenance.create') }}" class="text-[10px] font-bold text-teal-600 hover:underline">
                        + Catat Cek
                    </a>
                </div>
                <p class="text-[11px] text-slate-500 leading-snug">
                    Pemeriksaan berkala bulanan PC, Switch & Printer untuk cegah downtime.
                </p>
                <a href="{{ route('maintenance.index') }}" class="block text-center py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 transition">
                    Buka Log Pemeliharaan &rarr;
                </a>
            </div>

        </div>

    </div>

</div>
@endsection
