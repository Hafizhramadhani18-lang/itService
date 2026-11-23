@extends('layouts.app')

@section('title', $service->ticket_number . ' - Detail Tiket')

@section('content')
<div class="space-y-6">

@php
    $previousUrl = url()->previous();
    $currentUrl = url()->current();
    
    // Cek apakah halaman sebelumnya adalah halaman dalam aplikasi dan bukan halaman show ini sendiri
    $hasValidPrevious = !empty($previousUrl) && 
                        $previousUrl !== $currentUrl && 
                        !str_contains($previousUrl, '/services/' . $service->id);

    if ($hasValidPrevious) {
        $backUrl = $previousUrl;
    } elseif (Auth::user()->isAdmin()) {
        $backUrl = route('services.index', ['filter' => 'all']);
    } elseif (Auth::user()->isTeknisi()) {
        $backUrl = route('services.index', ['filter' => 'my']);
    } else {
        $backUrl = route('services.index');
    }

    // Tentukan label tombol kembali yang deskriptif dan akurat
    if (str_contains($backUrl, 'filter=all')) {
        $backLabel = 'Kembali ke Semua Tiket';
    } elseif (str_contains($backUrl, 'filter=my')) {
        $backLabel = 'Kembali ke Tiket Saya';
    } elseif (str_contains($backUrl, 'filter=history')) {
        $backLabel = 'Kembali ke Riwayat Tiket';
    } elseif (str_contains($backUrl, 'admin/penugasan')) {
        $backLabel = 'Kembali ke Penugasan Tiket';
    } elseif (str_contains($backUrl, 'admin')) {
        $backLabel = 'Kembali ke Dashboard Admin';
    } elseif (Auth::user()->isPegawai()) {
        $backLabel = 'Kembali ke Tiket Saya';
    } elseif (Auth::user()->isAdmin()) {
        $backLabel = 'Kembali ke Semua Tiket';
    } else {
        $backLabel = 'Kembali ke Daftar Tiket';
    }
@endphp

    <!-- Top Breadcrumb & Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ $backUrl }}" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs">
            <i class="fa-solid fa-arrow-left text-[10px]"></i>
            <span>{{ $backLabel }}</span>
        </a>

        @if(Auth::user()->isPegawai() && $service->status === 'Baru' && !$service->teknisi_id)
        <form action="{{ route('services.cancel', $service) }}" method="POST"
              onsubmit="return confirm('Apakah Anda yakin ingin membatalkan tiket #{{ $service->ticket_number }}?');">
            @csrf
            <button type="submit"
                    class="px-3.5 py-1.5 bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 rounded-lg text-xs font-semibold shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
                <i class="fa-solid fa-ban text-xs"></i>
                <span>Batalkan Tiket</span>
            </button>
        </form>
        @elseif((Auth::user()->isTeknisi() || Auth::user()->isAdmin()) && !$service->teknisi_id)
        <button type="button" 
                onclick="openClaimModal({{ json_encode([
                    'id' => $service->id,
                    'number' => $service->ticket_number,
                    'divisi' => $service->divisi,
                    'lokasi' => $service->lokasi_ruangan,
                    'perangkat' => $service->jenis_perangkat,
                    'kode' => $service->kode_perangkat,
                    'prioritas' => $service->prioritas,
                    'keluhan' => $service->keluhan,
                    'pelapor' => $service->nama_pelapor,
                    'url' => route('services.claim', $service)
                ]) }})"
                class="px-3.5 py-1.5 bg-teal-700 hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
            <i class="fa-solid fa-hand-holding-hand text-xs"></i>
            <span>{{ Auth::user()->isAdmin() ? 'Tugaskan / Ambil Tiket' : 'Ambil Tiket' }}</span>
        </button>

        <!-- Tombol Aksi Tim (Multi-Teknisi) -->
        <button type="button" 
                onclick="openTeamClaimModal({{ json_encode([
                    'id' => $service->id,
                    'number' => $service->ticket_number,
                    'divisi' => $service->divisi,
                    'lokasi' => $service->lokasi_ruangan,
                    'perangkat' => $service->jenis_perangkat,
                    'kode' => $service->kode_perangkat,
                    'prioritas' => $service->prioritas,
                    'keluhan' => Str::limit($service->keluhan, 90),
                    'pelapor' => $service->nama_pelapor,
                    'url' => route('services.claim', $service)
                ]) }})"
                class="px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg text-xs border border-indigo-200 transition flex items-center space-x-1.5 cursor-pointer"
                title="Ambil / Kerjakan bersama Tim Teknisi IT (Multi-Teknisi)">
            <i class="fa-solid fa-users text-xs"></i>
            <span>Kerja Tim</span>
        </button>
        @endif
    </div>

    <!-- Alert Notifikasi Flash -->
    @include('partials.flash-messages')

    @if(session('ticket_created_broadcast'))
    <script>
        (function() {
            try {
                const payload = {
                    event: 'new_ticket',
                    ticket_number: '{{ session('ticket_created_broadcast') }}',
                    time: Date.now()
                };
                if (window.BroadcastChannel) {
                    const ch = new BroadcastChannel('pltu_ticket_channel');
                    ch.postMessage(payload);
                }
                localStorage.setItem('pltu_new_ticket_broadcast', JSON.stringify(payload));
            } catch(e) {}
        })();
    </script>
    @endif

    @if(session('ticket_cancelled_broadcast'))
    <script>
        (function() {
            try {
                const payload = {
                    event: 'cancelled_ticket',
                    ticket_number: '{{ session('ticket_cancelled_broadcast')['ticket_number'] ?? '' }}',
                    pelapor: '{{ session('ticket_cancelled_broadcast')['pelapor'] ?? '' }}',
                    reason: '{{ session('ticket_cancelled_broadcast')['reason'] ?? '' }}',
                    time: Date.now()
                };
                if (window.BroadcastChannel) {
                    const ch = new BroadcastChannel('pltu_ticket_channel');
                    ch.postMessage(payload);
                }
                localStorage.setItem('pltu_cancelled_ticket_broadcast', JSON.stringify(payload));
            } catch(e) {}
        })();
    </script>
    @endif

