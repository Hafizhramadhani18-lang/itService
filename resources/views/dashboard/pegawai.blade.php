@extends('layouts.app')

@section('title', 'Beranda Pegawai - IT Service Desk')

@section('content')
<div class="space-y-6">

    <!-- Alert Notifikasi Flash -->
    @include('partials.flash-messages')

    <!-- Top Greeting Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            @php
                $firstName = explode(' ', Auth::user()->name)[0];
            @endphp
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Halo, {{ $firstName }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                Ada kendala IT di divisi {{ Auth::user()->divisi ?? 'Anda' }}? Laporkan di sini.
            </p>
        </div>

        <div class="w-full sm:w-auto">
            <a href="{{ route('services.create') }}" 
               class="w-full sm:w-auto flex sm:inline-flex items-center justify-center space-x-2 px-6 py-3 sm:py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-sm shadow-md shadow-teal-900/20 transition">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Buat Tiket Baru</span>
            </a>
        </div>
    </div>

    <!-- 3 Stat Cards (Tiket aktif, Perlu konfirmasi, Selesai bulan ini) - Tampil Kesamping (3 Kolom) di HP & Desktop -->
    <div class="grid grid-cols-3 gap-2 sm:gap-4">
        
        <!-- Card 1: Tiket Aktif -->
        <a href="{{ route('services.index', ['status' => 'aktif']) }}" 
           class="bg-white p-3 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-teal-500/50 hover:shadow-sm transition text-center group flex flex-col justify-center">
            <div class="text-2xl sm:text-4xl font-black text-slate-900 group-hover:text-teal-700 transition leading-tight">
                {{ $myActiveCount ?? 0 }}
            </div>
            <div class="text-[11px] sm:text-xs font-semibold text-slate-500 mt-1 leading-snug">
                Tiket aktif
            </div>
        </a>

        <!-- Card 2: Perlu Konfirmasi -->
        <a href="{{ route('services.index', ['status' => 'perlu_konfirmasi']) }}" 
           class="bg-white p-3 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-amber-500/50 hover:shadow-sm transition text-center group flex flex-col justify-center">
            <div class="text-2xl sm:text-4xl font-black {{ ($myNeedConfirmCount ?? 0) > 0 ? 'text-amber-600' : 'text-slate-900' }} group-hover:text-amber-600 transition leading-tight">
                {{ $myNeedConfirmCount ?? 0 }}
            </div>
            <div class="text-[11px] sm:text-xs font-semibold text-slate-500 mt-1 leading-snug">
                Perlu konfirmasi
            </div>
        </a>

        <!-- Card 3: Selesai Bulan Ini -->
        <a href="{{ route('services.index', ['status' => 'Selesai']) }}" 
           class="bg-white p-3 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-emerald-500/50 hover:shadow-sm transition text-center group flex flex-col justify-center">
            <div class="text-2xl sm:text-4xl font-black text-slate-900 group-hover:text-emerald-600 transition leading-tight">
                {{ $mySelesaiBulanIniCount ?? 0 }}
            </div>
            <div class="text-[11px] sm:text-xs font-semibold text-slate-500 mt-1 leading-snug">
                Selesai bulan ini
            </div>
        </a>

    </div>

    @if(($myNeedConfirmCount ?? 0) > 0)
    <div class="bg-amber-50/90 border border-amber-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-start sm:items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-bell text-sm"></i>
            </div>
            <div>
                <div class="text-xs sm:text-sm font-bold text-amber-950">
                    Ada {{ $myNeedConfirmCount }} tiket selesai yang menunggu konfirmasi Anda
                </div>
                <p class="text-[11px] text-amber-800/80 mt-0.5">
                    Pastikan perangkat sudah kembali normal atau laporkan jika kendala masih terjadi.
                </p>
            </div>
        </div>
        <a href="{{ route('services.index', ['status' => 'perlu_konfirmasi']) }}"
           class="inline-flex items-center justify-center space-x-1.5 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-xs transition shrink-0">
            <span>Tinjau &amp; Konfirmasi</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </a>
    </div>
    @endif

    <!-- 2-Column Content Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN: Tiket Terbaru (8 Cols) -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-4">
            
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900 tracking-tight">Tiket terbaru</h2>
                <a href="{{ route('services.index') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800 flex items-center space-x-1">
                    <span>Lihat Semua Tiket</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <div class="space-y-3">
                @forelse($myRecentTickets as $ticket)
                <a href="{{ route('services.show', $ticket) }}" 
                   class="block bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-teal-500/40 hover:shadow-md transition duration-150">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono font-extrabold text-sm text-slate-900 tracking-wide">
                            {{ $ticket->ticket_number }}
                        </span>
                        
                        <!-- Status Badge -->
                        <div>
                            @if($ticket->status === 'Diproses')
                                <span class="px-3 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    Diproses
                                </span>
                            @elseif($ticket->status === 'Menunggu sparepart')
                                <span class="px-3 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                    Menunggu sparepart
                                </span>
                            @elseif($ticket->status === 'Selesai')
                                <div class="flex items-center space-x-1.5">
                                    @if(!$ticket->pelapor_confirmed_at)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Perlu konfirmasi
                                        </span>
                                    @endif
                                    <span class="px-3 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Selesai
                                    </span>
                                </div>
                            @elseif($ticket->status === 'Dibatalkan')
                                <span class="px-3 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    Dibatalkan
                                </span>
                            @elseif($ticket->status === 'Baru')
                                <span class="px-3 py-0.5 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                    Baru
                                </span>
                            @else
                                <span class="px-3 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                                    {{ $ticket->status }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-2 text-sm font-bold text-slate-800 line-clamp-1">
                        {{ $ticket->display_judul }}
                    </div>

                    <div class="mt-2 flex items-center space-x-2 text-xs text-slate-400 font-medium">
                        <span>{{ $ticket->jenis_perangkat }}</span>
                        <span>&bull;</span>
                        <span>{{ $ticket->created_at->format('d M') }}</span>
                        @if($ticket->teknisi_nama)
                            <span>&bull;</span>
                            <span class="text-teal-700 font-semibold">Teknisi: {{ $ticket->teknisi_nama }}</span>
                        @endif
                    </div>
                </a>
                @empty
                <div class="bg-white p-8 rounded-2xl border border-dashed border-slate-300 text-center space-y-3">
                    <div class="w-12 h-12 rounded-full bg-teal-50 text-teal-700 mx-auto flex items-center justify-center text-lg">
                        <i class="fa-solid fa-inbox"></i>
                    </div>
                    <div class="text-sm font-bold text-slate-700">Belum ada tiket yang dibuat</div>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">
                        Jika ada kendala printer, PC kerja, jaringan internet atau akun di ruangan Anda, segera laporkan.
                    </p>
                    <a href="{{ route('services.create') }}" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-teal-700 text-white text-xs font-bold shadow-xs">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Buat Tiket Sekarang</span>
                    </a>
                </div>
                @endforelse
            </div>

        </div>

        <!-- RIGHT COLUMN: Cari Solusi Mandiri & Butuh Bantuan Langsung (4-5 Cols) -->
        <div class="lg:col-span-5 xl:col-span-4 space-y-5">
            
            <!-- Widget 1: Cari solusi mandiri -->
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 tracking-tight">Cari solusi mandiri</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Panduan penanganan cepat sebelum teknisi datang</p>
                </div>

                <!-- Search Input -->
                <form action="{{ route('knowledge.base') }}" method="GET" class="relative">
                    <input type="text" name="search" placeholder="Ketik kendala, mis. printer offline" 
                           class="w-full pl-3.5 pr-9 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                    <button type="submit" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-teal-700">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </button>
                </form>

                <!-- Quick troubleshooting topics -->
                <div class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                    <a href="{{ route('knowledge.base') }}#printer" class="py-2.5 flex items-center justify-between hover:text-teal-700 group transition">
                        <span>Printer tidak terdeteksi</span>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-300 group-hover:text-teal-600 transition"></i>
                    </a>
                    <a href="{{ route('knowledge.base') }}#akun" class="py-2.5 flex items-center justify-between hover:text-teal-700 group transition">
                        <span>Lupa password Windows / email</span>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-300 group-hover:text-teal-600 transition"></i>
                    </a>
                    <a href="{{ route('knowledge.base') }}#jaringan" class="py-2.5 flex items-center justify-between hover:text-teal-700 group transition">
                        <span>Tidak bisa konek Wi-Fi kantor</span>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-300 group-hover:text-teal-600 transition"></i>
                    </a>
                </div>
            </div>

            <!-- Widget 2: Butuh bantuan langsung? -->
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 tracking-tight">Butuh bantuan langsung?</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Untuk gangguan mendesak, hubungi IT Support.</p>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-mono text-xs font-bold flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-phone text-teal-700"></i>
                        <span>Ext. 102 (IT Support)</span>
                    </div>
                    <span class="text-[10px] text-emerald-600 font-sans font-semibold">Aktif 24/7</span>
                </div>

                <div class="text-[11px] text-slate-500 space-y-1">
                    <div class="flex items-center space-x-1.5">
                        <i class="fa-brands fa-whatsapp text-emerald-600 text-xs"></i>
                        <span>WA Helpdesk: <strong>0812-3456-7890</strong></span>
                    </div>
                    <div class="flex items-center space-x-1.5">
                        <i class="fa-solid fa-location-dot text-slate-400 text-xs"></i>
                        <span>Gedung Admin PLTU Karangkandri Lt. 1</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
