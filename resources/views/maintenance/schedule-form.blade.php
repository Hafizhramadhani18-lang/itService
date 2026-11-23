@extends('layouts.app')

@section('content')
@php
    $isEdit = isset($schedule) && $schedule !== null;

    // Mapping label frekuensi DB -> nama pill UI
    $mapFreqToPill = function (?string $f): string {
        $fl = strtolower(trim((string) $f));
        if ($fl === 'sekali') return 'Sekali';
        if (str_contains($fl, 'minggu') && !preg_match('/[2-9]/', $fl)) return 'Mingguan';
        if ($fl === 'setiap bulan' || $fl === 'bulanan') return 'Bulanan';
        if (str_contains($fl, '3 bulan') || str_contains($fl, 'tiga bulan')) return 'Tiga bulanan';
        if ($fl === '') return 'Bulanan';
        return 'Atur sendiri';
    };

    $initialPillFreq = $isEdit ? $mapFreqToPill($schedule->frekuensi) : 'Bulanan';
    $initialTim = $isEdit ? ($schedule->tim ?: 'Tim IT Lapangan') : 'Tim IT Lapangan';
    $initialJam = $isEdit && $schedule->jam_mulai
        ? str_replace(':', '.', substr($schedule->jam_mulai, 0, 5))
        : '09.00';
    $initialTanggalNext = $isEdit && $schedule->tanggal_berikutnya
        ? $schedule->tanggal_berikutnya->format('Y-m-d')
        : now()->addMonth()->format('Y-m-d');
    $initialTanggalNextDmy = $isEdit && $schedule->tanggal_berikutnya
        ? $schedule->tanggal_berikutnya->format('d/m/Y')
        : now()->addMonth()->format('d/m/Y');
    $initialCatatan = $isEdit ? (string) ($schedule->catatan_teknisi ?? '') : '';
    $initialTeknisiId = $isEdit ? ($schedule->teknisi_id ?? '') : '';
    $initialKirimPengingat = $isEdit ? (bool) ($schedule->kirim_pengingat ?? true) : true;

    $startedLabel = $isEdit && $schedule->created_at
        ? $schedule->created_at->locale('id')->isoFormat('DD MMMM Y') . ' (sudah berjalan)'
        : '';

    $jamOptions = ['07.00', '07.30', '08.00', '08.30', '09.00', '09.30', '10.00', '10.30', '11.00', '13.00', '13.30', '14.00', '14.30', '15.00', '15.30', '16.00'];
    if (!in_array($initialJam, $jamOptions)) {
        array_unshift($jamOptions, $initialJam);
    }
@endphp

