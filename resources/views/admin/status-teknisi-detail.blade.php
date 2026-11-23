@extends('layouts.app')

@section('title', 'Admin · Aktivitas & Status Teknisi ' . $user->name . ' - IT Service Desk PLTU Karangkandri')

@section('content')
<div class="space-y-6">

    <!-- Header Halaman Sesuai Mockup Referensi -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Aktivitas Teknisi
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Riwayat status dan pekerjaan satu teknisi dalam sehari
            </p>
            <!-- Breadcrumb helper & test compatibility -->
            <div class="sr-only">
                <span>Detail Teknisi</span> &gt; <span>{{ $user->name }}</span>
            </div>
        </div>

        <div class="flex items-center space-x-2.5">
            <a href="{{ route('admin.statusTeknisi') }}"
               class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition shadow-2xs inline-flex items-center space-x-1.5 cursor-pointer">
                <span>Kembali</span>
            </a>
            @include('partials.header-widget')
        </div>
    </div>

    <!-- Alert Flash Feedback -->
    @include('partials.flash-messages')

    <!-- KARTU FILTER TEKNISI, TANGGAL & STATUS LIVE (SESUAI MOCKUP) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Form Filter Teknisi & Tanggal -->
        <div class="flex flex-wrap items-center gap-4 sm:gap-6">
            <span class="text-xs font-black text-slate-900 tracking-wide uppercase sm:normal-case sm:text-sm">Teknisi</span>

            <!-- Dropdown Nama Teknisi -->
            <div class="flex items-center space-x-2.5">
                <label for="select-teknisi-detail" class="text-xs text-slate-400 font-medium">Nama</label>
                <div class="relative">
                    <select id="select-teknisi-detail"
                            onchange="window.location.href=this.value"
                            class="appearance-none bg-slate-50/80 hover:bg-slate-100 border border-slate-200 rounded-xl pl-3.5 pr-8 py-2 text-xs font-bold text-slate-900 cursor-pointer transition focus:outline-none focus:ring-1 focus:ring-teal-700">
                        @foreach($allTechnicians as $tech)
                            <option value="{{ route('admin.statusTeknisi.show', $tech) }}?tanggal={{ $selectedDateStr }}" {{ $tech->id === $user->id ? 'selected' : '' }}>
                                {{ $tech->name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                </div>
            </div>

            <!-- Input Tanggal Terpilih -->
            <div class="flex items-center space-x-2.5">
                <label for="select-tanggal-detail" class="text-xs text-slate-400 font-medium">Tanggal</label>
                <div class="relative">
                    <input type="date"
                           id="select-tanggal-detail"
                           value="{{ $selectedDateStr }}"
                           onchange="window.location.href='{{ route('admin.statusTeknisi.show', $user) }}?tanggal=' + this.value"
                           class="bg-slate-50/80 hover:bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-1.5 text-xs font-bold text-slate-900 cursor-pointer transition focus:outline-none focus:ring-1 focus:ring-teal-700">
                </div>
            </div>
        </div>

        <!-- Status Badge & Tim di Kanan -->
        <div class="flex items-center space-x-3.5">
            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-extrabold border {{ $user->availability_badge_class }}">
                <span class="w-2 h-2 rounded-full {{ $user->availability_dot_class }}"></span>
                <span>{{ $user->availability_label }}</span>
            </span>
            <span class="text-xs font-bold text-slate-500">
                {{ $user->label_tim }}
            </span>
            <button type="button"
                    onclick="openModalAvailability({{ json_encode([
                        'id' => $user->id,
                        'name' => $user->name,
                        'tim' => $user->label_tim,
                        'status' => $user->effective_availability,
                        'cuti_mulai' => $user->cuti_mulai ? $user->cuti_mulai->format('Y-m-d') : now()->format('Y-m-d'),
                        'cuti_selesai' => $user->cuti_selesai ? $user->cuti_selesai->format('Y-m-d') : '',
                        'catatan' => $user->catatan_ketersediaan ?? '',
                        'active_tickets' => $activeTicketCount,
                        'scheduled_pm' => $scheduledPmCount,
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }})"
                    class="text-xs text-teal-700 hover:text-teal-900 font-bold hover:underline cursor-pointer ml-1"
                    title="Ubah Status Teknisi Secara Manual">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
        </div>
    </div>

    <!-- TATA LETAK 2 KOLOM (KIRI: LINIMASA HARI INI, KANAN: RINGKASAN & JAM ON TASK 7 HARI) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- KOLOM KIRI (7/12): LINIMASA HARI INI -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">
                        Linimasa hari ini
                    </h2>
                    <span class="sr-only">Aktivitas hari ini</span>
                </div>
                <span class="text-xs text-slate-400 font-medium">
                    {{ $selectedDateDayFormatted }}
                </span>
            </div>

            <!-- Kontainer Linimasa Vertikal dengan Garis Alur -->
            <div class="relative pl-2 sm:pl-4 space-y-6 py-1">
                <!-- Garis Rel Vertikal (Line) -->
                <div class="absolute left-[54px] sm:left-[62px] top-3 bottom-3 w-[2px] bg-slate-100"></div>

                @forelse($timeline as $item)
                    <div class="relative flex items-start space-x-4 sm:space-x-5">
                        <!-- Waktu Pelaksanaan di Kiri -->
                        <div class="w-10 sm:w-12 text-xs font-extrabold text-slate-800 text-right shrink-0 pt-0.5 tabular-nums">
                            {{ $item['time'] }}
                        </div>

                        <!-- Titik Indikator Warna Status di Garis -->
                        <div class="relative z-10 flex items-center justify-center shrink-0 mt-1">
                            <span class="w-3 h-3 rounded-full {{ $item['dot_class'] }} ring-4 ring-white shadow-xs"></span>
                        </div>

                        <!-- Keterangan Status & Subtitle Aktivitas -->
                        <div class="min-w-0 flex-1 pt-0.5">
                            <div class="text-xs font-extrabold text-slate-900 leading-tight">
                                {{ $item['title'] }}
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                {{ $item['subtitle'] }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-slate-400 italic">
                        Belum ada aktivitas tercatat untuk tanggal ini.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- KOLOM KANAN (5/12): RINGKASAN HARI INI & JAM ON TASK 7 HARI TERAKHIR -->
        <div class="lg:col-span-5 space-y-6">

            <!-- KARTU 1: RINGKASAN HARI INI -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                <h2 class="text-sm font-extrabold text-slate-900">
                    Ringkasan hari ini
                </h2>

                <!-- Baris 1 (2 Box: Waktu Aktif & Waktu On Task) -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3.5 rounded-xl bg-slate-50/70 border border-slate-200/70">
                        <div class="text-[11px] font-semibold text-slate-500">Waktu aktif</div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 mt-1 tracking-tight">{{ $waktuAktif }}</div>
                        <span class="sr-only">Online hari ini: {{ $onlineHoursToday }}</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50/70 border border-slate-200/70">
                        <div class="text-[11px] font-semibold text-slate-500">Waktu On Task</div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 mt-1 tracking-tight">{{ $waktuOnTask }}</div>
                    </div>
                </div>

                <!-- Baris 2 (3 Box: Tiket Diambil, Tiket Selesai, PM Selesai) -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-200/70 text-center">
                        <div class="text-[11px] font-semibold text-slate-500">Tiket diambil</div>
                        <div class="text-2xl font-black text-slate-900 mt-1">{{ $tiketDiambilCount }}</div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-200/70 text-center">
                        <div class="text-[11px] font-semibold text-slate-500">Tiket selesai</div>
                        <div class="text-2xl font-black text-slate-900 mt-1">{{ $tiketSelesaiCount }}</div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-200/70 text-center">
                        <div class="text-[11px] font-semibold text-slate-500">PM selesai</div>
                        <div class="text-2xl font-black text-slate-900 mt-1">{{ $pmSelesaiCount }}</div>
                    </div>
                </div>
            </div>

            <!-- KARTU 2: JAM ON TASK, 7 HARI TERAKHIR (BAR CHART SESUAI MOCKUP) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-extrabold text-slate-900">
                        Jam On Task, 7 hari terakhir
                    </h2>
                    <span class="sr-only">Riwayat 7 hari</span>
                </div>

                <!-- Visualisasi Grafik Batang (Bar Chart) -->
                <div class="pt-3 pb-1">
                    <!-- Area Batang Diagram (Tinggi 110px) -->
                    <div class="h-28 flex items-end justify-between gap-2 px-1 pb-2 border-b border-slate-100">
                        @foreach($onTask7Days as $bar)
                            <div class="flex-1 flex flex-col items-center justify-end h-full group" title="{{ $bar['day'] }}: {{ $bar['label'] }}">
                                @if($bar['hours'] > 0)
                                    <!-- Batang Hijau Tua / Dark Teal Sesuai Referensi -->
                                    <div class="w-full max-w-[28px] bg-[#0c4a44] hover:bg-[#115e59] rounded-t-sm transition-all duration-300"
                                         style="height: {{ $bar['height_percent'] }}%;">
                                    </div>
                                @else
                                    <!-- Garis Strip Abu-abu untuk Hari Libur / Akhir Pekan -->
                                    <div class="w-full max-w-[28px] h-[2px] bg-slate-200 rounded-full mb-1"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Label Nama Hari & Nilai Jam di Bawah Batang -->
                    <div class="flex items-start justify-between gap-2 px-1 pt-2.5">
                        @foreach($onTask7Days as $bar)
                            <div class="flex-1 text-center">
                                <div class="text-[11px] font-extrabold text-slate-700">
                                    {{ $bar['day'] }}
                                </div>
                                <div class="text-[10px] font-semibold text-slate-500 mt-0.5 whitespace-nowrap">
                                    {{ $bar['label'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Modal Atur Status Kerja & Alih Tugas Teknisi (Tetap Dipertahankan) -->
<div id="modalAvailabilityUser" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Atur Status Kerja Teknisi</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    <span id="availUserName" class="font-bold text-slate-700"></span> &bull; <span id="availUserTim"></span>
                </p>
            </div>
            <button type="button" onclick="closeModalAvailability()" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="formAvailabilityUser" method="POST" class="mt-4 space-y-4 text-xs">
            @csrf

            <div class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                <span class="text-slate-500 font-medium">Beban saat ini</span>
                <span id="availTaskSummary" class="font-bold text-slate-800">0 tiket aktif · 0 jadwal PM</span>
            </div>

            <div>
                <label class="block font-bold text-slate-800 mb-1.5">Status Kerja</label>
                <select name="status_ketersediaan" id="availStatusSelect" onchange="toggleAvailabilityFields()"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white">
                    <option value="available">🟢 Available (Standby / Siap Tugas)</option>
                    <option value="on_task">🟡 On Task (Sedang Bertugas)</option>
                    <option value="cuti">🔴 Cuti / Izin</option>
                    <option value="offline">⚫ Offline / Pulang</option>
                </select>
            </div>

            <div id="availLeaveDetails" class="hidden space-y-3 p-3.5 rounded-xl bg-rose-50/40 border border-rose-200/70">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Mulai Tanggal</label>
                        <input type="date" name="cuti_mulai" id="availCutiMulai"
                               class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Sampai Tanggal</label>
                        <input type="date" name="cuti_selesai" id="availCutiSelesai"
                               class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800">
                    </div>
                </div>
            </div>

            <div id="availNoteWrap">
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Catatan / Keterangan (Opsional)</label>
                <input type="text" name="catatan_ketersediaan" id="availCatatan" placeholder="Contoh: Tarik kabel Control Room / Cuti tahunan"
                       class="w-full px-3 py-2 bg-slate-50 focus:bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-teal-700">
            </div>

            <div id="availReassignBox" data-has-tasks="0" class="hidden space-y-2.5 p-3.5 rounded-xl bg-amber-50/60 border border-amber-200/80">
                <div class="font-bold text-amber-900 flex items-center space-x-1.5">
                    <i class="fa-solid fa-arrows-turn-right text-amber-600"></i>
                    <span>Pengalihan Tugas Aktif &amp; Jadwal PM</span>
                </div>
                <select name="reassign_mode" id="availReassignMode" onchange="toggleAvailabilityFields()"
                        class="w-full px-3 py-2 bg-white border border-amber-200 rounded-lg text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-teal-700">
                    <option value="keep">Biarkan tetap (tidak dialihkan dulu)</option>
                    <option value="team">Lepas ke Antrian Tim (bisa diambil rekan satu tim)</option>
                    <option value="technician">Alihkan langsung ke Teknisi Pengganti</option>
                </select>

                <div id="availReplacementWrap" class="hidden pt-1">
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Pilih Teknisi Pengganti</label>
                    <select name="replacement_teknisi_id" id="availReplacementSelect"
                            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800">
                        <option value="">-- Pilih Teknisi Pengganti --</option>
                        @foreach($allTechnicians ?? [] as $tOpt)
                            @php
                                $isBlockedOpt = $tOpt->isCuti() || in_array(strtolower((string) $tOpt->status_ketersediaan), ['offline', 'off'], true);
                            @endphp
                            <option value="{{ $tOpt->id }}" {{ $isBlockedOpt ? 'disabled' : '' }}>
                                {{ $tOpt->name }} ({{ $tOpt->label_tim }}) — {{ $tOpt->availability_short_note }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModalAvailability()"
                        class="px-4 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold transition shadow-2xs cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2 bg-[#0f766e] hover:bg-[#115e59] text-white font-bold rounded-xl transition shadow-xs cursor-pointer">
                    Simpan Status
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openModalAvailability(data) {
        document.getElementById('formAvailabilityUser').action = '/admin/users/' + data.id + '/availability';
        document.getElementById('availUserName').innerText = data.name;
        document.getElementById('availUserTim').innerText = data.tim || 'Tim IT';
        document.getElementById('availStatusSelect').value = data.status || 'available';
        document.getElementById('availCutiMulai').value = data.cuti_mulai || '';
        document.getElementById('availCutiSelesai').value = data.cuti_selesai || '';
        document.getElementById('availCatatan').value = data.catatan || '';

        const activeTickets = parseInt(data.active_tickets || 0, 10);
        const scheduledPm = parseInt(data.scheduled_pm || 0, 10);
        const totalTasks = activeTickets + scheduledPm;
        document.getElementById('availTaskSummary').innerText = `${activeTickets} tiket aktif · ${scheduledPm} jadwal PM terjadwal`;
        document.getElementById('availReassignBox').dataset.hasTasks = totalTasks > 0 ? '1' : '0';

        const repSel = document.getElementById('availReplacementSelect');
        if (repSel) {
            repSel.value = '';
            Array.from(repSel.options).forEach(opt => {
                opt.hidden = opt.value && String(opt.value) === String(data.id);
            });
        }

        document.getElementById('availReassignMode').value = 'keep';
        toggleAvailabilityFields();
        document.getElementById('modalAvailabilityUser').classList.remove('hidden');
    }

    function closeModalAvailability() {
        document.getElementById('modalAvailabilityUser').classList.add('hidden');
    }

    function toggleAvailabilityFields() {
        const status = document.getElementById('availStatusSelect').value;
        const leaveBox = document.getElementById('availLeaveDetails');
        const noteWrap = document.getElementById('availNoteWrap');
        const reassignBox = document.getElementById('availReassignBox');
        const repWrap = document.getElementById('availReplacementWrap');
        const mode = document.getElementById('availReassignMode').value;

        const isCuti = status === 'cuti';
        const isUnavailable = status === 'cuti' || status === 'offline';
        leaveBox.classList.toggle('hidden', !isCuti);
        noteWrap.classList.toggle('hidden', status === 'available');
        reassignBox.classList.toggle('hidden', !isUnavailable || reassignBox.dataset.hasTasks !== '1');
        repWrap.classList.toggle('hidden', !isUnavailable || mode !== 'technician');
    }
</script>
@endpush
@endsection
