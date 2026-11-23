@extends('layouts.app')

@section('title', 'Data Master - Koordinator IT PLTU Karangkandri')

@section('content')
<div class="space-y-5">

    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2.5">
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                    Data Master
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                    Master Data PLTU
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Kelola divisi, kategori kendala, area lokasi, dan data aset IT yang tersinkron ke tiket &amp; pemeliharaan
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            @include('partials.header-widget')
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @include('partials.flash-messages')

    @if($errors->any())
        <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-xs">
            <div class="font-bold flex items-center space-x-2 mb-1">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Terdapat kesalahan pada input Data Master:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-rose-700">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 4 Kartu Tab Navigasi Master Data (Rapi & Sejajar) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <button type="button" onclick="switchMasterTab('divisi')" data-tab-btn="divisi"
                class="master-tab-btn group text-left p-4 rounded-2xl border bg-white border-slate-200/80 hover:border-teal-300 transition shadow-2xs cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xs transition tab-icon">
                    <i class="fa-solid fa-building-user"></i>
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-slate-100 text-slate-700 tab-badge">
                    {{ $divisions->count() }}
                </span>
            </div>
            <div class="mt-3">
                <p class="text-xs font-extrabold text-slate-900">Divisi</p>
                <p class="text-[11px] text-slate-400 mt-0.5 truncate">Unit kerja pegawai</p>
            </div>
        </button>

        <button type="button" onclick="switchMasterTab('kategori')" data-tab-btn="kategori"
                class="master-tab-btn group text-left p-4 rounded-2xl border bg-white border-slate-200/80 hover:border-teal-300 transition shadow-2xs cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xs transition tab-icon">
                    <i class="fa-solid fa-tags"></i>
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-slate-100 text-slate-700 tab-badge">
                    {{ $categories->count() }}
                </span>
            </div>
            <div class="mt-3">
                <p class="text-xs font-extrabold text-slate-900">Kategori Kendala</p>
                <p class="text-[11px] text-slate-400 mt-0.5 truncate">Kategori tiket &amp; solusi cepat</p>
            </div>
        </button>

        <button type="button" onclick="switchMasterTab('lokasi')" data-tab-btn="lokasi"
                class="master-tab-btn group text-left p-4 rounded-2xl border bg-white border-slate-200/80 hover:border-teal-300 transition shadow-2xs cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xs transition tab-icon">
                    <i class="fa-solid fa-location-dot"></i>
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-slate-100 text-slate-700 tab-badge">
                    {{ $locations->count() }}
                </span>
            </div>
            <div class="mt-3">
                <p class="text-xs font-extrabold text-slate-900">Area Lokasi</p>
                <p class="text-[11px] text-slate-400 mt-0.5 truncate">Ruangan tiket &amp; PM</p>
            </div>
        </button>

        <button type="button" onclick="switchMasterTab('aset')" data-tab-btn="aset"
                class="master-tab-btn group text-left p-4 rounded-2xl border bg-white border-slate-200/80 hover:border-teal-300 transition shadow-2xs cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xs transition tab-icon">
                    <i class="fa-solid fa-server"></i>
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-slate-100 text-slate-700 tab-badge">
                    {{ $assets->count() }}
                </span>
            </div>
            <div class="mt-3">
                <p class="text-xs font-extrabold text-slate-900">Data Aset IT</p>
                <p class="text-[11px] text-slate-400 mt-0.5 truncate">Inventaris &amp; kode otomatis</p>
            </div>
        </button>
    </div>

    <!-- ============================================================ -->
    <!-- PANEL 1: DIVISI                                              -->
    <!-- ============================================================ -->
    <div id="panel-master-divisi" data-tab-panel="divisi" class="master-tab-panel grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        <!-- Form Tambah Divisi (4 Kolom) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 lg:sticky lg:top-6">
            <div class="pb-3 border-b border-slate-100">
                <h3 class="text-sm font-extrabold text-slate-900">Tambah Divisi Baru</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Unit kerja pegawai yang otomatis terisi saat membuat tiket.</p>
            </div>

            <form action="{{ route('admin.divisi.store') }}" method="POST" onsubmit="rememberMasterTab('divisi')" class="mt-4 space-y-3.5 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Nama Divisi <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama" required placeholder="Contoh: Operasi Boiler Unit 2"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition font-medium">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="keterangan" placeholder="Contoh: Pengendalian operasional boiler"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                </div>
                <button type="submit"
                        class="w-full py-2.5 px-4 bg-[#0f766e] hover:bg-[#115e59] text-white font-bold rounded-xl text-xs transition cursor-pointer shadow-xs">
                    + Tambah Divisi
                </button>
            </form>
        </div>

        <!-- Daftar Divisi (8 Kolom) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center space-x-2">
                        <h3 class="text-sm font-extrabold text-slate-900">Daftar Divisi</h3>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                            {{ $divisions->count() }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0.5">Kelola daftar unit kerja aktif di PLTU Karangkandri.</p>
                </div>

                <div class="relative w-full sm:w-60">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-slate-400"></i>
                    <input type="text" oninput="filterMasterRows('divisi', this.value)" placeholder="Cari divisi..."
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-2.5 px-2">Nama Divisi</th>
                            <th class="py-2.5 px-2">Pegawai</th>
                            <th class="py-2.5 px-2">Status</th>
                            <th class="py-2.5 px-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($divisions as $div)
                        <tr class="master-row-divisi hover:bg-slate-50/70 transition"
                            data-search="{{ mb_strtolower($div->nama . ' ' . ($div->keterangan ?? '')) }}">
                            <td class="py-3 px-2">
                                <div class="font-bold text-slate-800 {{ !$div->is_active ? 'line-through text-slate-400' : '' }}">{{ $div->nama }}</div>
                                @if(!empty($div->keterangan))
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ $div->keterangan }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-2 text-slate-600 whitespace-nowrap font-medium">
                                {{ $div->user_count ?? 0 }} pegawai
                            </td>
                            <td class="py-3 px-2 whitespace-nowrap">
                                @if($div->is_active)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80">Aktif</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200/80">Nonaktif</span>
                                @endif
                            </td>
                            <td class="py-3 px-2 text-right whitespace-nowrap">
                                <div class="inline-flex items-center space-x-3">
                                    <button type="button"
                                            onclick="openModalEditDivisi({{ json_encode($div, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }})"
                                            class="text-teal-700 hover:text-teal-900 font-semibold hover:underline cursor-pointer">
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.divisi.toggle', $div) }}" method="POST" onsubmit="rememberMasterTab('divisi')" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="font-semibold cursor-pointer {{ $div->is_active ? 'text-slate-400 hover:text-slate-600 hover:underline' : 'text-emerald-600 hover:text-emerald-700 font-bold hover:underline' }}">
                                            {{ $div->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400">Belum ada divisi terdaftar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- PANEL 2: KATEGORI KENDALA                                    -->
    <!-- ============================================================ -->
    <div id="panel-master-kategori" data-tab-panel="kategori" class="master-tab-panel hidden grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        <!-- Form Tambah Kategori (4 Kolom) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 lg:sticky lg:top-6">
            <div class="pb-3 border-b border-slate-100">
                <h3 class="text-sm font-extrabold text-slate-900">Tambah Kategori Kendala</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Dipilih pegawai saat membuat tiket lengkap dengan panduan solusi cepat.</p>
            </div>

            <form action="{{ route('admin.kategori.store') }}" method="POST" onsubmit="rememberMasterTab('kategori')" class="mt-4 space-y-3.5 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama" required placeholder="Contoh: Perangkat Audio / Telepon"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition font-medium">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Deskripsi / contoh kendala</label>
                    <input type="text" name="deskripsi" placeholder="Deskripsi / contoh kendala (tampil di Pegawai)"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block font-bold text-slate-700">Solusi cepat untuk Pegawai</label>
                        <span class="text-[10px] text-slate-400">1 baris = 1 poin</span>
                    </div>
                    <textarea name="solusi_cepat" rows="3" placeholder="Solusi cepat untuk Pegawai (opsional, 1 solusi per baris)"
                              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition resize-y leading-relaxed"></textarea>
                </div>
                <button type="submit"
                        class="w-full py-2.5 px-4 bg-[#0f766e] hover:bg-[#115e59] text-white font-bold rounded-xl text-xs transition cursor-pointer shadow-xs">
                    + Tambah Kategori
                </button>
            </form>
        </div>

        <!-- Daftar Kategori Kendala (8 Kolom) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center space-x-2">
                        <h3 class="text-sm font-extrabold text-slate-900">Daftar Kategori Kendala</h3>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                            {{ $categories->count() }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0.5">Tersinkronisasi otomatis ke halaman Buat Tiket Pegawai.</p>
                </div>

                <div class="relative w-full sm:w-60">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-slate-400"></i>
                    <input type="text" oninput="filterMasterRows('kategori', this.value)" placeholder="Cari kategori..."
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-2.5 px-2">Kategori &amp; Deskripsi</th>
                            <th class="py-2.5 px-2">Penggunaan</th>
                            <th class="py-2.5 px-2">Status</th>
                            <th class="py-2.5 px-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($categories as $kat)
                        @php
                            $katDescDisplay = trim((string) ($kat->deskripsi ?? ''));
                            if ($katDescDisplay === '' && !empty($kat->kode) && strcasecmp(trim($kat->kode), trim($kat->nama)) !== 0) {
                                $katDescDisplay = trim((string) $kat->kode);
                            }
                        @endphp
                        <tr class="master-row-kategori hover:bg-slate-50/70 transition"
                            data-search="{{ mb_strtolower($kat->nama . ' ' . $katDescDisplay . ' ' . ($kat->kode ?? '')) }}">
                            <td class="py-3 px-2">
                                <div class="font-bold text-slate-800 {{ !$kat->is_active ? 'line-through text-slate-400' : '' }}">{{ $kat->nama }}</div>
                                @if($katDescDisplay !== '')
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ $katDescDisplay }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-2 whitespace-nowrap">
                                <div class="text-slate-700 font-medium">{{ $kat->ticket_count ?? 0 }} tiket</div>
                                @if(!empty($kat->solusi_cepat))
                                    <div class="text-[11px] text-teal-700 font-semibold mt-0.5">{{ count($kat->solusi_cepat) }} solusi cepat</div>
                                @endif
                            </td>
                            <td class="py-3 px-2 whitespace-nowrap">
                                @if($kat->is_active)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80">Aktif</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200/80">Nonaktif</span>
                                @endif
                            </td>
                            <td class="py-3 px-2 text-right whitespace-nowrap">
                                <div class="inline-flex items-center space-x-3">
                                    <button type="button"
                                            onclick="openModalEditKategori({{ json_encode($kat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }})"
                                            class="text-teal-700 hover:text-teal-900 font-semibold hover:underline cursor-pointer">
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.kategori.toggle', $kat) }}" method="POST" onsubmit="rememberMasterTab('kategori')" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="font-semibold cursor-pointer {{ $kat->is_active ? 'text-slate-400 hover:text-slate-600 hover:underline' : 'text-emerald-600 hover:text-emerald-700 font-bold hover:underline' }}">
                                            {{ $kat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400">Belum ada kategori kendala terdaftar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- PANEL 3: AREA LOKASI                                         -->
    <!-- ============================================================ -->
    <div id="panel-master-lokasi" data-tab-panel="lokasi" class="master-tab-panel hidden grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        <!-- Form Tambah Area Lokasi (4 Kolom) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 lg:sticky lg:top-6">
            <div class="pb-3 border-b border-slate-100">
                <h3 class="text-sm font-extrabold text-slate-900">Tambah Area Lokasi</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Dipilih pegawai saat melapor tiket &amp; Admin saat penjadwalan pemeliharaan.</p>
            </div>

            <form action="{{ route('admin.lokasi.store') }}" method="POST" onsubmit="rememberMasterTab('lokasi')" class="mt-4 space-y-3.5 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Nama Area / Ruangan <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama" required placeholder="Contoh: Ruang Kontrol Turbin Lt. 2"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition font-medium">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Deskripsi Area <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="deskripsi" placeholder="Contoh: Gedung utama unit 1 & 2"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                </div>
                <button type="submit"
                        class="w-full py-2.5 px-4 bg-[#0f766e] hover:bg-[#115e59] text-white font-bold rounded-xl text-xs transition cursor-pointer shadow-xs">
                    + Tambah Lokasi
                </button>
            </form>
        </div>

        <!-- Daftar Area Lokasi (8 Kolom) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center space-x-2">
                        <h3 class="text-sm font-extrabold text-slate-900">Daftar Area Lokasi</h3>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                            {{ $locations->count() }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0.5">Mengubah nama lokasi otomatis memperbarui Data Aset IT &amp; Jadwal Rutin terkait.</p>
                </div>

                <div class="relative w-full sm:w-60">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-slate-400"></i>
                    <input type="text" oninput="filterMasterRows('lokasi', this.value)" placeholder="Cari area lokasi..."
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-2.5 px-2">Area Lokasi</th>
                            <th class="py-2.5 px-2">Penggunaan</th>
                            <th class="py-2.5 px-2">Status</th>
                            <th class="py-2.5 px-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($locations as $loc)
                        <tr class="master-row-lokasi hover:bg-slate-50/70 transition"
                            data-search="{{ mb_strtolower($loc->nama . ' ' . ($loc->deskripsi ?? '')) }}">
                            <td class="py-3 px-2">
                                <div class="font-bold text-slate-800 {{ !$loc->is_active ? 'line-through text-slate-400' : '' }}">{{ $loc->nama }}</div>
                                @if(!empty($loc->deskripsi))
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ $loc->deskripsi }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-2 whitespace-nowrap text-slate-600 font-medium">
                                {{ $loc->ticket_count ?? 0 }} tiket
                                @if(($loc->asset_count ?? 0) > 0)
                                    <span class="text-slate-300 mx-1">&bull;</span>
                                    <span class="text-teal-700 font-semibold">{{ $loc->asset_count }} aset</span>
                                @endif
                            </td>
                            <td class="py-3 px-2 whitespace-nowrap">
                                @if($loc->is_active)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80">Aktif</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200/80">Nonaktif</span>
                                @endif
                            </td>
                            <td class="py-3 px-2 text-right whitespace-nowrap">
                                <div class="inline-flex items-center space-x-3">
                                    <button type="button"
                                            onclick="openModalEditLokasi({{ json_encode($loc, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }})"
                                            class="text-teal-700 hover:text-teal-900 font-semibold hover:underline cursor-pointer">
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.lokasi.toggle', $loc) }}" method="POST" onsubmit="rememberMasterTab('lokasi')" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="font-semibold cursor-pointer {{ $loc->is_active ? 'text-slate-400 hover:text-slate-600 hover:underline' : 'text-emerald-600 hover:text-emerald-700 font-bold hover:underline' }}">
                                            {{ $loc->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400">Belum ada area lokasi terdaftar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- PANEL 4: DATA ASET IT (SINKRON KE PM + KODE OTOMATIS)        -->
    <!-- ============================================================ -->
    <div id="master-aset-section" data-tab-panel="aset" class="master-tab-panel hidden grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        <!-- Form Tambah Aset IT (4 Kolom — Vertikal & Lega) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 lg:sticky lg:top-6">
            <div class="pb-3 border-b border-slate-100">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-sm font-extrabold text-slate-900">Tambah Aset IT Baru</h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                        Kode Otomatis
                    </span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Otomatis muncul di dropdown Aset pada form + Tambah jadwal.</p>
            </div>

            <form action="{{ route('admin.aset.store') }}" method="POST" onsubmit="rememberMasterTab('aset')" class="mt-4 space-y-3.5 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Nama Aset <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama" id="addAsetNama" required
                           oninput="autoGenerateAssetKode(true)"
                           placeholder="Contoh: Kabel LAN, Rack Server Backup"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition font-medium">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block font-bold text-slate-700">Kode Aset</label>
                        <span class="text-[10px] font-semibold text-teal-700">Otomatis dari nama &amp; lokasi</span>
                    </div>
                    <div class="relative">
                        <input type="text" name="kode_perangkat" id="addAsetKode"
                               oninput="onManualAssetKodeInput(this)"
                               placeholder="Otomatis terisi saat mengetik nama..."
                               class="w-full pl-3.5 pr-9 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono uppercase placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition font-bold text-teal-800">
                        <button type="button" onclick="resetAutoAssetKode()" title="Generate ulang kode otomatis"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-teal-700 transition cursor-pointer p-1">
                            <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Area Lokasi</label>
                    <select name="lokasi_ruangan" id="addAsetLokasi" onchange="autoGenerateAssetKode(false)"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                        <option value="">-- Pilih Lokasi dari Data Master --</option>
                        @foreach($activeLocations as $aloc)
                            <option value="{{ $aloc->nama }}">{{ $aloc->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Jenis Perangkat</label>
                    <select name="jenis_perangkat" id="addAsetJenis"
                            onchange="this.dataset.manual='1'; autoGenerateAssetKode(false)"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                        <option value="Server / UPS">Server / UPS</option>
                        <option value="Komputer PC">Komputer PC</option>
                        <option value="Kabel LAN / Jaringan">Kabel LAN / Jaringan</option>
                        <option value="Printer">Printer</option>
                        <option value="CCTV">CCTV</option>
                        <option value="Perangkat IT">Perangkat IT</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1.5">Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="keterangan" placeholder="Spesifikasi atau catatan singkat aset"
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                </div>

                <button type="submit"
                        class="w-full py-2.5 px-4 bg-[#0f766e] hover:bg-[#115e59] text-white font-bold rounded-xl text-xs transition cursor-pointer shadow-xs">
                    + Tambah Aset IT
                </button>
            </form>
        </div>

        <!-- Daftar Data Aset IT (8 Kolom) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center space-x-2">
                        <h3 class="text-sm font-extrabold text-slate-900">Data Aset IT</h3>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                            {{ $assets->count() }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0.5">Inventaris perangkat PLTU Karangkandri yang tersinkron ke Jadwal Pemeliharaan.</p>
                </div>

                <div class="relative w-full sm:w-64">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-slate-400"></i>
                    <input type="text" id="searchMasterAset" oninput="filterMasterRows('aset', this.value)"
                           placeholder="Cari nama, kode, atau lokasi..."
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-2.5 px-2">Aset &amp; Jenis</th>
                            <th class="py-2.5 px-2">Kode</th>
                            <th class="py-2.5 px-2">Area Lokasi</th>
                            <th class="py-2.5 px-2">PM</th>
                            <th class="py-2.5 px-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="masterAsetTableBody" class="divide-y divide-slate-100">
                        @forelse($assets as $asset)
                        <tr class="master-row-aset hover:bg-slate-50/70 transition"
                            data-search="{{ mb_strtolower($asset->nama . ' ' . $asset->kode_perangkat . ' ' . ($asset->lokasi_ruangan ?? '') . ' ' . ($asset->jenis_perangkat ?? '')) }}">
                            <td class="py-3 px-2">
                                <div class="flex items-center flex-wrap gap-1.5">
                                    <span class="font-bold text-slate-800 {{ !$asset->is_active ? 'line-through text-slate-400' : '' }}">{{ $asset->nama }}</span>
                                    @if(!$asset->is_active)
                                        <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-rose-50 text-rose-600 border border-rose-200">Nonaktif</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $asset->jenis_perangkat ?: 'Perangkat IT' }}
                                    @if(!empty($asset->keterangan))
                                        <span class="mx-1">&bull;</span>{{ $asset->keterangan }}
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-2 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-mono text-[11px] font-bold border border-slate-200/80">
                                    {{ $asset->kode_perangkat }}
                                </span>
                            </td>
                            <td class="py-3 px-2 text-slate-700 font-medium">
                                {{ $asset->lokasi_ruangan ?: '-' }}
                            </td>
                            <td class="py-3 px-2 text-[11px] text-slate-400 whitespace-nowrap">
                                {{ $asset->schedule_count ?? 0 }} jadwal &bull; {{ $asset->log_count ?? 0 }} log
                            </td>
                            <td class="py-3 px-2 text-right whitespace-nowrap">
                                <div class="inline-flex items-center space-x-2.5">
                                    <button type="button"
                                            onclick="openModalEditAset({{ json_encode($asset, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }})"
                                            class="text-teal-700 hover:text-teal-900 font-semibold hover:underline cursor-pointer">
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.aset.toggle', $asset) }}" method="POST" onsubmit="rememberMasterTab('aset')" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="font-semibold cursor-pointer {{ $asset->is_active ? 'text-slate-400 hover:text-slate-600 hover:underline' : 'text-emerald-600 hover:text-emerald-700 font-bold hover:underline' }}">
                                            {{ $asset->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.aset.destroy', $asset) }}" method="POST" onsubmit="rememberMasterTab('aset'); return confirm('Hapus permanen aset {{ addslashes($asset->nama) }} ({{ $asset->kode_perangkat }}) dari Data Master?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-rose-600 hover:text-rose-700 font-semibold hover:underline cursor-pointer">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">Belum ada data aset IT terdaftar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Catatan Kebijakan Data Master -->
    <div class="pt-1">
        <p class="text-[11px] text-slate-400 font-medium">
            Item yang sudah dipakai tiket atau jadwal pemeliharaan disarankan dinonaktifkan saja agar riwayat lama tetap utuh.
        </p>
    </div>

</div>

<!-- ==================================================== -->
<!-- MODAL: EDIT DIVISI                                   -->
<!-- ==================================================== -->
<div id="modalEditDivisi" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-slate-950/50 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
            <h3 class="text-sm font-extrabold text-slate-900">Edit Data Divisi</h3>
            <button type="button" onclick="closeModalEditDivisi()" class="text-slate-400 hover:text-slate-700 text-xs p-1 cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="formEditDivisi" method="POST" onsubmit="rememberMasterTab('divisi')" class="p-5 space-y-4 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="block font-bold text-slate-800 mb-1">Nama Divisi <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" id="editDivisiNama" required
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-xs font-medium">
            </div>

            <div>
                <label class="block font-bold text-slate-800 mb-1">Keterangan / Fungsi Kerja</label>
                <input type="text" name="keterangan" id="editDivisiKeterangan"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-xs font-medium">
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <button type="button" onclick="hapusItemAktif('formEditDivisi', 'divisi')" class="text-rose-600 hover:text-rose-700 text-xs font-semibold cursor-pointer">
                    <i class="fa-solid fa-trash-can mr-1"></i> Hapus Permanen
                </button>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="closeModalEditDivisi()" class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white font-bold transition shadow-xs cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL: EDIT KATEGORI                                 -->
<!-- ==================================================== -->
<div id="modalEditKategori" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-slate-950/50 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
            <h3 class="text-sm font-extrabold text-slate-900">Edit Kategori Kendala</h3>
            <button type="button" onclick="closeModalEditKategori()" class="text-slate-400 hover:text-slate-700 text-xs p-1 cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="formEditKategori" method="POST" onsubmit="rememberMasterTab('kategori')" class="p-5 space-y-4 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="block font-bold text-slate-800 mb-1">Nama Kategori <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" id="editKategoriNama" required
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-xs font-medium">
            </div>

            <div>
                <label class="block font-bold text-slate-800 mb-1">Kode / Tag Ringkas</label>
                <input type="text" name="kode" id="editKategoriKode" placeholder="Opsional, misal: Hardware, Jaringan"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-xs font-medium">
            </div>

            <div>
                <label class="block font-bold text-slate-800 mb-1">Deskripsi / Contoh Kendala</label>
                <input type="text" name="deskripsi" id="editKategoriDeskripsi" placeholder="Contoh: PC Desktop, Monitor, Laptop, RAM, PSU"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-xs font-medium">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block font-bold text-slate-800">Solusi Cepat (Panel Kanan Pegawai)</label>
                    <span class="text-[10px] text-slate-400 font-medium">1 baris = 1 poin solusi</span>
                </div>
                <textarea name="solusi_cepat" id="editKategoriSolusi" rows="4"
                          placeholder="Contoh:&#10;Periksa kabel power dan stopkontak&#10;Restart perangkat terlebih dahulu"
                          class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-xs leading-relaxed resize-y"></textarea>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <button type="button" onclick="hapusItemAktif('formEditKategori', 'kategori')" class="text-rose-600 hover:text-rose-700 text-xs font-semibold cursor-pointer">
                    <i class="fa-solid fa-trash-can mr-1"></i> Hapus Permanen
                </button>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="closeModalEditKategori()" class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white font-bold transition shadow-xs cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL: EDIT AREA LOKASI                              -->
<!-- ==================================================== -->
<div id="modalEditLokasi" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-slate-950/50 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
            <h3 class="text-sm font-extrabold text-slate-900">Edit Area Lokasi</h3>
            <button type="button" onclick="closeModalEditLokasi()" class="text-slate-400 hover:text-slate-700 text-xs p-1 cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="formEditLokasi" method="POST" onsubmit="rememberMasterTab('lokasi')" class="p-5 space-y-4 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="block font-bold text-slate-800 mb-1">Nama Area Lokasi <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" id="editLokasiNama" required
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-xs font-medium">
            </div>

            <div>
                <label class="block font-bold text-slate-800 mb-1">Deskripsi Tambahan</label>
                <input type="text" name="deskripsi" id="editLokasiDeskripsi" placeholder="misal: Ruang kontrol pembangkit Lt. 2"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-xs font-medium">
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <button type="button" onclick="hapusItemAktif('formEditLokasi', 'lokasi')" class="text-rose-600 hover:text-rose-700 text-xs font-semibold cursor-pointer">
                    <i class="fa-solid fa-trash-can mr-1"></i> Hapus Permanen
                </button>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="closeModalEditLokasi()" class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white font-bold transition shadow-xs cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL: EDIT DATA ASET IT                             -->
<!-- ==================================================== -->
<div id="modalEditAset" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-slate-950/50 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
            <h3 class="text-sm font-extrabold text-slate-900">Edit Data Aset IT</h3>
            <button type="button" onclick="closeModalEditAset()" class="text-slate-400 hover:text-slate-700 text-xs p-1 cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="formEditAset" method="POST" onsubmit="rememberMasterTab('aset')" class="p-5 space-y-4 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="block font-bold text-slate-800 mb-1">Nama Aset <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" id="editAsetNama" required
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-xs font-medium">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-800 mb-1">Kode Aset <span class="text-rose-500">*</span></label>
                    <input type="text" name="kode_perangkat" id="editAsetKode" required
                           class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono uppercase focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-xs font-semibold">
                </div>
                <div>
                    <label class="block font-bold text-slate-800 mb-1">Jenis Perangkat</label>
                    <select name="jenis_perangkat" id="editAsetJenis"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-xs font-medium">
                        <option value="Server / UPS">Server / UPS</option>
                        <option value="Komputer PC">Komputer PC</option>
                        <option value="PC Desktop">PC Desktop</option>
                        <option value="Kabel LAN / Jaringan">Kabel LAN / Jaringan</option>
                        <option value="Printer">Printer</option>
                        <option value="CCTV">CCTV</option>
                        <option value="Rakit PC Baru">Rakit PC Baru</option>
                        <option value="Perangkat IT">Perangkat IT</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-800 mb-1">Area Lokasi</label>
                <select name="lokasi_ruangan" id="editAsetLokasi"
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-xs font-medium">
                    <option value="">-- Pilih Area Lokasi --</option>
                    @foreach($activeLocations as $aloc)
                        <option value="{{ $aloc->nama }}">{{ $aloc->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-800 mb-1">Keterangan</label>
                <input type="text" name="keterangan" id="editAsetKeterangan" placeholder="Opsional: spesifikasi atau fungsi aset"
                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-xs font-medium">
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <button type="button" onclick="hapusItemAktif('formEditAset', 'aset')" class="text-rose-600 hover:text-rose-700 text-xs font-semibold cursor-pointer">
                    <i class="fa-solid fa-trash-can mr-1"></i> Hapus Permanen
                </button>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="closeModalEditAset()" class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white font-bold transition shadow-xs cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Hidden Form for Delete Action -->
<form id="formHapusItem" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
<script>
    const existingAssetCodes = @json($existingAssetCodes ?? []);

    function rememberMasterTab(tabName) {
        try {
            sessionStorage.setItem('admin_master_active_tab', tabName);
        } catch (e) {}
    }

    function switchMasterTab(tabName) {
        const validTabs = ['divisi', 'kategori', 'lokasi', 'aset'];
        const target = validTabs.includes(tabName) ? tabName : 'divisi';

        document.querySelectorAll('.master-tab-panel').forEach(panel => {
            panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== target);
        });

        document.querySelectorAll('.master-tab-btn').forEach(btn => {
            const isActive = btn.getAttribute('data-tab-btn') === target;
            const iconEl = btn.querySelector('.tab-icon');
            const badgeEl = btn.querySelector('.tab-badge');

            if (isActive) {
                btn.className = 'master-tab-btn group text-left p-4 rounded-2xl border-2 bg-teal-50/40 border-[#0f766e] transition shadow-xs cursor-pointer';
                if (iconEl) iconEl.className = 'w-8 h-8 rounded-xl bg-[#0f766e] text-white flex items-center justify-center text-xs transition tab-icon shadow-2xs';
                if (badgeEl) badgeEl.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-[#0f766e] text-white tab-badge';
            } else {
                btn.className = 'master-tab-btn group text-left p-4 rounded-2xl border bg-white border-slate-200/80 hover:border-teal-300 transition shadow-2xs cursor-pointer';
                if (iconEl) iconEl.className = 'w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xs transition tab-icon';
                if (badgeEl) badgeEl.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-slate-100 text-slate-700 tab-badge';
            }
        });

        rememberMasterTab(target);
    }

    function filterMasterRows(group, keyword) {
        const q = (keyword || '').trim().toLowerCase();
        document.querySelectorAll('.master-row-' + group).forEach(function(row) {
            const haystack = row.getAttribute('data-search') || '';
            row.style.display = (!q || haystack.includes(q)) ? '' : 'none';
        });
    }

    function computeAutoAssetKode(nama, lokasi, jenis) {
        const cleanNama = (nama || '').trim();
        if (!cleanNama) return '';

        const haystack = (cleanNama + ' ' + (jenis || '')).toLowerCase();
        let typePrefix = 'AST';
        if (haystack.includes('ups') || (haystack.includes('rack') && haystack.includes('server'))) {
            typePrefix = 'UPS';
        } else if (haystack.includes('nvr') && haystack.includes('server')) {
            typePrefix = 'SRV';
        } else if (haystack.includes('cctv') || haystack.includes('kamera') || haystack.includes('nvr')) {
            typePrefix = 'CCTV';
        } else if (haystack.includes('switch')) {
            typePrefix = 'SW';
        } else if (haystack.includes('access point') || haystack.includes('wifi') || /\bap\b/.test(haystack)) {
            typePrefix = 'AP';
        } else if (haystack.includes('router') || haystack.includes('mikrotik') || haystack.includes('gateway')) {
            typePrefix = 'RTR';
        } else if (haystack.includes('lan') || haystack.includes('kabel') || haystack.includes('jaringan') || haystack.includes('utp')) {
            typePrefix = 'LAN';
        } else if (haystack.includes('printer') || haystack.includes('scanner') || haystack.includes('laserjet')) {
            typePrefix = 'PRN';
        } else if (haystack.includes('server') || haystack.includes('database') || haystack.includes('historian')) {
            typePrefix = 'SRV';
        } else if (haystack.includes('laptop') || haystack.includes('notebook')) {
            typePrefix = 'LPT';
        } else if (haystack.includes('pc') || haystack.includes('komputer') || haystack.includes('workstation') || haystack.includes('desktop') || haystack.includes('monitor')) {
            typePrefix = 'PC';
        } else if (haystack.includes('genset') || haystack.includes('inverter') || haystack.includes('power')) {
            typePrefix = 'PWR';
        } else {
            const alpha = cleanNama.replace(/[^A-Za-z]/g, '').toUpperCase();
            typePrefix = (alpha || 'AST').substring(0, 3);
        }

        const locLower = (lokasi || '').trim().toLowerCase();
        let locCode = 'IT';
        if (!locLower) {
            locCode = 'IT';
        } else if (locLower.includes('boiler')) {
            locCode = 'BLR';
        } else if (locLower.includes('turbin')) {
            locCode = 'TRB';
        } else if (locLower.includes('coal handling')) {
            locCode = 'CH';
        } else if (locLower.includes('timbangan') || locLower.includes('coal yard')) {
            locCode = 'WB';
        } else if (locLower.includes('switchyard')) {
            locCode = 'SWY';
        } else if (locLower.includes('control room') || locLower.includes('ccr')) {
            locCode = 'CR';
        } else if (locLower.includes('server')) {
            locCode = 'SRV';
        } else if (locLower.includes('jetty')) {
            locCode = 'JTY';
        } else if (locLower.includes('security') || locLower.includes('k3')) {
            locCode = 'SEC';
        } else if (locLower.includes('admin') || locLower.includes('finance') || locLower.includes('hrd')) {
            locCode = 'ADM';
        } else if (locLower.includes('gudang') || locLower.includes('logistik') || locLower.includes('material')) {
            locCode = 'LOG';
        } else if (locLower.includes('maintenance') || locLower.includes('workshop')) {
            locCode = 'MNT';
        } else {
            const words = locLower.replace(/[^a-z0-9\s]/g, '').split(/\s+/).filter(Boolean);
            if (words.length >= 2) {
                locCode = (words[0].substring(0, 1) + words[1].substring(0, 2)).toUpperCase();
            } else {
                locCode = (words[0] || 'IT').substring(0, 3).toUpperCase();
            }
        }

        const basePrefix = `${typePrefix}-${locCode}`;
        const upperExisting = existingAssetCodes.map(c => String(c).toUpperCase());
        let maxNum = 0;
        upperExisting.forEach(code => {
            if (code.startsWith(basePrefix + '-')) {
                const match = code.match(/-(\d+)$/);
                if (match) {
                    maxNum = Math.max(maxNum, parseInt(match[1], 10));
                }
            }
        });

        let nextNum = maxNum + 1;
        let candidate = `${basePrefix}-${String(nextNum).padStart(2, '0')}`;
        while (upperExisting.includes(candidate)) {
            nextNum++;
            candidate = `${basePrefix}-${String(nextNum).padStart(2, '0')}`;
        }
        return candidate;
    }

    function autoGenerateAssetKode(detectJenis = false) {
        const namaInp = document.getElementById('addAsetNama');
        const kodeInp = document.getElementById('addAsetKode');
        const lokasiSel = document.getElementById('addAsetLokasi');
        const jenisSel = document.getElementById('addAsetJenis');
        if (!namaInp || !kodeInp) return;

        const nama = namaInp.value.trim();

        if (detectJenis && jenisSel && jenisSel.dataset.manual !== '1' && nama) {
            const n = nama.toLowerCase();
            if (n.includes('cctv') || n.includes('nvr') || n.includes('kamera')) {
                jenisSel.value = 'CCTV';
            } else if (n.includes('printer') || n.includes('scanner') || n.includes('laserjet')) {
                jenisSel.value = 'Printer';
            } else if (n.includes('lan') || n.includes('kabel') || n.includes('switch') || n.includes('wifi') || n.includes('access point') || n.includes('router') || n.includes('jaringan')) {
                jenisSel.value = 'Kabel LAN / Jaringan';
            } else if (n.includes('pc') || n.includes('komputer') || n.includes('workstation') || n.includes('desktop') || n.includes('laptop')) {
                jenisSel.value = 'Komputer PC';
            } else if (n.includes('server') || n.includes('ups') || n.includes('rack') || n.includes('database')) {
                jenisSel.value = 'Server / UPS';
            }
        }

        if (kodeInp.dataset.manual !== '1') {
            kodeInp.value = computeAutoAssetKode(
                nama,
                lokasiSel ? lokasiSel.value : '',
                jenisSel ? jenisSel.value : ''
            );
        }
    }

    function onManualAssetKodeInput(inp) {
        inp.value = inp.value.toUpperCase();
        if (inp.value.trim() === '') {
            inp.dataset.manual = '';
            autoGenerateAssetKode(false);
        } else {
            inp.dataset.manual = '1';
        }
    }

    function resetAutoAssetKode() {
        const kodeInp = document.getElementById('addAsetKode');
        if (!kodeInp) return;
        kodeInp.dataset.manual = '';
        autoGenerateAssetKode(true);
    }

    function openModalEditDivisi(div) {
        document.getElementById('formEditDivisi').action = '/admin/divisi/' + div.id;
        document.getElementById('editDivisiNama').value = div.nama || '';
        document.getElementById('editDivisiKeterangan').value = div.keterangan || '';
        document.getElementById('modalEditDivisi').classList.remove('hidden');
    }
    function closeModalEditDivisi() {
        document.getElementById('modalEditDivisi').classList.add('hidden');
    }

    function openModalEditKategori(kat) {
        document.getElementById('formEditKategori').action = '/admin/kategori/' + kat.id;
        document.getElementById('editKategoriNama').value = kat.nama || '';
        document.getElementById('editKategoriKode').value = kat.kode || '';
        document.getElementById('editKategoriDeskripsi').value = kat.deskripsi || '';
        const solusiList = Array.isArray(kat.solusi_cepat) ? kat.solusi_cepat : [];
        document.getElementById('editKategoriSolusi').value = solusiList.join('\n');
        document.getElementById('modalEditKategori').classList.remove('hidden');
    }
    function closeModalEditKategori() {
        document.getElementById('modalEditKategori').classList.add('hidden');
    }

    function openModalEditLokasi(loc) {
        document.getElementById('formEditLokasi').action = '/admin/lokasi/' + loc.id;
        document.getElementById('editLokasiNama').value = loc.nama || '';
        document.getElementById('editLokasiDeskripsi').value = loc.deskripsi || '';
        document.getElementById('modalEditLokasi').classList.remove('hidden');
    }
    function closeModalEditLokasi() {
        document.getElementById('modalEditLokasi').classList.add('hidden');
    }

    function openModalEditAset(asset) {
        document.getElementById('formEditAset').action = '/admin/aset/' + asset.id;
        document.getElementById('editAsetNama').value = asset.nama || '';
        document.getElementById('editAsetKode').value = asset.kode_perangkat || '';
        document.getElementById('editAsetKeterangan').value = asset.keterangan || '';

        const jenisSel = document.getElementById('editAsetJenis');
        const jenisVal = asset.jenis_perangkat || 'Perangkat IT';
        if (jenisSel) {
            let foundJenis = Array.from(jenisSel.options).some(o => o.value === jenisVal);
            if (!foundJenis) {
                const o = document.createElement('option');
                o.value = jenisVal;
                o.text = jenisVal;
                jenisSel.appendChild(o);
            }
            jenisSel.value = jenisVal;
        }

        const lokSel = document.getElementById('editAsetLokasi');
        const lokVal = asset.lokasi_ruangan || '';
        if (lokSel) {
            if (lokVal && !Array.from(lokSel.options).some(o => o.value === lokVal)) {
                const o = document.createElement('option');
                o.value = lokVal;
                o.text = lokVal;
                lokSel.appendChild(o);
            }
            lokSel.value = lokVal;
        }

        document.getElementById('modalEditAset').classList.remove('hidden');
    }
    function closeModalEditAset() {
        document.getElementById('modalEditAset').classList.add('hidden');
    }

    function hapusItemAktif(formId, tabName) {
        if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) return;
        if (tabName) rememberMasterTab(tabName);
        const targetForm = document.getElementById(formId);
        const deleteForm = document.getElementById('formHapusItem');
        deleteForm.action = targetForm.action;
        deleteForm.submit();
    }

    window.addEventListener('click', function(e) {
        ['modalEditDivisi', 'modalEditKategori', 'modalEditLokasi', 'modalEditAset'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el && e.target === el) {
                el.classList.add('hidden');
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const params = new URLSearchParams(window.location.search);
        const queryTab = params.get('tab');
        const hash = (window.location.hash || '').toLowerCase();
        let initialTab = 'divisi';

        if (queryTab && ['divisi', 'kategori', 'lokasi', 'aset'].includes(queryTab)) {
            initialTab = queryTab;
        } else if (hash.includes('aset')) {
            initialTab = 'aset';
        } else if (hash.includes('lokasi')) {
            initialTab = 'lokasi';
        } else if (hash.includes('kategori')) {
            initialTab = 'kategori';
        } else if (hash.includes('divisi')) {
            initialTab = 'divisi';
        } else {
            try {
                const saved = sessionStorage.getItem('admin_master_active_tab');
                if (saved && ['divisi', 'kategori', 'lokasi', 'aset'].includes(saved)) {
                    initialTab = saved;
                }
            } catch (e) {}
        }

        switchMasterTab(initialTab);
    });
</script>
@endpush
@endsection
