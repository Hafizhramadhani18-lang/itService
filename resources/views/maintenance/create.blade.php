@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Flash Error Notification -->
    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-xs animate-fade-in">
            <div class="font-bold flex items-center space-x-2 mb-1">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Terdapat kesalahan pada input:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-rose-700">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Top Header Sesuai Mockup Image 2 -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                Catat Pemeliharaan <span class="sr-only">Catat Log Pemeliharaan Rutin</span>
            </h1>
            <nav class="flex items-center space-x-1.5 text-xs text-slate-400 mt-1">
                <a href="{{ route('maintenance.index') }}" class="hover:text-teal-700 transition">Log Pemeliharaan</a>
                <span>&rsaquo;</span>
                <span class="text-slate-600 font-semibold">Catatan baru</span>
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

    <!-- Form Body (2 Kolom: Kiri Form Input, Kanan Ringkasan) -->
    <form method="POST" action="{{ route('maintenance.store') }}" enctype="multipart/form-data" id="form-catat-pm">
        @csrf
        <input type="hidden" name="sumber" id="input_sumber" value="dari_jadwal">
        <input type="hidden" name="jenis_pemeliharaan" id="input_jenis_pemeliharaan" value="Rutin">
        <input type="hidden" name="kondisi" id="input_kondisi" value="Prima">
        <input type="hidden" name="scheduled_log_id" id="input_scheduled_log_id" value="{{ $selectedSchedule?->id }}">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <!-- KOLOM KIRI: DETAIL PELAKSANAAN & TEMUAN (2 SPAN) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- KARTU 1: ASET DAN JADWAL -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Aset dan jadwal</h2>
                    </div>

                    <!-- Pilihan Sumber: Dari Jadwal vs Catatan Baru -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Sumber</label>
                        <div class="flex items-center space-x-2">
                            <button type="button" onclick="setSumber('dari_jadwal')" id="btn-sumber-jadwal"
                                    class="px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs">
                                Dari jadwal
                            </button>
                            <button type="button" onclick="setSumber('catatan_baru')" id="btn-sumber-baru"
                                    class="px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-700 hover:bg-slate-200">
                                Catatan baru
                            </button>
                        </div>
                    </div>

                    <!-- Dropdown Jadwal (Aktif bila Sumber = Dari Jadwal) -->
                    <div id="container-pilih-jadwal">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Jadwal</label>
                        <select name="from_schedule" id="select_schedule" onchange="onScheduleChanged(this)"
                                class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                            <option value="">-- Pilih Jadwal Pemeliharaan --</option>
                            @foreach($scheduledTasks as $task)
                                @php
                                    $isSel = $selectedSchedule && $selectedSchedule->id == $task->id;
                                    $tglStr = $task->tanggal_cek ? $task->tanggal_cek->format('d/m/Y') : '-';
                                    $jamLabel = $task->jam_mulai ? ' ' . str_replace(':', '.', substr($task->jam_mulai, 0, 5)) . ' WIB' : '';
                                    $statusStr = $task->isTerlambat() ? 'terlambat' : 'terjadwal';
                                    $taskChecklist = is_array($task->checklist) ? array_values(array_filter(array_map(fn($c) => is_array($c) ? ($c['item'] ?? null) : (is_string($c) ? $c : null), $task->checklist))) : [];
                                @endphp
                                <option value="{{ $task->id }}" {{ $isSel ? 'selected' : '' }}
                                        data-nama="{{ $task->nama_perangkat }}"
                                        data-kode="{{ $task->kode_perangkat }}"
                                        data-lokasi="{{ $task->lokasi_ruangan }}"
                                        data-divisi="{{ $task->divisi }}"
                                        data-tim="{{ $task->tim_label }}"
                                        data-kegiatan="{{ $task->display_kegiatan }}"
                                        data-jenis="{{ $task->jenis_perangkat }}"
                                        data-frekuensi="{{ $task->frekuensi_label }}"
                                        data-jam="{{ $task->jam_mulai ? substr(str_replace('.', ':', $task->jam_mulai), 0, 5) : '' }}"
                                        data-is-late="{{ $task->isTerlambat() ? '1' : '0' }}"
                                        data-checklist='@json($taskChecklist)'
                                        data-tanggal="{{ $task->tanggal_cek ? $task->tanggal_cek->format('Y-m-d') : '' }}">
                                    {{ $task->nama_perangkat }} · {{ $task->lokasi_ruangan }} · {{ $statusStr }} {{ $tglStr }}{{ $jamLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Form Input Manual Aset (Aktif bila Sumber = Catatan Baru) -->
                    <div id="container-aset-manual" class="hidden space-y-4 pt-3 border-t border-slate-100">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Perangkat <span class="text-rose-500">*</span></label>
                                <input type="text" name="nama_perangkat" id="input_nama_perangkat" value="{{ old('nama_perangkat', $selectedSchedule?->nama_perangkat ?? 'Server NVR CCTV') }}" 
                                       placeholder="Contoh: Server NVR CCTV"
                                       class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kode / No. Aset</label>
                                <input type="text" name="kode_perangkat" id="input_kode_perangkat" value="{{ old('kode_perangkat', $selectedSchedule?->kode_perangkat ?? 'AST-NVR-01') }}" 
                                       placeholder="Contoh: CCTV-POS-01"
                                       class="w-full text-xs font-mono font-semibold uppercase rounded-xl border border-slate-200 px-3.5 py-2 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kategori Perangkat</label>
                                <select name="jenis_perangkat" id="select_jenis_perangkat" onchange="loadChecklistByCategory(this.value)"
                                        class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3 py-2 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                                    <option value="Kabel LAN / Jaringan">Switch / Jaringan / CCTV</option>
                                    <option value="Komputer PC">Komputer / Workstation PC</option>
                                    <option value="Printer">Printer & Scanner</option>
                                    <option value="Server / UPS">Server & Power UPS</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Lokasi Ruangan <span class="text-rose-500">*</span></label>
                                <input type="text" name="lokasi_ruangan" id="input_lokasi_ruangan" value="{{ old('lokasi_ruangan', $selectedSchedule?->lokasi_ruangan ?? 'Pos Utama Security') }}" 
                                       placeholder="Contoh: Pos Utama Security"
                                       class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3 py-2 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Divisi PLTU</label>
                                <input type="text" name="divisi" id="input_divisi" value="{{ old('divisi', $selectedSchedule?->divisi ?? 'Operasi Keamanan') }}" 
                                       placeholder="Contoh: Operasi Keamanan"
                                       class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3 py-2 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KARTU 2: PELAKSANAAN -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Pelaksanaan</h2>
                    </div>

                    <!-- Tanggal, Jam Mulai, Jam Selesai -->
                    @php
                        $defaultExecDate = ($selectedSchedule && $selectedSchedule->tanggal_cek && !$selectedSchedule->isTerlambat())
                            ? $selectedSchedule->tanggal_cek->format('Y-m-d')
                            : now()->format('Y-m-d');
                        $defaultJamMulai = ($selectedSchedule && $selectedSchedule->jam_mulai)
                            ? substr(str_replace('.', ':', $selectedSchedule->jam_mulai), 0, 5)
                            : now()->format('H:i');
                        try {
                            $defaultJamSelesai = \Carbon\Carbon::createFromFormat('H:i', $defaultJamMulai)->addHour()->format('H:i');
                        } catch (\Exception $e) {
                            $defaultJamSelesai = now()->copy()->addHour()->format('H:i');
                        }
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal</label>
                            <input type="date" name="tanggal_cek" id="input_tanggal_cek" 
                                   value="{{ old('tanggal_cek', $defaultExecDate) }}" 
                                   onchange="recalculateNextSchedule(this.value)" required
                                   class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Jam mulai</label>
                            <input type="time" name="jam_mulai" id="input_jam_mulai" 
                                   value="{{ old('jam_mulai', $defaultJamMulai) }}" onchange="updateDuration()"
                                   class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Jam selesai</label>
                            <input type="time" name="jam_selesai" id="input_jam_selesai" 
                                   value="{{ old('jam_selesai', $defaultJamSelesai) }}" onchange="updateDuration()"
                                   class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                        </div>
                    </div>

                    <!-- Jenis Pemeliharaan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Jenis pemeliharaan</label>
                        <div class="flex flex-wrap items-center gap-2" id="group-jenis-pemeliharaan">
                            <button type="button" onclick="setJenisPemeliharaan('Rutin')" id="btn-jenis-rutin"
                                    class="px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs">
                                Rutin
                            </button>
                            <button type="button" onclick="setJenisPemeliharaan('Perbaikan ringan')" id="btn-jenis-ringan"
                                    class="px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-700 hover:bg-slate-200">
                                Perbaikan ringan
                            </button>
                            <button type="button" onclick="setJenisPemeliharaan('Penggantian komponen')" id="btn-jenis-ganti"
                                    class="px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-700 hover:bg-slate-200">
                                Penggantian komponen
                            </button>
                        </div>
                    </div>

                    <!-- Checklist Kegiatan -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold text-slate-700">Checklist kegiatan <span class="sr-only">Checklist Pengecekan Rutin</span></label>
                            <button type="button" onclick="addCustomChecklistItem()" class="text-[11px] font-bold text-teal-700 hover:underline">
                                + Tambah item
                            </button>
                        </div>
                        <div id="checklist_container" class="space-y-2.5">
                            <!-- Item 1 -->
                            <label class="flex items-center space-x-3 p-2.5 rounded-xl bg-slate-50/80 border border-slate-200 hover:bg-slate-100/70 transition cursor-pointer">
                                <input type="checkbox" name="checklist[0][checked]" value="1" checked 
                                       class="w-4 h-4 rounded text-[#0f766e] focus:ring-[#0f766e] border-slate-300">
                                <input type="hidden" name="checklist[0][item]" value="Cek hard disk dan kapasitas rekaman">
                                <span class="text-xs font-medium text-slate-800">Cek hard disk dan kapasitas rekaman</span>
                            </label>
                            <!-- Item 2 -->
                            <label class="flex items-center space-x-3 p-2.5 rounded-xl bg-slate-50/80 border border-slate-200 hover:bg-slate-100/70 transition cursor-pointer">
                                <input type="checkbox" name="checklist[1][checked]" value="1" checked 
                                       class="w-4 h-4 rounded text-[#0f766e] focus:ring-[#0f766e] border-slate-300">
                                <input type="hidden" name="checklist[1][item]" value="Cek suhu dan kipas">
                                <span class="text-xs font-medium text-slate-800">Cek suhu dan kipas</span>
                            </label>
                            <!-- Item 3 -->
                            <label class="flex items-center space-x-3 p-2.5 rounded-xl bg-slate-50/80 border border-slate-200 hover:bg-slate-100/70 transition cursor-pointer">
                                <input type="checkbox" name="checklist[2][checked]" value="1" 
                                       class="w-4 h-4 rounded text-[#0f766e] focus:ring-[#0f766e] border-slate-300">
                                <input type="hidden" name="checklist[2][item]" value="Bersihkan debu">
                                <span class="text-xs font-medium text-slate-800">Bersihkan debu</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- KARTU 3: HASIL -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Hasil</h2>
                    </div>

                    <!-- Kondisi Setelah Pemeliharaan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Kondisi setelah pemeliharaan</label>
                        <div class="flex flex-wrap items-center gap-2" id="group-kondisi">
                            <button type="button" onclick="setKondisi('Baik', 'Prima')" id="btn-kondisi-baik"
                                    class="px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs">
                                Baik
                            </button>
                            <button type="button" onclick="setKondisi('Perlu tindak lanjut', 'Perlu Perhatian')" id="btn-kondisi-tindak"
                                    class="px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-700 hover:bg-slate-200">
                                Perlu tindak lanjut
                            </button>
                            <button type="button" onclick="setKondisi('Rusak', 'Kritis')" id="btn-kondisi-rusak"
                                    class="px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-700 hover:bg-slate-200">
                                Rusak
                            </button>
                        </div>
                    </div>

                    <!-- Catatan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan</label>
                        <textarea name="tindakan" id="input_tindakan" rows="3"
                                  placeholder="Tulis temuan atau tindakan tambahan"
                                  class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition leading-relaxed shadow-2xs">{{ old('tindakan') }}</textarea>
                    </div>

                    <!-- Checkbox Buat Tiket Tindak Lanjut -->
                    <div class="pt-1">
                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input type="checkbox" name="buat_tiket_servis" id="check_buat_tiket" value="1"
                                   class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300">
                            <span class="text-xs font-medium text-slate-700">
                                Buat tiket tindak lanjut otomatis (jika kondisi Perlu tindak lanjut atau Rusak)
                            </span>
                        </label>
                    </div>

                    <!-- Upload Foto Sebelum & Sesudah -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <!-- Dropzone Foto Sebelum -->
                        <div class="border-2 border-dashed border-slate-200 hover:border-teal-500/70 rounded-2xl p-5 text-center transition cursor-pointer relative bg-slate-50/50 hover:bg-slate-50"
                             onclick="document.getElementById('input_foto_sebelum').click()">
                            <input type="file" name="foto_sebelum" id="input_foto_sebelum" accept="image/*" class="hidden" onchange="previewUpload(this, 'label_foto_sebelum')">
                            <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-sm">
                                <i class="fa-regular fa-image"></i>
                            </div>
                            <span id="label_foto_sebelum" class="text-xs font-bold text-slate-600 block">Unggah foto sebelum</span>
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Format JPG, PNG (maks. 5MB)</span>
                        </div>

                        <!-- Dropzone Foto Sesudah -->
                        <div class="border-2 border-dashed border-slate-200 hover:border-teal-500/70 rounded-2xl p-5 text-center transition cursor-pointer relative bg-slate-50/50 hover:bg-slate-50"
                             onclick="document.getElementById('input_foto_sesudah').click()">
                            <input type="file" name="foto_sesudah" id="input_foto_sesudah" accept="image/*" class="hidden" onchange="previewUpload(this, 'label_foto_sesudah')">
                            <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-sm">
                                <i class="fa-regular fa-image"></i>
                            </div>
                            <span id="label_foto_sesudah" class="text-xs font-bold text-slate-600 block">Unggah foto sesudah</span>
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Format JPG, PNG (maks. 5MB)</span>
                        </div>
                    </div>

                </div>

            </div>

            <!-- KOLOM KANAN: RINGKASAN & SUBMIT BUTTON (1 SPAN) -->
            <div class="lg:col-span-1 space-y-4 lg:sticky lg:top-6">
                
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">Ringkasan</h2>
                    </div>

                    <!-- Baris Info Ringkasan -->
                    <div class="space-y-4 text-xs">
                        
                        <!-- Teknisi -->
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">Teknisi</span>
                            <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200/70">
                                <i class="fa-regular fa-user text-[10px] text-teal-600"></i>
                                <span>{{ Auth::user()->name }}</span>
                            </div>
                        </div>

                        <!-- Tim -->
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">Tim</span>
                            <span id="summary-tim" class="font-bold text-slate-800">
                                {{ $selectedSchedule?->tim_label ?: (Auth::user()->divisi ?: 'Tim IT Lapangan') }}
                            </span>
                        </div>

                        <!-- Durasi -->
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">Durasi</span>
                            <span id="summary-durasi" class="font-bold text-slate-800">1 jam</span>
                        </div>

                        <!-- Jadwal Berikutnya -->
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-medium">Jadwal berikutnya</span>
                                <span id="summary-jadwal-berikutnya" class="font-bold text-slate-800">
                                    {{ date('d/m/Y', strtotime('+1 month')) }}
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1 leading-snug" id="summary-jadwal-note">
                                Dihitung otomatis dari jadwal rutin.
                            </p>
                        </div>

                    </div>

                    <!-- Tombol Aksi Submit -->
                    <div class="pt-4 border-t border-slate-100 space-y-2.5">
                        <button type="submit" name="submit_action" value="save"
                                class="w-full py-2.5 px-4 rounded-xl bg-[#0f766e] hover:bg-[#115e59] text-white text-xs font-extrabold transition shadow-xs cursor-pointer text-center flex items-center justify-center space-x-1.5">
                            <i class="fa-solid fa-check text-[11px]"></i>
                            <span>Simpan Log</span>
                        </button>

                        <button type="submit" name="submit_action" value="draft"
                                class="w-full py-2.5 px-4 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer text-center">
                            Simpan Draft
                        </button>
                    </div>

                </div>

            </div>

        </div>
    </form>

</div>

@push('scripts')
<script>
    function setSumber(mode) {
        document.getElementById('input_sumber').value = mode;
        const btnJadwal = document.getElementById('btn-sumber-jadwal');
        const btnBaru = document.getElementById('btn-sumber-baru');
        const cJadwal = document.getElementById('container-pilih-jadwal');
        const cManual = document.getElementById('container-aset-manual');

        if (mode === 'dari_jadwal') {
            btnJadwal.className = "px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs";
            btnBaru.className = "px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-700 hover:bg-slate-200";
            cJadwal.classList.remove('hidden');
            cManual.classList.add('hidden');
            const sel = document.getElementById('select_schedule');
            if (sel && sel.value) {
                onScheduleChanged(sel);
            }
        } else {
            btnBaru.className = "px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs";
            btnJadwal.className = "px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-700 hover:bg-slate-200";
            cJadwal.classList.add('hidden');
            cManual.classList.remove('hidden');
            document.getElementById('input_scheduled_log_id').value = '';
            const sel = document.getElementById('select_schedule');
            if (sel) sel.value = '';
        }
    }

    let currentScheduleFreq = '{{ $selectedSchedule?->frekuensi_label ?? "Setiap bulan" }}';

    function onScheduleChanged(sel) {
        const opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) return;

        currentScheduleFreq = opt.getAttribute('data-frekuensi') || 'Setiap bulan';
        const nama = opt.getAttribute('data-nama') || '';
        const kode = opt.getAttribute('data-kode') || '';
        const lokasi = opt.getAttribute('data-lokasi') || '';
        const divisi = opt.getAttribute('data-divisi') || '';
        const tim = opt.getAttribute('data-tim') || '';
        const tgl = opt.getAttribute('data-tanggal') || '';
        const jenis = opt.getAttribute('data-jenis') || 'Komputer PC';
        const jam = opt.getAttribute('data-jam') || '';
        const isLate = opt.getAttribute('data-is-late') === '1';
        let scheduledChecklist = [];
        try {
            scheduledChecklist = JSON.parse(opt.getAttribute('data-checklist') || '[]');
        } catch (e) {
            scheduledChecklist = [];
        }

        document.getElementById('input_scheduled_log_id').value = opt.value;
        document.getElementById('input_nama_perangkat').value = nama;
        document.getElementById('input_kode_perangkat').value = kode;
        document.getElementById('input_lokasi_ruangan').value = lokasi;
        document.getElementById('input_divisi').value = divisi;
        document.getElementById('select_jenis_perangkat').value = jenis;

        const todayYmd = '{{ now()->format("Y-m-d") }}';
        const execDate = isLate ? todayYmd : (tgl || todayYmd);
        document.getElementById('input_tanggal_cek').value = execDate;
        recalculateNextSchedule(execDate);

        if (jam && /^\d{2}:\d{2}$/.test(jam)) {
            document.getElementById('input_jam_mulai').value = jam;
            const [h, m] = jam.split(':').map(Number);
            const endH = String((h + 1) % 24).padStart(2, '0');
            const endM = String(m).padStart(2, '0');
            document.getElementById('input_jam_selesai').value = `${endH}:${endM}`;
            updateDuration();
        }

        if (tim) {
            document.getElementById('summary-tim').innerText = tim;
        }

        if (scheduledChecklist.length > 0) {
            renderCustomChecklist(scheduledChecklist);
        } else {
            loadChecklistByCategory(jenis);
        }
    }

    function renderCustomChecklist(list) {
        const container = document.getElementById('checklist_container');
        if (!container) return;
        container.innerHTML = '';
        list.forEach((item, idx) => {
            const label = document.createElement('label');
            label.className = "flex items-center space-x-3 p-2.5 rounded-xl bg-slate-50/80 border border-slate-200 hover:bg-slate-100/70 transition cursor-pointer";
            label.innerHTML = `
                <input type="checkbox" name="checklist[${idx}][checked]" value="1" checked 
                       class="w-4 h-4 rounded text-[#0f766e] focus:ring-[#0f766e] border-slate-300">
                <input type="hidden" name="checklist[${idx}][item]" value="${item.replace(/"/g, '&quot;')}">
                <span class="text-xs font-medium text-slate-800">${item}</span>
            `;
            container.appendChild(label);
        });
    }

    function setJenisPemeliharaan(val) {
        document.getElementById('input_jenis_pemeliharaan').value = val;
        const btns = {
            'Rutin': document.getElementById('btn-jenis-rutin'),
            'Perbaikan ringan': document.getElementById('btn-jenis-ringan'),
            'Penggantian komponen': document.getElementById('btn-jenis-ganti'),
        };
        for (const [key, btn] of Object.entries(btns)) {
            if (btn) {
                if (key === val) {
                    btn.className = "px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs";
                } else {
                    btn.className = "px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-700 hover:bg-slate-200";
                }
            }
        }
    }

    function setKondisi(label, dbValue) {
        document.getElementById('input_kondisi').value = dbValue;
        const btns = {
            'Baik': document.getElementById('btn-kondisi-baik'),
            'Perlu tindak lanjut': document.getElementById('btn-kondisi-tindak'),
            'Rusak': document.getElementById('btn-kondisi-rusak'),
        };
        for (const [key, btn] of Object.entries(btns)) {
            if (btn) {
                if (key === label) {
                    btn.className = "px-4 py-1.5 rounded-full text-xs font-bold transition cursor-pointer bg-[#0e1a2b] text-white shadow-2xs";
                } else {
                    btn.className = "px-4 py-1.5 rounded-full text-xs font-semibold transition cursor-pointer bg-slate-100 text-slate-700 hover:bg-slate-200";
                }
            }
        }

        const chk = document.getElementById('check_buat_tiket');
        if (chk) {
            chk.checked = (label === 'Perlu tindak lanjut' || label === 'Rusak');
        }
    }

    function updateDuration() {
        const start = document.getElementById('input_jam_mulai').value;
        const end = document.getElementById('input_jam_selesai').value;
        if (!start || !end) return;

        const [sh, sm] = start.split(':').map(Number);
        const [eh, em] = end.split(':').map(Number);

        let diffM = (eh * 60 + em) - (sh * 60 + sm);
        if (diffM < 0) diffM += 24 * 60;

        let str = '';
        if (diffM < 60) {
            str = `${diffM} menit`;
        } else {
            const h = Math.floor(diffM / 60);
            const m = diffM % 60;
            str = m > 0 ? `${h} jam ${m} mnt` : `${h} jam`;
        }
        document.getElementById('summary-durasi').innerText = str;
    }

    function recalculateNextSchedule(dateVal) {
        if (!dateVal) return;
        const parts = dateVal.split('-').map(Number);
        if (parts.length !== 3) return;
        const d = new Date(parts[0], parts[1] - 1, parts[2]);
        const freqLower = (currentScheduleFreq || '').toLowerCase();

        const elNext = document.getElementById('summary-jadwal-berikutnya');
        const elNote = document.getElementById('summary-jadwal-note');

        if (freqLower === 'sekali' || freqLower.includes('sekali')) {
            if (elNext) elNext.innerText = 'Tidak berulang';
            if (elNote) elNote.innerText = 'Pemeliharaan satu kali selesai, tidak ada siklus berikutnya.';
            return;
        }

        let noteText = 'Dihitung otomatis dari jadwal rutin.';
        if (freqLower.includes('hari')) {
            const m = freqLower.match(/(\d+)\s*hari/);
            const days = m ? parseInt(m[1], 10) : 1;
            d.setDate(d.getDate() + days);
            noteText = `Siklus setiap ${days} hari.`;
        } else if (freqLower.includes('minggu')) {
            const m = freqLower.match(/(\d+)\s*minggu/);
            const weeks = m ? parseInt(m[1], 10) : 1;
            d.setDate(d.getDate() + (weeks * 7));
            noteText = weeks > 1 ? `Siklus setiap ${weeks} minggu (+${weeks * 7} hari).` : 'Siklus mingguan (+7 hari).';
        } else if (freqLower.includes('tahun')) {
            const m = freqLower.match(/(\d+)\s*tahun/);
            const years = m ? parseInt(m[1], 10) : 1;
            d.setFullYear(d.getFullYear() + years);
            noteText = years > 1 ? `Siklus setiap ${years} tahun.` : 'Siklus tahunan (+1 tahun).';
        } else if (freqLower.includes('3 bulan') || freqLower.includes('tiga bulan')) {
            d.setMonth(d.getMonth() + 3);
            noteText = 'Siklus triwulan (+3 bulan).';
        } else {
            const m = freqLower.match(/(\d+)\s*bulan/);
            const months = m ? parseInt(m[1], 10) : 1;
            d.setMonth(d.getMonth() + months);
            noteText = months > 1 ? `Siklus setiap ${months} bulan.` : 'Siklus bulanan (+1 bulan).';
        }

        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        if (elNext) elNext.innerText = `${day}/${month}/${year}`;
        if (elNote) elNote.innerText = noteText;
    }

    function previewUpload(input, labelId) {
        if (input.files && input.files[0]) {
            const fileName = input.files[0].name;
            document.getElementById(labelId).innerText = fileName.length > 20 ? fileName.substring(0, 17) + '...' : fileName;
        }
    }

    const templateChecklists = {
        'Kabel LAN / Jaringan': [
            'Cek hard disk dan kapasitas rekaman',
            'Cek suhu dan kipas',
            'Bersihkan debu'
        ],
        'Komputer PC': [
            'Pembersihan debu batu bara di casing, fan & heatsink',
            'Pemeriksaan suhu CPU & pasta termal',
            'Cek kesehatan storage harddisk/SSD'
        ],
        'Printer': [
            'Kebersihan roller pickup kertas dari serbuk debu',
            'Level toner / tinta cartridge',
            'Hasil cetak test page bersih'
        ],
        'Server / UPS': [
            'Status baterai & backup power UPS',
            'Suhu ruang server (18°C - 22°C)',
            'Utilisasi storage & error log server'
        ]
    };

    function loadChecklistByCategory(cat) {
        const list = templateChecklists[cat] || templateChecklists['Kabel LAN / Jaringan'];
        const container = document.getElementById('checklist_container');
        if (!container) return;

        container.innerHTML = '';
        list.forEach((item, idx) => {
            const label = document.createElement('label');
            label.className = "flex items-center space-x-3 p-2.5 rounded-xl bg-slate-50/80 border border-slate-200 hover:bg-slate-100/70 transition cursor-pointer";
            label.innerHTML = `
                <input type="checkbox" name="checklist[${idx}][checked]" value="1" ${idx < 2 ? 'checked' : ''} 
                       class="w-4 h-4 rounded text-[#0f766e] focus:ring-[#0f766e] border-slate-300">
                <input type="hidden" name="checklist[${idx}][item]" value="${item}">
                <span class="text-xs font-medium text-slate-800">${item}</span>
            `;
            container.appendChild(label);
        });
    }

    function addCustomChecklistItem() {
        const text = prompt('Masukkan nama item checklist pemeriksaan:');
        if (!text || text.trim() === '') return;

        const container = document.getElementById('checklist_container');
        const idx = container.children.length;
        const label = document.createElement('label');
        label.className = "flex items-center space-x-3 p-2.5 rounded-xl bg-slate-50/80 border border-slate-200 hover:bg-slate-100/70 transition cursor-pointer";
        label.innerHTML = `
            <input type="checkbox" name="checklist[${idx}][checked]" value="1" checked 
                   class="w-4 h-4 rounded text-[#0f766e] focus:ring-[#0f766e] border-slate-300">
            <input type="hidden" name="checklist[${idx}][item]" value="${text.trim()}">
            <span class="text-xs font-medium text-slate-800">${text.trim()}</span>
        `;
        container.appendChild(label);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const sel = document.getElementById('select_schedule');
        @if($scheduledTasks->isEmpty())
            setSumber('catatan_baru');
        @else
            if (sel && sel.value) {
                onScheduleChanged(sel);
            }
        @endif
        updateDuration();
    });
</script>
@endpush
@endsection
