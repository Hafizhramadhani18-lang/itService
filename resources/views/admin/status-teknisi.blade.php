@extends('layouts.app')

@section('title', 'Admin · Status Teknisi - IT Service Desk PLTU Karangkandri')

@section('content')
<div class="space-y-6">

    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2.5">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    Status Teknisi
                </h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Pantau ketersediaan, tiket yang sedang dikerjakan, dan jadwal PM seluruh teknisi
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            @include('partials.header-widget')
        </div>
    </div>

    <!-- Alert Flash Feedback -->
    @include('partials.flash-messages')

    <!-- Banner Jam Operasional & Jam Live WIB -->
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
                    Status teknisi default Offline di pagi hari sebelum teknisi check-in, dan otomatis Offline pada pukul 17.00 WIB.
                </p>
            </div>
        </div>
        <div class="flex items-center space-x-2 shrink-0 self-start sm:self-center">
            <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-white border border-slate-200/90 shadow-2xs">
                <span id="admin-live-wib-clock" class="text-xs font-black text-slate-900 tabular-nums">{{ $nowWib->format('H:i') }} WIB</span>
            </div>
            <a href="{{ route('admin.statusTeknisi', request()->query()) }}"
               class="px-3.5 py-1.5 bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 rounded-xl text-xs font-bold transition shadow-2xs inline-flex items-center space-x-1.5"
               title="Muat ulang status terbaru">
                <span>Muat Ulang</span>
            </a>
        </div>
    </div>

    <!-- 4 KARTU RINGKASAN STATUS SESUAI MOCKUP REFERENSI -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- 1. Available -->
        <a href="{{ route('admin.statusTeknisi', array_merge(request()->except('status'), ['status' => $statusFilter === 'available' ? 'all' : 'available'])) }}"
           class="bg-white p-4 rounded-2xl border shadow-xs transition flex flex-col justify-between {{ $statusFilter === 'available' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/80 hover:border-emerald-300' }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-xs font-bold text-slate-800">Available</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-emerald-100/70 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-check"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-none">
                    {{ $availableCount }}<span class="text-sm font-bold text-slate-400">/{{ $totalCount }}</span>
                </div>
                <div class="text-[11px] font-medium text-slate-500 mt-1.5">
                    Siap menerima tugas
                </div>
            </div>
        </a>

        <!-- 2. On Task -->
        <a href="{{ route('admin.statusTeknisi', array_merge(request()->except('status'), ['status' => $statusFilter === 'on_task' ? 'all' : 'on_task'])) }}"
           class="bg-white p-4 rounded-2xl border shadow-xs transition flex flex-col justify-between {{ $statusFilter === 'on_task' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200/80 hover:border-amber-300' }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span class="text-xs font-bold text-slate-800">On Task</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-amber-100/70 text-amber-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-wrench"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-none">
                    {{ $onTaskCount }}<span class="text-sm font-bold text-slate-400">/{{ $totalCount }}</span>
                </div>
                <div class="text-[11px] font-medium text-slate-500 mt-1.5">
                    Sedang mengerjakan tiket atau PM
                </div>
            </div>
        </a>

        <!-- 3. Cuti / Izin -->
        <a href="{{ route('admin.statusTeknisi', array_merge(request()->except('status'), ['status' => $statusFilter === 'cuti' ? 'all' : 'cuti'])) }}"
           class="bg-white p-4 rounded-2xl border shadow-xs transition flex flex-col justify-between {{ $statusFilter === 'cuti' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-slate-200/80 hover:border-rose-300' }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span class="text-xs font-bold text-slate-800">Cuti / Izin</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-rose-100/70 text-rose-600 flex items-center justify-center text-xs">
                    <i class="fa-regular fa-calendar"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-none">
                    {{ $cutiCount }}<span class="text-sm font-bold text-slate-400">/{{ $totalCount }}</span>
                </div>
                <div class="text-[11px] font-medium text-slate-500 mt-1.5">
                    Tidak tersedia sementara
                </div>
            </div>
        </a>

        <!-- 4. Offline -->
        <a href="{{ route('admin.statusTeknisi', array_merge(request()->except('status'), ['status' => $statusFilter === 'offline' ? 'all' : 'offline'])) }}"
           class="bg-white p-4 rounded-2xl border shadow-xs transition flex flex-col justify-between {{ $statusFilter === 'offline' ? 'border-slate-500 ring-2 ring-slate-400/20' : 'border-slate-200/80 hover:border-slate-300' }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <span class="text-xs font-bold text-slate-800">Offline</span>
                </div>
                <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-power-off"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-none">
                    {{ $offlineCount }}<span class="text-sm font-bold text-slate-400">/{{ $totalCount }}</span>
                </div>
                <div class="text-[11px] font-medium text-slate-500 mt-1.5">
                    Belum check-in atau sudah pulang
                </div>
            </div>
        </a>

    </div>

    <!-- FILTER BAR: TABS TIM, SEARCH INPUT, STATUS DROPDOWN, & TIKET MENUNGGU TUGAS -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <!-- Tabs Filter Tim -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.statusTeknisi', array_merge(request()->except('tim'), ['tim' => 'all'])) }}"
               class="px-4 py-2 rounded-xl text-xs font-extrabold transition {{ $timFilter === 'all' ? 'bg-[#0e1a2b] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                Semua Tim &bull; {{ $countSemua }}
            </a>
            <a href="{{ route('admin.statusTeknisi', array_merge(request()->except('tim'), ['tim' => 'lapangan'])) }}"
               class="px-4 py-2 rounded-xl text-xs font-extrabold transition {{ $timFilter === 'lapangan' ? 'bg-[#0e1a2b] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                Tim IT Lapangan &bull; {{ $countLapangan }}
            </a>
            <a href="{{ route('admin.statusTeknisi', array_merge(request()->except('tim'), ['tim' => 'aplikasi'])) }}"
               class="px-4 py-2 rounded-xl text-xs font-extrabold transition {{ $timFilter === 'aplikasi' ? 'bg-[#0e1a2b] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                Tim IT Support &bull; {{ $countAplikasi }}
            </a>
        </div>

        <!-- Right Side: Search, Filter Dropdown, & CTA Penugasan -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-2.5">
            <!-- Form Search & Status Filter -->
            <form id="filter-status-form" method="GET" action="{{ route('admin.statusTeknisi') }}" class="flex items-center gap-2">
                <input type="hidden" name="tim" value="{{ $timFilter }}">

                <!-- Search Input -->
                <div class="relative w-full sm:w-56">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama teknisi atau tiket"
                           class="w-full pl-3.5 pr-8 py-2 bg-white border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 shadow-2xs transition">
                    <button type="submit" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-teal-700 cursor-pointer">
                        <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                    </button>
                </div>

                <!-- Dropdown Status -->
                <div class="relative">
                    <select name="status" onchange="document.getElementById('filter-status-form').submit()"
                            class="appearance-none pl-3.5 pr-8 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-teal-700 shadow-2xs transition cursor-pointer">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua status</option>
                        <option value="available" {{ $statusFilter === 'available' ? 'selected' : '' }}>Available</option>
                        <option value="on_task" {{ $statusFilter === 'on_task' ? 'selected' : '' }}>On Task</option>
                        <option value="cuti" {{ $statusFilter === 'cuti' ? 'selected' : '' }}>Cuti / Izin</option>
                        <option value="offline" {{ $statusFilter === 'offline' ? 'selected' : '' }}>Offline</option>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                </div>
            </form>

            <!-- Tombol Tiket Menunggu Tugas -->
            <a href="{{ route('admin.penugasan') }}"
               class="px-4 py-2 bg-[#0f766e] hover:bg-[#115e59] text-white rounded-xl text-xs font-extrabold shadow-xs transition inline-flex items-center justify-center space-x-1.5 shrink-0">
                <span>{{ $unassignedCount }} tiket menunggu tugas</span>
            </a>
        </div>
    </div>

    <!-- TABEL UTAMA: STATUS TEKNISI SESUAI MOCKUP REFERENSI -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/60 text-slate-500 font-semibold text-[11px]">
                        <th class="py-3.5 px-4">Teknisi</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Aktivitas saat ini</th>
                        <th class="py-3.5 px-4 text-center">Tiket aktif</th>
                        <th class="py-3.5 px-4 text-center">Selesai hari ini</th>
                        <th class="py-3.5 px-4 text-center">Jadwal PM</th>
                        <th class="py-3.5 px-4 text-center">Update</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($technicians as $tek)
                        @php
                            $eff = $tek->effective_status;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition align-middle">
                            <!-- Kolom 1: Teknisi -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full bg-teal-800 text-white font-black text-xs flex items-center justify-center shrink-0 shadow-2xs overflow-hidden">
                                        @if($tek->foto_url)
                                            <img src="{{ $tek->foto_url }}" alt="Foto {{ $tek->name }}" class="w-full h-full object-cover">
                                        @else
                                            {{ strtoupper(substr($tek->name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-extrabold text-slate-900 leading-tight">
                                            {{ $tek->name }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-medium mt-0.5">
                                            {{ $tek->label_tim }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom 2: Status & Sejak -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div>
                                    <span class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $tek->availability_badge_class }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $tek->availability_dot_class }}"></span>
                                        <span>{{ $tek->availability_label }}</span>
                                    </span>
                                    <div class="text-[10px] text-slate-400 font-medium mt-0.5 pl-0.5">
                                        {{ $tek->status_since_label }}
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom 3: Aktivitas saat ini -->
                            <td class="py-3.5 px-4 max-w-xs">
                                @if($eff === 'on_task' && $tek->top_active_ticket)
                                    <div>
                                        <a href="{{ route('services.show', $tek->top_active_ticket) }}" class="font-extrabold text-slate-900 hover:text-teal-700 leading-tight truncate block">
                                            {{ $tek->top_active_ticket->ticket_number }} &bull; {{ $tek->top_active_ticket->divisi }}
                                        </a>
                                        <div class="text-[11px] text-slate-400 truncate mt-0.5" title="{{ $tek->top_active_ticket->keluhan }}">
                                            {{ $tek->top_active_ticket->keluhan }}
                                        </div>
                                    </div>
                                @elseif($eff === 'available')
                                    <div>
                                        <div class="font-extrabold text-slate-900 leading-tight">Standby</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">Siap menerima penugasan</div>
                                    </div>
                                @elseif($eff === 'cuti')
                                    <div>
                                        <div class="font-extrabold text-rose-700 leading-tight">Cuti / Izin</div>
                                        <div class="text-[11px] text-slate-400 truncate mt-0.5">
                                            {{ $tek->catatan_ketersediaan ?: ($tek->cuti_selesai ? 'Hingga ' . $tek->cuti_selesai->locale('id')->isoFormat('D MMM') : 'Sedang cuti') }}
                                        </div>
                                    </div>
                                @elseif($eff === 'offline')
                                    <div>
                                        <div class="font-extrabold text-slate-600 leading-tight">Offline</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            @if($tek->top_active_ticket)
                                                Belum check-in &bull; {{ $tek->top_active_ticket->ticket_number }}
                                            @elseif(!$withinWorkHours)
                                                Luar jam kerja
                                            @else
                                                Belum check-in
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div>
                                        <div class="font-extrabold text-slate-900 leading-tight">On Task</div>
                                        <div class="text-[11px] text-slate-400 truncate mt-0.5">{{ $tek->catatan_ketersediaan ?: 'Sedang tugas lapangan' }}</div>
                                    </div>
                                @endif
                            </td>

                            <!-- Kolom 4: Tiket aktif -->
                            <td class="py-3.5 px-4 text-center font-bold text-slate-700 whitespace-nowrap">
                                {{ $tek->active_tickets_count }}
                            </td>

                            <!-- Kolom 5: Selesai hari ini -->
                            <td class="py-3.5 px-4 text-center font-bold text-slate-700 whitespace-nowrap">
                                {{ $tek->completed_today_count }}
                            </td>

                            <!-- Kolom 6: Jadwal PM -->
                            <td class="py-3.5 px-4 text-center font-bold text-slate-700 whitespace-nowrap">
                                {{ $tek->scheduled_pm_count }}
                            </td>

                            <!-- Kolom 7: Update -->
                            <td class="py-3.5 px-4 text-center font-semibold text-slate-600 whitespace-nowrap text-xs">
                                {{ $tek->last_update_time }}
                            </td>

                            <!-- Kolom 8: Aksi -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-center">
                                <div class="inline-flex items-center space-x-1.5">
                                    <a href="{{ route('admin.statusTeknisi.show', $tek) }}"
                                       class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition shadow-2xs">
                                        Detail
                                    </a>
                                    <button type="button"
                                            onclick="openModalAvailability({{ json_encode([
                                                'id' => $tek->id,
                                                'name' => $tek->name,
                                                'tim' => $tek->label_tim,
                                                'status' => $tek->effective_status,
                                                'cuti_mulai' => $tek->cuti_mulai ? $tek->cuti_mulai->format('Y-m-d') : now()->format('Y-m-d'),
                                                'cuti_selesai' => $tek->cuti_selesai ? $tek->cuti_selesai->format('Y-m-d') : '',
                                                'catatan' => $tek->catatan_ketersediaan ?? '',
                                                'active_tickets' => $tek->active_tickets_count,
                                                'scheduled_pm' => $tek->scheduled_pm_count,
                                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }})"
                                            class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg text-xs cursor-pointer"
                                            title="Atur status teknisi">
                                        <i class="fa-solid fa-ellipsis"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-users-slash text-2xl text-slate-300 mb-2 block"></i>
                                <span class="font-bold text-slate-700">Tidak ada teknisi yang sesuai filter</span>
                                <p class="text-xs text-slate-400 mt-1">Coba ubah kata kunci atau reset filter status/tim.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Catatan Kaki Bawah Tabel Sesuai Mockup -->
        <div class="p-4 border-t border-slate-100 text-[11px] text-slate-400">
            Status teknisi otomatis menjadi Offline pukul 17.00 dan direset setiap pagi. Atur mengubah status teknisi secara manual.
        </div>
    </div>

