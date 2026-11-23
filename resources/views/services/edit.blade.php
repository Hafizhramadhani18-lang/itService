@extends('layouts.app')

@section('title', 'Perbarui Penanganan Tiket #' . $service->ticket_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('services.show', $service) }}" class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs mb-2">
                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                <span>Kembali ke Detail Tiket</span>
            </a>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Penanganan Servis IT Support
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                No. Tiket: <span class="font-mono font-bold text-blue-700">{{ $service->ticket_number }}</span> | {{ $service->jenis_perangkat }} ({{ $service->divisi }})
            </p>
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @include('partials.flash-messages')

    <!-- Edit Form Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <form action="{{ route('services.update', $service) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Problem Summary (Readonly) -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Pelapor: <strong class="text-slate-800">{{ $service->nama_pelapor }}</strong> ({{ $service->divisi }} - {{ $service->lokasi_ruangan }})</span>
                    <span class="font-mono text-blue-600">{{ $service->kode_perangkat ?? 'Tanpa Kode' }}</span>
                </div>
                <div class="text-slate-700">
                    <span class="font-semibold text-slate-500 block mb-0.5">Keluhan Awal:</span>
                    <p class="italic bg-white p-2.5 rounded-xl border border-slate-200">{{ $service->keluhan }}</p>
                </div>
            </div>

            <!-- Status & Technician Selection -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Status Pengerjaan <span class="text-rose-500">*</span>
                    </label>
                    <select name="status" required class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                        <option value="Baru" {{ old('status', $service->status) === 'Baru' ? 'selected' : '' }}>Baru</option>
                        <option value="Diproses" {{ old('status', $service->status) === 'Diproses' ? 'selected' : '' }}>Diproses</option>
                        <option value="Menunggu sparepart" {{ old('status', $service->status) === 'Menunggu sparepart' ? 'selected' : '' }}>Menunggu sparepart</option>
                        <option value="Selesai" {{ old('status', $service->status) === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="Dibatalkan" {{ old('status', $service->status) === 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Teknisi Penanggung Jawab
                    </label>
                    <select name="teknisi_nama" class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                        <option value="">-- Belum ada teknisi --</option>
                        @foreach($teknisiList as $keyName => $labelName)
                        <option value="{{ $keyName }}" {{ old('teknisi_nama', $service->teknisi_nama) === $keyName ? 'selected' : '' }}>
                            {{ $labelName }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Prioritas
                    </label>
                    <select name="prioritas" required class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                        <option value="Rendah" {{ old('prioritas', $service->prioritas) === 'Rendah' ? 'selected' : '' }}>Rendah</option>
                        <option value="Sedang" {{ old('prioritas', $service->prioritas) === 'Sedang' ? 'selected' : '' }}>Sedang</option>
                        <option value="Tinggi" {{ old('prioritas', $service->prioritas) === 'Tinggi' ? 'selected' : '' }}>Tinggi</option>
                        <option value="Mendesak" {{ old('prioritas', $service->prioritas) === 'Mendesak' ? 'selected' : '' }}>Mendesak</option>
                    </select>
                </div>
            </div>

            <!-- Technician Action / Solution -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Tindakan Perbaikan / Solusi Teknis
                </label>
                <textarea name="tindakan_perbaikan" rows="4" placeholder="Jelaskan tindakan teknis yang telah Anda lakukan, contoh: Pemotongan kabel LAN dan krimping ulang konektor RJ-45 Cat6, penggantian thermal paste processor, atau instalasi ulang sistem operasi..."
                    class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">{{ old('tindakan_perbaikan', $service->tindakan_perbaikan) }}</textarea>
                <p class="text-[11px] text-slate-400 mt-1">Catatan ini akan muncul pada rekap laporan perbaikan resmi.</p>
            </div>

            <!-- Spare parts / Material used -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Sparepart / Material Digunakan <span class="text-slate-400 font-normal">(Opsional)</span>
                </label>
                <input type="text" name="sparepart" value="{{ old('sparepart', $service->sparepart) }}" placeholder="Contoh: 2x Konektor RJ-45 Cat6, Pasta Thermal Arctic, 1x Kabel Power"
                    class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('services.show', $service) }}" class="px-5 py-2.5 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm shadow-lg shadow-blue-600/25 transition flex items-center space-x-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Perubahan Penanganan</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

