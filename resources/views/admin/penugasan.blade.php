@extends('layouts.app')

@section('title', 'Penugasan Tiket - Koordinator IT PLTU Karangkandri')

@section('content')
<div class="space-y-6">

    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2.5">
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                    Penugasan Tiket Masuk
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    {{ $unassignedTickets->total() }} Menunggu
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Daftar seluruh tiket baru dari divisi PLTU yang belum diambil dan perlu ditugaskan ke personil teknisi IT
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            @include('partials.header-widget')
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @include('partials.flash-messages')

    @php
        $unavailableTechs = $teknisiList->filter(fn ($t) => $t->isUnavailable());
    @endphp
    @if($unavailableTechs->isNotEmpty())
        <div class="p-3.5 rounded-2xl bg-rose-50/70 border border-rose-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs">
            <div class="flex items-center space-x-2.5 text-rose-800">
                <i class="fa-solid fa-calendar-xmark text-rose-600"></i>
                <div>
                    <span class="font-extrabold">Teknisi sedang tidak tersedia:</span>
                    <span class="font-medium">
                        {{ $unavailableTechs->map(fn ($t) => $t->name . ' (' . $t->availability_short_note . ')')->implode(', ') }}
                    </span>
                </div>
            </div>
            <a href="{{ route('admin.users', ['role' => 'teknisi']) }}"
               class="px-3 py-1.5 rounded-xl bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 font-bold text-[11px] transition shrink-0 text-center">
                Kelola Status / Alih Tugas
            </a>
        </div>
    @endif

    <!-- Toolbar Filter & Search -->
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('admin.penugasan') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
            <div class="sm:col-span-5 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor tiket, pelapor, divisi, atau keluhan..."
                       class="w-full pl-3 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                </span>
            </div>

            <div class="sm:col-span-3">
                <select name="divisi" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                    <option value="">-- Semua Divisi --</option>
                    @foreach($divisiList as $div)
                        <option value="{{ $div }}" {{ request('divisi') === $div ? 'selected' : '' }}>{{ $div }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <select name="prioritas" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                    <option value="">-- Prioritas --</option>
                    <option value="Mendesak" {{ request('prioritas') === 'Mendesak' ? 'selected' : '' }}>Mendesak</option>
                    <option value="Sedang" {{ request('prioritas') === 'Sedang' ? 'selected' : '' }}>Sedang</option>
                    <option value="Rendah" {{ request('prioritas') === 'Rendah' ? 'selected' : '' }}>Rendah</option>
                </select>
            </div>

            <div class="sm:col-span-2 flex space-x-2">
                <button type="submit" class="w-full py-2 bg-[#0e1a2b] hover:bg-[#152438] text-white font-bold rounded-lg text-xs transition">
                    Filter
                </button>
                @if(request('search') || request('divisi') || request('prioritas'))
                    <a href="{{ route('admin.penugasan') }}" class="p-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition" title="Reset">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Penugasan -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/60 text-slate-500 font-semibold text-[11px]">
                        <th class="py-3 px-4">No. Tiket</th>
                        <th class="py-3 px-4">Waktu Masuk</th>
                        <th class="py-3 px-4">Divisi & Pelapor</th>
                        <th class="py-3 px-4">Perangkat & Lokasi</th>
                        <th class="py-3 px-4">Kendala / Keluhan</th>
                        <th class="py-3 px-4">Prioritas</th>
                        <th class="py-3 px-4 text-center">Tugaskan Teknisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($unassignedTickets as $ticket)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                            <a href="{{ route('services.show', $ticket) }}" class="text-teal-700 hover:underline">
                                {{ $ticket->ticket_number }}
                            </a>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 text-[11px]">
                            <div class="font-medium text-slate-800">{{ $ticket->created_at->format('d/m/Y') }}</div>
                            <div class="text-[10px] text-slate-400">{{ $ticket->created_at->format('H:i') }} WIB</div>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <div class="font-bold text-slate-900">{{ $ticket->divisi }}</div>
                            <div class="text-[11px] text-slate-400">{{ $ticket->nama_pelapor }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center space-x-1.5">
                                <span class="font-semibold text-slate-800">{{ $ticket->jenis_perangkat }}</span>
                                @php $tb = $ticket->badge_target; @endphp
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold border {{ $tb['bg'] }}">
                                    <i class="{{ $tb['icon'] }} mr-0.5"></i>{{ $tb['short'] }}
                                </span>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-0.5">
                                <i class="fa-solid fa-location-dot text-[9px] mr-1 text-slate-400"></i>{{ $ticket->lokasi_ruangan }}
                            </div>
                        </td>
                        <td class="py-3.5 px-4 max-w-xs">
                            <div class="font-medium text-slate-700 truncate" title="{{ $ticket->keluhan }}">{{ $ticket->keluhan }}</div>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($ticket->prioritas === 'Mendesak')
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-rose-50 text-rose-700 text-[10px] border border-rose-200/60">
                                    Mendesak
                                </span>
                            @elseif($ticket->prioritas === 'Sedang' || $ticket->prioritas === 'Tinggi')
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-amber-50 text-amber-700 text-[10px] border border-amber-200/60">
                                    Sedang
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-sky-50 text-sky-700 text-[10px] border border-sky-200/60">
                                    Rendah
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap text-center">
                            <div class="inline-flex items-center space-x-1.5">
                                <form action="{{ route('admin.assignTicket') }}" method="POST" class="inline-flex items-center space-x-1.5">
                                    @csrf
                                    <input type="hidden" name="service_id" value="{{ $ticket->id }}">

                                    <select name="teknisi_id" required
                                            class="py-1.5 px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700 font-medium max-w-[210px] truncate"
                                            title="Pilih teknisi tunggal (prioritas teknisi yang sedang longgar)">
                                        <option value="" selected>Pilih Teknisi</option>
                                        <optgroup label="🛠️ Tim Lapangan (Hardware & Jaringan)">
                                            @foreach($teknisiList->filter(fn($t) => $t->spesialisasi === 'lapangan') as $tek)
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
                                                @endphp
                                                <option value="{{ $tek->id }}" {{ $isBlocked ? 'disabled' : '' }}>
                                                    {{ $tek->name }} ({{ $badge }})
                                                </option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="💻 Tim Support & Aplikasi (Software & Akun)">
                                            @foreach($teknisiList->filter(fn($t) => $t->spesialisasi === 'aplikasi') as $tek)
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
                                                @endphp
                                                <option value="{{ $tek->id }}" {{ $isBlocked ? 'disabled' : '' }}>
                                                    {{ $tek->name }} ({{ $badge }})
                                                </option>
                                            @endforeach
                                        </optgroup>
                                        @if($teknisiList->filter(fn($t) => empty($t->spesialisasi))->isNotEmpty())
                                        <optgroup label="Teknisi Lainnya">
                                            @foreach($teknisiList->filter(fn($t) => empty($t->spesialisasi)) as $tek)
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
                                                @endphp
                                                <option value="{{ $tek->id }}" {{ $isBlocked ? 'disabled' : '' }}>{{ $tek->name }} ({{ $badge }})</option>
                                            @endforeach
                                        </optgroup>
                                        @endif
                                    </select>

                                    <button type="submit" 
                                            class="px-2.5 py-1.5 bg-teal-700 hover:bg-teal-800 active:scale-95 text-white font-bold rounded-lg text-xs shadow-xs transition flex items-center space-x-1 cursor-pointer"
                                            title="Tugaskan ke teknisi ini">
                                        <i class="fa-solid fa-paper-plane text-[10px]"></i>
                                        <span>Tugaskan</span>
                                    </button>
                                </form>

                                <!-- Tombol Penugasan Tim (Multi-Teknisi) -->
                                <button type="button" 
                                        onclick="openTeamModal({{ $ticket->id }}, '{{ $ticket->ticket_number }}', '{{ addslashes($ticket->divisi) }}', '{{ addslashes($ticket->jenis_perangkat) }}', '{{ addslashes(Str::limit($ticket->keluhan, 70)) }}')"
                                        class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg text-xs border border-indigo-200/80 transition flex items-center space-x-1 cursor-pointer"
                                        title="Tugaskan sebagai Kerja Tim (Multi-Teknisi)">
                                    <i class="fa-solid fa-users text-[11px]"></i>
                                    <span class="hidden sm:inline">Tim</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-circle-check text-emerald-500 text-3xl mb-2 block"></i>
                            <span class="font-semibold text-slate-700">Tidak ada tiket menunggu penugasan</span>
                            <p class="text-xs text-slate-400 mt-1">Seluruh keluhan divisi PLTU telah didelegasikan kepada personil teknisi IT.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($unassignedTickets->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $unassignedTickets->links() }}
        </div>
        @endif
    </div>

    <!-- Modal Penugasan Kerja Tim (Multi-Teknisi) -->
    <div id="modal-team-assign" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-lg w-full overflow-hidden animate-in fade-in duration-200">
            <!-- Header Modal -->
            <div class="px-6 py-4 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-300">
                        <i class="fa-solid fa-users text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-white">Penugasan Kerja Tim IT</h3>
                        <p class="text-[11px] text-slate-300">Kolaborasi multi-teknisi untuk aduan fisik / kendala berat</p>
                    </div>
                </div>
                <button type="button" onclick="closeTeamModal()" class="text-slate-400 hover:text-white transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form action="{{ route('admin.assignTicket') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="service_id" id="team-modal-service-id">

                <!-- Detail Tiket Ringkas -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-xs space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-teal-800 font-mono text-[11px]" id="team-modal-ticket-number">TKT-XXXX</span>
                        <span class="text-slate-500 font-semibold" id="team-modal-divisi">Divisi</span>
                    </div>
                    <div class="font-semibold text-slate-700" id="team-modal-perangkat">Perangkat</div>
                    <div class="text-slate-500 text-[11px] line-clamp-2" id="team-modal-keluhan">Keluhan</div>
                </div>

                <!-- 1. Pilih Teknisi Utama (Lead / PIC) -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">
                        Teknisi Utama (PIC / Lead Penanggung Jawab) <span class="text-rose-500">*</span>
                    </label>
                    <select name="teknisi_id" id="team-modal-pic" required onchange="updateCoTechAvailability()"
                            class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-600/30 focus:bg-white text-slate-800 font-medium">
                        <option value="">-- Pilih Penanggung Jawab Utama --</option>
                        @foreach($teknisiList as $tek)
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
                            @endphp
                            <option value="{{ $tek->id }}" {{ $isBlocked ? 'disabled' : '' }}>
                                {{ $tek->name }} ({{ $badge }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">PIC bertanggung jawab mengkoordinasikan pekerjaan dan input laporan akhir.</p>
                </div>

                <!-- 2. Pilih Anggota Tim Pendamping -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">
                        Anggota Tim Pendamping (Pilih 1 atau lebih) <span class="text-rose-500">*</span>
                    </label>
                    <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50 max-h-44 overflow-y-auto space-y-2">
                        @foreach($teknisiList as $tek)
                            @php
                                $tasks = $tek->active_tasks_count ?? 0;
                                $eff = $tek->effective_availability;
                                $isBlocked = $tek->isCuti() || in_array(strtolower((string) $tek->status_ketersediaan), ['offline', 'off'], true);
                            @endphp
                            <label class="flex items-center justify-between p-2 rounded-lg {{ $isBlocked ? 'bg-slate-50 opacity-60 cursor-not-allowed' : 'bg-white hover:border-indigo-300 cursor-pointer' }} border border-slate-200/70 transition select-none team-co-option" data-tech-id="{{ $tek->id }}">
                                <div class="flex items-center space-x-2.5">
                                    <input type="checkbox" name="teknisi_pendamping[]" value="{{ $tek->id }}" {{ $isBlocked ? 'disabled' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 co-tech-checkbox">
                                    <span class="text-xs font-bold text-slate-800">{{ $tek->name }}</span>
                                </div>
                                <span class="inline-flex items-center space-x-1 text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $tek->availability_badge_class }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $tek->availability_dot_class }}"></span>
                                    <span>{{ $tek->availability_label }}{{ in_array($eff, ['available', 'on_task'], true) ? " ({$tasks})" : '' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Tiket ini otomatis akan muncul di menu Tiket Saya pada seluruh akun teknisi yang dicentang.</p>
                </div>

                <!-- 3. Catatan Penugasan / Instruksi -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">
                        Instruksi Kerja Lapangan (Opsional)
                    </label>
                    <textarea name="catatan_penugasan" rows="2" placeholder="Contoh: Penarikan kabel LAN 4 titik di Control Room bersama Hafizh & Bagas..."
                              class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-600/30 focus:bg-white text-slate-800"></textarea>
                </div>

                <!-- Tombol Aksi -->
                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button type="button" onclick="closeTeamModal()" class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-users-gear text-xs"></i>
                        <span>Tugaskan Tim IT</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTeamModal(id, ticketNumber, divisi, perangkat, keluhan) {
            document.getElementById('team-modal-service-id').value = id;
            document.getElementById('team-modal-ticket-number').textContent = ticketNumber;
            document.getElementById('team-modal-divisi').textContent = divisi;
            document.getElementById('team-modal-perangkat').textContent = perangkat;
            document.getElementById('team-modal-keluhan').textContent = keluhan;

            // Reset selection
            document.getElementById('team-modal-pic').value = '';
            document.querySelectorAll('.co-tech-checkbox').forEach(cb => {
                cb.checked = false;
                cb.disabled = false;
            });
            document.querySelectorAll('.team-co-option').forEach(el => {
                el.classList.remove('opacity-40', 'pointer-events-none');
            });

            document.getElementById('modal-team-assign').classList.remove('hidden');
        }

        function closeTeamModal() {
            document.getElementById('modal-team-assign').classList.add('hidden');
        }

        function updateCoTechAvailability() {
            const selectedPic = document.getElementById('team-modal-pic').value;
            document.querySelectorAll('.team-co-option').forEach(el => {
                const techId = el.getAttribute('data-tech-id');
                const cb = el.querySelector('.co-tech-checkbox');
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
</div>
@endsection

