@extends('layouts.app')

@section('title', (Auth::user()->isAdmin() ? 'Admin' : (Auth::user()->isTeknisi() ? 'Teknisi' : 'Pegawai')) . ' · Pengaturan Profil - PT S2P PLTU Karangkandri')

@section('content')
<div class="space-y-6">

    <!-- ============================================== -->
    <!-- HEADER HALAMAN SESUAI MOCKUP REFERENSI         -->
    <!-- ============================================== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Pengaturan Profil
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Kelola data akun dan kata sandi Anda
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            @if(!Auth::user()->isPegawai())
                <!-- Header Date & Notification Widget (Admin & Teknisi) -->
                @include('partials.header-widget')
            @else
                <!-- Tanggal Hari Ini Sesuai Format Pegawai -->
                <div class="inline-flex items-center space-x-1.5 px-3 py-2 bg-white rounded-xl border border-slate-200/80 text-xs font-semibold text-slate-600 shadow-xs">
                    <i class="fa-regular fa-calendar text-teal-700 text-xs"></i>
                    <span>{{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Alert Flash Feedback -->
    @include('partials.flash-messages')

    @if ($errors->any())
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200/80 text-xs text-rose-700 shadow-xs">
        <div class="flex items-center space-x-2 font-bold mb-1">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Terdapat beberapa kesalahan input:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-600 pl-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- ============================================== -->
    <!-- 2 KOLOM KARTU: PROFIL & UBAH KATA SANDI        -->
    <!-- ============================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- ========================================== -->
        <!-- KARTU 1 (KIRI, 7/12): DATA PROFIL AKUN     -->
        <!-- ========================================== -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Baris Avatar & Header Profil Pengguna -->
                <div class="flex items-center justify-between pb-5 border-b border-slate-100">
                    <div class="flex items-center space-x-4">
                        <!-- Avatar Foto / Inisial -->
                        <div class="relative group">
                            <div id="avatar-container" class="w-14 h-14 rounded-full bg-teal-700 text-white font-black text-xl flex items-center justify-center shadow-xs overflow-hidden flex-shrink-0">
                                @if($user->foto_url)
                                    <img src="{{ $user->foto_url }}" alt="Foto {{ $user->name }}" class="w-full h-full object-cover">
                                @else
                                    <span id="avatar-initial">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <h2 class="text-sm sm:text-base font-black text-slate-900 leading-tight">
                                {{ $user->name }}
                            </h2>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">
                                @if($user->isAdmin())
                                    Admin &bull; Koordinator IT
                                @elseif($user->isTeknisi())
                                    Teknisi &bull; {{ $user->label_tim }}
                                @else
                                    Pegawai &bull; {{ $user->divisi ?? 'Karyawan PLTU' }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Ganti Foto -->
                    <div>
                        <input type="file" name="foto" id="input-avatar-file" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                        <button type="button" onclick="document.getElementById('input-avatar-file').click()" 
                                class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs flex items-center space-x-1.5 cursor-pointer">
                            <span>Ganti foto</span>
                        </button>
                    </div>
                </div>

                <!-- Grid Input 2 Kolom -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <!-- 1. Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Nama lengkap
                        </label>
                        <input type="text" id="name" name="name" required
                               value="{{ old('name', $user->name) }}"
                               class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-700/30 focus:border-teal-700 transition">
                    </div>

                    <!-- 2. NIP / Username (Disabled with Lock) -->
                    <div>
                        <label for="username" class="block text-xs font-bold text-slate-700 mb-1.5">
                            NIP / Username
                        </label>
                        <div class="relative">
                            <input type="text" id="username" readonly disabled
                                   value="{{ $user->username }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 pr-10 text-xs font-semibold text-slate-500 cursor-not-allowed select-none">
                            <i class="fa-solid fa-lock absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        </div>
                    </div>

                    <!-- 3. Email -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Email
                        </label>
                        @if(Auth::user()->isAdmin())
                            <input type="email" id="email" name="email" required
                                   value="{{ old('email', $user->email) }}"
                                   class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-700/30 focus:border-teal-700 transition">
                        @else
                            <div class="relative">
                                <input type="email" id="email" readonly disabled
                                       value="{{ $user->email }}"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 pr-10 text-xs font-semibold text-slate-500 cursor-not-allowed select-none">
                                <i class="fa-solid fa-lock absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs" title="Email resmi dikelola oleh Admin"></i>
                            </div>
                        @endif
                    </div>

                    <!-- 4. No. HP / Ekstensi -->
                    <div>
                        <label for="no_hp" class="block text-xs font-bold text-slate-700 mb-1.5">
                            No. HP / ekstensi
                        </label>
                        <input type="text" id="no_hp" name="no_hp"
                               value="{{ old('no_hp', $user->no_hp ?? '0812-0000-0000') }}"
                               placeholder="0812-0000-0000"
                               class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-700/30 focus:border-teal-700 transition">
                    </div>

                    <!-- 5. Jabatan / Divisi -->
                    <div>
                        <label for="jabatan" class="block text-xs font-bold text-slate-700 mb-1.5">
                            {{ $user->isPegawai() ? 'Divisi / Unit Kerja' : 'Jabatan' }}
                        </label>
                        @if(Auth::user()->isAdmin())
                            <input type="text" id="jabatan" name="jabatan"
                                   value="{{ old('jabatan', $user->label_jabatan) }}"
                                   class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-700/30 focus:border-teal-700 transition">
                        @elseif($user->isPegawai())
                            <div class="relative">
                                <input type="text" id="jabatan" readonly disabled
                                       value="{{ $user->divisi ?? 'Operasional PLTU' }}"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 pr-10 text-xs font-semibold text-slate-500 cursor-not-allowed select-none">
                                <i class="fa-solid fa-lock absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs" title="Divisi resmi dikelola oleh Admin"></i>
                            </div>
                        @else
                            <div class="relative">
                                <input type="text" id="jabatan" readonly disabled
                                       value="{{ $user->label_jabatan }}"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 pr-10 text-xs font-semibold text-slate-500 cursor-not-allowed select-none">
                                <i class="fa-solid fa-lock absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs" title="Jabatan kedinasan dikelola oleh Admin"></i>
                            </div>
                        @endif
                    </div>

                    <!-- 6. Role (Disabled with Lock) -->
                    <div>
                        <label for="role" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Role
                        </label>
                        <div class="relative">
                            <input type="text" id="role" readonly disabled
                                   value="{{ ucfirst($user->role) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 pr-10 text-xs font-semibold text-slate-500 cursor-not-allowed select-none">
                            <i class="fa-solid fa-lock absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        </div>
                    </div>

                </div>

                <!-- Keterangan Catatan Tambahan Sesuai Mockup -->
                <p class="text-[11px] text-slate-400 font-medium leading-relaxed pt-1">
                    @if(Auth::user()->isAdmin())
                        NIP / username dan role akun sendiri tidak dapat diubah dari halaman ini. Pengguna lain dikelola di menu Pengguna.
                    @elseif(Auth::user()->isTeknisi())
                        NIP / username, email resmi, jabatan, dan role akun dikunci oleh sistem. Perubahan data kedinasan dikelola oleh Admin di menu Pengguna.
                    @else
                        NIP / username, email dinas, divisi/unit kerja, dan role akun dikunci oleh sistem. Perubahan data kedinasan dikelola oleh Koordinator IT / Admin.
                    @endif
                </p>

                <!-- Tombol Aksi Batal & Simpan Perubahan -->
                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                    <a href="{{ Auth::user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" 
                       class="px-5 py-2.5 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-5 py-2.5 bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        <!-- ========================================== -->
        <!-- KARTU 2 (KANAN, 5/12): UBAH KATA SANDI     -->
        <!-- ========================================== -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-5">
            <div>
                <h2 class="text-sm font-black text-slate-900 tracking-tight">
                    Ubah kata sandi
                </h2>
            </div>

            <form action="{{ route('profile.password.update') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <!-- 1. Kata Sandi Saat Ini -->
                <div>
                    <label for="current_password" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Kata sandi saat ini
                    </label>
                    <div class="relative">
                        <input type="password" id="current_password" name="current_password" required placeholder="••••••••"
                               class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 pr-20 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-700/30 focus:border-teal-700 transition">
                        <button type="button" onclick="togglePassVisibility('current_password', this)" 
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-600 hover:text-slate-900 transition">
                            Tampilkan
                        </button>
                    </div>
                </div>

                <!-- 2. Kata Sandi Baru -->
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Kata sandi baru
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required placeholder="••••••••"
                               class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 pr-20 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-700/30 focus:border-teal-700 transition">
                        <button type="button" onclick="togglePassVisibility('password', this)" 
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-600 hover:text-slate-900 transition">
                            Tampilkan
                        </button>
                    </div>
                </div>

                <!-- 3. Ulangi Kata Sandi Baru -->
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Ulangi kata sandi baru
                    </label>
                    <div class="relative">
                        <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="••••••••"
                               class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 pr-20 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-700/30 focus:border-teal-700 transition">
                        <button type="button" onclick="togglePassVisibility('password_confirmation', this)" 
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-600 hover:text-slate-900 transition">
                            Tampilkan
                        </button>
                    </div>
                </div>

                <!-- Hint Password Requirement -->
                <p class="text-[11px] text-slate-400 font-medium leading-relaxed">
                    Minimal 8 karakter, kombinasi huruf dan angka.
                </p>

                <!-- Tombol Submit Perbarui Kata Sandi -->
                <button type="submit" 
                        class="w-full py-2.5 px-4 bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer mt-1">
                    Perbarui Kata Sandi
                </button>
            </form>
        </div>

    </div>

</div>

@push('scripts')
<script>
    // Toggle Show/Hide Password
    function togglePassVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        if (input) {
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'Sembunyikan';
            } else {
                input.type = 'password';
                btn.textContent = 'Tampilkan';
            }
        }
    }

    // Preview Foto Avatar Saat Dipilih
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const container = document.getElementById('avatar-container');
                if (container) {
                    container.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endpush
@endsection
