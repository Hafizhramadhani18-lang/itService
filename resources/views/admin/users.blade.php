@extends('layouts.app')

@section('title', 'Manajemen Pengguna - Koordinator IT PLTU Karangkandri')

@section('content')
<div class="space-y-6">

    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2.5">
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                    Manajemen Pengguna
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                    {{ $totalUsers }} Akun
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Kelola akun karyawan PLTU Karangkandri, staf teknisi IT Support, dan fitur reset kata sandi
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            <button onclick="toggleModalTambahUser()" 
                    class="px-4 py-2 bg-teal-700 hover:bg-teal-800 text-white font-bold rounded-lg text-xs shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
                <i class="fa-solid fa-user-plus text-[11px]"></i>
                <span>Tambah Pengguna</span>
            </button>
            @include('partials.header-widget')
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @include('partials.flash-messages')

    <!-- 3 Kartu Ringkasan Role -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500">Total Pengguna</span>
                <div class="text-2xl font-extrabold text-slate-900 mt-0.5">{{ $totalUsers }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Seluruh akun terdaftar</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-700 border border-slate-200 flex items-center justify-center text-sm">
                <i class="fa-solid fa-address-book"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500">Pegawai Divisi</span>
                <div class="text-2xl font-extrabold text-teal-700 mt-0.5">{{ $totalPegawai }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Pengguna layanan tiket</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 border border-teal-100 flex items-center justify-center text-sm">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500">Teknisi Bertugas</span>
                <div class="text-2xl font-extrabold text-emerald-600 mt-0.5">{{ $totalTeknisi }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Personil IT Support</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-sm">
                <i class="fa-solid fa-user-gear"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('admin.users') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
            <div class="sm:col-span-8 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari berdasarkan nama, email, atau divisi..."
                       class="w-full pl-3 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                </span>
            </div>

            <div class="sm:col-span-2">
                <select name="role" class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                    <option value="">-- Semua Role --</option>
                    <option value="pegawai" {{ request('role') === 'pegawai' ? 'selected' : '' }}>Pegawai</option>
                    <option value="teknisi" {{ request('role') === 'teknisi' ? 'selected' : '' }}>Teknisi</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <div class="sm:col-span-2 flex space-x-2">
                <button type="submit" class="w-full py-2 bg-[#0e1a2b] hover:bg-[#152438] text-white font-bold rounded-lg text-xs transition">
                    Filter
                </button>
                @if(request('search') || request('role'))
                    <a href="{{ route('admin.users') }}" class="p-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition" title="Reset">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Pengguna -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/60 text-slate-500 font-semibold text-[11px]">
                        <th class="py-3 px-4">Nama Lengkap</th>
                        <th class="py-3 px-4">Email / Login</th>
                        <th class="py-3 px-4">Role</th>
                        <th class="py-3 px-4">Divisi PLTU</th>
                        <th class="py-3 px-4">No. Kontak</th>
                        <th class="py-3 px-4 text-center">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $u)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                            @php
                                $fotoUrl = $u->foto_url;
                                $avatarInitial = strtoupper(substr($u->name, 0, 1));
                                $avatarBgClass = $fotoUrl
                                    ? 'bg-slate-100 border border-slate-200 text-slate-600'
                                    : ($u->isAdmin()
                                        ? 'bg-teal-600 text-white'
                                        : ($u->isTeknisi()
                                            ? 'bg-slate-800 text-white'
                                            : 'bg-teal-50 text-teal-700 border border-teal-200'));
                            @endphp
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-lg {{ $avatarBgClass }} flex items-center justify-center font-bold text-xs flex-shrink-0 overflow-hidden shadow-2xs">
                                    @if($fotoUrl)
                                        <img src="{{ $fotoUrl }}"
                                             alt="Foto {{ $u->name }}"
                                             class="w-full h-full object-cover"
                                             onerror="this.classList.add('hidden'); this.nextElementSibling?.classList.remove('hidden');">
                                        <span class="hidden">{{ $avatarInitial }}</span>
                                    @else
                                        <span>{{ $avatarInitial }}</span>
                                    @endif
                                </div>
                                <span>{{ $u->name }}</span>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap text-slate-600">
                            {{ $u->email }}
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($u->isAdmin())
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-teal-50 text-teal-700 border border-teal-200 text-[10px]">
                                    Admin (Koordinator)
                                </span>
                            @elseif($u->isTeknisi())
                                @if($u->spesialisasi === 'lapangan')
                                    <span class="px-2.5 py-0.5 rounded-full font-bold bg-amber-50 text-amber-800 border border-amber-200 text-[10px]">
                                        <i class="fa-solid fa-wrench mr-1"></i>Teknisi Lapangan
                                    </span>
                                @elseif($u->spesialisasi === 'aplikasi')
                                    <span class="px-2.5 py-0.5 rounded-full font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px]">
                                        <i class="fa-solid fa-laptop-code mr-1"></i>Teknisi Aplikasi
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full font-bold bg-slate-100 text-slate-700 border border-slate-300 text-[10px]">
                                        Teknisi IT
                                    </span>
                                @endif
                                <div class="mt-1">
                                    <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full font-bold border text-[10px] {{ $u->availability_badge_class }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $u->availability_dot_class }}"></span>
                                        <span>{{ $u->availability_short_note }}</span>
                                    </span>
                                </div>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-sky-50 text-sky-700 border border-sky-200 text-[10px]">
                                    Pegawai Divisi
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap font-medium text-slate-800">
                            {{ $u->divisi ?: '-' }}
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap text-slate-500">
                            {{ $u->no_hp ?: '-' }}
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center space-x-1.5">
                                <!-- Tombol Atur Status / Cuti (Khusus Teknisi) -->
                                @if($u->isTeknisi())
                                <button type="button"
                                        onclick="openModalAvailability({{ json_encode([
                                            'id' => $u->id,
                                            'name' => $u->name,
                                            'tim' => $u->label_tim,
                                            'status' => $u->effective_availability,
                                            'cuti_mulai' => $u->cuti_mulai ? $u->cuti_mulai->format('Y-m-d') : now()->format('Y-m-d'),
                                            'cuti_selesai' => $u->cuti_selesai ? $u->cuti_selesai->format('Y-m-d') : '',
                                            'catatan' => $u->catatan_ketersediaan ?? '',
                                            'active_tickets' => $u->active_tickets_count ?? 0,
                                            'scheduled_pm' => $u->scheduled_pm_count ?? 0,
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }})"
                                        class="px-2.5 py-1 {{ $u->isUnavailable() ? 'bg-rose-50 hover:bg-rose-100 border-rose-200 text-rose-700' : 'bg-teal-50 hover:bg-teal-100 border-teal-200 text-teal-700' }} border rounded-lg text-xs font-semibold transition flex items-center space-x-1 cursor-pointer"
                                        title="Atur status ketersediaan / cuti & alih tugas">
                                    <i class="fa-regular fa-calendar-xmark text-[10px]"></i>
                                    <span>{{ $u->isUnavailable() ? 'Cuti / Off' : 'Status' }}</span>
                                </button>
                                @endif

                                <!-- Tombol Edit Akun -->
                                <button type="button"
                                        onclick="openModalEditUser({{ json_encode([
                                            'id' => $u->id,
                                            'name' => $u->name,
                                            'email' => $u->email,
                                            'role' => $u->role,
                                            'spesialisasi' => $u->spesialisasi,
                                            'divisi' => $u->divisi,
                                            'no_hp' => $u->no_hp,
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }})"
                                        class="px-2.5 py-1 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition flex items-center space-x-1 cursor-pointer"
                                        title="Edit data pengguna">
                                    <i class="fa-solid fa-pen text-[10px]"></i>
                                    <span>Edit</span>
                                </button>

                                <!-- Tombol Reset Password -->
                                <form action="{{ route('admin.users.resetPassword', $u) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Apakah Anda yakin ingin me-reset kata sandi akun ' + {{ Js::from($u->name) }} + ' menjadi default \'password\'?');">
                                    @csrf
                                    <button type="submit" 
                                            class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 rounded-lg text-xs font-semibold transition flex items-center space-x-1 cursor-pointer"
                                            title="Reset kata sandi menjadi 'password'">
                                        <i class="fa-solid fa-key text-[10px]"></i>
                                        <span>Reset Sandi</span>
                                    </button>
                                </form>

                                <!-- Tombol Hapus Akun (Kecuali Akun Diri Sendiri) -->
                                @if($u->id !== Auth::id())
                                <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ' + {{ Js::from($u->name) }} + '?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                                            title="Hapus akun">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            Tidak ditemukan akun dengan kriteria pencarian tersebut.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $users->links() }}
        </div>
        @endif
    </div>

