@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Back Button -->
    <div class="flex items-center justify-between">
        <a href="{{ route('maintenance.show', $maintenanceLog) }}" class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs">
            <i class="fa-solid fa-arrow-left text-[10px]"></i>
            <span>Kembali ke Detail Log</span>
        </a>
        <div class="text-[11px] text-slate-400 font-medium">
            Edit Log Pemeliharaan: <span class="font-mono font-bold text-teal-700">{{ $maintenanceLog->log_number }}</span>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-xs">
            <div class="font-bold flex items-center space-x-2 mb-1">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Terdapat kesalahan pada input form:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-rose-700">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Card -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-[#0e1a2b] to-[#162740] text-white p-6 sm:p-8">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-2xl bg-teal-500/20 border border-teal-400/30 text-teal-300 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fa-regular fa-pen-to-square"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black tracking-tight text-white">Edit Log Pemeliharaan</h1>
                    <p class="text-xs text-slate-300 mt-1">
                        Perbarui rincian checklist, status kondisi perangkat atau catatan tindakan pemeliharaan.
                    </p>
                </div>
            </div>
        </div>

        <!-- Form Body -->
        <form method="POST" action="{{ route('maintenance.update', $maintenanceLog) }}" enctype="multipart/form-data" class="p-6 sm:p-8 space-y-8">
            @csrf
            @method('PUT')

            <!-- Section 1: Informasi Target & Perangkat -->
            <div>
                <div class="flex items-center space-x-2 pb-3 mb-5 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-lg bg-teal-50 text-teal-700 font-black text-xs flex items-center justify-center">1</span>
                    <h2 class="text-sm font-extrabold text-slate-900">Identitas Perangkat & Lokasi PLTU</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Tanggal Pengecekan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Tanggal Pengecekan <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="tanggal_cek" value="{{ old('tanggal_cek', $maintenanceLog->tanggal_cek ? $maintenanceLog->tanggal_cek->format('Y-m-d') : '') }}" required
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                    </div>

                    <!-- Kode Perangkat / Tag Aset -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Kode / No. Aset Perangkat <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="kode_perangkat" 
                               value="{{ old('kode_perangkat', $maintenanceLog->kode_perangkat) }}" required
                               class="w-full text-xs font-mono font-semibold uppercase rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                    </div>

                    <!-- Nama Perangkat -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Nama / Deskripsi Perangkat <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="nama_perangkat" 
                               value="{{ old('nama_perangkat', $maintenanceLog->nama_perangkat) }}" required
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                    </div>

                    <!-- Kategori / Jenis Perangkat -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Kategori Perangkat <span class="text-rose-500">*</span>
                        </label>
                        <select name="jenis_perangkat" required
                                class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                            <option value="Komputer PC" {{ old('jenis_perangkat', $maintenanceLog->jenis_perangkat) === 'Komputer PC' ? 'selected' : '' }}>Komputer / Workstation PC</option>
                            <option value="Kabel LAN / Jaringan" {{ old('jenis_perangkat', $maintenanceLog->jenis_perangkat) === 'Kabel LAN / Jaringan' ? 'selected' : '' }}>Switch / Jaringan / CCTV</option>
                            <option value="Printer" {{ old('jenis_perangkat', $maintenanceLog->jenis_perangkat) === 'Printer' ? 'selected' : '' }}>Printer & Scanner</option>
                            <option value="Server / UPS" {{ old('jenis_perangkat', $maintenanceLog->jenis_perangkat) === 'Server / UPS' ? 'selected' : '' }}>Server & Power UPS</option>
                            @foreach($categories as $cat)
                                @if(!in_array($cat->nama, ['Komputer PC', 'Kabel LAN / Jaringan', 'Printer', 'Server / UPS']))
                                    <option value="{{ $cat->nama }}" {{ old('jenis_perangkat', $maintenanceLog->jenis_perangkat) === $cat->nama ? 'selected' : '' }}>{{ $cat->nama }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <!-- Divisi PLTU -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Divisi PLTU <span class="text-rose-500">*</span>
                        </label>
                        <select name="divisi" required
                                class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                            @foreach($divisions as $div)
                                <option value="{{ $div->nama }}" {{ old('divisi', $maintenanceLog->divisi) === $div->nama ? 'selected' : '' }}>{{ $div->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Lokasi / Ruangan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Lokasi / Ruangan Fisik <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="lokasi_ruangan" 
                               value="{{ old('lokasi_ruangan', $maintenanceLog->lokasi_ruangan) }}" required
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                    </div>
                </div>
            </div>

            <!-- Section 2: Personil Pemeriksa & Teknisi Pendamping -->
            <div>
                <div class="flex items-center space-x-2 pb-3 mb-5 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-lg bg-teal-50 text-teal-700 font-black text-xs flex items-center justify-center">2</span>
                    <h2 class="text-sm font-extrabold text-slate-900">Personil Teknisi</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Teknisi Lead Pemeriksa
                        </label>
                        <input type="text" value="{{ $maintenanceLog->teknisi_nama }}" disabled
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-100 text-slate-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Teknisi Pendamping (Kerja Tim IT)
                        </label>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 max-h-36 overflow-y-auto space-y-2">
                            @php
                                $currentPendamping = is_array($maintenanceLog->teknisi_pendamping) ? $maintenanceLog->teknisi_pendamping : [];
                            @endphp
                            @foreach($technicians as $tech)
                                @if($tech->id !== $maintenanceLog->user_id)
                                    <label class="flex items-center space-x-2.5 text-xs text-slate-700 hover:text-slate-900 cursor-pointer">
                                        <input type="checkbox" name="teknisi_pendamping[]" value="{{ $tech->id }}"
                                               {{ in_array($tech->id, old('teknisi_pendamping', $currentPendamping)) ? 'checked' : '' }}
                                               class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                        <span class="font-medium">{{ $tech->name }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Status Kondisi & Checklist -->
            <div>
                <div class="flex items-center space-x-2 pb-3 mb-5 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-lg bg-teal-50 text-teal-700 font-black text-xs flex items-center justify-center">3</span>
                    <h2 class="text-sm font-extrabold text-slate-900">Status Kondisi & Checklist</h2>
                </div>

                <!-- Radio Pilihan Kondisi -->
                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-700 mb-2">
                        Status Kondisi Akhir Aset <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="relative flex items-center p-3.5 rounded-2xl border-2 cursor-pointer transition has-checked:border-emerald-500 has-checked:bg-emerald-50/40 border-slate-200 hover:border-slate-300">
                            <input type="radio" name="kondisi" value="Prima" 
                                   {{ old('kondisi', $maintenanceLog->kondisi) === 'Prima' ? 'checked' : '' }}
                                   class="text-emerald-600 focus:ring-emerald-500 mr-3">
                            <div>
                                <div class="font-extrabold text-xs text-slate-900 flex items-center space-x-1.5">
                                    <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                    <span>Prima / Normal</span>
                                </div>
                            </div>
                        </label>

                        <label class="relative flex items-center p-3.5 rounded-2xl border-2 cursor-pointer transition has-checked:border-amber-500 has-checked:bg-amber-50/40 border-slate-200 hover:border-slate-300">
                            <input type="radio" name="kondisi" value="Perlu Perhatian" 
                                   {{ old('kondisi', $maintenanceLog->kondisi) === 'Perlu Perhatian' ? 'checked' : '' }}
                                   class="text-amber-600 focus:ring-amber-500 mr-3">
                            <div>
                                <div class="font-extrabold text-xs text-slate-900 flex items-center space-x-1.5">
                                    <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                                    <span>Perlu Perhatian</span>
                                </div>
                            </div>
                        </label>

                        <label class="relative flex items-center p-3.5 rounded-2xl border-2 cursor-pointer transition has-checked:border-rose-500 has-checked:bg-rose-50/40 border-slate-200 hover:border-slate-300">
                            <input type="radio" name="kondisi" value="Kritis" 
                                   {{ old('kondisi', $maintenanceLog->kondisi) === 'Kritis' ? 'checked' : '' }}
                                   class="text-rose-600 focus:ring-rose-500 mr-3">
                            <div>
                                <div class="font-extrabold text-xs text-slate-900 flex items-center space-x-1.5">
                                    <i class="fa-solid fa-circle-xmark text-rose-500"></i>
                                    <span>Kritis / Rusak</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Checklist Items -->
                <div class="p-5 rounded-2xl bg-slate-50/80 border border-slate-200">
                    <h3 class="text-xs font-extrabold text-slate-800 mb-3">Item Pemeriksaan Fisik & Sistem</h3>
                    
                    @php
                        $checkItems = is_array($maintenanceLog->checklist) ? $maintenanceLog->checklist : [];
                    @endphp

                    <div class="space-y-2">
                        @foreach($checkItems as $idx => $item)
                            @php
                                $isChecked = is_array($item) ? (!empty($item['checked'])) : true;
                                $text = is_array($item) ? ($item['item'] ?? '') : $item;
                            @endphp
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-slate-200/80">
                                <label class="flex items-center space-x-3 text-xs font-semibold text-slate-700 cursor-pointer flex-1 mr-2">
                                    <input type="checkbox" name="checklist[{{ $idx }}][checked]" value="1" {{ $isChecked ? 'checked' : '' }} class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                    <input type="hidden" name="checklist[{{ $idx }}][item]" value="{{ $text }}">
                                    <span>{{ $text }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Section 4: Tindakan, Rekomendasi & Foto -->
            <div>
                <div class="flex items-center space-x-2 pb-3 mb-5 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-lg bg-teal-50 text-teal-700 font-black text-xs flex items-center justify-center">4</span>
                    <h2 class="text-sm font-extrabold text-slate-900">Tindakan & Catatan</h2>
                </div>

                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Tindakan Pemeliharaan yang Dilakukan <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="tindakan" rows="3" required
                                  class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition leading-relaxed">{{ old('tindakan', $maintenanceLog->tindakan) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Catatan & Rekomendasi Pemeliharaan Berikutnya
                        </label>
                        <textarea name="catatan_rekomendasi" rows="2"
                                  class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition leading-relaxed">{{ old('catatan_rekomendasi', $maintenanceLog->catatan_rekomendasi) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Ganti Foto Dokumentasi (Opsional)
                        </label>
                        @if($maintenanceLog->foto_dokumentasi)
                            <div class="mb-2 w-32 h-24 rounded-lg overflow-hidden border border-slate-200">
                                <img src="{{ asset('storage/' . $maintenanceLog->foto_dokumentasi) }}" class="w-full h-full object-cover">
                            </div>
                        @endif
                        <input type="file" name="foto_dokumentasi" accept="image/*"
                               class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 transition">
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="pt-6 border-t border-slate-200 flex items-center justify-end space-x-3">
                <a href="{{ route('maintenance.show', $maintenanceLog) }}" class="px-5 py-2.5 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-extrabold transition shadow-md shadow-teal-700/20 flex items-center space-x-2">
                    <i class="fa-solid fa-check"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
