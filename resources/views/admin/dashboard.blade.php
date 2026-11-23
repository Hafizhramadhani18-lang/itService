@extends('layouts.app')

@section('title', 'Dashboard Admin - IT Service Desk PLTU Karangkandri')

@section('content')
<div class="space-y-6">

    <!-- Header Halaman Sesuai Mockup Referensi -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2.5">
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                    Dashboard Admin
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200/80">
                    Koordinator IT
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Sistem Informasi Manajemen Pelayanan Servis & Troubleshooting IT • PT S2P PLTU Karangkandri
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            @include('partials.header-widget')
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @include('partials.flash-messages')

    <!-- 4 KARTU METRIK UTAMA SESUAI MOCKUP REFERENSI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Belum Ditugaskan (Merah / Rose) -->
        <a href="{{ route('admin.penugasan') }}" 
           class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs hover:border-rose-300 hover:shadow-sm transition group flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-600">Belum ditugaskan</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 border border-rose-200/80 text-rose-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-inbox"></i>
                </div>
            </div>
            <div class="mt-2">
                <div class="text-3xl font-extrabold text-slate-900">{{ $belumDitugaskanCount }}</div>
                <div class="text-[11px] text-rose-600 font-medium mt-1 flex items-center space-x-1 group-hover:translate-x-0.5 transition-transform">
                    <span>Perlu segera ditugaskan</span>
                    <span>&rarr;</span>
                </div>
            </div>
        </a>

        <!-- Card 2: Tiket Aktif (Kuning / Amber) -->
        <a href="{{ route('services.index', ['filter' => 'my', 'teknisi' => 'semua']) }}" 
           class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs hover:border-amber-300 hover:shadow-sm transition group flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-600">Tiket aktif</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-200/80 text-amber-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div class="mt-2">
                <div class="text-3xl font-extrabold text-slate-900">{{ $tiketAktifCount }}</div>
                <div class="text-[11px] text-amber-600 font-medium mt-1 flex items-center space-x-1 group-hover:translate-x-0.5 transition-transform">
                    <span>Sedang ditangani</span>
                    <span>&rarr;</span>
                </div>
            </div>
        </a>

        <!-- Card 3: Teknisi Aktif (Hijau / Emerald) -->
        <a href="{{ route('admin.statusTeknisi') }}" 
           class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs hover:border-emerald-300 hover:shadow-sm transition group flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-600">Teknisi aktif hari ini</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 border border-emerald-200/80 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>
            <div class="mt-2">
                <div class="text-3xl font-extrabold text-slate-900">
                    {{ $teknisiOnlineCount }}<span class="text-base font-bold text-slate-400">/{{ $teknisiAktifCount }}</span>
                </div>
                <div class="text-[11px] text-emerald-600 font-medium mt-1 flex items-center space-x-1 group-hover:translate-x-0.5 transition-transform">
                    <span>{{ $teknisiStandbyCount }} Available &bull; siap tugas</span>
                    <span>&rarr;</span>
                </div>
            </div>
        </a>

        <!-- Card 4: Pengguna Terdaftar (Biru / Sky) -->
        <a href="{{ route('admin.users') }}" 
           class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs hover:border-sky-300 hover:shadow-sm transition group flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-600">Pengguna terdaftar</span>
                <div class="w-8 h-8 rounded-lg bg-sky-50 border border-sky-200/80 text-sky-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="mt-2">
                <div class="text-3xl font-extrabold text-slate-900">{{ $penggunaTerdaftarCount }}</div>
                <div class="text-[11px] text-sky-600 font-medium mt-1 flex items-center space-x-1 group-hover:translate-x-0.5 transition-transform">
                    <span>Pegawai dan teknisi</span>
                    <span>&rarr;</span>
                </div>
            </div>
        </a>

    </div>

    <!-- TABEL UTAMA: TIKET BELUM DITUGASKAN -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <!-- Header Tabel & Search Box -->
        <div class="p-4 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-2">
                <h2 class="text-sm font-bold text-slate-900">Tiket Belum Ditugaskan</h2>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                    {{ $belumDitugaskanCount }} Tiket
                </span>
            </div>

            <form method="GET" action="{{ route('admin.dashboard') }}" class="w-full sm:w-72 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari tiket atau divisi..."
                       class="w-full pl-3 pr-8 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                <button type="submit" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                </button>
            </form>
        </div>

        <!-- Tabel Tiket -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/60 text-slate-500 font-semibold text-[11px]">
                        <th class="py-3 px-4">No.</th>
                        <th class="py-3 px-4">Divisi</th>
                        <th class="py-3 px-4">Kendala</th>
                        <th class="py-3 px-4">Prioritas</th>
                        <th class="py-3 px-4 min-w-[200px]">Tugaskan ke</th>
                        <th class="py-3 px-4 min-w-[170px] text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tiketBelumDitugaskan as $ticket)
                    <tr class="hover:bg-slate-50/70 transition align-middle">
                        <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                            <a href="{{ route('services.show', $ticket) }}" class="text-teal-700 hover:underline">
                                {{ $ticket->ticket_number }}
                            </a>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <div class="font-bold text-slate-900">{{ $ticket->divisi }}</div>
                            <div class="text-[10px] text-slate-400">{{ $ticket->nama_pelapor }}</div>
                        </td>
                        <td class="py-3.5 px-4 max-w-xs">
                            <div class="font-medium text-slate-800 truncate" title="{{ $ticket->keluhan }}">
                                {{ $ticket->keluhan }}
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5 text-[10px] text-slate-400 mt-0.5">
                                <span>{{ $ticket->jenis_perangkat }}</span>
                                <span>&bull;</span>
                                <span>{{ $ticket->lokasi_ruangan }}</span>
                                @php $tb = $ticket->badge_target; @endphp
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold border {{ $tb['bg'] }}">
                                    <i class="{{ $tb['icon'] }} mr-0.5"></i>{{ $tb['short'] }}
                                </span>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($ticket->prioritas === 'Mendesak')
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-rose-50 text-rose-700 text-[10px] border border-rose-200/60">
                                    Mendesak
                                </span>
                            @elseif($ticket->prioritas === 'Sedang' || $ticket->prioritas === 'Tinggi')
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-amber-50 text-amber-700 text-[10px] border border-amber-200/60">
                                    Sedang
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-sky-50 text-sky-700 text-[10px] border border-sky-200/60">
                                    Rendah
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <select name="teknisi_id" form="assign-dash-{{ $ticket->id }}" required
                                    class="w-full min-w-[190px] max-w-[230px] py-1.5 px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-teal-700/20 focus:border-teal-700 focus:bg-white transition text-slate-700 font-medium">
                                <option value="" selected>Pilih Teknisi</option>
                                <optgroup label="🛠️ Tim Lapangan (Hardware & Jaringan)">
                                    @foreach($teknisiList->filter(fn($t) => $t->spesialisasi === 'lapangan') as $tek)
                                        @php
                                            $eff = $tek->effective_availability;
                                            $isBlocked = $tek->isCuti() || in_array(strtolower((string) $tek->status_ketersediaan), ['offline', 'off'], true);
                                            $icon = $eff === 'available' ? '🟢' : ($eff === 'on_task' ? '🟡' : ($eff === 'cuti' ? '⛔' : '⚫'));
                                        @endphp
                                        <option value="{{ $tek->id }}" {{ $isBlocked ? 'disabled' : '' }}>
                                            {{ $tek->name }} ({{ $icon }} {{ $tek->availability_label }})
                                        </option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="💻 Tim Support & Aplikasi (Software & Akun)">
                                    @foreach($teknisiList->filter(fn($t) => $t->spesialisasi === 'aplikasi') as $tek)
                                        @php
                                            $eff = $tek->effective_availability;
                                            $isBlocked = $tek->isCuti() || in_array(strtolower((string) $tek->status_ketersediaan), ['offline', 'off'], true);
                                            $icon = $eff === 'available' ? '🟢' : ($eff === 'on_task' ? '🟡' : ($eff === 'cuti' ? '⛔' : '⚫'));
                                        @endphp
                                        <option value="{{ $tek->id }}" {{ $isBlocked ? 'disabled' : '' }}>
                                            {{ $tek->name }} ({{ $icon }} {{ $tek->availability_label }})
                                        </option>
                                    @endforeach
                                </optgroup>
                                @if($teknisiList->filter(fn($t) => empty($t->spesialisasi))->isNotEmpty())
                                <optgroup label="Teknisi Lainnya">
                                    @foreach($teknisiList->filter(fn($t) => empty($t->spesialisasi)) as $tek)
                                        @php
                                            $eff = $tek->effective_availability;
                                            $isBlocked = $tek->isCuti() || in_array(strtolower((string) $tek->status_ketersediaan), ['offline', 'off'], true);
                                            $icon = $eff === 'available' ? '🟢' : ($eff === 'on_task' ? '🟡' : ($eff === 'cuti' ? '⛔' : '⚫'));
                                        @endphp
                                        <option value="{{ $tek->id }}" {{ $isBlocked ? 'disabled' : '' }}>
                                            {{ $tek->name }} ({{ $icon }} {{ $tek->availability_label }})
                                        </option>
                                    @endforeach
                                </optgroup>
                                @endif
                            </select>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap text-center">
                            <div class="inline-flex items-center justify-center space-x-1.5">
                                <form id="assign-dash-{{ $ticket->id }}" action="{{ route('admin.assignTicket') }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="service_id" value="{{ $ticket->id }}">
                                    <button type="submit" 
                                            class="px-2.5 py-1.5 bg-[#0f766e] hover:bg-[#115e59] active:scale-95 text-white font-bold rounded-lg text-xs shadow-xs transition inline-flex items-center space-x-1 cursor-pointer"
                                            title="Tugaskan ke teknisi terpilih">
                                        <i class="fa-solid fa-paper-plane text-[10px]"></i>
                                        <span>Tugaskan</span>
                                    </button>
                                </form>

                                <!-- Tombol Penugasan Tim (Multi-Teknisi) -->
                                <button type="button" 
                                        onclick="openTeamModal({{ $ticket->id }}, '{{ $ticket->ticket_number }}', '{{ addslashes($ticket->divisi) }}', '{{ addslashes($ticket->jenis_perangkat) }}', '{{ addslashes(Str::limit($ticket->keluhan, 70)) }}')"
                                        class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg text-xs border border-indigo-200/80 transition inline-flex items-center space-x-1 cursor-pointer"
                                        title="Tugaskan sebagai Kerja Tim (Multi-Teknisi)">
                                    <i class="fa-solid fa-users text-[10px]"></i>
                                    <span>Tim</span>
                                </button>

                                <!-- Tombol Detail Tiket -->
                                <a href="{{ route('services.show', $ticket) }}" 
                                   class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs transition inline-flex items-center space-x-1"
                                   title="Lihat detail tiket">
                                    <span>Detail</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-circle-check text-emerald-500 text-2xl mb-2 block"></i>
                            <span class="font-medium">Semua tiket telah ditugaskan! Tidak ada antrian tiket yang tertunda.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2 WIDGET BAWAH: BEBAN KERJA TEKNISI & RINGKASAN BULAN INI -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Widget Kiri: Beban Kerja & Status Live Teknisi -->
        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Beban kerja teknisi</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5">Status live &bull; Jam kerja 08.00 – 17.00 WIB (otomatis Offline 17.00)</p>
                </div>
                <a href="{{ route('admin.statusTeknisi') }}" class="text-[11px] font-bold text-teal-700 hover:underline">
                    Pantau Live &rarr;
                </a>
            </div>

            <div class="space-y-4 mt-4">
                @forelse($bebanKerja as $b)
                <div>
                    <div class="flex items-center justify-between gap-2 text-xs">
                        <div class="flex items-center space-x-2 min-w-0">
                            <span class="w-2 h-2 rounded-full shrink-0 {{ $b['dot_class'] }}"></span>
                            <span class="font-bold text-slate-800 truncate">{{ $b['name'] }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border shrink-0 {{ $b['badge_class'] }}">
                                {{ $b['status_short'] }}
                            </span>
                        </div>
                        <span class="font-semibold text-slate-500 text-[11px] shrink-0">{{ $b['active_count'] }} tiket aktif</span>
                    </div>
                    @if(!empty($b['catatan']))
                        <div class="text-[10px] text-slate-400 pl-4 mt-0.5 truncate" title="{{ $b['catatan'] }}">
                            <i class="fa-regular fa-note-sticky mr-1"></i>{{ $b['catatan'] }}
                        </div>
                    @endif
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden mt-1.5 border border-slate-200/60">
                        <div class="h-full rounded-full transition-all duration-300 {{ $b['effective_status'] === 'cuti' ? 'bg-rose-400' : ($b['effective_status'] === 'offline' ? 'bg-slate-400' : ($b['effective_status'] === 'on_task' ? 'bg-amber-500' : 'bg-teal-600')) }}" style="width: {{ $b['percent'] }}%"></div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-xs text-slate-400">
                    Belum ada data teknisi terdaftar.
                </div>
                @endforelse
            </div>
        </div>

        <!-- Widget Kanan: Ringkasan Bulan Ini -->
        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">Ringkasan bulan ini</h3>
                <span class="text-[11px] text-slate-400">{{ now()->translatedFormat('F Y') }}</span>
            </div>

            @php
                $persenSelesai = $tiketMasukBulanIni > 0 
                    ? min(100, round(($tiketSelesaiBulanIni / $tiketMasukBulanIni) * 100)) 
                    : ($tiketSelesaiBulanIni > 0 ? 100 : 0);
            @endphp

            <div class="space-y-4 mt-4">
                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-800">Tiket masuk</span>
                        <span class="font-extrabold text-slate-900 text-sm">{{ $tiketMasukBulanIni }}</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden mt-1.5 border border-slate-200/60">
                        <div class="h-full bg-teal-600 rounded-full transition-all duration-300" style="width: {{ $tiketMasukBulanIni > 0 ? 100 : 0 }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-800">Tiket selesai</span>
                        <div class="flex items-center space-x-1.5">
                            <span class="font-semibold text-emerald-600 text-[11px]">({{ $persenSelesai }}% selesai)</span>
                            <span class="font-extrabold text-emerald-600 text-sm">{{ $tiketSelesaiBulanIni }}</span>
                        </div>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden mt-1.5 border border-slate-200/60">
                        <div class="h-full bg-emerald-600 rounded-full transition-all duration-300" style="width: {{ $persenSelesai }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-800">Rata-rata waktu penanganan</span>
                        <span class="font-extrabold text-teal-700 text-sm">{{ $avgHours }}</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden mt-1.5 border border-slate-200/60">
                        <div class="h-full bg-teal-600 rounded-full transition-all duration-300" style="width: {{ $tiketSelesaiBulanIni > 0 ? 100 : 0 }}%"></div>
                    </div>
                </div>

                <!-- Preventive Maintenance Quick Link -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-slate-700 flex items-center space-x-1.5">
                            <i class="fa-solid fa-screwdriver-wrench text-teal-600 text-[10px]"></i>
                            <span>Pemeliharaan Rutin Aset</span>
                        </div>
                        <div class="text-[10px] text-slate-400">Monitoring & Checklist PM Bulanan</div>
                    </div>
                    <a href="{{ route('maintenance.index') }}" class="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center space-x-1">
                        <span>Buka Log</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Penugasan Kerja Tim (Multi-Teknisi) -->
    <div id="modal-team-assign" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-lg w-full overflow-hidden animate-in fade-in duration-200">
            <!-- Header Modal -->
            <div class="px-6 py-4 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-300">
                        <i class="fa-solid fa-users text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-white">Penugasan Kerja Tim IT</h3>
                        <p class="text-[11px] text-slate-300">Kolaborasi multi-teknisi untuk aduan fisik / kendala berat</p>
                    </div>
                </div>
                <button type="button" onclick="closeTeamModal()" class="text-slate-400 hover:text-white transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form action="{{ route('admin.assignTicket') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="service_id" id="team-modal-service-id">

                <!-- Detail Tiket Ringkas -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-xs space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-teal-800 font-mono text-[11px]" id="team-modal-ticket-number">TKT-XXXX</span>
                        <span class="text-slate-500 font-semibold" id="team-modal-divisi">Divisi</span>
                    </div>
                    <div class="font-semibold text-slate-700" id="team-modal-perangkat">Perangkat</div>
                    <div class="text-slate-500 text-[11px] line-clamp-2" id="team-modal-keluhan">Keluhan</div>
                </div>

                <!-- 1. Pilih Teknisi Utama (Lead / PIC) -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">
                        Teknisi Utama (PIC / Lead Penanggung Jawab) <span class="text-rose-500">*</span>
                    </label>
                    <select name="teknisi_id" id="team-modal-pic" required onchange="updateCoTechAvailability()"
                            class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-600/30 focus:bg-white text-slate-800 font-medium">
                        <option value="">-- Pilih Penanggung Jawab Utama --</option>
                        @foreach($teknisiList as $tek)
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
                            @endphp
                            <option value="{{ $tek->id }}" {{ $isBlocked ? 'disabled' : '' }}>
                                {{ $tek->name }} ({{ $badge }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">PIC bertanggung jawab mengkoordinasikan pekerjaan dan input laporan akhir.</p>
                </div>

                <!-- 2. Pilih Anggota Tim Pendamping -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">
                        Anggota Tim Pendamping (Pilih 1 atau lebih) <span class="text-rose-500">*</span>
                    </label>
                    <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50 max-h-44 overflow-y-auto space-y-2">
                        @foreach($teknisiList as $tek)
                            @php
                                $tasks = $tek->active_tasks_count ?? 0;
                                $eff = $tek->effective_availability;
                                $isBlocked = $tek->isCuti() || in_array(strtolower((string) $tek->status_ketersediaan), ['offline', 'off'], true);
                            @endphp
                            <label class="flex items-center justify-between p-2 rounded-lg {{ $isBlocked ? 'bg-slate-50 opacity-60 cursor-not-allowed' : 'bg-white hover:border-indigo-300 cursor-pointer' }} border border-slate-200/70 transition select-none team-co-option" data-tech-id="{{ $tek->id }}">
                                <div class="flex items-center space-x-2.5">
                                    <input type="checkbox" name="teknisi_pendamping[]" value="{{ $tek->id }}" {{ $isBlocked ? 'disabled' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 co-tech-checkbox">
                                    <span class="text-xs font-bold text-slate-800">{{ $tek->name }}</span>
                                </div>
                                <span class="inline-flex items-center space-x-1 text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $tek->availability_badge_class }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $tek->availability_dot_class }}"></span>
                                    <span>{{ $tek->availability_label }}{{ in_array($eff, ['available', 'on_task'], true) ? " ({$tasks})" : '' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Tiket ini otomatis akan muncul di menu Tiket Saya pada seluruh akun teknisi yang dicentang.</p>
                </div>

                <!-- 3. Catatan Penugasan / Instruksi -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">
                        Instruksi Kerja Lapangan (Opsional)
                    </label>
                    <textarea name="catatan_penugasan" rows="2" placeholder="Contoh: Penarikan kabel LAN 4 titik di Control Room bersama Hafizh & Bagas..."
                              class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-600/30 focus:bg-white text-slate-800"></textarea>
                </div>

                <!-- Tombol Aksi -->
                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button type="button" onclick="closeTeamModal()" class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-users-gear text-xs"></i>
                        <span>Tugaskan Tim IT</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTeamModal(id, ticketNumber, divisi, perangkat, keluhan) {
            document.getElementById('team-modal-service-id').value = id;
            document.getElementById('team-modal-ticket-number').textContent = ticketNumber;
            document.getElementById('team-modal-divisi').textContent = divisi;
            document.getElementById('team-modal-perangkat').textContent = perangkat;
            document.getElementById('team-modal-keluhan').textContent = keluhan;

            // Reset selection
            document.getElementById('team-modal-pic').value = '';
            document.querySelectorAll('.co-tech-checkbox').forEach(cb => {
                cb.checked = false;
                cb.disabled = false;
            });
            document.querySelectorAll('.team-co-option').forEach(el => {
                el.classList.remove('opacity-40', 'pointer-events-none');
            });

            document.getElementById('modal-team-assign').classList.remove('hidden');
        }

        function closeTeamModal() {
            document.getElementById('modal-team-assign').classList.add('hidden');
        }

        function updateCoTechAvailability() {
            const selectedPic = document.getElementById('team-modal-pic').value;
            document.querySelectorAll('.team-co-option').forEach(el => {
                const techId = el.getAttribute('data-tech-id');
                const cb = el.querySelector('.co-tech-checkbox');
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
</div>
@endsection