<div class="space-y-6">

    <!-- Flash Error Notification -->
    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-xs animate-fade-in">
            <div class="font-bold flex items-center space-x-2 mb-1">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Terdapat kesalahan pada input jadwal rutin:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-rose-700">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Top Header Sesuai Mockup Referensi -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                {{ $isEdit ? 'Ubah Jadwal Rutin' : 'Tambah Jadwal Rutin' }}
            </h1>
            <nav class="flex flex-wrap items-center space-x-1.5 text-xs text-slate-400 mt-1">
                <a href="{{ route('maintenance.index') }}" class="hover:text-teal-700 transition font-medium">Log Pemeliharaan</a>
                <span>&rsaquo;</span>
                <a href="{{ route('maintenance.index') }}#jadwal-rutin-section" class="hover:text-teal-700 transition font-medium">Jadwal rutin</a>
                <span>&rsaquo;</span>
                <span class="text-slate-600 font-semibold">{{ $isEdit ? $schedule->nama_aset : 'Jadwal baru' }}</span>
            </nav>
        </div>

        <div class="flex items-center space-x-2.5">
            <a href="{{ route('maintenance.index') }}"
               class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs">
                Kembali
            </a>

            @include('partials.header-widget')
        </div>
    </div>

    <!-- Form Utama 2 Kolom -->
    <form method="POST"
          action="{{ $isEdit ? route('maintenance.schedules.update', $schedule) : route('maintenance.schedules.store') }}"
          id="form-jadwal-rutin-page">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" name="nama_aset" id="hidden_nama_aset" value="{{ old('nama_aset', $isEdit ? $schedule->nama_aset : '') }}">
        <input type="hidden" name="kode_perangkat" id="hidden_kode_aset" value="{{ old('kode_perangkat', $isEdit ? $schedule->kode_perangkat : '') }}">
        <input type="hidden" name="lokasi_ruangan" id="hidden_lokasi_aset" value="{{ old('lokasi_ruangan', $isEdit ? $schedule->lokasi_ruangan : '') }}">
        <input type="hidden" name="frekuensi" id="hidden_frekuensi" value="{{ old('frekuensi', $initialPillFreq) }}">
        <input type="hidden" name="tim" id="hidden_tim" value="{{ old('tim', $initialTim) }}">
        <input type="hidden" name="berlaku_untuk" id="hidden_berlaku_untuk" value="berikutnya_saja">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <!-- KOLOM KIRI (2 SPAN) -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-7 space-y-7">

                    <!-- SECTION 1: ASET -->
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Aset</h2>
                            <span class="text-[11px] font-medium text-slate-400">
                                Sumber data: <a href="{{ route('admin.master') }}#master-aset-section" class="text-[#0f766e] hover:underline font-semibold">Data Master Admin (Aset IT &amp; Area Lokasi)</a>
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Aset</label>
                            <select id="select_aset" onchange="onAssetSelected(this)" required
                                    class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                                @foreach($assetList as $idx => $asset)
                                    @php
                                        $isSel = $isEdit && mb_strtolower($asset['nama']) === mb_strtolower($schedule->nama_aset);
                                    @endphp
                                    <option value="{{ $idx }}" {{ $isSel ? 'selected' : '' }}
                                            data-nama="{{ $asset['nama'] }}"
                                            data-kode="{{ $asset['kode'] }}"
                                            data-lokasi="{{ $asset['lokasi'] }}"
                                            data-kegiatan='@json($asset['kegiatan'])'>
                                        {{ $asset['nama'] }} &nbsp;&nbsp;&nbsp; ({{ $asset['kode'] }})
                                    </option>
                                @endforeach
                                <option value="__custom__">+ Tambah aset baru (ketik manual...)</option>
                            </select>

                            <!-- Input Manual Aset Baru -->
                            <div id="custom_asset_fields" class="hidden mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Aset Baru <span class="text-rose-500">*</span></label>
                                    <input type="text" id="custom_nama_aset" oninput="syncCustomAsset()" placeholder="Contoh: Rack Server Backup"
                                           class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Kode Aset</label>
                                    <input type="text" id="custom_kode_aset" oninput="syncCustomAsset()" placeholder="Contoh: UPS-SRV-02"
                                           class="w-full text-xs font-mono font-semibold uppercase rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                                </div>
                            </div>
                        </div>

                        <!-- Lokasi (Dropdown seluruh lokasi proyek, auto-terpilih saat memilih aset) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Lokasi</label>

                            <div class="space-y-2">
                                <select id="select_lokasi" onchange="onLocationChange(this)" required
                                        class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                                    <option value="">-- Pilih Lokasi Ruangan --</option>
                                    @foreach($existingLocations as $locName)
                                        <option value="{{ $locName }}" {{ old('lokasi_ruangan', $isEdit ? $schedule->lokasi_ruangan : '') === $locName ? 'selected' : '' }}>
                                            {{ $locName }}
                                        </option>
                                    @endforeach
                                    <option value="__custom_loc__">+ Lokasi lainnya (ketik manual...)</option>
                                </select>
                                <input type="text" id="input_lokasi_custom" oninput="document.getElementById('hidden_lokasi_aset').value = this.value; updateSidebarLive();"
                                       placeholder="Ketik nama ruangan baru..."
                                       class="hidden w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: KEGIATAN -->
                    <div class="space-y-3 pt-1">
                        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Kegiatan</h2>

                        <div id="kegiatan_list_container" class="divide-y divide-slate-100">
                            <!-- Diisi otomatis via JS -->
                        </div>

                        <div id="inline_add_kegiatan_box" class="hidden flex items-center gap-2 pt-2">
                            <input type="text" id="inline_new_kegiatan_input"
                                   placeholder="Tulis kegiatan pemeliharaan rutin..."
                                   onkeydown="if(event.key === 'Enter'){ event.preventDefault(); confirmAddKegiatan(); }"
                                   class="flex-1 text-xs rounded-xl border border-teal-300 bg-white px-3 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                            <button type="button" onclick="confirmAddKegiatan()"
                                    class="px-3.5 py-2 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white text-xs font-bold transition cursor-pointer">
                                Tambah
                            </button>
                            <button type="button" onclick="cancelAddKegiatan()"
                                    class="px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500 text-xs font-semibold transition cursor-pointer">
                                Batal
                            </button>
                        </div>

                        <div class="pt-1">
                            <button type="button" onclick="showAddKegiatanBox()"
                                    class="inline-flex items-center space-x-1 text-xs font-bold text-[#0f766e] hover:text-[#115e59] transition cursor-pointer">
                                <span>+ Tambah kegiatan</span>
                            </button>
                        </div>
                    </div>

                    <!-- SECTION 3: JADWAL -->
                    <div class="space-y-4 pt-1">
                        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Jadwal</h2>

                        <!-- Frekuensi Pills -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">Frekuensi</label>
                            <div class="flex flex-wrap items-center gap-2">
                                @foreach(['Sekali', 'Mingguan', 'Bulanan', 'Tiga bulanan', 'Atur sendiri'] as $freq)
                                    @php $isDef = $freq === old('frekuensi', $initialPillFreq); @endphp
                                    <button type="button"
                                            onclick="setFrekuensi('{{ $freq }}')"
                                            data-freq="{{ $freq }}"
                                            class="freq-pill px-4 py-1.5 rounded-full text-xs transition cursor-pointer {{ $isDef ? 'bg-[#0e1a2b] text-white font-bold shadow-2xs' : 'bg-slate-100 text-slate-600 font-semibold hover:bg-slate-200/80' }}">
                                        {{ $freq }}
                                    </button>
                                @endforeach
                            </div>

                            <div id="custom_freq_box" class="{{ $initialPillFreq === 'Atur sendiri' ? '' : 'hidden' }} mt-3 flex items-center space-x-2 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                                <span class="font-semibold text-slate-600">Ulangi setiap</span>
                                <input type="number" name="custom_interval" id="input_custom_interval" value="2" min="1" max="365"
                                       onchange="updateSidebarLive()" oninput="updateSidebarLive()"
                                       class="w-20 text-center font-bold rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                                <select name="custom_unit" id="select_custom_unit" onchange="updateSidebarLive()"
                                        class="font-semibold rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                                    <option value="hari">Hari</option>
                                    <option value="minggu">Minggu</option>
                                    <option value="bulan" selected>Bulan</option>
                                </select>
                            </div>
                        </div>

                        <!-- Tanggal Mulai & Jam -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    {{ $isEdit ? 'Tanggal mulai' : 'Tanggal mulai / berikutnya' }}
                                </label>
                                @if($isEdit)
                                    <input type="text" readonly value="{{ $startedLabel }}"
                                           class="w-full text-xs font-medium rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-slate-500 cursor-default focus:outline-none">
                                    <input type="hidden" name="tanggal_berikutnya" id="input_tanggal_berikutnya" value="{{ old('tanggal_berikutnya', $initialTanggalNext) }}">
                                @else
                                    <input type="date" name="tanggal_berikutnya" id="input_tanggal_berikutnya"
                                           value="{{ old('tanggal_berikutnya', $initialTanggalNext) }}"
                                           onchange="updateSidebarLive()" required
                                           class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                                @endif
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jam</label>
                                <select name="jam_mulai" id="select_jam_mulai" onchange="updateSidebarLive()"
                                        class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                                    @foreach($jamOptions as $jam)
                                        <option value="{{ $jam }}" {{ old('jam_mulai', $initialJam) === $jam ? 'selected' : '' }}>{{ $jam }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: PENANGGUNG JAWAB -->
                    <div class="space-y-4 pt-1">
                        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Penanggung jawab</h2>

                        <!-- Pill Tim -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">Tim</label>
                            <div class="flex flex-wrap items-center gap-2">
                                @foreach(['Tim IT Lapangan', 'Tim IT Support'] as $tOpt)
                                    @php $isDefTim = $tOpt === old('tim', $initialTim); @endphp
                                    <button type="button" onclick="setTim('{{ $tOpt }}')" data-tim="{{ $tOpt }}"
                                            class="tim-pill px-4 py-1.5 rounded-full text-xs transition cursor-pointer {{ $isDefTim ? 'bg-[#0e1a2b] text-white font-bold shadow-2xs' : 'bg-slate-100 text-slate-600 font-semibold hover:bg-slate-200/80' }}">
                                        {{ $tOpt }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Dropdown Teknisi -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Teknisi</label>
                            <select name="teknisi_id" id="select_teknisi" onchange="updateSidebarLive()"
                                    class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                                <option value="">Siapa saja di tim (yang pertama mengambil)</option>
                                @foreach($technicians as $tech)
                                    @php $isCurSelected = (string) old('teknisi_id', $initialTeknisiId) === (string) $tech->id; @endphp
                                    <option value="{{ $tech->id }}" data-name="{{ $tech->name }}" {{ $isCurSelected ? 'selected' : '' }} {{ (!$isCurSelected && $tech->isUnavailable()) ? 'disabled' : '' }}>
                                        {{ $tech->name }} ({{ $tech->label_spesialisasi }}){{ $tech->isUnavailable() ? ' — ⛔ ' . $tech->availability_short_note : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Checkbox Pengingat -->
                        <div class="pt-1 pb-2 border-b border-slate-100">
                            <label class="inline-flex items-center space-x-2.5 cursor-pointer">
                                <input type="checkbox" name="kirim_pengingat" value="1" {{ old('kirim_pengingat', $initialKirimPengingat) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-slate-300 text-[#0f766e] focus:ring-[#0f766e] accent-[#0f766e]">
                                <span class="text-xs font-medium text-slate-700">
                                    Kirim pengingat ke teknisi satu hari sebelumnya
                                </span>
                            </label>
                        </div>

                        <!-- Catatan untuk Teknisi -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan untuk teknisi</label>
                            <textarea name="catatan_teknisi" id="input_catatan_teknisi" rows="3"
                                      oninput="updateSidebarLive()"
                                      placeholder="Koordinasi dengan operator sebelum mematikan UPS"
                                      class="w-full text-xs rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">{{ old('catatan_teknisi', $initialCatatan) }}</textarea>
                        </div>
                    </div>

                    @if($isEdit)
                    <!-- SECTION 5: BERLAKU UNTUK (SESUAI MOCKUP UBAH JADWAL RUTIN) -->
                    <div class="space-y-2.5 pt-2 border-t border-slate-100">
                        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Berlaku untuk</h2>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" onclick="setBerlakuUntuk('berikutnya_saja')" data-berlaku="berikutnya_saja"
                                    class="berlaku-pill px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs">
                                Jadwal berikutnya saja
                            </button>
                            <button type="button" onclick="setBerlakuUntuk('termasuk_aktif')" data-berlaku="termasuk_aktif"
                                    class="berlaku-pill px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-600 hover:bg-slate-200/80">
                                Termasuk jadwal aktif saat ini
                            </button>
                        </div>
                    </div>
                    @endif

                </div>
            </div>

            <!-- KOLOM KANAN (1 SPAN) -->
            <div class="lg:col-span-1 space-y-4 lg:sticky lg:top-6">

                @if($isEdit)
                    <!-- KARTU 1 (EDIT MODE): RINGKASAN PERUBAHAN -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3.5">
                        <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Ringkasan perubahan</h3>

                        <div id="diff_changes_container" class="space-y-3 text-xs">
                            <!-- Diisi live diff via JS -->
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span class="text-slate-400 font-medium">Jadwal berikutnya</span>
                            <span id="summary_next_date" class="font-extrabold text-slate-900">{{ $initialTanggalNextDmy }}</span>
                        </div>
                    </div>

                    <!-- KARTU 2 (EDIT MODE): RIWAYAT JADWAL INI -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3.5">
                        <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Riwayat jadwal ini</h3>

                        <div class="divide-y divide-slate-100 text-xs">
                            @forelse($scheduleHistory as $hist)
                                <a href="{{ route('maintenance.show', $hist) }}" class="py-2.5 flex items-center justify-between hover:bg-slate-50/70 -mx-2 px-2 rounded-lg transition">
                                    <span class="font-semibold text-slate-700">{{ $hist->tanggal_cek ? $hist->tanggal_cek->format('d/m/Y') : '-' }}</span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                                        {{ $hist->computed_status }}
                                    </span>
                                </a>
                            @empty
                                <p class="py-2 text-xs text-slate-400">Belum ada log pemeliharaan selesai untuk jadwal ini.</p>
                            @endforelse
                        </div>

                        <div class="pt-1">
                            <a href="{{ route('maintenance.index', ['q' => $schedule->nama_aset, 'bulan' => 'all']) }}"
                               class="text-xs font-bold text-[#0f766e] hover:text-[#115e59] hover:underline">
                                Lihat semua log aset ini
                            </a>
                        </div>
                    </div>

                    <!-- KARTU 3 (EDIT MODE): NONAKTIFKAN JADWAL -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3">
                        <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Nonaktifkan jadwal</h3>
                        <p class="text-[11px] text-slate-400 leading-relaxed">
                            Jadwal berhenti membuat log baru. Log lama tetap tersimpan dan bisa dilihat.
                        </p>
                        <button type="button" onclick="confirmDeactivateSchedule()"
                                class="w-full py-2.5 px-4 rounded-xl bg-white hover:bg-rose-50 text-rose-600 border border-rose-400/80 text-xs font-bold transition cursor-pointer">
                            Nonaktifkan Jadwal
                        </button>
                    </div>
                @else
                    <!-- KARTU 1 (CREATE MODE): PRATINJAU JADWAL -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3.5">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Pratinjau jadwal</h3>
                            <p id="preview_subtitle" class="text-[11px] text-slate-400 mt-0.5">
                                Tiga jadwal berikutnya yang akan dibuat otomatis
                            </p>
                        </div>
                        <div id="preview_dates_container" class="divide-y divide-slate-100 text-xs"></div>
                    </div>

                    <!-- KARTU 2 (CREATE MODE): RINGKASAN -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3.5">
                        <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Ringkasan</h3>
                        <div class="space-y-3 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-medium">Frekuensi</span>
                                <span id="summary_frekuensi" class="font-bold text-slate-900">Setiap bulan</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-medium">Tim</span>
                                <span id="summary_tim" class="font-bold text-slate-900">Tim IT Lapangan</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-medium">Kode aset</span>
                                <span id="summary_kode_aset" class="font-mono font-bold text-slate-900">-</span>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-slate-100">
                            <p id="summary_info_note" class="text-[11px] text-slate-400 leading-relaxed">
                                Jadwal rutin otomatis muncul di Log Pemeliharaan teknisi.
                            </p>
                        </div>
                    </div>
                @endif

                <!-- KARTU AKSI: SIMPAN PERUBAHAN / SIMPAN JADWAL RUTIN & BATAL -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-2.5">
                    <button type="submit"
                            class="w-full py-2.5 px-4 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white text-xs font-extrabold transition shadow-xs cursor-pointer text-center">
                        {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Jadwal Rutin' }}
                    </button>
                    <a href="{{ route('maintenance.index') }}"
                       class="block w-full py-2.5 px-4 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs text-center">
                        Batal
                    </a>
                </div>

            </div>

        </div>
    </form>

    @if($isEdit)
    <!-- Form Terpisah untuk Nonaktifkan Jadwal -->
    <form id="form-deactivate-schedule" method="POST" action="{{ route('maintenance.schedules.deactivate', $schedule) }}" class="hidden">
        @csrf
    </form>
    @endif

</div>

@push('scripts')
<script>
    const isEditMode = {{ $isEdit ? 'true' : 'false' }};
    const originalState = {
        jam: @json($initialJam),
        catatan: @json($initialCatatan),
        frekuensi: @json($initialPillFreq),
        tim: @json($initialTim),
        teknisiId: @json((string) $initialTeknisiId),
        lokasi: @json($isEdit ? (string) $schedule->lokasi_ruangan : ''),
    };

    let kegiatanCounter = 0;

    function renderKegiatanList(items) {
        const container = document.getElementById('kegiatan_list_container');
        if (!container) return;
        container.innerHTML = '';
        kegiatanCounter = 0;
        items.forEach(text => appendKegiatanRow(text, true));
    }

    function appendKegiatanRow(text, checked = true) {
        const container = document.getElementById('kegiatan_list_container');
        if (!container) return;
        const idx = kegiatanCounter++;
        const row = document.createElement('div');
        row.className = 'kegiatan-row flex items-center justify-between gap-3 py-2.5';
        const safeText = text.replace(/"/g, '&quot;');
        row.innerHTML = `
            <label class="flex items-center space-x-3 cursor-pointer flex-1 min-w-0">
                <input type="checkbox" name="kegiatan_list[${idx}][checked]" value="1" ${checked ? 'checked' : ''}
                       class="w-4 h-4 rounded border-slate-300 text-[#0f766e] focus:ring-[#0f766e] accent-[#0f766e] shrink-0">
                <input type="hidden" name="kegiatan_list[${idx}][item]" value="${safeText}">
                <span class="text-xs font-medium text-slate-800 leading-snug">${text}</span>
            </label>
            <button type="button" onclick="this.closest('.kegiatan-row').remove()"
                    class="text-xs font-bold text-rose-600 hover:text-rose-700 transition shrink-0 cursor-pointer">
                Hapus
            </button>
        `;
        container.appendChild(row);
    }

    function showAddKegiatanBox() {
        const box = document.getElementById('inline_add_kegiatan_box');
        const inp = document.getElementById('inline_new_kegiatan_input');
        box.classList.remove('hidden');
        inp.value = '';
        inp.focus();
    }

    function cancelAddKegiatan() {
        document.getElementById('inline_add_kegiatan_box').classList.add('hidden');
    }

    function confirmAddKegiatan() {
        const inp = document.getElementById('inline_new_kegiatan_input');
        const val = inp.value.trim();
        if (!val) return;
        appendKegiatanRow(val, true);
        inp.value = '';
        document.getElementById('inline_add_kegiatan_box').classList.add('hidden');
    }

    function selectLocationValue(loc) {
        const locSel = document.getElementById('select_lokasi');
        const customInp = document.getElementById('input_lokasi_custom');
        const hiddenLoc = document.getElementById('hidden_lokasi_aset');
        if (!locSel) return;

        customInp.classList.add('hidden');
        customInp.required = false;

        const target = (loc || '').trim();
        if (!target) {
            locSel.value = '';
            hiddenLoc.value = '';
            return;
        }

        let matchedVal = null;
        for (let i = 0; i < locSel.options.length; i++) {
            const optVal = locSel.options[i].value;
            if (optVal && optVal !== '__custom_loc__' && optVal.toLowerCase() === target.toLowerCase()) {
                matchedVal = optVal;
                break;
            }
        }

        if (matchedVal !== null) {
            locSel.value = matchedVal;
            hiddenLoc.value = matchedVal;
        } else {
            // Sisipkan sementara di atas opsi "__custom_loc__" agar tetap terlihat sebagai dropdown terpilih
            const newOpt = document.createElement('option');
            newOpt.value = target;
            newOpt.text = target;
            const customOpt = locSel.querySelector('option[value="__custom_loc__"]');
            if (customOpt) {
                locSel.insertBefore(newOpt, customOpt);
            } else {
                locSel.appendChild(newOpt);
            }
            locSel.value = target;
            hiddenLoc.value = target;
        }
    }

    function onAssetSelected(sel) {
        const val = sel.value;
        const customBox = document.getElementById('custom_asset_fields');

        if (val === '__custom__') {
            customBox.classList.remove('hidden');
            document.getElementById('custom_nama_aset').required = true;
            document.getElementById('hidden_nama_aset').value = '';
            document.getElementById('hidden_kode_aset').value = '';
            selectLocationValue('');
            const kodeEl = document.getElementById('summary_kode_aset');
            if (kodeEl) kodeEl.innerText = '-';
            renderKegiatanList(['Pemeriksaan kondisi fisik & kebersihan perangkat', 'Pengecekan performa sistem']);
            updateSidebarLive();
            return;
        }

        customBox.classList.add('hidden');
        document.getElementById('custom_nama_aset').required = false;

        const opt = sel.options[sel.selectedIndex];
        const nama = opt.getAttribute('data-nama') || '';
        const kode = opt.getAttribute('data-kode') || '-';
        const lokasi = opt.getAttribute('data-lokasi') || '';
        let kegiatan = [];
        try { kegiatan = JSON.parse(opt.getAttribute('data-kegiatan') || '[]'); } catch (e) {}

        document.getElementById('hidden_nama_aset').value = nama;
        document.getElementById('hidden_kode_aset').value = kode;
        const kodeEl = document.getElementById('summary_kode_aset');
        if (kodeEl) kodeEl.innerText = kode;

        selectLocationValue(lokasi);
        renderKegiatanList(kegiatan.length ? kegiatan : ['Pemeriksaan berkala perangkat']);
        updateSidebarLive();
    }

    function syncCustomAsset() {
        const nama = document.getElementById('custom_nama_aset').value.trim();
        const kode = document.getElementById('custom_kode_aset').value.trim().toUpperCase();
        document.getElementById('hidden_nama_aset').value = nama;
        document.getElementById('hidden_kode_aset').value = kode;
        const kodeEl = document.getElementById('summary_kode_aset');
        if (kodeEl) kodeEl.innerText = kode || 'OTOMATIS';
    }

    function onLocationChange(sel) {
        const customInp = document.getElementById('input_lokasi_custom');
        if (sel.value === '__custom_loc__') {
            customInp.classList.remove('hidden');
            customInp.required = true;
            customInp.focus();
            document.getElementById('hidden_lokasi_aset').value = customInp.value;
        } else {
            customInp.classList.add('hidden');
            customInp.required = false;
            document.getElementById('hidden_lokasi_aset').value = sel.value;
        }
        updateSidebarLive();
    }

    function setFrekuensi(freq) {
        document.getElementById('hidden_frekuensi').value = freq;
        document.querySelectorAll('.freq-pill').forEach(btn => {
            btn.className = btn.getAttribute('data-freq') === freq
                ? 'freq-pill px-4 py-1.5 rounded-full text-xs transition cursor-pointer bg-[#0e1a2b] text-white font-bold shadow-2xs'
                : 'freq-pill px-4 py-1.5 rounded-full text-xs transition cursor-pointer bg-slate-100 text-slate-600 font-semibold hover:bg-slate-200/80';
        });
        document.getElementById('custom_freq_box').classList.toggle('hidden', freq !== 'Atur sendiri');
        updateSidebarLive();
    }

    function setTim(tim) {
        document.getElementById('hidden_tim').value = tim;
        document.querySelectorAll('.tim-pill').forEach(btn => {
            btn.className = btn.getAttribute('data-tim') === tim
                ? 'tim-pill px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs'
                : 'tim-pill px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-600 hover:bg-slate-200/80';
        });
        updateSidebarLive();
    }

    function setBerlakuUntuk(val) {
        document.getElementById('hidden_berlaku_untuk').value = val;
        document.querySelectorAll('.berlaku-pill').forEach(btn => {
            btn.className = btn.getAttribute('data-berlaku') === val
                ? 'berlaku-pill px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs'
                : 'berlaku-pill px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-600 hover:bg-slate-200/80';
        });
    }

    function confirmDeactivateSchedule() {
        if (confirm('Nonaktifkan jadwal rutin ini? Jadwal akan berhenti membuat log baru, namun seluruh log pemeliharaan lama tetap tersimpan rapi.')) {
            document.getElementById('form-deactivate-schedule').submit();
        }
    }

    function formatDMY(dateObj) {
        return `${String(dateObj.getDate()).padStart(2, '0')}/${String(dateObj.getMonth() + 1).padStart(2, '0')}/${dateObj.getFullYear()}`;
    }

    function addStep(baseDate, freq, stepIndex) {
        const d = new Date(baseDate.getTime());
        if (stepIndex === 0) return d;
        if (freq === 'Mingguan') d.setDate(d.getDate() + (7 * stepIndex));
        else if (freq === 'Bulanan') d.setMonth(d.getMonth() + stepIndex);
        else if (freq === 'Tiga bulanan') d.setMonth(d.getMonth() + (3 * stepIndex));
        else if (freq === 'Atur sendiri') {
            const iv = Math.max(1, parseInt(document.getElementById('input_custom_interval')?.value || '1', 10));
            const unit = document.getElementById('select_custom_unit')?.value || 'bulan';
            if (unit === 'hari') d.setDate(d.getDate() + (iv * stepIndex));
            else if (unit === 'minggu') d.setDate(d.getDate() + (iv * 7 * stepIndex));
            else d.setMonth(d.getMonth() + (iv * stepIndex));
        }
        return d;
    }

    function truncate(str, maxLen = 28) {
        if (!str) return '(kosong)';
        return str.length > maxLen ? str.substring(0, maxLen) + '...' : str;
    }

    function updateSidebarLive() {
        const jam = document.getElementById('select_jam_mulai')?.value || '09.00';
        const freq = document.getElementById('hidden_frekuensi')?.value || 'Bulanan';
        const tim = document.getElementById('hidden_tim')?.value || 'Tim IT Lapangan';
        const catatan = (document.getElementById('input_catatan_teknisi')?.value || '').trim();
        const techSel = document.getElementById('select_teknisi');
        const techVal = techSel ? techSel.value : '';
        const techName = techSel && techSel.value ? (techSel.options[techSel.selectedIndex]?.getAttribute('data-name') || '') : '';

        if (isEditMode) {
            const diffBox = document.getElementById('diff_changes_container');
            if (!diffBox) return;
            const rows = [];
            const lokasiNow = (document.getElementById('hidden_lokasi_aset')?.value || '').trim();

            if (lokasiNow && lokasiNow !== originalState.lokasi) {
                rows.push(`
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-slate-400 font-medium shrink-0">Lokasi</span>
                        <span class="text-right font-bold leading-snug">
                            <span class="line-through text-slate-400 font-normal">${truncate(originalState.lokasi, 16)}</span>
                            <span class="text-slate-400 mx-1">&rarr;</span>
                            <span class="text-[#0f766e]">${truncate(lokasiNow, 22)}</span>
                        </span>
                    </div>
                `);
            }

            if (jam !== originalState.jam) {
                rows.push(`
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-slate-400 font-medium shrink-0">Jam</span>
                        <span class="text-right font-bold">
                            <span class="line-through text-slate-400 font-normal">${originalState.jam}</span>
                            <span class="text-slate-400 mx-1">&rarr;</span>
                            <span class="text-[#0f766e]">${jam}</span>
                        </span>
                    </div>
                `);
            }

            if (catatan !== originalState.catatan) {
                rows.push(`
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-slate-400 font-medium shrink-0">Catatan</span>
                        <span class="text-right font-bold leading-snug">
                            <span class="text-slate-400 font-normal">${truncate(originalState.catatan, 16)}</span>
                            <span class="text-slate-400 mx-1">&rarr;</span>
                            <span class="text-[#0f766e]">${truncate(catatan, 24)}</span>
                        </span>
                    </div>
                `);
            }

            if (freq !== originalState.frekuensi) {
                rows.push(`
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-slate-400 font-medium shrink-0">Frekuensi</span>
                        <span class="text-right font-bold">
                            <span class="line-through text-slate-400 font-normal">${originalState.frekuensi}</span>
                            <span class="text-slate-400 mx-1">&rarr;</span>
                            <span class="text-[#0f766e]">${freq}</span>
                        </span>
                    </div>
                `);
            }

            if (tim !== originalState.tim) {
                rows.push(`
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-slate-400 font-medium shrink-0">Tim</span>
                        <span class="text-right font-bold">
                            <span class="line-through text-slate-400 font-normal">${originalState.tim}</span>
                            <span class="text-slate-400 mx-1">&rarr;</span>
                            <span class="text-[#0f766e]">${tim}</span>
                        </span>
                    </div>
                `);
            }

            if (techVal !== originalState.teknisiId) {
                rows.push(`
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-slate-400 font-medium shrink-0">Teknisi</span>
                        <span class="text-right font-bold text-[#0f766e]">${techName || 'Siapa saja di tim'}</span>
                    </div>
                `);
            }

            if (rows.length === 0) {
                diffBox.innerHTML = `<p class="text-[11px] text-slate-400">Belum ada perubahan pada jadwal ini.</p>`;
            } else {
                diffBox.innerHTML = rows.join('');
            }
        } else {
            // Create mode live preview
            let freqLabel = 'Setiap bulan';
            if (freq === 'Sekali') freqLabel = 'Sekali saja';
            else if (freq === 'Mingguan') freqLabel = 'Setiap minggu';
            else if (freq === 'Bulanan') freqLabel = 'Setiap bulan';
            else if (freq === 'Tiga bulanan') freqLabel = 'Setiap 3 bulan';
            else if (freq === 'Atur sendiri') {
                const iv = Math.max(1, parseInt(document.getElementById('input_custom_interval')?.value || '1', 10));
                const unit = document.getElementById('select_custom_unit')?.value || 'bulan';
                freqLabel = `Setiap ${iv} ${unit}`;
            }

            const sf = document.getElementById('summary_frekuensi');
            const st = document.getElementById('summary_tim');
            const note = document.getElementById('summary_info_note');
            if (sf) sf.innerText = freqLabel;
            if (st) st.innerText = tim;
            if (note) {
                note.innerText = techName
                    ? `Jadwal rutin ditugaskan ke ${techName} (${tim}) dan otomatis masuk ke Log Pemeliharaan teknisi.`
                    : `Jadwal rutin otomatis muncul di Log Pemeliharaan semua teknisi ${tim}.`;
            }

            const dateVal = document.getElementById('input_tanggal_berikutnya')?.value;
            const container = document.getElementById('preview_dates_container');
            const subtitle = document.getElementById('preview_subtitle');
            if (!container || !dateVal) return;
            container.innerHTML = '';

            const parts = dateVal.split('-').map(Number);
            const baseDate = new Date(parts[0], parts[1] - 1, parts[2]);
            const count = freq === 'Sekali' ? 1 : 3;
            if (subtitle) {
                subtitle.innerText = freq === 'Sekali'
                    ? 'Satu jadwal yang akan dibuat'
                    : 'Tiga jadwal berikutnya yang akan dibuat otomatis';
            }

            for (let i = 0; i < count; i++) {
                const d = addStep(baseDate, freq, i);
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between py-2.5';
                row.innerHTML = `
                    <div>
                        <span class="font-semibold text-slate-700">${formatDMY(d)}</span>
                        <span class="text-[11px] text-slate-400 font-medium ml-1.5">${jam} WIB</span>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200/70">Terjadwal</span>
                `;
                container.appendChild(row);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const assetSel = document.getElementById('select_aset');
        if (assetSel) {
            onAssetSelected(assetSel);
        }
        updateSidebarLive();
    });
</script>
@endpush
@endsection