@if(Auth::user()->isPegawai())
    <!-- ============================================== -->
    <!-- TAMPILAN DETAIL TIKET KHUSUS PEGAWAI / KARYAWAN -->
    <!-- ============================================== -->
    <div class="space-y-6">
        
        <!-- Header Card: Ticket Number, Title, Time, Status Badge -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    {{ $service->ticket_number }} - {{ $service->display_judul }}
                </h1>
                <p class="text-xs text-slate-400 mt-1 font-medium">
                    Dikirim {{ $service->created_at->format('d M Y, H:i') }} WIB
                </p>
            </div>

            <!-- Status & Action Badges -->
            <div class="flex flex-wrap items-center gap-2">
                @if(Auth::user()->isPegawai())
                    @if($service->teknisi_wa_url)
                        <a href="{{ $service->teknisi_wa_url }}" target="_blank"
                           class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300 font-bold text-xs shadow-2xs transition"
                           title="Hubungi Teknisi via WhatsApp">
                            <i class="fa-brands fa-whatsapp text-emerald-600 text-sm"></i>
                            <span>WA Teknisi</span>
                        </a>
                    @elseif($service->admin_wa_url && $service->status === 'Baru')
                        <a href="{{ $service->admin_wa_url }}" target="_blank"
                           class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300 font-bold text-xs shadow-2xs transition"
                           title="Hubungi Helpdesk IT via WhatsApp">
                            <i class="fa-brands fa-whatsapp text-emerald-600 text-sm"></i>
                            <span>WA Helpdesk IT</span>
                        </a>
                    @endif
                @else
                    @if($service->pelapor_wa_url)
                        <a href="{{ $service->pelapor_wa_url }}" target="_blank"
                           class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300 font-bold text-xs shadow-2xs transition"
                           title="Hubungi Pelapor via WhatsApp">
                            <i class="fa-brands fa-whatsapp text-emerald-600 text-sm"></i>
                            <span>WA Pelapor</span>
                        </a>
                    @endif
                @endif

                @if($service->status === 'Diproses')
                    <span class="px-4 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        Diproses
                    </span>
                @elseif($service->status === 'Menunggu sparepart')
                    <span class="px-4 py-1.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                        Menunggu sparepart
                    </span>
                @elseif($service->status === 'Selesai')
                    <span class="px-4 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center space-x-1">
                        <i class="fa-solid fa-circle-check text-xs"></i>
                        <span>Selesai</span>
                    </span>
                @elseif($service->status === 'Baru')
                    <span class="px-4 py-1.5 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                        Baru
                    </span>
                @elseif($service->status === 'Dibatalkan')
                    <span class="px-4 py-1.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                        Dibatalkan
                    </span>
                @else
                    <span class="px-4 py-1.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                        {{ $service->status }}
                    </span>
                @endif
            </div>
        </div>

        <!-- 2-Column Content Layout (Left: Info & Progres, Right: Komentar) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- LEFT COLUMN: Info Tiket & Progres Timeline (8 Cols) -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Card 1: Info Detail Tiket -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Detail Permintaan Servis</h2>
                    </div>

                    <div class="divide-y divide-slate-100 text-xs">
                        <div class="p-4 grid grid-cols-3 sm:grid-cols-4 gap-2">
                            <span class="text-slate-400 font-semibold">Kategori</span>
                            <span class="col-span-2 sm:col-span-3 font-bold text-slate-800">{{ $service->jenis_perangkat }}</span>
                        </div>
                        <div class="p-4 grid grid-cols-3 sm:grid-cols-4 gap-2">
                            <span class="text-slate-400 font-semibold">Lokasi Ruangan</span>
                            <span class="col-span-2 sm:col-span-3 font-bold text-slate-800">{{ $service->lokasi_ruangan }}</span>
                        </div>
                        <div class="p-4 grid grid-cols-3 sm:grid-cols-4 gap-2 items-center">
                            <span class="text-slate-400 font-semibold">Pelapor &amp; Divisi</span>
                            <div class="col-span-2 sm:col-span-3 flex flex-wrap items-center gap-2">
                                <span class="font-bold text-slate-800">{{ $service->nama_pelapor }}</span>
                                <span class="text-slate-300">&bull;</span>
                                <span class="text-slate-600 font-medium">{{ $service->divisi }}</span>
                                @if($service->pelapor_wa_url && !Auth::user()->isPegawai())
                                    <a href="{{ $service->pelapor_wa_url }}" target="_blank"
                                       class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300 font-bold text-[11px] transition shadow-2xs"
                                       title="Chat WhatsApp Pelapor">
                                        <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                                        <span>Chat WA</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="p-4 grid grid-cols-3 sm:grid-cols-4 gap-2 items-center">
                            <span class="text-slate-400 font-semibold">Prioritas &amp; SLA</span>
                            <div class="col-span-2 sm:col-span-3 flex flex-wrap items-center gap-2">
                                @if($service->prioritas === 'Mendesak')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fa-solid fa-fire text-[10px] mr-1"></i> Mendesak (SLA 2 Jam)
                                    </span>
                                @elseif($service->prioritas === 'Tinggi')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-50 text-orange-700 border border-orange-200">
                                        Tinggi (SLA 8 Jam)
                                    </span>
                                @elseif($service->prioritas === 'Sedang')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Sedang (SLA 24 Jam)
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        Rendah (SLA 48 Jam)
                                    </span>
                                @endif

                                @if($service->sla_status === 'ontime')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center">
                                        <i class="fa-solid fa-circle-check text-[10px] mr-1"></i> Sesuai SLA (Tepat Waktu)
                                    </span>
                                @elseif($service->sla_status === 'overdue_finished')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 inline-flex items-center">
                                        <i class="fa-solid fa-triangle-exclamation text-[10px] mr-1"></i> Melewati SLA ({{ $service->sla_remaining_text }})
                                    </span>
                                @elseif($service->sla_status === 'breached')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300 inline-flex items-center animate-pulse">
                                        <i class="fa-solid fa-clock text-[10px] mr-1"></i> Melewati SLA ({{ $service->sla_remaining_text }})
                                    </span>
                                @elseif($service->sla_status === 'warning')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center">
                                        <i class="fa-solid fa-hourglass-half text-[10px] mr-1"></i> Mendekati Batas ({{ $service->sla_remaining_text }})
                                    </span>
                                @elseif($service->sla_status === 'safe' && $service->status !== 'Dibatalkan')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200 inline-flex items-center">
                                        <i class="fa-solid fa-stopwatch text-[10px] mr-1"></i> SLA Aman ({{ $service->sla_remaining_text }})
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="p-4 grid grid-cols-3 sm:grid-cols-4 gap-2 items-center">
                            <span class="text-slate-400 font-semibold">Teknisi Bertugas</span>
                            <div class="col-span-2 sm:col-span-3 flex flex-wrap items-center gap-2">
                                @if($service->is_team)
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 inline-flex items-center space-x-1 shadow-2xs">
                                        <i class="fa-solid fa-users text-[10px]"></i>
                                        <span>Kerja Tim IT</span>
                                    </span>
                                    <span class="font-bold text-slate-800">
                                        {{ $service->teknisi_tim_label }}
                                    </span>
                                @else
                                    <span class="font-bold text-teal-800">
                                        {{ $service->teknisi_nama ?? 'Menunggu penugasan teknisi' }}
                                    </span>
                                @endif
                                @if($service->teknisi_wa_url && Auth::user()->isPegawai())
                                    <a href="{{ $service->teknisi_wa_url }}" target="_blank"
                                       class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300 font-bold text-[11px] transition shadow-2xs"
                                       title="Hubungi Teknisi via WhatsApp">
                                        <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                                        <span>Chat WA Teknisi</span>
                                    </a>
                                @elseif(!$service->teknisi_nama && $service->admin_wa_url && Auth::user()->isPegawai() && $service->status === 'Baru')
                                    <a href="{{ $service->admin_wa_url }}" target="_blank"
                                       class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300 font-bold text-[11px] transition shadow-2xs"
                                       title="Hubungi Helpdesk IT via WhatsApp">
                                        <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                                        <span>Chat Helpdesk IT</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="p-4 grid grid-cols-3 sm:grid-cols-4 gap-2">
                            <span class="text-slate-400 font-semibold">Deskripsi Kendala</span>
                            <span class="col-span-2 sm:col-span-3 text-slate-700 leading-relaxed font-medium">
                                {{ $service->keluhan }}
                            </span>
                        </div>

                        @if($service->lampiran_foto || $service->foto_selesai)
                        <div class="p-4 grid grid-cols-3 sm:grid-cols-4 gap-2">
                            <span class="text-slate-400 font-semibold">Dokumentasi Visual</span>
                            <div class="col-span-2 sm:col-span-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @if($service->lampiran_foto)
                                    <div class="space-y-1.5">
                                        <div class="text-[11px] font-bold text-slate-600 uppercase tracking-wider flex items-center space-x-1.5">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            <span>Foto Kendala (Sebelum)</span>
                                        </div>
                                        <a href="{{ asset($service->lampiran_foto) }}" target="_blank" class="block group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-xs">
                                            <img src="{{ asset($service->lampiran_foto) }}" alt="Foto Kendala Sebelum" class="w-full h-36 object-cover group-hover:scale-105 transition duration-200">
                                            <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-bold">
                                                <i class="fa-solid fa-arrow-up-right-from-square mr-1.5"></i> Lihat Ukuran Penuh
                                            </div>
                                        </a>
                                    </div>
                                    @endif

                                    @if($service->foto_selesai)
                                    <div class="space-y-1.5">
                                        <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider flex items-center space-x-1.5">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span>Hasil Perbaikan (Sesudah)</span>
                                        </div>
                                        <a href="{{ asset($service->foto_selesai) }}" target="_blank" class="block group relative overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50/30 shadow-xs">
                                            <img src="{{ asset($service->foto_selesai) }}" alt="Foto Hasil Perbaikan" class="w-full h-36 object-cover group-hover:scale-105 transition duration-200">
                                            <div class="absolute inset-0 bg-emerald-950/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-bold">
                                                <i class="fa-solid fa-arrow-up-right-from-square mr-1.5"></i> Lihat Ukuran Penuh
                                            </div>
                                        </a>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Card 2: Progres (Visual Vertical Timeline) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 tracking-tight">Progres</h2>

                    <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                        
                        <!-- Step 1: Tiket Dikirim -->
                        <div class="relative">
                            <span class="absolute -left-[19px] top-0.5 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white shadow-xs flex items-center justify-center text-white text-[8px]">
                                <i class="fa-solid fa-check"></i>
                            </span>
                            <div>
                                <div class="text-xs font-bold text-slate-900">Tiket dikirim</div>
                                <div class="text-[11px] text-slate-400">{{ $service->created_at->format('d M, H:i') }} WIB</div>
                            </div>
                        </div>

                        <!-- Step 2: Ditugaskan ke Teknisi -->
                        <div class="relative">
                            @if($service->teknisi_nama || $service->teknisi_id)
                                <span class="absolute -left-[19px] top-0.5 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white shadow-xs flex items-center justify-center text-white text-[8px]">
                                    <i class="fa-solid fa-check"></i>
                                </span>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">
                                        @if($service->is_team)
                                            Ditugaskan ke Tim IT: {{ $service->teknisi_tim_label }}
                                        @else
                                            Ditugaskan ke {{ $service->teknisi_nama ?? $service->teknisi->name }}
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-400">Teknisi telah menerima penugasan</div>
                                </div>
                            @else
                                <span class="absolute -left-[19px] top-0.5 w-4 h-4 rounded-full bg-slate-300 border-2 border-white shadow-xs"></span>
                                <div>
                                    <div class="text-xs font-bold text-slate-500">Menunggu penugasan teknisi</div>
                                    <div class="text-[11px] text-slate-400">Antrian IT Service Desk</div>
                                </div>
                            @endif
                        </div>

                        <!-- Step 3: Sedang Dicek -->
                        <div class="relative">
                            @if(in_array($service->status, ['Diproses', 'Menunggu sparepart', 'Selesai']))
                                <span class="absolute -left-[19px] top-0.5 w-4 h-4 rounded-full {{ $service->status === 'Selesai' ? 'bg-emerald-500' : 'bg-amber-500 ring-4 ring-amber-100' }} border-2 border-white shadow-xs flex items-center justify-center text-white text-[8px]">
                                    @if($service->status === 'Selesai')
                                        <i class="fa-solid fa-check"></i>
                                    @else
                                        <i class="fa-solid fa-wrench"></i>
                                    @endif
                                </span>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Sedang dicek</div>
                                    <div class="text-[11px] text-slate-600 font-medium">
                                        {{ $service->tindakan_perbaikan ?: ($service->status === 'Menunggu sparepart' ? 'Menunggu suku cadang: ' . $service->sparepart : 'Pemeriksaan driver dan komponen hardware') }}
                                    </div>
                                </div>
                            @else
                                <span class="absolute -left-[19px] top-0.5 w-4 h-4 rounded-full bg-slate-300 border-2 border-white shadow-xs"></span>
                                <div>
                                    <div class="text-xs font-bold text-slate-500">Pemeriksaan teknisi</div>
                                    <div class="text-[11px] text-slate-400">Menunggu investigasi fisik</div>
                                </div>
                            @endif
                        </div>

                        <!-- Step 4: Selesai -->
                        <div class="relative">
                            @if($service->status === 'Selesai')
                                <span class="absolute -left-[19px] top-0.5 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white shadow-xs flex items-center justify-center text-white text-[8px]">
                                    <i class="fa-solid fa-check"></i>
                                </span>
                                <div>
                                    <div class="text-xs font-bold text-emerald-800">
                                        {{ $service->pelapor_confirmed_at ? 'Selesai & Dikonfirmasi Pelapor' : 'Selesai' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        Diselesaikan pada {{ $service->tanggal_selesai ? $service->tanggal_selesai->format('d M, H:i') . ' WIB' : 'Hari ini' }}
                                    </div>
                                    @if($service->pelapor_confirmed_at)
                                        <div class="text-[11px] text-emerald-700 font-semibold mt-0.5">
                                            Dikonfirmasi Anda pada {{ $service->pelapor_confirmed_at->format('d M Y, H:i') }} WIB
                                        </div>
                                    @else
                                        <div class="text-[11px] text-amber-700 font-semibold mt-0.5">
                                            Menunggu konfirmasi Anda
                                        </div>
                                    @endif
                                </div>
                            @elseif($service->status === 'Dibatalkan')
                                <span class="absolute -left-[19px] top-0.5 w-4 h-4 rounded-full bg-rose-500 border-2 border-white shadow-xs flex items-center justify-center text-white text-[8px]">
                                    <i class="fa-solid fa-xmark"></i>
                                </span>
                                <div>
                                    <div class="text-xs font-bold text-rose-700">Dibatalkan</div>
                                    <div class="text-[11px] text-slate-500">Tiket dibatalkan oleh pelapor</div>
                                </div>
                            @else
                                <span class="absolute -left-[19px] top-0.5 w-4 h-4 rounded-full bg-slate-200 border-2 border-white shadow-xs"></span>
                                <div>
                                    <div class="text-xs font-bold text-slate-400">Selesai: menunggu</div>
                                    <div class="text-[11px] text-slate-400">Menunggu penyelesaian teknisi</div>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

                @if($service->status === 'Selesai')
                    @if(!$service->pelapor_confirmed_at)
                    <!-- Card 3: Konfirmasi Penyelesaian dari Pegawai -->
                    <div class="bg-amber-50/80 rounded-2xl border border-amber-200 shadow-xs p-5 sm:p-6 space-y-4">
                        <div class="flex items-start space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-clipboard-check text-sm"></i>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Konfirmasi Penyelesaian Tiket</h2>
                                <p class="text-xs text-slate-600 mt-0.5">
                                    Teknisi telah menandai tiket ini selesai. Silakan cek kondisi perangkat Anda dan konfirmasi hasilnya.
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <form action="{{ route('services.confirm', $service) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="action" value="confirm">
                                <button type="submit"
                                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
                                    <i class="fa-solid fa-circle-check text-xs"></i>
                                    <span>Ya, Sudah Selesai &amp; Normal</span>
                                </button>
                            </form>

                            <button type="button"
                                    onclick="document.getElementById('reopen-form-box').classList.toggle('hidden')"
                                    class="px-4 py-2 rounded-xl bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold transition flex items-center space-x-1.5 cursor-pointer">
                                <i class="fa-solid fa-rotate-left text-xs"></i>
                                <span>Kendala Belum Teratasi</span>
                            </button>
                        </div>

                        <form id="reopen-form-box" action="{{ route('services.confirm', $service) }}" method="POST"
                              class="{{ old('action') === 'reopen' ? '' : 'hidden' }} pt-3 border-t border-amber-200/70 space-y-2.5">
                            @csrf
                            <input type="hidden" name="action" value="reopen">
                            <label class="block text-xs font-bold text-slate-800">
                                Jelaskan kendala yang masih terjadi <span class="text-rose-500">*</span>
                            </label>
                            <textarea name="note" rows="2" required
                                      placeholder="Misal: Printer masih offline saat dicoba cetak ulang..."
                                      class="w-full px-3.5 py-2 bg-white border border-amber-300 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-rose-500">{{ old('note') }}</textarea>
                            <div class="flex justify-end">
                                <button type="submit"
                                        class="px-4 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition cursor-pointer">
                                    Kirim &amp; Buka Kembali Tiket
                                </button>
                            </div>
                        </form>
                    </div>
                    @else
                    <div class="bg-emerald-50/80 rounded-2xl border border-emerald-200 p-4 flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-circle-check text-sm"></i>
                        </div>
                        <div class="text-xs">
                            <div class="font-bold text-emerald-900">Penyelesaian Tiket Telah Dikonfirmasi</div>
                            <div class="text-emerald-700">
                                Dikonfirmasi oleh Anda pada {{ $service->pelapor_confirmed_at->format('d M Y, H:i') }} WIB.
                            </div>
                        </div>
                    </div>
                    @endif
                @endif

            </div>

            <!-- RIGHT COLUMN: Komentar (4 Cols) -->
            <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 tracking-tight">Komentar</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Komunikasi langsung dengan teknisi yang bertugas</p>
                </div>

                <!-- Message History Stream -->
                <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                    @forelse($service->comments as $c)
                    <div class="p-3 rounded-2xl {{ in_array($c->author_role, ['teknisi', 'admin']) ? 'bg-teal-50/70 border border-teal-200/70' : 'bg-slate-50 border border-slate-200/80' }} space-y-1">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold {{ in_array($c->author_role, ['teknisi', 'admin']) ? 'text-teal-900' : 'text-slate-900' }}">
                                {{ $c->author_name }}
                                @if($c->author_role === 'admin')
                                    <span class="text-[9px] px-1.5 py-0.2 rounded bg-teal-700 text-white ml-1 font-semibold">Koordinator IT</span>
                                @elseif($c->author_role === 'teknisi')
                                    <span class="text-[9px] px-1.5 py-0.2 rounded bg-teal-200/70 text-teal-800 ml-1 font-semibold">Teknisi</span>
                                @endif
                            </span>
                            <span class="text-[10px] text-slate-400">{{ $c->created_at->format('d M, H:i') }}</span>
                        </div>
                        <div class="text-xs text-slate-700 leading-relaxed font-medium">
                            {{ $c->message }}
                        </div>
                    </div>
                    @empty
                    <div class="p-4 rounded-xl bg-slate-50 text-center text-xs text-slate-400 italic">
                        Belum ada pesan. Anda dapat menuliskan pertanyaan atau informasi tambahan kepada teknisi di bawah.
                    </div>
                    @endforelse
                </div>

                <!-- Comment Input Form -->
                <form action="{{ route('services.comment', $service) }}" method="POST" class="space-y-3 pt-2 border-t border-slate-100">
                    @csrf
                    <div>
                        <textarea name="message" 
                                  rows="3" 
                                  required 
                                  placeholder="Tulis komentar..."
                                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition leading-relaxed"></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                            <i class="fa-solid fa-paper-plane text-[10px]"></i>
                            <span>Kirim</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>

    </div>

@else
    <!-- ============================================== -->
    <!-- TAMPILAN DETAIL TIKET KHUSUS TEKNISI IT        -->
    <!-- ============================================== -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-6 sm:p-8 space-y-6">
        
        <!-- Header: Ticket Title, Subtitle, Status Badge -->
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-6 border-b border-slate-100">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                        {{ $service->ticket_number }} &bull; {{ $service->jenis_perangkat }} - {{ Str::limit($service->keluhan, 40) }}
                    </h1>
                    @php $tb = $service->badge_target; @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $tb['bg'] }} flex items-center space-x-1">
                        <i class="{{ $tb['icon'] }} text-[10px]"></i>
                        <span>{{ $tb['unit'] }}</span>
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-y-1.5 gap-x-3 text-xs text-slate-500 mt-2 font-medium">
                    <div class="flex items-center space-x-1 text-slate-700 font-bold">
                        <i class="fa-solid fa-building text-slate-400 text-[10px]"></i>
                        <span>{{ $service->divisi }}</span>
                    </div>
                    <span>&bull;</span>
                    <div class="flex items-center space-x-1 font-bold text-teal-800">
                        <i class="fa-solid fa-location-dot text-rose-500 text-[10px]"></i>
                        <span>{{ $service->lokasi_ruangan }}</span>
                    </div>
                    <span>&bull;</span>
                    <div class="flex items-center space-x-1">
                        <span>Pelapor:</span>
                        <span class="font-bold text-slate-800">{{ $service->nama_pelapor }}</span>
                        @if(!empty($service->user?->no_hp))
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $service->user->no_hp) }}" 
                               target="_blank" 
                               class="inline-flex items-center space-x-1 px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[11px] hover:bg-emerald-100 transition ml-1"
                               title="Hubungi Pelapor via WhatsApp">
                                <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                                <span>{{ $service->user->no_hp }}</span>
                            </a>
                        @endif
                    </div>
                    @if($service->kode_perangkat)
                    <span>&bull;</span>
                    <div>Kode: <span class="font-mono text-teal-700 font-bold">{{ $service->kode_perangkat }}</span></div>
                    @endif
                    <span>&bull;</span>
                    @if($service->is_team)
                        <div class="flex items-center space-x-1.5">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 inline-flex items-center space-x-1">
                                <i class="fa-solid fa-users text-[9px]"></i>
                                <span>Kerja Tim IT</span>
                            </span>
                            <span class="font-bold text-slate-800">{{ $service->teknisi_tim_label }}</span>
                        </div>
                    @else
                        <div>Teknisi: <span class="font-bold text-teal-800">{{ $service->teknisi_nama ?? ($service->teknisi ? $service->teknisi->name : 'Belum Ada') }}</span></div>
                    @endif
                </div>
            </div>

            <!-- Status Pill Badge -->
            <div class="flex-shrink-0">
                @if($service->status === 'Diproses')
                    <span class="px-3.5 py-1 rounded-full font-bold bg-amber-50 text-amber-700 border border-amber-200/80 text-xs">
                        Diproses
                    </span>
                @elseif($service->status === 'Menunggu sparepart')
                    <span class="px-3.5 py-1 rounded-full font-bold bg-purple-50 text-purple-700 border border-purple-200/80 text-xs">
                        Menunggu sparepart
                    </span>
                @elseif($service->status === 'Selesai')
                    <span class="px-3.5 py-1 rounded-full font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 text-xs flex items-center space-x-1">
                        <i class="fa-solid fa-circle-check text-[11px]"></i>
                        <span>Selesai</span>
                    </span>
                @elseif($service->status === 'Baru')
                    <span class="px-3.5 py-1 rounded-full font-bold bg-blue-50 text-blue-700 border border-blue-200/80 text-xs">
                        Baru
                    </span>
                @else
                    <span class="px-3.5 py-1 rounded-full font-bold bg-slate-100 text-slate-600 text-xs">
                        Dibatalkan
                    </span>
                @endif
            </div>
        </div>

        <!-- 2 Column Layout (Left 8 cols, Right 4 cols) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Left Column: Problem Description & Troubleshooting Checklist -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Deskripsi Masalah Box -->
                <div class="space-y-2">
                    <h2 class="text-xs font-bold text-slate-900">Deskripsi masalah</h2>
                    <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 text-xs text-slate-700 leading-relaxed font-medium">
                        {{ $service->keluhan }}
                    </div>

                    @if($service->lampiran_foto)
                    <div class="mt-2">
                        <a href="{{ asset($service->lampiran_foto) }}" target="_blank" class="inline-block">
                            <img src="{{ asset($service->lampiran_foto) }}" alt="Lampiran" class="w-32 h-24 object-cover rounded-xl border border-slate-200 shadow-xs">
                            <span class="text-[10px] text-teal-700 font-semibold block mt-1">Buka Lampiran Foto Pelapor</span>
                        </a>
                    </div>
                    @endif
                </div>

                <!-- Checklist Troubleshooting -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xs font-bold text-slate-900">Checklist troubleshooting</h2>
                        @php
                            $checklist = $service->checklist ?? [];
                            $totalChecklist = count($checklist);
                            $completedChecklist = count(array_filter($checklist, fn($item) => !empty($item['done'])));
                        @endphp
                        <span class="text-[11px] font-semibold text-slate-500">
                            {{ $completedChecklist }} dari {{ $totalChecklist }} selesai
                        </span>
                    </div>

                    <div class="space-y-2">
                        @forelse($checklist as $index => $item)
                        <div class="p-3 rounded-xl border {{ !empty($item['done']) ? 'bg-teal-50/40 border-teal-200/60' : 'bg-white border-slate-200' }} flex items-center justify-between transition">
                            <label class="flex items-center space-x-3 cursor-pointer text-xs flex-1">
                                <form action="{{ route('services.checklist', $service) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="index" value="{{ $index }}">
                                    <input type="checkbox" onchange="this.form.submit()" {{ !empty($item['done']) ? 'checked' : '' }}
                                           class="w-4 h-4 rounded text-teal-700 focus:ring-teal-600 border-slate-300 transition cursor-pointer">
                                </form>
                                <span class="{{ !empty($item['done']) ? 'line-through text-slate-400 font-medium' : 'text-slate-800 font-medium' }}">
                                    {{ $item['task'] }}
                                </span>
                            </label>
                            @if(!empty($item['done']))
                            <span class="text-[10px] text-teal-700 font-semibold flex items-center space-x-1">
                                <i class="fa-solid fa-check text-[9px]"></i>
                                <span>Selesai</span>
                            </span>
                            @endif
                        </div>
                        @empty
                        <div class="p-3 bg-slate-50 rounded-xl text-slate-400 text-xs">
                            Tidak ada checklist khusus untuk jenis perangkat ini.
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Sparepart / Material info if any -->
                @if($service->sparepart)
                <div class="p-3.5 rounded-xl bg-purple-50/60 border border-purple-200/80 text-xs text-purple-900 flex items-center space-x-2.5">
                    <i class="fa-solid fa-microchip text-purple-600"></i>
                    <div>
                        <span class="font-bold">Sparepart / Material Digunakan:</span>
                        <span class="ml-1 text-purple-800">{{ $service->sparepart }}</span>
                    </div>
                </div>
                @endif

                <!-- Komentar / Pesan Diskusi dengan Pelapor -->
                <div class="bg-slate-50/80 rounded-2xl border border-slate-200 p-5 space-y-4">
                    <div>
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Komentar & Catatan dengan Pelapor</h2>
                        <p class="text-[10px] text-slate-400 mt-0.5">Pesan yang ditulis akan terbaca oleh pelapor tiket ({{ $service->nama_pelapor }})</p>
                    </div>

                    <div class="space-y-2.5 max-h-60 overflow-y-auto">
                        @forelse($service->comments as $c)
                        <div class="p-3 rounded-xl {{ in_array($c->author_role, ['teknisi', 'admin']) ? 'bg-teal-50 border border-teal-200' : 'bg-white border border-slate-200' }} text-xs space-y-1">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-bold {{ in_array($c->author_role, ['teknisi', 'admin']) ? 'text-teal-900' : 'text-slate-900' }}">
                                    {{ $c->author_name }}
                                    @if($c->author_role === 'admin')
                                        <span class="text-[9px] px-1.5 py-0.2 rounded bg-teal-700 text-white ml-1 font-semibold">Koordinator IT</span>
                                    @elseif($c->author_role === 'teknisi')
                                        <span class="text-[9px] px-1 py-0.5 rounded bg-teal-200 text-teal-800 ml-1">Teknisi</span>
                                    @endif
                                </span>
                                <span class="text-[10px] text-slate-400">{{ $c->created_at->format('d M, H:i') }}</span>
                            </div>
                            <div class="text-slate-700">{{ $c->message }}</div>
                        </div>
                        @empty
                        <div class="p-3 bg-white rounded-xl text-center text-xs text-slate-400 italic">
                            Belum ada komentar dari pelapor.
                        </div>
                        @endforelse
                    </div>

                    <form action="{{ route('services.comment', $service) }}" method="POST" class="space-y-2">
                        @csrf
                        <textarea name="message" rows="2" required placeholder="Balas atau tinggalkan catatan untuk pelapor..."
                                  class="w-full py-2 px-3 bg-white border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700"></textarea>
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-1.5 bg-teal-700 hover:bg-teal-800 text-white rounded-lg text-xs font-semibold shadow-xs">
                                Kirim Balasan
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- Right Column: Update Ticket Box & Timeline History -->
            <div class="lg:col-span-4 space-y-6">

                <!-- Box 1: Perbarui Tiket -->
                <div class="bg-white rounded-xl border border-slate-200/80 p-5 space-y-4 shadow-xs">
                    <h3 class="text-xs font-bold text-slate-900">Perbarui tiket</h3>

                    <form action="{{ route('services.update', $service) }}" method="POST" enctype="multipart/form-data" class="space-y-3.5 text-xs">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-slate-600 font-semibold mb-1">Status</label>
                            <select name="status" class="w-full py-2 px-3 bg-white border border-slate-200 rounded-lg text-xs font-medium focus:outline-none focus:ring-1 focus:ring-teal-700 focus:border-teal-700">
                                <option value="Baru" {{ $service->status === 'Baru' ? 'selected' : '' }}>Baru</option>
                                <option value="Diproses" {{ $service->status === 'Diproses' ? 'selected' : '' }}>Diproses</option>
                                <option value="Menunggu sparepart" {{ $service->status === 'Menunggu sparepart' ? 'selected' : '' }}>Menunggu sparepart</option>
                                <option value="Selesai" {{ $service->status === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                                <option value="Dibatalkan" {{ $service->status === 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-600 font-semibold mb-1">Teknisi Penanggung Jawab</label>
                            <select name="teknisi_nama" class="w-full py-2 px-3 bg-white border border-slate-200 rounded-lg text-xs font-medium focus:outline-none focus:ring-1 focus:ring-teal-700 focus:border-teal-700">
                                <option value="">-- Belum ada teknisi --</option>
                                @foreach($teknisiList as $keyName => $labelName)
                                <option value="{{ $keyName }}" {{ ($service->teknisi_nama ?? '') === $keyName ? 'selected' : '' }}>
                                    {{ $labelName }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-600 font-semibold mb-1">Catatan penyelesaian</label>
                            <textarea name="tindakan_perbaikan" rows="3" placeholder="Tuliskan tindakan dan penyebab masalah"
                                      class="w-full py-2 px-3 bg-white border border-slate-200 rounded-lg text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:border-teal-700">{{ old('tindakan_perbaikan', $service->tindakan_perbaikan) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-slate-600 font-semibold mb-1">Kebutuhan sparepart</label>
                            <input type="text" name="sparepart" value="{{ old('sparepart', $service->sparepart) }}" placeholder="Contoh: 1x Modul RAM 8GB, 2x RJ45"
                                   class="w-full py-2 px-3 bg-white border border-slate-200 rounded-lg text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:border-teal-700">
                        </div>

                        <div>
                            <label class="block text-slate-600 font-semibold mb-1">Foto Bukti Hasil Perbaikan (Opsional)</label>
                            <input type="file" name="foto_selesai" accept="image/*"
                                   class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                            @if($service->foto_selesai)
                                <div class="mt-1 text-[11px] text-emerald-700 flex items-center space-x-1">
                                    <i class="fa-solid fa-circle-check text-[10px]"></i>
                                    <span>Sudah ada foto hasil perbaikan terunggah.</span>
                                </div>
                            @endif
                        </div>

                        <button type="submit" class="w-full py-2 bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-lg text-xs shadow-xs transition">
                            Simpan Perubahan
                        </button>
                    </form>

                    <!-- Tandai Selesai Outline Button -->
                    @if($service->status !== 'Selesai')
                    <form action="{{ route('services.done', $service) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-2 border border-teal-700 text-teal-800 hover:bg-teal-50 font-semibold rounded-lg text-xs transition">
                            Tandai Selesai
                        </button>
                    </form>
                    @endif
                </div>

                <!-- Box 2: Riwayat (Timeline History) -->
                <div class="bg-white rounded-xl border border-slate-200/80 p-5 space-y-3 shadow-xs">
                    <h3 class="text-xs font-bold text-slate-900">Riwayat</h3>

                    <div class="relative pl-4 border-l-2 border-slate-200 space-y-3 text-xs">
                        <div class="relative">
                            <span class="absolute -left-[21px] top-1 w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                            <div class="font-medium text-slate-800">Tiket dibuat</div>
                            <div class="text-[10px] text-slate-400">{{ $service->created_at->format('d M Y, H:i') }} WIB</div>
                        </div>

                        @if($service->teknisi_nama || $service->teknisi)
                        <div class="relative">
                            <span class="absolute -left-[21px] top-1 w-2.5 h-2.5 rounded-full bg-teal-600"></span>
                            <div class="font-medium text-slate-800">Ditangani oleh <span class="font-bold text-teal-800">{{ $service->teknisi_nama ?? $service->teknisi->name }}</span></div>
                            <div class="text-[10px] text-slate-400">Penanganan troubleshooting hardware</div>
                        </div>
                        @endif

                        <div class="relative">
                            <span class="absolute -left-[21px] top-1 w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <div class="font-medium text-slate-800">Checklist {{ $completedChecklist }} dari {{ $totalChecklist }} selesai</div>
                            <div class="text-[10px] text-slate-400">Progres investigasi</div>
                        </div>

                        @if($service->tanggal_selesai)
                        <div class="relative">
                            <span class="absolute -left-[21px] top-1 w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            <div class="font-bold text-emerald-800">Perbaikan Selesai</div>
                            <div class="text-[10px] text-slate-400">{{ $service->tanggal_selesai->format('d M Y, H:i') }} WIB</div>
                        </div>
                        @endif
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- MODAL POP-UP: KONFIRMASI PENUGASAN TEKNISI -->
    <div id="claimModal" class="fixed inset-0 z-50 hidden transition-opacity duration-200">
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs" onclick="closeClaimModal()"></div>

        <div class="fixed inset-0 flex items-center justify-center p-4 z-10 pointer-events-none">
            <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200/90 shadow-2xl p-6 pointer-events-auto transform transition-all duration-200 scale-95 opacity-0 space-y-4" id="claimModalBox">
                
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

                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span id="modalTicketDivisi" class="font-bold text-slate-800">Divisi</span>
                        <span id="modalTicketPriority" class="px-2 py-0.5 rounded-full font-bold text-[10px]">Prioritas</span>
                    </div>
                    <div>
                        <span class="text-slate-400">Perangkat:</span>
                        <span id="modalTicketDevice" class="font-semibold text-slate-700 ml-1">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400">Lokasi:</span>
                        <span id="modalTicketLocation" class="text-slate-700 ml-1">-</span>
                    </div>
                    <div class="pt-1.5 border-t border-slate-200/60">
                        <span class="text-slate-400">Keluhan:</span>
                        <p id="modalTicketComplaint" class="text-slate-600 italic mt-0.5 font-medium line-clamp-2">"..."</p>
                    </div>
                </div>

                <form id="claimForm" method="POST" action="" class="space-y-4">
                    @csrf
                    <div>
                        <label for="teknisi_nama" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Pilih Personil Teknisi yang Menangani:
                        </label>
                        <select name="teknisi_nama" id="modalTeknisiSelect" required
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                            @if(Auth::user()->isTeknisi())
                                <option value="{{ Auth::user()->name }}" selected>{{ Auth::user()->name }} (Saya Sendiri)</option>
                            @endif
                            @foreach($teknisiList as $keyName => $labelName)
                                @if($keyName !== Auth::user()->name)
                                    <option value="{{ $keyName }}" {{ (Auth::user()->isAdmin() && $loop->first) ? 'selected' : '' }}>{{ $labelName }}</option>
                                @endif
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">
                            Tiket otomatis berpindah dari "Antrian Tiket" ke menu "Tiket Saya" teknisi bersangkutan.
                        </p>
                    </div>

                    <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
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

    <script>
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
@endif

</div>
@endsection
