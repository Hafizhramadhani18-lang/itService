@extends('layouts.app')

@php
    $isEdit = isset($guide);
    $valJudul = old('judul', $isEdit ? $guide->judul : 'SOP Printer Offline di Control Room');
    $valKategori = old('kategori', $isEdit ? $guide->kategori : 'Sistem & Printer');
    $valArea = old('area_penerapan', $isEdit ? ($guide->area_penerapan ?? '') : 'Control Room dan kantor');
    $valAudience = old('target_audience', $isEdit ? ($guide->target_audience ?? 'teknisi') : 'teknisi');
    $valPengantar = old('pengantar', $isEdit ? ($guide->pengantar ?? '') : 'Gejala dan kapan SOP ini dipakai: printer berstatus offline di Windows atau antrian cetak macet.');
    $valJudulPerintah = old('judul_perintah', $isEdit ? ($guide->judul_perintah ?? '') : 'Restart Print Spooler');
    $valPerintahCmd = old('perintah_cmd', $isEdit ? ($guide->perintah_cmd ?? '') : "net stop spooler\nnet start spooler");
    $valPeringatan = old('peringatan', $isEdit ? ($guide->peringatan ?? '') : 'Jangan reset spooler jika PC server cetak sedang aktif dipakai operasional.');
    $valStatus = old('status', $isEdit ? ($guide->status ?? 'Terbit') : 'Terbit');
    $valTag = old('tag', $isEdit ? ($guide->tag ?? '') : 'printer, offline, spooler');
    $valIzinOperator = old('perlu_izin_operator', $isEdit ? (bool) $guide->perlu_izin_operator : false);
    $valServiceId = old('service_id', $isEdit ? ($guide->service_id ?? '') : '');
    $initialSteps = old('langkah_langkah');
    if (!is_array($initialSteps)) {
        $initialSteps = $isEdit
            ? (!empty($guide->langkah_langkah) ? $guide->langkah_langkah : [''])
            : [
                'Pastikan printer menyala dan kabel terpasang kencang',
                'Hapus antrian cetak yang macet (clear print spooler queue)',
            ];
    }

    $deviceCategories = $deviceCategories ?? \App\Models\DeviceCategory::where('is_active', true)->orderBy('nama')->get();
    $masterLocations = $masterLocations ?? \App\Models\Location::where('is_active', true)->orderBy('nama')->get();

    $standardCategories = ['Jaringan & LAN', 'Hardware & PC', 'Sistem & Printer'];
    $knownCategories = array_unique(array_merge(
        $standardCategories,
        $deviceCategories->pluck('nama')->toArray()
    ));

    $specialAreas = [
        'Control Room dan kantor',
        'Area Boiler, Coal Handling, & Jetty',
        'Infrastruktur Jaringan Gigabit PLTU',
        'Subnet Internal PLTU Cilacap',
        'Workstation Kantor & Control Room',
        'Seluruh Area PLTU (Umum)'
    ];
    $knownAreas = array_unique(array_merge(
        $masterLocations->pluck('nama')->toArray(),
        $specialAreas
    ));
@endphp