</div>

<!-- Modal Atur Status Kerja & Alih Tugas Teknisi -->
<div id="modalAvailabilityUser" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Atur Status Kerja Teknisi</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    <span id="availUserName" class="font-bold text-slate-700"></span> &bull; <span id="availUserTim"></span>
                </p>
            </div>
            <button type="button" onclick="closeModalAvailability()" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="formAvailabilityUser" method="POST" class="mt-4 space-y-4 text-xs">
            @csrf

            <div class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                <span class="text-slate-500 font-medium">Beban saat ini</span>
                <span id="availTaskSummary" class="font-bold text-slate-800">0 tiket aktif · 0 jadwal PM</span>
            </div>

            <div>
                <label class="block font-bold text-slate-800 mb-1.5">Status Kerja</label>
                <select name="status_ketersediaan" id="availStatusSelect" onchange="toggleAvailabilityFields()"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white">
                    <option value="available">🟢 Available (Standby / Siap Tugas)</option>
                    <option value="on_task">🟡 On Task (Sedang Bertugas)</option>
                    <option value="cuti">🔴 Cuti / Izin</option>
                    <option value="offline">⚫ Offline / Pulang</option>
                </select>
            </div>

            <div id="availLeaveDetails" class="hidden space-y-3 p-3.5 rounded-xl bg-rose-50/40 border border-rose-200/70">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Mulai Tanggal</label>
                        <input type="date" name="cuti_mulai" id="availCutiMulai"
                               class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Sampai Tanggal</label>
                        <input type="date" name="cuti_selesai" id="availCutiSelesai"
                               class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800">
                    </div>
                </div>
            </div>

            <div id="availNoteWrap">
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Catatan / Keterangan (Opsional)</label>
                <input type="text" name="catatan_ketersediaan" id="availCatatan" placeholder="Contoh: Tarik kabel Control Room / Cuti tahunan"
                       class="w-full px-3 py-2 bg-slate-50 focus:bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-teal-700">
            </div>

            <div id="availReassignBox" data-has-tasks="0" class="hidden space-y-2.5 p-3.5 rounded-xl bg-amber-50/60 border border-amber-200/80">
                <div class="font-bold text-amber-900 flex items-center space-x-1.5">
                    <i class="fa-solid fa-arrows-turn-right text-amber-600"></i>
                    <span>Pengalihan Tugas Aktif &amp; Jadwal PM</span>
                </div>
                <select name="reassign_mode" id="availReassignMode" onchange="toggleAvailabilityFields()"
                        class="w-full px-3 py-2 bg-white border border-amber-200 rounded-lg text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-teal-700">
                    <option value="keep">Biarkan tetap (tidak dialihkan dulu)</option>
                    <option value="team">Lepas ke Antrian Tim (bisa diambil rekan satu tim)</option>
                    <option value="technician">Alihkan langsung ke Teknisi Pengganti</option>
                </select>

                <div id="availReplacementWrap" class="hidden pt-1">
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Pilih Teknisi Pengganti</label>
                    <select name="replacement_teknisi_id" id="availReplacementSelect"
                            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800">
                        <option value="">-- Pilih Teknisi Pengganti --</option>
                        @foreach($allTechnicians ?? [] as $tOpt)
                            @php
                                $isBlockedOpt = $tOpt->isCuti() || in_array(strtolower((string) $tOpt->status_ketersediaan), ['offline', 'off'], true);
                            @endphp
                            <option value="{{ $tOpt->id }}" {{ $isBlockedOpt ? 'disabled' : '' }}>
                                {{ $tOpt->name }} ({{ $tOpt->label_tim }}) — {{ $tOpt->availability_short_note }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModalAvailability()"
                        class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2 bg-[#0f766e] hover:bg-[#115e59] text-white font-bold rounded-xl transition shadow-xs cursor-pointer">
                    Simpan Status
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openModalAvailability(data) {
        document.getElementById('formAvailabilityUser').action = '/admin/users/' + data.id + '/availability';
        document.getElementById('availUserName').innerText = data.name;
        document.getElementById('availUserTim').innerText = data.tim || 'Tim IT';
        document.getElementById('availStatusSelect').value = data.status || 'available';
        document.getElementById('availCutiMulai').value = data.cuti_mulai || '';
        document.getElementById('availCutiSelesai').value = data.cuti_selesai || '';
        document.getElementById('availCatatan').value = data.catatan || '';

        const activeTickets = parseInt(data.active_tickets || 0, 10);
        const scheduledPm = parseInt(data.scheduled_pm || 0, 10);
        const totalTasks = activeTickets + scheduledPm;
        document.getElementById('availTaskSummary').innerText = `${activeTickets} tiket aktif · ${scheduledPm} jadwal PM terjadwal`;
        document.getElementById('availReassignBox').dataset.hasTasks = totalTasks > 0 ? '1' : '0';

        const repSel = document.getElementById('availReplacementSelect');
        if (repSel) {
            repSel.value = '';
            Array.from(repSel.options).forEach(opt => {
                opt.hidden = opt.value && String(opt.value) === String(data.id);
            });
        }

        document.getElementById('availReassignMode').value = 'keep';
        toggleAvailabilityFields();
        document.getElementById('modalAvailabilityUser').classList.remove('hidden');
    }

    function closeModalAvailability() {
        document.getElementById('modalAvailabilityUser').classList.add('hidden');
    }

    function toggleAvailabilityFields() {
        const status = document.getElementById('availStatusSelect').value;
        const leaveBox = document.getElementById('availLeaveDetails');
        const noteWrap = document.getElementById('availNoteWrap');
        const reassignBox = document.getElementById('availReassignBox');
        const repWrap = document.getElementById('availReplacementWrap');
        const mode = document.getElementById('availReassignMode').value;

        const isCuti = status === 'cuti';
        const isUnavailable = status === 'cuti' || status === 'offline';
        leaveBox.classList.toggle('hidden', !isCuti);
        noteWrap.classList.toggle('hidden', status === 'available');
        reassignBox.classList.toggle('hidden', !isUnavailable || reassignBox.dataset.hasTasks !== '1');
        repWrap.classList.toggle('hidden', !isUnavailable || mode !== 'technician');
    }

    (function startAdminLiveClock() {
        const el = document.getElementById('admin-live-wib-clock');
        if (!el) return;
        setInterval(function () {
            try {
                const formatted = new Intl.DateTimeFormat('id-ID', {
                    timeZone: 'Asia/Jakarta',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                }).format(new Date()).replace(/\./g, ':');
                el.textContent = formatted + ' WIB';
            } catch (e) {}
        }, 1000);
    })();

    // Sinkronisasi otomatis halaman status teknisi setiap 60 detik (hanya saat modal tertutup)
    (function autoSyncStatusBoard() {
        setInterval(function () {
            const modal = document.getElementById('modalAvailabilityUser');
            if (modal && modal.classList.contains('hidden') && !document.hidden) {
                window.location.reload();
            }
        }, 60000);
    })();
</script>
@endpush
@endsection