</div>

<!-- Modal Tambah Pengguna Baru -->
<div id="modalTambahUser" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Tambah Akun Pengguna Baru</h3>
            <button onclick="toggleModalTambahUser()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4 mt-4 text-xs">
            @csrf

            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" required placeholder="misal: Budi Santoso"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Email / NIP Akun</label>
                <input type="email" name="email" required placeholder="misal: budi@s2p.co.id"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Peran (Role)</label>
                    <select name="role" id="tambahUserRole" required onchange="handleRoleChange('tambahUserRole', 'tambahUserSpesialisasi')"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                        <option value="pegawai">Pegawai Divisi</option>
                        <option value="teknisi">Teknisi IT</option>
                        <option value="admin">Admin / Koordinator</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Spesialisasi (Teknisi)</label>
                    <select name="spesialisasi" id="tambahUserSpesialisasi"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                        <option value="">-- Umum / Pegawai --</option>
                        <option value="lapangan">🛠️ Tim Lapangan (Hardware & Jaringan)</option>
                        <option value="aplikasi">💻 Tim Support (Aplikasi & Akun)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Divisi PLTU</label>
                <div class="space-y-1.5">
                    <select name="divisi" id="tambahUserDivisi" onchange="handleDivisiChange('tambahUserDivisi', 'tambahUserDivisiCustomWrapper', 'tambahUserDivisiCustom')"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700 font-medium">
                        <option value="">-- Pilih Divisi PLTU --</option>
                        @if(isset($divisiOperasional) && $divisiOperasional->isNotEmpty())
                            <optgroup label="🏢 Divisi Operasional PLTU">
                                @foreach($divisiOperasional as $divName)
                                    <option value="{{ $divName }}">{{ $divName }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        <option value="__custom__">➕ Ketik Divisi Lainnya (Ketik Manual)...</option>
                    </select>

                    <div id="tambahUserDivisiCustomWrapper" class="hidden">
                        <input type="text" id="tambahUserDivisiCustom" placeholder="Ketik nama divisi baru..."
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Ketik nama divisi baru jika tidak tersedia pada opsi di atas.</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Kata Sandi Awal</label>
                <input type="password" name="password" required value="password"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Kata sandi default awal adalah 'password'.</span>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Nomor HP / WhatsApp (Opsional)</label>
                <input type="text" name="no_hp" placeholder="misal: 0812-3456-7890"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="toggleModalTambahUser()"
                        class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-teal-700 hover:bg-teal-800 text-white font-bold rounded-lg text-xs shadow-xs transition">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Data Pengguna -->
<div id="modalEditUser" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Edit Data Pengguna</h3>
            <button type="button" onclick="closeModalEditUser()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="formEditUser" method="POST" class="space-y-4 mt-4 text-xs">
            @csrf
            @method('PUT')

            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" id="editUserName" required
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Email / NIP Akun</label>
                <input type="email" name="email" id="editUserEmail" required
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Peran (Role)</label>
                    <select name="role" id="editUserRole" required onchange="handleRoleChange('editUserRole', 'editUserSpesialisasi')"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                        <option value="pegawai">Pegawai Divisi</option>
                        <option value="teknisi">Teknisi IT</option>
                        <option value="admin">Admin / Koordinator</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Spesialisasi (Teknisi)</label>
                    <select name="spesialisasi" id="editUserSpesialisasi" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700">
                        <option value="">-- Umum / Pegawai --</option>
                        <option value="lapangan">🛠️ Tim Lapangan (Hardware & Jaringan)</option>
                        <option value="aplikasi">💻 Tim Support (Aplikasi & Akun)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Divisi PLTU</label>
                <div class="space-y-1.5">
                    <select name="divisi" id="editUserDivisi" onchange="handleDivisiChange('editUserDivisi', 'editUserDivisiCustomWrapper', 'editUserDivisiCustom')"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition text-slate-700 font-medium">
                        <option value="">-- Pilih Divisi PLTU --</option>
                        @if(isset($divisiOperasional) && $divisiOperasional->isNotEmpty())
                            <optgroup label="🏢 Divisi Operasional PLTU">
                                @foreach($divisiOperasional as $divName)
                                    <option value="{{ $divName }}">{{ $divName }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        <option value="__custom__">➕ Ketik Divisi Lainnya (Ketik Manual)...</option>
                    </select>

                    <div id="editUserDivisiCustomWrapper" class="hidden">
                        <input type="text" id="editUserDivisiCustom" placeholder="Ketik nama divisi baru..."
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Ketik nama divisi baru jika tidak tersedia pada opsi di atas.</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Nomor HP / WhatsApp (Opsional)</label>
                <input type="text" name="no_hp" id="editUserNoHp" placeholder="misal: 0812-3456-7890"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="closeModalEditUser()"
                        class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-teal-700 hover:bg-teal-800 text-white font-bold rounded-lg text-xs shadow-xs transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function handleRoleChange(roleSelectId, spesialisasiSelectId) {
        const roleEl = document.getElementById(roleSelectId);
        const spesialisasiEl = document.getElementById(spesialisasiSelectId);
        if (!roleEl || !spesialisasiEl) return;

        if (roleEl.value !== 'teknisi') {
            spesialisasiEl.value = '';
            spesialisasiEl.disabled = true;
            spesialisasiEl.classList.add('opacity-50', 'bg-slate-100', 'cursor-not-allowed');
        } else {
            spesialisasiEl.disabled = false;
            spesialisasiEl.classList.remove('opacity-50', 'bg-slate-100', 'cursor-not-allowed');
        }
    }

    function handleDivisiChange(selectId, wrapperId, customInputId) {
        const select = document.getElementById(selectId);
        const wrapper = document.getElementById(wrapperId);
        const customInput = document.getElementById(customInputId);
        if (!select || !wrapper || !customInput) return;

        if (select.value === '__custom__') {
            wrapper.classList.remove('hidden');
            customInput.name = 'divisi';
            customInput.focus();
            select.removeAttribute('name');
        } else {
            wrapper.classList.add('hidden');
            customInput.removeAttribute('name');
            customInput.value = '';
            select.name = 'divisi';
        }
    }

    function toggleModalTambahUser() {
        const modal = document.getElementById('modalTambahUser');
        modal.classList.toggle('hidden');
        if (!modal.classList.contains('hidden')) {
            handleRoleChange('tambahUserRole', 'tambahUserSpesialisasi');
            const tambahSelect = document.getElementById('tambahUserDivisi');
            if (tambahSelect) {
                tambahSelect.value = '';
                handleDivisiChange('tambahUserDivisi', 'tambahUserDivisiCustomWrapper', 'tambahUserDivisiCustom');
            }
        }
    }

    function openModalEditUser(user) {
        document.getElementById('formEditUser').action = '/admin/users/' + user.id;
        document.getElementById('editUserName').value = user.name || '';
        document.getElementById('editUserEmail').value = user.email || '';
        document.getElementById('editUserRole').value = user.role || 'pegawai';
        document.getElementById('editUserSpesialisasi').value = user.spesialisasi || '';
        document.getElementById('editUserNoHp').value = user.no_hp || '';

        handleRoleChange('editUserRole', 'editUserSpesialisasi');

        const editSelect = document.getElementById('editUserDivisi');
        const userDivisi = (user.divisi || '').trim();

        if (editSelect) {
            let found = false;
            for (let i = 0; i < editSelect.options.length; i++) {
                if (editSelect.options[i].value === userDivisi) {
                    editSelect.selectedIndex = i;
                    found = true;
                    break;
                }
            }

            // Jika divisi user tidak ditemukan di opsi standar dan tidak kosong, sisipkan sebagai opsi
            if (!found && userDivisi !== '') {
                const opt = new Option(userDivisi, userDivisi, true, true);
                const lastIdx = Math.max(0, editSelect.options.length - 1); // sebelum __custom__
                editSelect.add(opt, editSelect.options[lastIdx]);
                editSelect.value = userDivisi;
            } else if (!found) {
                editSelect.value = '';
            }

            handleDivisiChange('editUserDivisi', 'editUserDivisiCustomWrapper', 'editUserDivisiCustom');
        }

        document.getElementById('modalEditUser').classList.remove('hidden');
    }

    function closeModalEditUser() {
        document.getElementById('modalEditUser').classList.add('hidden');
    }

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

        // Sembunyikan teknisi yang sama di dropdown pengganti
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
        const reassignBox = document.getElementById('availReassignBox');
        const repWrap = document.getElementById('availReplacementWrap');
        const mode = document.getElementById('availReassignMode').value;

        const isCuti = status === 'cuti';
        const isUnavailable = status === 'cuti' || status === 'offline';
        leaveBox.classList.toggle('hidden', !isCuti);
        reassignBox.classList.toggle('hidden', !isUnavailable || reassignBox.dataset.hasTasks !== '1');
        repWrap.classList.toggle('hidden', !isUnavailable || mode !== 'technician');
    }

    document.addEventListener('DOMContentLoaded', function () {
        handleRoleChange('tambahUserRole', 'tambahUserSpesialisasi');

        ['modalTambahUser', 'modalEditUser'].forEach(function(modalId) {
            const form = document.querySelector('#' + modalId + ' form');
            if (form) {
                form.addEventListener('submit', function () {
                    const selSpes = form.querySelector('select[id$="Spesialisasi"]');
                    if (selSpes && selSpes.disabled) {
                        selSpes.disabled = false;
                        selSpes.value = '';
                    }
                });
            }
        });
    });
</script>

<!-- Modal Atur Status Ketersediaan / Cuti & Alih Tugas Teknisi -->
<div id="modalAvailabilityUser" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Status Ketersediaan &amp; Cuti</h3>
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
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Alasan / Catatan</label>
                    <input type="text" name="catatan_ketersediaan" id="availCatatan" placeholder="Contoh: Cuti tahunan, izin keluarga"
                           class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800">
                </div>
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
                        class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs cursor-pointer">
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
@endsection