@section('title', ($isEdit ? 'Edit Panduan SOP' : 'Tulis Panduan Baru') . ' - Basis Pengetahuan')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <!-- Breadcrumb Navigation -->
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('knowledge.base') }}" class="hover:text-teal-700 transition flex items-center space-x-1">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i>
                    <span>Basis Pengetahuan</span>
                </a>
                <span>&bull;</span>
                <span class="text-slate-600 font-semibold">{{ $isEdit ? 'Edit SOP' : 'Editor' }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                {{ $isEdit ? 'Edit Panduan SOP' : 'Tulis Panduan Baru' }}
            </h1>
        </div>

        <!-- Top Right Actions: Hapus (saat edit), Simpan Draft, Terbitkan, Tanggal & Notifikasi -->
        <div class="flex items-center space-x-2 sm:space-x-2.5 flex-shrink-0">
            @if($isEdit)
            <button type="button" onclick="if(confirm('Apakah Anda yakin ingin menghapus SOP ' + {{ Js::from($guide->judul) }} + '?')) document.getElementById('delete-sop-form').submit();"
                    class="px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 text-xs font-bold shadow-xs transition cursor-pointer flex-shrink-0 whitespace-nowrap flex items-center space-x-1.5">
                <i class="fa-regular fa-trash-can text-[10px]"></i>
                <span>Hapus</span>
            </button>
            @endif

            <!-- Tombol Kembali -->
            <a href="{{ route('knowledge.base') }}"
               class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs flex-shrink-0 whitespace-nowrap">
                Kembali
            </a>

            <!-- Tombol Simpan Draft -->
            <button type="button" onclick="submitWithStatus('Draft')" 
                    class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer flex-shrink-0 whitespace-nowrap">
                Simpan Draft
            </button>

            <!-- Tombol Terbitkan / Simpan Perubahan -->
            <button type="button" onclick="submitWithStatus('Terbit')" 
                    class="px-4 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold shadow-xs transition flex items-center space-x-1.5 cursor-pointer flex-shrink-0 whitespace-nowrap">
                <i class="fa-solid fa-paper-plane text-[10px]"></i>
                <span>{{ $isEdit ? 'Simpan & Terbitkan' : 'Terbitkan' }}</span>
            </button>

            <!-- Header Widget: Tanggal & Lonceng Notifikasi -->
            <div class="flex items-center space-x-2 sm:space-x-2.5 flex-shrink-0">
                @include('partials.header-widget')
            </div>
        </div>
    </div>

    <!-- Alert Notifikasi Error Jika Ada -->
    @if($errors->any())
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
        <div class="font-bold flex items-center space-x-1.5">
            <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
            <span>Terdapat beberapa isian yang perlu diperiksa:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-rose-700">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($isEdit)
    <form id="delete-sop-form" action="{{ route('knowledge.destroy', $guide) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
    @endif

    <!-- Form & Pratinjau Grid (2 Kolom: Kiri Form, Kanan Pratinjau Sesuai Mockup) -->
    <form action="{{ $isEdit ? route('knowledge.update', $guide) : route('knowledge.store') }}" method="POST" id="sop-form" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
        <input type="hidden" name="status" id="input-status" value="{{ $valStatus }}">
        <input type="hidden" name="kategori" id="input-kategori" value="{{ $valKategori }}">
        <input type="hidden" name="target_audience" id="input-audience" value="{{ $valAudience }}">
        <input type="hidden" name="service_id" id="input-service-id" value="{{ $valServiceId }}">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ============================================================== -->
            <!-- KOLOM KIRI (LEBAR 7/12): FORM INPUT UTAMA                       -->
            <!-- ============================================================== -->
            <div class="lg:col-span-7 space-y-6">

                <!-- KARTU 1: Informasi Dasar -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Informasi Dasar</h2>
                        <p class="text-[11px] text-slate-400">Identitas dan jangkauan sasaran panduan SOP</p>
                    </div>

                    <!-- Judul Panduan -->
                    <div>
                        <label for="form-judul" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Judul panduan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="judul" id="form-judul" required
                               value="{{ $valJudul }}"
                               placeholder="Contoh: SOP Printer Offline di Control Room"
                               oninput="updatePreview()"
                               class="w-full px-3.5 py-2.5 bg-slate-50/70 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-700/20 focus:border-teal-700 transition font-medium">
                    </div>

                    <!-- Kategori Dropdown -->
                    <div>
                        <label for="select-kategori-dropdown" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                            <span>Kategori SOP <span class="text-rose-500">*</span></span>
                            <span class="text-[11px] font-normal text-slate-400">Pilih kategori panduan</span>
                        </label>
                        <div class="relative">
                            <select id="select-kategori-dropdown" 
                                    onchange="handleKategoriChange(this.value)"
                                    class="w-full px-3.5 py-2.5 bg-slate-50/70 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-700/20 focus:border-teal-700 transition appearance-none pr-9">
                                <option value="" disabled {{ empty($valKategori) ? 'selected' : '' }}>-- Pilih Kategori SOP --</option>
                                <optgroup label="📂 Kategori Standar SOP">
                                    @foreach($standardCategories as $stdKat)
                                        <option value="{{ $stdKat }}" {{ $valKategori === $stdKat ? 'selected' : '' }}>{{ $stdKat }}</option>
                                    @endforeach
                                </optgroup>
                                @if(!empty($deviceCategories) && $deviceCategories->isNotEmpty())
                                <optgroup label="🖥️ Kategori Data Master Perangkat">
                                    @foreach($deviceCategories as $devCat)
                                        @if(!in_array($devCat->nama, $standardCategories))
                                            <option value="{{ $devCat->nama }}" {{ $valKategori === $devCat->nama ? 'selected' : '' }}>{{ $devCat->nama }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                                @endif
                                <option value="custom" {{ (!empty($valKategori) && !in_array($valKategori, $knownCategories)) ? 'selected' : '' }}>
                                    ➕ Ketik Kategori Lainnya (Ketik Manual)...
                                </option>
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-chevron-down text-[10px]"></i>
                            </span>
                        </div>

                        <!-- Input custom kategori (muncul jika pilih Ketik Manual) -->
                        <div id="custom-kategori-container" class="{{ (!empty($valKategori) && !in_array($valKategori, $knownCategories)) ? '' : 'hidden' }} pt-2">
                            <input type="text" id="input-custom-kategori" 
                                   placeholder="Ketik nama kategori baru..." 
                                   value="{{ (!empty($valKategori) && !in_array($valKategori, $knownCategories)) ? $valKategori : '' }}"
                                   oninput="handleCustomKategoriInput(this.value)"
                                   class="w-full px-3.5 py-2.5 bg-white border border-teal-600/50 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-700/20 focus:border-teal-700 transition font-medium">
                        </div>
                    </div>

                    <!-- Area Penerapan Dropdown -->
                    <div>
                        <label for="select-area-dropdown" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                            <span>Area penerapan</span>
                            <span class="text-[11px] font-normal text-slate-400">Lokasi / lingkup kerja SOP di PLTU</span>
                        </label>
                        <div class="relative">
                            <select id="select-area-dropdown" 
                                    onchange="handleAreaChange(this.value)"
                                    class="w-full px-3.5 py-2.5 bg-slate-50/70 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-700/20 focus:border-teal-700 transition appearance-none pr-9">
                                <option value="" {{ empty($valArea) ? 'selected' : '' }}>-- Pilih Area Penerapan --</option>
                                <option value="Seluruh Area PLTU (Umum)" {{ $valArea === 'Seluruh Area PLTU (Umum)' ? 'selected' : '' }}>🌐 Seluruh Area PLTU (Umum)</option>
                                
                                <optgroup label="🏢 Lokasi Kerja PLTU (Data Master)">
                                    @foreach($masterLocations as $loc)
                                        <option value="{{ $loc->nama }}" {{ $valArea === $loc->nama ? 'selected' : '' }}>
                                            {{ $loc->nama }}{{ !empty($loc->deskripsi) ? ' — ' . $loc->deskripsi : '' }}
                                        </option>
                                    @endforeach
                                </optgroup>

                                <optgroup label="🏭 Area Khusus Operasional">
                                    @foreach($specialAreas as $specArea)
                                        <option value="{{ $specArea }}" {{ $valArea === $specArea ? 'selected' : '' }}>{{ $specArea }}</option>
                                    @endforeach
                                </optgroup>

                                <option value="custom" {{ (!empty($valArea) && !in_array($valArea, $knownAreas)) ? 'selected' : '' }}>
                                    ➕ Ketik Area Lainnya (Ketik Manual)...
                                </option>
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-chevron-down text-[10px]"></i>
                            </span>
                        </div>

                        <!-- Hidden input area_penerapan untuk form submission & JavaScript preview -->
                        <input type="hidden" name="area_penerapan" id="form-area" value="{{ $valArea }}">

                        <!-- Input custom area (muncul jika pilih Ketik Manual) -->
                        <div id="custom-area-container" class="{{ (!empty($valArea) && !in_array($valArea, $knownAreas)) ? '' : 'hidden' }} pt-2">
                            <input type="text" id="input-custom-area" 
                                   placeholder="Contoh: Gedung Admin Lt. 2, Jetty Area, Workshop Listrik..." 
                                   value="{{ (!empty($valArea) && !in_array($valArea, $knownAreas)) ? $valArea : '' }}"
                                   oninput="handleCustomAreaInput(this.value)"
                                   class="w-full px-3.5 py-2.5 bg-white border border-teal-600/50 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-700/20 focus:border-teal-700 transition font-medium">
                        </div>
                    </div>

                    <!-- Tampilkan Untuk: Teknisi Saja / Teknisi dan Pegawai -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Tampilkan untuk
                        </label>
                        <div class="flex items-center space-x-2">
                            <button type="button" onclick="setAudience('teknisi')" id="btn-aud-teknisi" 
                                    class="aud-pill px-4 py-2 rounded-xl text-xs font-bold border transition bg-[#0e1a2b] text-white border-[#0e1a2b] shadow-xs flex items-center space-x-1.5">
                                <i class="fa-solid fa-lock text-[10px]"></i>
                                <span>Teknisi saja</span>
                            </button>
                            <button type="button" onclick="setAudience('all')" id="btn-aud-all" 
                                    class="aud-pill px-4 py-2 rounded-xl text-xs font-bold border transition bg-white border-slate-200 text-slate-600 hover:bg-slate-50 flex items-center space-x-1.5">
                                <i class="fa-solid fa-users text-[10px]"></i>
                                <span>Teknisi dan pegawai</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-teal-700 mt-1.5 font-medium flex items-center space-x-1">
                            <i class="fa-solid fa-circle-info text-[10px]"></i>
                            <span>Pilih <strong>"Teknisi dan pegawai"</strong> agar panduan ini otomatis muncul di menu <strong>Panduan Mandiri</strong> milik pegawai.</span>
                        </p>
                    </div>
                </div>

                <!-- KARTU 2: Isi Panduan (Urutan Blok & Langkah Dinamis) -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Isi Panduan</h2>
                        <p class="text-[11px] text-slate-400">Susun alur diagnosis, langkah perbaikan, dan script perintah penanganan</p>
                    </div>

                    <!-- Pengantar / Gejala -->
                    <div>
                        <label for="form-pengantar" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Pengantar / Gejala kendala
                        </label>
                        <textarea name="pengantar" id="form-pengantar" rows="2"
                                  placeholder="Jelaskan gejala kerusakan dan kapan SOP ini diterapkan..."
                                  oninput="updatePreview()"
                                  class="w-full px-3.5 py-2.5 bg-slate-50/70 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-700/20 focus:border-teal-700 transition leading-relaxed">{{ $valPengantar }}</textarea>
                    </div>

                    <!-- Langkah-Langkah (Dinamis: Tambah/Hapus Langkah) -->
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700">
                                Langkah-langkah penyelesaian
                            </label>
                            <button type="button" onclick="addStep()" class="text-xs text-teal-700 hover:text-teal-900 font-bold flex items-center space-x-1">
                                <i class="fa-solid fa-plus text-[10px]"></i>
                                <span>Tambah langkah</span>
                            </button>
                        </div>

                        <div id="steps-container" class="space-y-2">
                            @foreach($initialSteps as $idx => $stepText)
                            <div class="step-item flex items-center space-x-2.5 bg-slate-50/60 p-2 rounded-xl border border-slate-200/60">
                                <span class="step-num w-6 h-6 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs flex-shrink-0">{{ $idx + 1 }}</span>
                                <input type="text" name="langkah_langkah[]" 
                                       value="{{ $stepText }}"
                                       placeholder="Tulis langkah instruksi perbaikan..."
                                       oninput="updatePreview()"
                                       class="step-input flex-1 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700">
                                <button type="button" onclick="removeStep(this)" class="text-[11px] text-slate-400 hover:text-rose-600 px-1.5 py-1 cursor-pointer" title="Hapus langkah">
                                    Hapus
                                </button>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Perintah Script / Command Terminal (Opsional) -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700">
                            Perintah Terminal / Script CMD <span class="text-slate-400 font-normal">(opsional)</span>
                        </label>
                        <input type="text" name="judul_perintah" id="form-cmd-title"
                               value="{{ $valJudulPerintah }}"
                               placeholder="Judul perintah, misal: Restart Print Spooler"
                               oninput="updatePreview()"
                               class="w-full px-3.5 py-2 bg-slate-50/70 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 transition">

                        <div class="relative rounded-xl overflow-hidden border border-slate-800 bg-[#09121e]">
                            <div class="px-3.5 py-1.5 bg-[#0e1a2b] border-b border-slate-800 text-[11px] text-slate-400 flex items-center space-x-1.5">
                                <i class="fa-solid fa-terminal text-teal-400 text-[10px]"></i>
                                <span>Ketik script perintah per baris</span>
                            </div>
                            <textarea name="perintah_cmd" id="form-cmd" rows="2"
                                      placeholder="net stop spooler&#10;net start spooler"
                                      oninput="updatePreview()"
                                      class="w-full p-3 bg-transparent text-teal-300 font-mono text-xs focus:outline-none leading-relaxed resize-y">{{ $valPerintahCmd }}</textarea>
                        </div>
                    </div>

                    <!-- Catatan Peringatan / Safety Warning (Opsional) -->
                    <div class="space-y-1.5 pt-2 border-t border-slate-100">
                        <label for="form-warning" class="block text-xs font-bold text-slate-700">
                            Catatan Peringatan / K3 <span class="text-slate-400 font-normal">(opsional)</span>
                        </label>
                        <textarea name="peringatan" id="form-warning" rows="2"
                                  placeholder="Contoh: Jangan reset spooler jika PC server cetak terpakai operasional logsheet..."
                                  oninput="updatePreview()"
                                  class="w-full px-3.5 py-2 bg-amber-50/40 hover:bg-amber-50/60 focus:bg-white border border-amber-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-amber-500 transition leading-relaxed">{{ $valPeringatan }}</textarea>
                    </div>

                </div>

            </div>

            <!-- ============================================================== -->
            <!-- KOLOM KANAN (LEBAR 5/12): PRATINJAU LIVE & PENGATURAN           -->
            <!-- ============================================================== -->
            <div class="lg:col-span-5 space-y-6 lg:sticky lg:top-24">

                <!-- KARTU 1: Pratinjau Live Kartu SOP -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <div class="flex items-center space-x-1.5">
                            <i class="fa-regular fa-eye text-teal-700 text-xs"></i>
                            <h2 class="text-xs font-extrabold text-slate-900 tracking-tight">Pratinjau Kartu</h2>
                        </div>
                        <span class="text-[10px] text-slate-400 font-medium">Tampilan di halaman SOP</span>
                    </div>

                    <!-- Miniatur Kartu Hasil Pratinjau -->
                    <div class="p-4 rounded-xl border border-teal-500/30 bg-white shadow-xs space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-start space-x-2.5 min-w-0">
                                <div id="preview-icon" class="w-8 h-8 rounded-lg bg-purple-50 text-purple-700 border border-purple-100 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                    <i class="fa-solid fa-print"></i>
                                </div>
                                <div class="min-w-0">
                                    <div id="preview-judul" class="font-extrabold text-slate-900 text-xs leading-snug truncate">
                                        SOP Printer Offline di Control Room
                                    </div>
                                    <div id="preview-area" class="text-[10px] text-slate-400 font-medium truncate mt-0.5">
                                        Control Room dan kantor
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span id="preview-kategori-badge" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                    Sistem & Printer
                                </span>
                                <span id="preview-audience-badge" class="px-1.5 py-0.2 rounded text-[8px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    Teknisi Saja
                                </span>
                            </div>
                        </div>

                        <p id="preview-pengantar" class="text-[11px] text-slate-600 leading-relaxed font-normal">
                            Gejala dan kapan SOP ini dipakai: printer berstatus offline di Windows atau antrian cetak macet.
                        </p>

                        <!-- Preview Steps -->
                        <div id="preview-steps-container">
                            <ol id="preview-steps-list" class="list-decimal list-inside text-[11px] text-slate-700 space-y-1 font-normal">
                                <li>Pastikan printer menyala dan kabel terpasang kencang</li>
                                <li>Hapus antrian cetak yang macet (clear print spooler queue)</li>
                            </ol>
                        </div>

                        <!-- Preview Command Box -->
                        <div id="preview-cmd-box" class="rounded-lg overflow-hidden border border-slate-800 bg-[#09121e] text-white">
                            <div class="px-2.5 py-1 bg-[#0e1a2b] border-b border-slate-800 flex items-center justify-between text-[10px]">
                                <span id="preview-cmd-title" class="text-slate-300 font-mono">Restart Print Spooler</span>
                                <span class="text-[9px] bg-slate-800 px-1.5 py-0.5 rounded text-slate-300">Salin</span>
                            </div>
                            <pre class="p-2 font-mono text-[10px] text-teal-300"><code id="preview-cmd-code">net stop spooler
net start spooler</code></pre>
                        </div>

                        <!-- Preview Warning Box -->
                        <div id="preview-warning-box" class="text-[10px] text-amber-900 bg-amber-50/80 p-2.5 rounded-lg border border-amber-200 flex items-start space-x-1.5">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600 text-[10px] mt-0.5 flex-shrink-0"></i>
                            <div id="preview-warning-text" class="leading-relaxed">
                                Jangan reset spooler jika PC server cetak sedang aktif dipakai operasional.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KARTU 2: Pengaturan SOP & Muat Dari Tiket Selesai -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                    <div class="border-b border-slate-100 pb-2">
                        <h2 class="text-xs font-extrabold text-slate-900 tracking-tight">Pengaturan</h2>
                        <p class="text-[10px] text-slate-400">Status publikasi dan integrasi tiket selesai</p>
                    </div>

                    <!-- Status Dropdown -->
                    <div>
                        <label for="select-status" class="block text-xs font-bold text-slate-700 mb-1">
                            Status
                        </label>
                        <select id="select-status" onchange="syncStatus(this.value)"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 font-semibold text-slate-700">
                            <option value="Terbit" {{ $valStatus === 'Terbit' ? 'selected' : '' }}>Terbitkan Sekarang (Terbit)</option>
                            <option value="Draft" {{ $valStatus === 'Draft' ? 'selected' : '' }}>Simpan Sebagai Draft</option>
                        </select>
                    </div>

                    <!-- Tag Input -->
                    <div>
                        <label for="form-tag" class="block text-xs font-bold text-slate-700 mb-1">
                            Tag / Kata Kunci
                        </label>
                        <input type="text" name="tag" id="form-tag"
                               value="{{ $valTag }}"
                               placeholder="Contoh: printer, offline, spooler"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-teal-700">
                    </div>

                    <!-- Checkbox: Perlu Izin Operator -->
                    <div class="pt-1">
                        <label class="flex items-center space-x-2 text-xs font-medium text-slate-700 cursor-pointer">
                            <input type="checkbox" name="perlu_izin_operator" value="1" {{ $valIzinOperator ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-teal-700 focus:ring-teal-600 border-slate-300">
                            <span>Perlu izin operator sebelum dikerjakan</span>
                        </label>
                    </div>

                    <!-- Fitur Unggulan: Muat Dari Tiket Selesai -->
                    <div class="pt-3 border-t border-slate-100 space-y-1.5">
                        <label for="select-ticket" class="block text-xs font-bold text-teal-800 flex items-center space-x-1.5">
                            <i class="fa-solid fa-wand-magic-sparkles text-teal-600"></i>
                            <span>Muat dari tiket selesai</span>
                        </label>
                        <select id="select-ticket" onchange="loadFromTicket(this)"
                                class="w-full px-3 py-2 bg-teal-50/60 border border-teal-200 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 font-medium text-slate-800">
                            <option value="">-- Pilih tiket yang telah selesai --</option>
                            @foreach($finishedTickets as $ticket)
                                <option value="{{ $ticket->id }}"
                                        data-number="{{ $ticket->ticket_number }}"
                                        data-judul="{{ $ticket->display_judul }}"
                                        data-perangkat="{{ $ticket->jenis_perangkat }}"
                                        data-lokasi="{{ $ticket->lokasi_ruangan }}"
                                        data-divisi="{{ $ticket->divisi }}"
                                        data-keluhan="{{ $ticket->keluhan }}"
                                        data-tindakan="{{ $ticket->tindakan_perbaikan }}">
                                    {{ $ticket->ticket_number }} &bull; {{ $ticket->display_judul }} ({{ $ticket->divisi }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 leading-normal">
                            Langkah dan tindakan otomatis terisi dari catatan laporan teknisi pada tiket tersebut.
                        </p>
                    </div>

                </div>

            </div>

        </div>
    </form>

</div>

@push('scripts')
<script>
    let currentKategori = 'Sistem & Printer';
    let currentAudience = 'teknisi';

    function setKategori(kat) {
        currentKategori = kat || 'Sistem & Printer';
        document.getElementById('input-kategori').value = currentKategori;

        const select = document.getElementById('select-kategori-dropdown');
        const customContainer = document.getElementById('custom-kategori-container');
        const customInput = document.getElementById('input-custom-kategori');

        if (select) {
            let optionExists = false;
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value === currentKategori && select.options[i].value !== 'custom') {
                    optionExists = true;
                    break;
                }
            }

            if (optionExists) {
                select.value = currentKategori;
                if (customContainer) customContainer.classList.add('hidden');
            } else {
                select.value = 'custom';
                if (customContainer) customContainer.classList.remove('hidden');
                if (customInput) customInput.value = currentKategori;
            }
        }

        updatePreview();
    }

    function handleKategoriChange(val) {
        const customContainer = document.getElementById('custom-kategori-container');
        const customInput = document.getElementById('input-custom-kategori');

        if (val === 'custom') {
            if (customContainer) customContainer.classList.remove('hidden');
            if (customInput) {
                customInput.focus();
                currentKategori = customInput.value.trim() || 'Kategori Lainnya';
            } else {
                currentKategori = 'Kategori Lainnya';
            }
        } else {
            if (customContainer) customContainer.classList.add('hidden');
            currentKategori = val;
        }

        document.getElementById('input-kategori').value = currentKategori;
        updatePreview();
    }

    function handleCustomKategoriInput(val) {
        currentKategori = val.trim() || 'Kategori Lainnya';
        document.getElementById('input-kategori').value = val.trim();
        updatePreview();
    }

    function setArea(area) {
        const formArea = document.getElementById('form-area');
        if (formArea) formArea.value = area;

        const select = document.getElementById('select-area-dropdown');
        const customContainer = document.getElementById('custom-area-container');
        const customInput = document.getElementById('input-custom-area');

        if (select) {
            let optionExists = false;
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value === area && select.options[i].value !== 'custom') {
                    optionExists = true;
                    break;
                }
            }

            if (optionExists) {
                select.value = area;
                if (customContainer) customContainer.classList.add('hidden');
            } else if (area) {
                select.value = 'custom';
                if (customContainer) customContainer.classList.remove('hidden');
                if (customInput) customInput.value = area;
            } else {
                select.value = '';
                if (customContainer) customContainer.classList.add('hidden');
            }
        }

        updatePreview();
    }

    function handleAreaChange(val) {
        const customContainer = document.getElementById('custom-area-container');
        const customInput = document.getElementById('input-custom-area');
        const formArea = document.getElementById('form-area');

        if (val === 'custom') {
            if (customContainer) customContainer.classList.remove('hidden');
            if (customInput) {
                customInput.focus();
                if (formArea) formArea.value = customInput.value.trim();
            }
        } else {
            if (customContainer) customContainer.classList.add('hidden');
            if (formArea) formArea.value = val;
        }

        updatePreview();
    }

    function handleCustomAreaInput(val) {
        const formArea = document.getElementById('form-area');
        if (formArea) formArea.value = val;
        updatePreview();
    }

    function setAudience(aud) {
        currentAudience = aud;
        document.getElementById('input-audience').value = aud;

        document.querySelectorAll('.aud-pill').forEach(btn => {
            btn.className = 'aud-pill px-4 py-2 rounded-xl text-xs font-bold border transition bg-white border-slate-200 text-slate-600 hover:bg-slate-50 flex items-center space-x-1.5';
        });

        if (aud === 'teknisi') {
            document.getElementById('btn-aud-teknisi').className = 'aud-pill px-4 py-2 rounded-xl text-xs font-bold border transition bg-[#0e1a2b] text-white border-[#0e1a2b] shadow-xs flex items-center space-x-1.5';
        } else {
            document.getElementById('btn-aud-all').className = 'aud-pill px-4 py-2 rounded-xl text-xs font-bold border transition bg-[#0e1a2b] text-white border-[#0e1a2b] shadow-xs flex items-center space-x-1.5';
        }

        updatePreview();
    }

    function syncStatus(val) {
        document.getElementById('input-status').value = val;
    }

    function submitWithStatus(statusVal) {
        document.getElementById('input-status').value = statusVal;
        document.getElementById('select-status').value = statusVal;
        document.getElementById('sop-form').submit();
    }

    function addStep(initialText = '') {
        const container = document.getElementById('steps-container');
        const count = container.querySelectorAll('.step-item').length + 1;

        const div = document.createElement('div');
        div.className = 'step-item flex items-center space-x-2.5 bg-slate-50/60 p-2 rounded-xl border border-slate-200/60';
        div.innerHTML = `
            <span class="step-num w-6 h-6 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs flex-shrink-0">${count}</span>
            <input type="text" name="langkah_langkah[]" 
                   placeholder="Tulis langkah instruksi perbaikan..."
                   oninput="updatePreview()"
                   class="step-input flex-1 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700">
            <button type="button" onclick="removeStep(this)" class="text-[11px] text-slate-400 hover:text-rose-600 px-1.5 py-1 cursor-pointer" title="Hapus langkah">
                Hapus
            </button>
        `;
        div.querySelector('.step-input').value = initialText;
        container.appendChild(div);
        updateStepNumbers();
        updatePreview();
    }

    function removeStep(btn) {
        const item = btn.closest('.step-item');
        if (item) {
            item.remove();
            updateStepNumbers();
            updatePreview();
        }
    }

    function updateStepNumbers() {
        const items = document.querySelectorAll('#steps-container .step-item');
        items.forEach((item, index) => {
            const num = item.querySelector('.step-num');
            if (num) num.textContent = index + 1;
        });
    }

    function updatePreview() {
        const judul = document.getElementById('form-judul').value.trim() || 'Judul SOP Panduan Baru';
        const area = (document.getElementById('form-area')?.value || '').trim() || 'Area penerapan belum diisi';
        const pengantar = document.getElementById('form-pengantar').value.trim();
        const cmdTitle = document.getElementById('form-cmd-title').value.trim() || 'Perintah Terminal';
        const cmdCode = document.getElementById('form-cmd').value.trim();
        const warning = document.getElementById('form-warning').value.trim();

        // Update Text Elements
        document.getElementById('preview-judul').textContent = judul;
        document.getElementById('preview-area').textContent = area;
        document.getElementById('preview-pengantar').textContent = pengantar;

        // Update Badges & Icon
        const badgeKat = document.getElementById('preview-kategori-badge');
        const iconDiv = document.getElementById('preview-icon');
        badgeKat.textContent = currentKategori;

        const katLower = currentKategori.toLowerCase();
        if (katLower.includes('jaringan') || katLower.includes('lan') || katLower.includes('kabel') || katLower.includes('network') || katLower.includes('cctv')) {
            badgeKat.className = 'px-2 py-0.5 rounded-full text-[9px] font-bold bg-teal-50 text-teal-700 border border-teal-200';
            iconDiv.className = 'w-8 h-8 rounded-lg bg-teal-50 text-teal-700 border border-teal-100 flex items-center justify-center font-bold text-xs flex-shrink-0';
            iconDiv.innerHTML = '<i class="fa-solid fa-network-wired"></i>';
        } else if (katLower.includes('hardware') || katLower.includes('pc') || katLower.includes('komputer') || katLower.includes('laptop') || katLower.includes('server')) {
            badgeKat.className = 'px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200';
            iconDiv.className = 'w-8 h-8 rounded-lg bg-amber-50 text-amber-700 border border-amber-100 flex items-center justify-center font-bold text-xs flex-shrink-0';
            iconDiv.innerHTML = '<i class="fa-solid fa-microchip"></i>';
        } else if (katLower.includes('printer')) {
            badgeKat.className = 'px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-50 text-purple-700 border border-purple-200';
            iconDiv.className = 'w-8 h-8 rounded-lg bg-purple-50 text-purple-700 border border-purple-100 flex items-center justify-center font-bold text-xs flex-shrink-0';
            iconDiv.innerHTML = '<i class="fa-solid fa-print"></i>';
        } else {
            badgeKat.className = 'px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200';
            iconDiv.className = 'w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100 flex items-center justify-center font-bold text-xs flex-shrink-0';
            iconDiv.innerHTML = '<i class="fa-solid fa-layer-group"></i>';
        }

        const badgeAud = document.getElementById('preview-audience-badge');
        if (currentAudience === 'all') {
            badgeAud.className = 'px-1.5 py-0.2 rounded text-[8px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
            badgeAud.textContent = 'Teknisi & Pegawai';
        } else {
            badgeAud.className = 'px-1.5 py-0.2 rounded text-[8px] font-bold bg-slate-100 text-slate-600 border border-slate-200';
            badgeAud.textContent = 'Teknisi Saja';
        }

        // Update Steps List
        const list = document.getElementById('preview-steps-list');
        list.innerHTML = '';
        const stepInputs = document.querySelectorAll('#steps-container .step-input');
        stepInputs.forEach(input => {
            const val = input.value.trim();
            if (val) {
                const li = document.createElement('li');
                li.textContent = val;
                list.appendChild(li);
            }
        });

        // Update CMD box
        const cmdBox = document.getElementById('preview-cmd-box');
        if (cmdCode) {
            cmdBox.classList.remove('hidden');
            document.getElementById('preview-cmd-title').textContent = cmdTitle;
            document.getElementById('preview-cmd-code').textContent = cmdCode;
        } else {
            cmdBox.classList.add('hidden');
        }

        // Update Warning box
        const warnBox = document.getElementById('preview-warning-box');
        if (warning) {
            warnBox.classList.remove('hidden');
            document.getElementById('preview-warning-text').textContent = warning;
        } else {
            warnBox.classList.add('hidden');
        }
    }

    function loadFromTicket(select) {
        const option = select.options[select.selectedIndex];
        if (!option || !option.value) return;

        const ticketId = option.value;
        const number = option.getAttribute('data-number');
        const judul = option.getAttribute('data-judul');
        const perangkat = option.getAttribute('data-perangkat');
        const lokasi = option.getAttribute('data-lokasi');
        const divisi = option.getAttribute('data-divisi');
        const keluhan = option.getAttribute('data-keluhan');
        const tindakan = option.getAttribute('data-tindakan');

        // Set Hidden Service ID
        document.getElementById('input-service-id').value = ticketId;

        // Auto-fill Judul
        document.getElementById('form-judul').value = 'SOP Penanganan: ' + judul;

        // Auto-fill Area via setArea
        setArea(lokasi + (divisi ? ' (' + divisi + ')' : ''));

        // Auto-fill Pengantar
        document.getElementById('form-pengantar').value = 'Gejala kendala: ' + keluhan;

        // Auto-detect Category
        if (perangkat.toLowerCase().includes('printer')) {
            setKategori('Sistem & Printer');
            document.getElementById('form-cmd-title').value = 'Restart Print Spooler';
            document.getElementById('form-cmd').value = "net stop spooler\nnet start spooler";
        } else if (perangkat.toLowerCase().includes('jaringan') || perangkat.toLowerCase().includes('kabel') || perangkat.toLowerCase().includes('cctv')) {
            setKategori('Jaringan & LAN');
            document.getElementById('form-cmd-title').value = 'Uji Ping & Cek IP Gateway';
            document.getElementById('form-cmd').value = "ping 192.168.1.1 -t\nipconfig /all";
        } else {
            setKategori('Hardware & PC');
            document.getElementById('form-cmd-title').value = 'Cek Kondisi Sistem';
            document.getElementById('form-cmd').value = "sfc /scannow";
        }

        // Clear and reload steps from tindakan
        const container = document.getElementById('steps-container');
        container.innerHTML = '';
        if (tindakan) {
            addStep('Identifikasi fisik perangkat di ' + lokasi);
            addStep(tindakan);
            addStep('Uji fungsi kembali bersama operator ruangan dan pastikan operasional normal');
        } else {
            addStep('Pemeriksaan koneksi kabel dan daya');
            addStep('Pengecekan driver dan software');
        }

        // Tag
        document.getElementById('form-tag').value = perangkat.toLowerCase() + ', ' + number.toLowerCase();

        updatePreview();
    }

    // Initialize pill state & preview on page load
    document.addEventListener('DOMContentLoaded', function() {
        setKategori(document.getElementById('input-kategori').value || 'Sistem & Printer');
        setArea(document.getElementById('form-area')?.value || 'Control Room dan kantor');
        setAudience(document.getElementById('input-audience').value || 'teknisi');
        syncStatus(document.getElementById('select-status').value || 'Terbit');
    });
</script>
@endpush
@endsection

