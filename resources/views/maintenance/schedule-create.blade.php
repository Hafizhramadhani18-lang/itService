@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Flash Error Notification -->
    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-xs animate-fade-in">
            <div class="font-bold flex items-center space-x-2 mb-1">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Terdapat kesalahan pada input jadwal:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-rose-700">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Top Header Sesuai Mockup Referensi Jadwalkan Pemeliharaan -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Jadwalkan Pemeliharaan</h1>
            <nav class="flex items-center space-x-1.5 text-xs text-slate-400 mt-1">
                <a href="{{ route('maintenance.index') }}" class="hover:text-teal-700 transition font-medium">Log Pemeliharaan</a>
                <span>&rsaquo;</span>
                <span class="text-slate-600 font-semibold">Jadwal baru</span>
            </nav>
        </div>

        <div class="flex items-center space-x-2.5">
            <!-- Tombol Kembali -->
            <a href="{{ route('maintenance.index') }}"
               class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs">
                Kembali
            </a>

            <!-- Widget Tanggal & Lonceng Notifikasi -->
            @include('partials.header-widget')
        </div>
    </div>

    @php
        $prefillFrekuensi = isset($prefillLog) && $prefillLog ? 'Sekali' : 'Bulanan';
        $prefillDate = isset($prefillLog) && $prefillLog ? now()->addDays(3)->format('Y-m-d') : now()->addDays(2)->format('Y-m-d');
        $prefillCatatan = isset($prefillLog) && $prefillLog
            ? "Tindak lanjut temuan log {$prefillLog->log_number}: " . ($prefillLog->catatan_rekomendasi ?: $prefillLog->tindakan)
            : '';
    @endphp

    <!-- Form Utama 2 Kolom -->
    <form method="POST" action="{{ route('maintenance.jadwalkan') }}" id="form-jadwalkan-pm">
        @csrf
        <input type="hidden" name="nama_perangkat" id="hidden_nama_perangkat" value="{{ old('nama_perangkat') }}">
        <input type="hidden" name="kode_perangkat" id="hidden_kode_perangkat" value="{{ old('kode_perangkat') }}">
        <input type="hidden" name="lokasi_ruangan" id="hidden_lokasi_ruangan" value="{{ old('lokasi_ruangan') }}">
        <input type="hidden" name="jenis_perangkat" id="hidden_jenis_perangkat" value="{{ old('jenis_perangkat', 'Server / UPS') }}">
        <input type="hidden" name="frekuensi" id="hidden_frekuensi" value="{{ old('frekuensi', $prefillFrekuensi) }}">
        <input type="hidden" name="tim" id="hidden_tim" value="{{ old('tim', isset($prefillLog) && $prefillLog ? $prefillLog->tim_label : 'Tim IT Lapangan') }}">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <!-- KOLOM KIRI: FORM KONFIGURASI JADWAL (2 SPAN) -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-7 space-y-7">

                    <!-- SECTION 1: ASET -->
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Aset</h2>
                            <span class="text-[11px] font-medium text-slate-400">
                                Sumber data: <a href="{{ route('maintenance.schedules.create') }}" class="text-[#0f766e] hover:underline font-semibold">Master Jadwal Rutin (+ Tambah jadwal)</a>
                            </span>
                        </div>

                        <!-- Dropdown Pilih Aset (bersumber dari + Tambah jadwal) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Aset</label>
                            <select id="select_aset" onchange="onAssetSelected(this, false)" required
                                    class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                                @foreach($assetList as $idx => $asset)
                                    @php
                                        $isPrefillAsset = isset($prefillLog) && $prefillLog && mb_strtolower($asset['nama']) === mb_strtolower($prefillLog->nama_perangkat);
                                    @endphp
                                    <option value="{{ $idx }}" {{ $isPrefillAsset ? 'selected' : '' }}
                                            data-nama="{{ $asset['nama'] }}"
                                            data-kode="{{ $asset['kode'] }}"
                                            data-lokasi="{{ $asset['lokasi'] }}"
                                            data-jenis="{{ $asset['jenis'] }}"
                                            data-frekuensi="{{ $asset['frekuensi'] ?? 'Bulanan' }}"
                                            data-jam="{{ $asset['jam'] ?? '09.00' }}"
                                            data-tim="{{ $asset['tim'] ?? 'Tim IT Lapangan' }}"
                                            data-teknisi-id="{{ $asset['teknisi_id'] ?? '' }}"
                                            data-catatan="{{ $asset['catatan'] ?? '' }}"
                                            data-kegiatan='@json($asset['kegiatan'])'>
                                        {{ $asset['nama'] }} &nbsp;&nbsp;&nbsp; ({{ $asset['kode'] }})
                                    </option>
                                @endforeach
                                <option value="__custom__">+ Tambah aset baru (ketik manual...)</option>
                            </select>

                            @if($assetList->isEmpty())
                                <p class="mt-2 text-[11px] text-amber-700 bg-amber-50 border border-amber-200/80 rounded-xl px-3 py-2">
                                    Belum ada aset di Master Jadwal Rutin. Anda bisa mengetik manual di bawah atau menambahkan dulu melalui
                                    <a href="{{ route('maintenance.schedules.create') }}" class="font-bold underline text-amber-900">+ Tambah jadwal</a>.
                                </p>
                            @endif

                            <!-- Input Manual Aset Baru (jika memilih __custom__) -->
                            <div id="custom_asset_fields" class="hidden mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Aset Baru <span class="text-rose-500">*</span></label>
                                    <input type="text" id="custom_nama_aset" oninput="syncCustomAsset()" placeholder="Contoh: Router Core Unit 2"
                                           class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Kode Aset</label>
                                    <input type="text" id="custom_kode_aset" oninput="syncCustomAsset()" placeholder="Contoh: RTR-U2-01"
                                           class="w-full text-xs font-mono font-semibold uppercase rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                                </div>
                            </div>
                        </div>

                        <!-- Field Lokasi (Dropdown dari data + Tambah jadwal, auto-terpilih saat memilih aset) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Lokasi</label>

                            <div class="space-y-2">
                                <select id="select_lokasi" onchange="onLocationChange(this)" required
                                        class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                                    <option value="">-- Pilih Lokasi dari Jadwal Rutin --</option>
                                    @foreach($existingLocations as $locName)
                                        <option value="{{ $locName }}">{{ $locName }}</option>
                                    @endforeach
                                    <option value="__custom_loc__">+ Lokasi lainnya (ketik manual...)</option>
                                </select>
                                <input type="text" id="input_lokasi_custom" oninput="document.getElementById('hidden_lokasi_ruangan').value = this.value"
                                       placeholder="Ketik nama ruangan..."
                                       class="hidden w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: KEGIATAN -->
                    <div class="space-y-3 pt-1">
                        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Kegiatan</h2>

                        <div id="kegiatan_list_container" class="divide-y divide-slate-100">
                            <!-- Diisi otomatis dari data aset yang dipilih -->
                        </div>

                        <!-- Inline Form Tambah Kegiatan Baru -->
                        <div id="inline_add_kegiatan_box" class="hidden flex items-center gap-2 pt-2">
                            <input type="text" id="inline_new_kegiatan_input"
                                   placeholder="Tulis kegiatan pemeliharaan baru..."
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

                        <!-- Pill Frekuensi -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">Frekuensi</label>
                            <div class="flex flex-wrap items-center gap-2" id="frekuensi_pills">
                                @foreach(['Sekali', 'Mingguan', 'Bulanan', 'Tiga bulanan', 'Atur sendiri'] as $freq)
                                    @php $isDef = $freq === old('frekuensi', $prefillFrekuensi); @endphp
                                    <button type="button"
                                            onclick="setFrekuensi('{{ $freq }}')"
                                            data-freq="{{ $freq }}"
                                            class="freq-pill px-4 py-1.5 rounded-full text-xs transition cursor-pointer {{ $isDef ? 'bg-[#0e1a2b] text-white font-bold shadow-2xs' : 'bg-slate-100 text-slate-600 font-semibold hover:bg-slate-200/80' }}">
                                        {{ $freq }}
                                    </button>
                                @endforeach
                            </div>

                            <!-- Pengaturan Interval Kustom (muncul jika "Atur sendiri") -->
                            <div id="custom_freq_box" class="hidden mt-3 flex items-center space-x-2 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                                <span class="font-semibold text-slate-600">Ulangi setiap</span>
                                <input type="number" name="custom_interval" id="input_custom_interval" value="2" min="1" max="365"
                                       onchange="updatePreviewAndSummary()" oninput="updatePreviewAndSummary()"
                                       class="w-20 text-center font-bold rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                                <select name="custom_unit" id="select_custom_unit" onchange="updatePreviewAndSummary()"
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
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal mulai</label>
                                <input type="date" name="tanggal_cek" id="input_tanggal_mulai"
                                       value="{{ old('tanggal_cek', $prefillDate) }}"
                                       onchange="updatePreviewAndSummary()" required
                                       class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jam</label>
                                <select name="jam_mulai" id="select_jam_mulai" onchange="updatePreviewAndSummary()"
                                        class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                                    @foreach(['07.00', '07.30', '08.00', '08.30', '09.00', '09.30', '10.00', '10.30', '11.00', '13.00', '13.30', '14.00', '14.30', '15.00', '15.30', '16.00'] as $jam)
                                        <option value="{{ $jam }}" {{ old('jam_mulai', '09.00') === $jam ? 'selected' : '' }}>{{ $jam }}</option>
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
                                <button type="button" onclick="setTim('Tim IT Lapangan')" data-tim="Tim IT Lapangan"
                                        class="tim-pill px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs">
                                    Tim IT Lapangan
                                </button>
                                <button type="button" onclick="setTim('Tim IT Support')" data-tim="Tim IT Support"
                                        class="tim-pill px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-600 hover:bg-slate-200/80">
                                    Tim IT Support
                                </button>
                            </div>
                        </div>

                        <!-- Dropdown Teknisi -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Teknisi</label>
                            <select name="teknisi_id" id="select_teknisi" onchange="updatePreviewAndSummary()"
                                    class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                                <option value="">Siapa saja di tim (yang pertama mengambil)</option>
                                @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}" data-name="{{ $tech->name }}" {{ $tech->isUnavailable() ? 'disabled' : '' }}>
                                        {{ $tech->name }} ({{ $tech->label_spesialisasi }}) &bull; Beban: {{ $tech->scheduled_pm_count ?? 0 }} PM aktif{{ $tech->isUnavailable() ? ' — ⛔ ' . $tech->availability_short_note : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Checkbox Pengingat -->
                        <div class="pt-1 pb-2 border-b border-slate-100">
                            <label class="inline-flex items-center space-x-2.5 cursor-pointer">
                                <input type="checkbox" name="kirim_pengingat" value="1" checked
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
                                      placeholder="Contoh: koordinasi dulu dengan petugas Pos Utama Security"
                                      class="w-full text-xs rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">{{ old('catatan_teknisi', $prefillCatatan) }}</textarea>
                        </div>
                    </div>

                </div>
            </div>

            <!-- KOLOM KANAN: PRATINJAU JADWAL, RINGKASAN & TOMBOL SIMPAN (1 SPAN) -->
            <div class="lg:col-span-1 space-y-4 lg:sticky lg:top-6">

                <!-- KARTU 1: PRATINJAU JADWAL -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3.5">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Pratinjau jadwal</h3>
                        <p id="preview_subtitle" class="text-[11px] text-slate-400 mt-0.5">
                            Tiga jadwal berikutnya yang akan dibuat otomatis
                        </p>
                    </div>

                    <div id="preview_dates_container" class="divide-y divide-slate-100 text-xs">
                        <!-- Diisi otomatis via JavaScript -->
                    </div>
                </div>

                <!-- KARTU 2: RINGKASAN -->
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
                            Jadwal muncul di Log Pemeliharaan semua teknisi Tim IT Lapangan.
                        </p>
                    </div>
                </div>

                <!-- KARTU 3: TOMBOL AKSI -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-2.5">
                    <button type="submit"
                            class="w-full py-2.5 px-4 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white text-xs font-extrabold transition shadow-xs cursor-pointer text-center">
                        Simpan Jadwal
                    </button>
                    <a href="{{ route('maintenance.index') }}"
                       class="block w-full py-2.5 px-4 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs text-center">
                        Batal
                    </a>
                </div>

            </div>

        </div>
    </form>

</div>

@push('scripts')
<script>
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

    const hasPrefillLog = {{ isset($prefillLog) && $prefillLog ? 'true' : 'false' }};

    function selectLocationValue(loc) {
        const locSel = document.getElementById('select_lokasi');
        const customInp = document.getElementById('input_lokasi_custom');
        const hiddenLoc = document.getElementById('hidden_lokasi_ruangan');
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

    function onAssetSelected(sel, isInitialLoad = false) {
        const val = sel.value;
        const customBox = document.getElementById('custom_asset_fields');

        if (val === '__custom__') {
            customBox.classList.remove('hidden');
            document.getElementById('custom_nama_aset').required = true;
            document.getElementById('hidden_nama_perangkat').value = '';
            document.getElementById('hidden_kode_perangkat').value = '';
            selectLocationValue('');
            document.getElementById('summary_kode_aset').innerText = '-';
            renderKegiatanList([
                'Pemeriksaan kondisi fisik & kebersihan perangkat',
                'Pengecekan performa & kestabilan sistem',
            ]);
            updatePreviewAndSummary();
            return;
        }

        customBox.classList.add('hidden');
        document.getElementById('custom_nama_aset').required = false;

        const opt = sel.options[sel.selectedIndex];
        const nama = opt.getAttribute('data-nama') || '';
        const kode = opt.getAttribute('data-kode') || '-';
        const lokasi = opt.getAttribute('data-lokasi') || '';
        const jenis = opt.getAttribute('data-jenis') || 'Server / UPS';
        const frekuensi = opt.getAttribute('data-frekuensi') || 'Bulanan';
        const jam = opt.getAttribute('data-jam') || '09.00';
        const tim = opt.getAttribute('data-tim') || 'Tim IT Lapangan';
        const teknisiId = opt.getAttribute('data-teknisi-id') || '';
        const catatan = opt.getAttribute('data-catatan') || '';
        let kegiatan = [];
        try {
            kegiatan = JSON.parse(opt.getAttribute('data-kegiatan') || '[]');
        } catch (e) {
            kegiatan = [];
        }

        document.getElementById('hidden_nama_perangkat').value = nama;
        document.getElementById('hidden_kode_perangkat').value = kode;
        document.getElementById('hidden_jenis_perangkat').value = jenis;
        document.getElementById('summary_kode_aset').innerText = kode;

        selectLocationValue(lokasi);
        if (lokasi) {
            document.getElementById('input_catatan_teknisi').placeholder = `Contoh: koordinasi dulu dengan petugas ${lokasi}`;
        }

        renderKegiatanList(kegiatan.length ? kegiatan : ['Pemeriksaan berkala perangkat']);

        // Sinkronkan preferensi dari Master Jadwal Rutin (+ Tambah jadwal), kecuali saat initial load tindak lanjut log
        if (!(isInitialLoad && hasPrefillLog)) {
            setFrekuensi(frekuensi);
            setTim(tim);

            const jamSel = document.getElementById('select_jam_mulai');
            if (jamSel && jam) {
                let hasJamOpt = Array.from(jamSel.options).some(o => o.value === jam);
                if (!hasJamOpt) {
                    const o = document.createElement('option');
                    o.value = jam;
                    o.text = jam;
                    jamSel.appendChild(o);
                }
                jamSel.value = jam;
            }

            const techSel = document.getElementById('select_teknisi');
            if (techSel) {
                techSel.value = teknisiId;
            }

            const catatanEl = document.getElementById('input_catatan_teknisi');
            if (catatanEl && (!isInitialLoad || catatanEl.value.trim() === '')) {
                catatanEl.value = catatan;
            }
        }

        updatePreviewAndSummary();
    }

    function syncCustomAsset() {
        const nama = document.getElementById('custom_nama_aset').value.trim();
        const kode = document.getElementById('custom_kode_aset').value.trim().toUpperCase();
        document.getElementById('hidden_nama_perangkat').value = nama;
        document.getElementById('hidden_kode_perangkat').value = kode || 'ASET-BARU';
        document.getElementById('summary_kode_aset').innerText = kode || 'OTOMATIS';
    }

    function onLocationChange(sel) {
        const customInp = document.getElementById('input_lokasi_custom');
        if (sel.value === '__custom_loc__') {
            customInp.classList.remove('hidden');
            customInp.required = true;
            customInp.focus();
            document.getElementById('hidden_lokasi_ruangan').value = customInp.value;
        } else {
            customInp.classList.add('hidden');
            customInp.required = false;
            document.getElementById('hidden_lokasi_ruangan').value = sel.value;
            if (sel.value) {
                document.getElementById('input_catatan_teknisi').placeholder = `Contoh: koordinasi dulu dengan petugas ${sel.value}`;
            }
        }
    }

    function setFrekuensi(freq) {
        document.getElementById('hidden_frekuensi').value = freq;
        document.querySelectorAll('.freq-pill').forEach(btn => {
            if (btn.getAttribute('data-freq') === freq) {
                btn.className = 'freq-pill px-4 py-1.5 rounded-full text-xs transition cursor-pointer bg-[#0e1a2b] text-white font-bold shadow-2xs';
            } else {
                btn.className = 'freq-pill px-4 py-1.5 rounded-full text-xs transition cursor-pointer bg-slate-100 text-slate-600 font-semibold hover:bg-slate-200/80';
            }
        });

        const customBox = document.getElementById('custom_freq_box');
        if (freq === 'Atur sendiri') {
            customBox.classList.remove('hidden');
        } else {
            customBox.classList.add('hidden');
        }

        updatePreviewAndSummary();
    }

    function setTim(tim) {
        document.getElementById('hidden_tim').value = tim;
        document.querySelectorAll('.tim-pill').forEach(btn => {
            if (btn.getAttribute('data-tim') === tim) {
                btn.className = 'tim-pill px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs';
            } else {
                btn.className = 'tim-pill px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-600 hover:bg-slate-200/80';
            }
        });

        updatePreviewAndSummary();
    }

    function formatDMY(dateObj) {
        const d = String(dateObj.getDate()).padStart(2, '0');
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const y = dateObj.getFullYear();
        return `${d}/${m}/${y}`;
    }

    function addStep(baseDate, freq, stepIndex) {
        const d = new Date(baseDate.getTime());
        if (stepIndex === 0) return d;

        if (freq === 'Mingguan') {
            d.setDate(d.getDate() + (7 * stepIndex));
        } else if (freq === 'Bulanan') {
            d.setMonth(d.getMonth() + stepIndex);
        } else if (freq === 'Tiga bulanan') {
            d.setMonth(d.getMonth() + (3 * stepIndex));
        } else if (freq === 'Atur sendiri') {
            const interval = Math.max(1, parseInt(document.getElementById('input_custom_interval')?.value || '1', 10));
            const unit = document.getElementById('select_custom_unit')?.value || 'bulan';
            if (unit === 'hari') {
                d.setDate(d.getDate() + (interval * stepIndex));
            } else if (unit === 'minggu') {
                d.setDate(d.getDate() + (interval * 7 * stepIndex));
            } else {
                d.setMonth(d.getMonth() + (interval * stepIndex));
            }
        }
        return d;
    }

    function updatePreviewAndSummary() {
        const dateVal = document.getElementById('input_tanggal_mulai').value;
        const freq = document.getElementById('hidden_frekuensi').value;
        const tim = document.getElementById('hidden_tim').value;
        const techSel = document.getElementById('select_teknisi');
        const techOpt = techSel ? techSel.options[techSel.selectedIndex] : null;
        const techName = techOpt && techOpt.value ? (techOpt.getAttribute('data-name') || techOpt.text) : '';

        // Label Frekuensi di Ringkasan
        let freqLabel = 'Setiap bulan';
        if (freq === 'Sekali') freqLabel = 'Sekali saja';
        else if (freq === 'Mingguan') freqLabel = 'Setiap minggu';
        else if (freq === 'Bulanan') freqLabel = 'Setiap bulan';
        else if (freq === 'Tiga bulanan') freqLabel = 'Setiap 3 bulan';
        else if (freq === 'Atur sendiri') {
            const interval = Math.max(1, parseInt(document.getElementById('input_custom_interval')?.value || '1', 10));
            const unit = document.getElementById('select_custom_unit')?.value || 'bulan';
            freqLabel = `Setiap ${interval} ${unit}`;
        }

        document.getElementById('summary_frekuensi').innerText = freqLabel;
        document.getElementById('summary_tim').innerText = tim;

        const noteEl = document.getElementById('summary_info_note');
        if (techName) {
            noteEl.innerText = `Jadwal ditugaskan ke ${techName} (${tim}) dan muncul di Log Pemeliharaan teknisi.`;
        } else {
            noteEl.innerText = `Jadwal muncul di Log Pemeliharaan semua teknisi ${tim}.`;
        }

        // Pratinjau Jadwal
        const container = document.getElementById('preview_dates_container');
        const subtitle = document.getElementById('preview_subtitle');
        container.innerHTML = '';

        if (!dateVal) return;
        const parts = dateVal.split('-').map(Number);
        const baseDate = new Date(parts[0], parts[1] - 1, parts[2]);
        const count = freq === 'Sekali' ? 1 : 3;

        subtitle.innerText = freq === 'Sekali'
            ? 'Satu jadwal yang akan dibuat'
            : 'Tiga jadwal berikutnya yang akan dibuat otomatis';

        const jamVal = document.getElementById('select_jam_mulai')?.value || '09.00';

        for (let i = 0; i < count; i++) {
            const d = addStep(baseDate, freq, i);
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between py-2.5';
            row.innerHTML = `
                <div>
                    <span class="font-semibold text-slate-700">${formatDMY(d)}</span>
                    <span class="text-[11px] text-slate-400 font-medium ml-1.5">${jamVal} WIB</span>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200/70">Terjadwal</span>
            `;
            container.appendChild(row);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const assetSel = document.getElementById('select_aset');
        if (assetSel) {
            onAssetSelected(assetSel, true);
        }
        updatePreviewAndSummary();
    });
</script>
@endpush
@endsection
