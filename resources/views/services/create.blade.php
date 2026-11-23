@extends('layouts.app')

@section('title', 'Buat Tiket Baru - IT Service Desk')

@section('content')
<div class="space-y-6">

    <!-- Alert Notifikasi Flash -->
    @include('partials.flash-messages')

    <!-- Header -->
    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            Buat Tiket Baru
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
            Jelaskan kendala agar teknisi bisa menangani lebih cepat
        </p>
    </div>

    <!-- 2-Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN: Form Pengajuan Tiket (8 Cols) -->
        <div class="lg:col-span-8 bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-xs">
            <form action="{{ route('services.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <!-- 1. Kategori Kendala / Perangkat (Custom Styled Dropdown Rapi & Modern) -->
                @php
                    $selectedCat = old('jenis_perangkat', request('kategori'));

                    // Helper to determine category icon and badge styling
                    $getCategoryMeta = function($key, $nama = '') {
                        $check = strtolower($key . ' ' . $nama);
                        if (str_contains($check, 'jaringan') || str_contains($check, 'lan') || str_contains($check, 'switch') || str_contains($check, 'wi-fi')) {
                            return [
                                'icon' => 'fa-solid fa-network-wired',
                                'icon_bg' => 'bg-sky-50 text-sky-700',
                            ];
                        }
                        if (str_contains($check, 'hardware') || str_contains($check, 'pc') || str_contains($check, 'komputer') || str_contains($check, 'laptop')) {
                            return [
                                'icon' => 'fa-solid fa-desktop',
                                'icon_bg' => 'bg-indigo-50 text-indigo-700',
                            ];
                        }
                        if (str_contains($check, 'printer') || str_contains($check, 'scanner') || str_contains($check, 'cetak')) {
                            return [
                                'icon' => 'fa-solid fa-print',
                                'icon_bg' => 'bg-teal-50 text-teal-700',
                            ];
                        }
                        if (str_contains($check, 'akun') || str_contains($check, 'email') || str_contains($check, 'sandi') || str_contains($check, 'password')) {
                            return [
                                'icon' => 'fa-solid fa-user-lock',
                                'icon_bg' => 'bg-amber-50 text-amber-700',
                            ];
                        }
                        if (str_contains($check, 'cctv') || str_contains($check, 'kamera') || str_contains($check, 'nvr')) {
                            return [
                                'icon' => 'fa-solid fa-video',
                                'icon_bg' => 'bg-rose-50 text-rose-700',
                            ];
                        }
                        if (str_contains($check, 'aplikasi') || str_contains($check, 'software') || str_contains($check, 'erp') || str_contains($check, 'portal')) {
                            return [
                                'icon' => 'fa-solid fa-laptop-code',
                                'icon_bg' => 'bg-emerald-50 text-emerald-700',
                            ];
                        }
                        if (str_contains($check, 'telepon') || str_contains($check, 'pabx') || str_contains($check, 'radio') || str_contains($check, 'ht')) {
                            return [
                                'icon' => 'fa-solid fa-phone',
                                'icon_bg' => 'bg-blue-50 text-blue-700',
                            ];
                        }
                        return [
                            'icon' => 'fa-solid fa-screwdriver-wrench',
                            'icon_bg' => 'bg-slate-100 text-slate-700',
                        ];
                    };

                    $categoryItems = [];
                    $useDbCategories = !empty($hasMasterCategories) || (isset($dbCategories) && $dbCategories->count() > 0);
                    if ($useDbCategories) {
                        foreach ($dbCategories ?? [] as $dbc) {
                            $key = $dbc->kode ?: $dbc->nama;
                            // Hindari tabrakan key jika ada beberapa kategori tanpa kode
                            if (isset($categoryItems[$key])) {
                                $key = $dbc->nama;
                            }
                            $meta = $getCategoryMeta($key, $dbc->nama);
                            $descText = trim((string) ($dbc->deskripsi ?? ''));
                            if ($descText === '' && !empty($dbc->kode) && strcasecmp(trim($dbc->kode), trim($dbc->nama)) !== 0) {
                                $descText = trim((string) $dbc->kode);
                            }
                            $categoryItems[$key] = [
                                'key' => $key,
                                'nama' => $dbc->nama,
                                'deskripsi' => $descText,
                                'solusi_cepat' => is_array($dbc->solusi_cepat) ? array_values($dbc->solusi_cepat) : [],
                                'icon' => $meta['icon'],
                                'icon_bg' => $meta['icon_bg'],
                            ];
                        }
                    } else {
                        $fallbacks = [
                            'Jaringan' => ['nama' => 'Jaringan & Kabel LAN / Switch', 'deskripsi' => 'Kabel UTP, konektor RJ45, Wi-Fi, port switch'],
                            'Printer' => ['nama' => 'Printer & Scanner', 'deskripsi' => 'Kertas Macet, Tinta, Offline, Tidak Mau Cetak'],
                            'Hardware' => ['nama' => 'Hardware & Komputer PC', 'deskripsi' => 'Komputer Mati Total, Monitor, RAM, Blue Screen'],
                            'Akun' => ['nama' => 'Akun, Email & Sandi', 'deskripsi' => 'Lupa Password, Akun Windows Terkunci'],
                            'CCTV' => ['nama' => 'Server CCTV & NVR Keamanan', 'deskripsi' => 'Kamera Mati, Blank, Rekaman'],
                            'Aplikasi' => ['nama' => 'Software & Aplikasi Internal', 'deskripsi' => 'Error Aplikasi, Sistem Lambat'],
                            'Telepon' => ['nama' => 'Telepon PABX & Radio HT', 'deskripsi' => 'Gagang Mati, Noise, Frekuensi HT'],
                            'Lainnya' => ['nama' => 'Lainnya', 'deskripsi' => 'Kendala Teknis Khusus / Konsultasi IT'],
                        ];
                        foreach ($fallbacks as $k => $f) {
                            $meta = $getCategoryMeta($k, $f['nama']);
                            $categoryItems[$k] = [
                                'key' => $k,
                                'nama' => $f['nama'],
                                'deskripsi' => $f['deskripsi'],
                                'solusi_cepat' => [],
                                'icon' => $meta['icon'],
                                'icon_bg' => $meta['icon_bg'],
                            ];
                        }
                    }

                    if (!empty($selectedCat) && !isset($categoryItems[$selectedCat])) {
                        $needle = strtolower(trim((string) $selectedCat));
                        foreach ($categoryItems as $itemKey => $itemVal) {
                            if (strtolower($itemKey) === $needle || str_contains(strtolower($itemVal['nama']), $needle)) {
                                $selectedCat = $itemKey;
                                break;
                            }
                        }
                    }

                    $hasSelection = !empty($selectedCat) && isset($categoryItems[$selectedCat]);
                    $activeCatItem = $hasSelection ? $categoryItems[$selectedCat] : null;
                    $activeCatKey = $hasSelection ? $selectedCat : '';
                @endphp

                <div class="relative" id="kategori-dropdown-container">
                    <label for="kategori-trigger-btn" class="block text-xs font-bold text-slate-900 mb-1.5 flex items-center justify-between">
                        <span>Kategori Kendala / Perangkat <span class="text-rose-500">*</span></span>
                        <span class="text-[11px] font-normal text-slate-400">Pilih jenis kendala yang dialami</span>
                    </label>

                    <!-- Real select element for accessibility, form submission, and automated tests -->
                    <select name="jenis_perangkat" 
                            id="jenis_perangkat" 
                            class="sr-only" 
                            tabindex="-1"
                            required
                            onchange="onCategoryChange(this.value, this.options[this.selectedIndex]?.dataset?.nama)">
                        <option value="" disabled {{ !$hasSelection ? 'selected' : '' }}>Pilih jenis kategori kendala / perangkat</option>
                        @foreach($categoryItems as $item)
                            <option value="{{ $item['key'] }}" data-nama="{{ $item['nama'] }}" {{ ($hasSelection && $activeCatKey === $item['key']) ? 'selected' : '' }}>
                                {{ $item['nama'] }}{{ !empty($item['deskripsi']) ? ' (' . $item['deskripsi'] . ')' : '' }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Custom Trigger Button (Menggantikan select bawaan browser) -->
                    <button type="button" 
                            id="kategori-trigger-btn"
                            onclick="toggleKategoriDropdown(event)"
                            class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-left hover:border-teal-500/50 hover:bg-white focus:outline-none focus:ring-1 focus:ring-teal-700 transition shadow-2xs relative flex items-center justify-between group">
                        
                        <div class="flex items-center space-x-3 overflow-hidden">
                            <div id="kategori-trigger-icon-box" class="w-7 h-7 rounded-lg {{ $hasSelection ? $activeCatItem['icon_bg'] : 'bg-slate-100 text-slate-400' }} flex items-center justify-center shrink-0 transition">
                                <i id="kategori-trigger-icon" class="{{ $hasSelection ? $activeCatItem['icon'] : 'fa-solid fa-layer-group' }} text-xs"></i>
                            </div>
                            <div class="truncate">
                                <span id="kategori-trigger-nama" class="text-xs sm:text-sm {{ $hasSelection ? 'font-bold text-slate-800' : 'font-normal text-slate-400' }}">
                                    {{ $hasSelection ? $activeCatItem['nama'] : 'Pilih jenis kendala yang dialami...' }}
                                </span>
                                <span id="kategori-trigger-desc" class="hidden sm:inline text-xs text-slate-400 font-normal ml-1.5">
                                    {{ ($hasSelection && !empty($activeCatItem['deskripsi'])) ? '(' . Str::limit($activeCatItem['deskripsi'], 55) . ')' : '' }}
                                </span>
                            </div>
                        </div>

                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" id="kategori-chevron"></i>
                        </div>
                    </button>

                    <!-- Custom Dropdown Menu (Rapi, Berwarna & Elegan) -->
                    <div id="kategori-dropdown-menu" 
                         class="hidden absolute left-0 right-0 top-full mt-1.5 z-50 bg-white border border-slate-200 rounded-2xl shadow-xl max-h-72 overflow-y-auto divide-y divide-slate-100 py-1.5 transition-all">
                        
                        <div class="px-3.5 py-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 bg-slate-50/70 flex items-center justify-between">
                            <span>Pilihan Kategori Kendala &amp; Sistem</span>
                            <span class="text-[9px] font-normal text-slate-400">Klik untuk memilih</span>
                        </div>

                        <div class="divide-y divide-slate-50">
                            @forelse($categoryItems as $item)
                            <div class="kategori-option-item px-3.5 py-2.5 hover:bg-teal-50/70 cursor-pointer transition flex items-center justify-between group {{ ($hasSelection && $activeCatKey === $item['key']) ? 'bg-teal-50/40' : '' }}"
                                 onclick="selectKategoriEl(this)"
                                 data-key="{{ $item['key'] }}"
                                 data-nama="{{ $item['nama'] }}"
                                 data-deskripsi="{{ $item['deskripsi'] }}"
                                 data-icon="{{ $item['icon'] }}"
                                 data-icon-bg="{{ $item['icon_bg'] }}">
                                <div class="flex items-start space-x-2.5">
                                    <div class="w-8 h-8 rounded-lg {{ $item['icon_bg'] }} flex items-center justify-center shrink-0 mt-0.5 group-hover:bg-teal-700 group-hover:text-white transition">
                                        <i class="{{ $item['icon'] }} text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="text-xs sm:text-sm font-bold text-slate-800 group-hover:text-teal-900 transition">
                                            {{ $item['nama'] }}
                                        </div>
                                        @if(!empty($item['deskripsi']))
                                        <div class="text-[11px] text-slate-400 group-hover:text-slate-600 transition">
                                            {{ $item['deskripsi'] }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <i class="fa-solid fa-check text-xs text-teal-700 {{ ($hasSelection && $activeCatKey === $item['key']) ? 'opacity-100' : 'opacity-0' }} group-hover:opacity-100 transition kategori-check-icon"></i>
                            </div>
                            @empty
                            <div class="px-4 py-4 text-center text-xs text-slate-400">
                                Belum ada kategori aktif di Data Master Admin.
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 2. Judul Kendala -->
                <div>
                    <label for="judul_kendala" class="block text-xs font-bold text-slate-900 mb-1.5">
                        Judul kendala <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="judul_kendala" 
                           id="judul_kendala" 
                           value="{{ old('judul_kendala') }}" 
                           required 
                           placeholder="Contoh: PC tidak bisa konek jaringan"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition">
                </div>

                <!-- 3. Lokasi / Ruangan (Searchable Combobox Modern & Rapi) -->
                <div class="relative" id="lokasi-combobox-container">
                    <label for="lokasi_ruangan" class="block text-xs font-bold text-slate-900 mb-1.5 flex items-center justify-between">
                        <span>Lokasi / ruangan <span class="text-rose-500">*</span></span>
                        <span class="text-[11px] font-normal text-slate-400">Pilih dari area atau ketik ruangan spesifik</span>
                    </label>
                    
                    <div class="relative">
                        <input type="text" 
                               name="lokasi_ruangan" 
                               id="lokasi_ruangan" 
                               autocomplete="off"
                               value="{{ old('lokasi_ruangan') }}" 
                               required 
                               placeholder="Pilih atau ketik, misal: Control Room Unit 1"
                               onclick="openLokasiDropdown()"
                               oninput="filterLokasiOptions(this.value)"
                               class="w-full pl-4 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition font-medium shadow-2xs">
                        
                        <button type="button" 
                                id="lokasi-toggle-btn"
                                onclick="toggleLokasiDropdown(event)" 
                                tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-teal-700 transition focus:outline-none">
                            <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" id="lokasi-chevron"></i>
                        </button>
                    </div>

                    <!-- Custom Dropdown Menu -->
                    <div id="lokasi-dropdown-menu" 
                         class="hidden absolute left-0 right-0 top-full mt-1.5 z-40 bg-white border border-slate-200 rounded-2xl shadow-xl max-h-64 overflow-y-auto divide-y divide-slate-100 py-1.5 transition-all">
                        
                        <div class="px-3.5 py-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 bg-slate-50/70 flex items-center justify-between">
                            <span>Pilihan Area Pembangkit PLTU</span>
                            <span class="text-[9px] font-normal text-slate-400">Klik untuk memilih</span>
                        </div>

                        <div id="lokasi-options-list" class="divide-y divide-slate-50">
                            @foreach($dbLocations ?? [] as $loc)
                            <div class="lokasi-option-item px-3.5 py-2.5 hover:bg-teal-50/70 cursor-pointer transition flex items-center justify-between group"
                                 onclick="selectLokasiEl(this)"
                                 data-value="{{ $loc->nama }}"
                                 data-nama="{{ strtolower($loc->nama) }}"
                                 data-deskripsi="{{ strtolower($loc->deskripsi ?? '') }}">
                                <div class="flex items-start space-x-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center shrink-0 mt-0.5 group-hover:bg-teal-700 group-hover:text-white transition">
                                        <i class="fa-solid fa-location-dot text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="text-xs sm:text-sm font-bold text-slate-800 group-hover:text-teal-900 transition">
                                            {{ $loc->nama }}
                                        </div>
                                        @if(!empty($loc->deskripsi))
                                        <div class="text-[11px] text-slate-400 group-hover:text-slate-600 transition">
                                            {{ $loc->deskripsi }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <i class="fa-solid fa-check text-xs text-teal-600 opacity-0 group-hover:opacity-100 transition"></i>
                            </div>
                            @endforeach
                        </div>

                        <!-- Empty State when searching -->
                        <div id="lokasi-empty-state" class="hidden px-4 py-3 text-center text-xs text-slate-500 bg-slate-50/50">
                            <i class="fa-solid fa-pen-to-square mr-1 text-teal-600"></i>
                            <span>Ruangan spesifik: "<strong id="lokasi-custom-text" class="text-slate-800"></strong>" (Lanjut ketik atau pilih)</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Deskripsi Keluhan -->
                <div>
                    <label for="deskripsi" class="block text-xs font-bold text-slate-900 mb-1.5">
                        Deskripsi <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="deskripsi" 
                              id="deskripsi" 
                              rows="4" 
                              required 
                              placeholder="Jelaskan gejala, kapan mulai terjadi, dan pesan error"
                              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-teal-700 focus:bg-white transition leading-relaxed">{{ old('deskripsi') }}</textarea>
                </div>

                <!-- 5. Prioritas (Pill Selectors) -->
                <div>
                    <label class="block text-xs font-bold text-slate-900 mb-2">
                        Prioritas <span class="text-rose-500">*</span>
                    </label>
                    <input type="hidden" name="prioritas" id="prioritas" value="{{ old('prioritas', 'Sedang') }}">
                    
                    <div class="flex flex-wrap gap-2" id="prioritas-pills">
                        @php
                            $selectedPriority = old('prioritas', 'Sedang');
                            $priorities = ['Rendah', 'Sedang', 'Mendesak'];
                        @endphp
                        @foreach($priorities as $p)
                        <button type="button" 
                                onclick="selectPriority('{{ $p }}')" 
                                data-priority="{{ $p }}"
                                class="priority-pill px-4 py-2 rounded-full text-xs font-semibold border transition duration-150 {{ $selectedPriority === $p ? 'bg-[#0e1a2b] text-white border-[#0e1a2b] shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200' }}">
                            {{ $p }}
                        </button>
                        @endforeach
                    </div>
                </div>

                <!-- Hidden file input for Photo Upload -->
                <input type="file" name="foto" id="foto-input" accept="image/*" class="hidden" onchange="handlePhotoUpload(this)">

                <!-- Photo Preview Container -->
                <div id="photo-preview-box" class="hidden p-3 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <img id="photo-preview-img" src="" alt="Lampiran" class="w-12 h-12 rounded-xl object-cover border border-slate-200">
                        <div>
                            <div id="photo-file-name" class="text-xs font-bold text-slate-800 truncate max-w-xs"></div>
                            <div class="text-[10px] text-slate-400">Lampiran foto kendala</div>
                        </div>
                    </div>
                    <button type="button" onclick="removePhoto()" class="text-rose-500 hover:text-rose-700 p-1 text-xs">
                        <i class="fa-solid fa-trash-can"></i> Hapus
                    </button>
                </div>

                <!-- Action Buttons: Lampirkan Foto & Kirim Tiket -->
                <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                    <button type="button" 
                            onclick="document.getElementById('foto-input').click()"
                            class="px-5 py-2.5 rounded-xl border border-slate-300 hover:border-slate-400 text-slate-700 hover:bg-slate-50 text-xs font-bold transition flex items-center space-x-2">
                        <i class="fa-solid fa-paperclip text-slate-500"></i>
                        <span>Lampirkan foto</span>
                    </button>

                    <button type="submit" 
                            class="px-6 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold shadow-md shadow-teal-900/20 transition flex items-center space-x-2">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        <span>Kirim Tiket</span>
                    </button>
                </div>

            </form>
        </div>

        <!-- RIGHT COLUMN: Coba Solusi Cepat Dulu (4 Cols) -->
        <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">Coba solusi cepat dulu</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    Berdasarkan kategori <span id="quick-category-title" class="font-bold {{ $hasSelection ? 'text-teal-700' : 'text-slate-400' }}">{{ $hasSelection ? $activeCatItem['nama'] : '- Belum dipilih -' }}</span>
                </p>
            </div>

            <!-- Dynamic Troubleshooting Tips -->
            <div id="quick-tips-container" class="space-y-2 text-xs font-medium text-slate-700">
                <!-- Javascript will populate based on category -->
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
    // Troubleshooting Tips Data
    @php
        $customTipsMap = collect($categoryItems)->mapWithKeys(function ($item) {
            return [
                $item['key'] => array_values(array_filter(
                    $item['solusi_cepat'] ?? [],
                    fn ($s) => trim((string) $s) !== ''
                )),
            ];
        })->all();
    @endphp
    const categoryCustomTips = {!! json_encode($customTipsMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};

    const quickTips = {
        'Jaringan': [
            'Cek kabel LAN dan lampu port di belakang CPU',
            'Restart adaptor jaringan / Wi-Fi komputer',
            'Pastikan koneksi Wi-Fi kantor terhubung dengan benar'
        ],
        'Printer': [
            'Pastikan kabel power dan kabel USB terpasang kencang',
            'Cek antrian cetak dan kertas macet (paper jam)',
            'Restart printer dan coba cetak ulang'
        ],
        'Hardware': [
            'Periksa kabel power dan stopkontak di bawah meja',
            'Pastikan monitor menyala dan kabel display (HDMI/VGA) kencang',
            'Catat bunyi beep atau lampu indikator jika CPU tidak mau menyala'
        ],
        'Akun': [
            'Periksa tombol Caps Lock saat mengetik password',
            'Pastikan format domain email (@s2p.co.id) sudah benar',
            'Jika akun terkunci karena salah PIN/password, tunggu 5 menit'
        ],
        'CCTV': [
            'Pastikan adaptor PoE atau switch CCTV dalam kondisi menyala',
            'Catat nomor channel kamera yang hilang atau blank',
            'Hubungi IT Support segera jika terjadi insiden keamanan mendesak'
        ],
        'Aplikasi': [
            'Coba tutup aplikasi dan jalankan kembali (Restart app)',
            'Coba buka aplikasi melalui browser lain jika berbasis web',
            'Pastikan koneksi jaringan LAN aktif sebelum login ke sistem'
        ],
        'Telepon': [
            'Periksa kabel gagang telepon dan jack RJ11 ke stopkontak',
            'Untuk radio HT, pastikan baterai terisi penuh dan antena kencang',
            'Pastikan saluran channel HT sesuai dengan grup divisi Anda'
        ],
        'Lainnya': [
            'Coba restart aplikasi atau komputer terlebih dahulu',
            'Pastikan tidak ada update Windows yang sedang pending',
            'Tuliskan pesan error lengkap pada formulir di samping'
        ]
    };

    function resolveQuickTipsKey(category, displayName = '') {
        if (quickTips[category]) return category;
        const combined = `${category || ''} ${displayName || ''}`.toLowerCase();
        if (combined.includes('jaringan') || combined.includes('lan') || combined.includes('wifi') || combined.includes('wi-fi')) return 'Jaringan';
        if (combined.includes('printer') || combined.includes('scanner')) return 'Printer';
        if (combined.includes('hardware') || combined.includes('pc') || combined.includes('komputer') || combined.includes('laptop')) return 'Hardware';
        if (combined.includes('akun') || combined.includes('email') || combined.includes('sandi') || combined.includes('password')) return 'Akun';
        if (combined.includes('cctv') || combined.includes('kamera') || combined.includes('nvr')) return 'CCTV';
        if (combined.includes('aplikasi') || combined.includes('software') || combined.includes('erp')) return 'Aplikasi';
        if (combined.includes('telepon') || combined.includes('pabx') || combined.includes('radio')) return 'Telepon';
        return 'Lainnya';
    }

    function onCategoryChange(category, displayName = '') {
        // Update Right Column Title
        const titleEl = document.getElementById('quick-category-title');
        if (titleEl) {
            if (category) {
                titleEl.textContent = displayName || category;
                titleEl.className = 'font-bold text-teal-700';
            } else {
                titleEl.textContent = '- Belum dipilih -';
                titleEl.className = 'font-bold text-slate-400';
            }
        }

        // Render Quick Tips
        renderQuickTips(category, displayName);
    }

    function selectPriority(priority) {
        document.getElementById('prioritas').value = priority;

        // Update Pills UI
        document.querySelectorAll('.priority-pill').forEach(pill => {
            if (pill.dataset.priority === priority) {
                pill.className = 'priority-pill px-4 py-2 rounded-full text-xs font-semibold border transition duration-150 bg-[#0e1a2b] text-white border-[#0e1a2b] shadow-xs';
            } else {
                pill.className = 'priority-pill px-4 py-2 rounded-full text-xs font-semibold border transition duration-150 bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200';
            }
        });
    }

    function renderQuickTips(category, displayName = '') {
        const container = document.getElementById('quick-tips-container');
        if (!container) return;

        if (!category) {
            container.innerHTML = `
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 text-center space-y-2">
                    <div class="w-9 h-9 rounded-full bg-teal-50 text-teal-700 mx-auto flex items-center justify-center text-sm">
                        <i class="fa-solid fa-lightbulb"></i>
                    </div>
                    <div class="text-xs font-bold text-slate-700">Panduan Penanganan Mandiri</div>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        Pilih kategori kendala di samping untuk melihat panduan solusi cepat sebelum teknisi tiba.
                    </p>
                </div>
            `;
            return;
        }

        const customTips = Array.isArray(categoryCustomTips[category])
            ? categoryCustomTips[category].filter(t => String(t).trim() !== '')
            : [];
        const fallbackKey = resolveQuickTipsKey(category, displayName);
        const tips = customTips.length > 0 ? customTips : (quickTips[fallbackKey] || quickTips['Lainnya']);

        container.replaceChildren();
        tips.forEach(tip => {
            const row = document.createElement('div');
            row.className = 'p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-start space-x-2.5';

            const icon = document.createElement('i');
            icon.className = 'fa-solid fa-circle-check text-teal-600 mt-0.5 text-xs';

            const label = document.createElement('span');
            label.textContent = tip;

            row.append(icon, label);
            container.appendChild(row);
        });
    }

    function handlePhotoUpload(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                document.getElementById('photo-preview-img').src = e.target.result;
                document.getElementById('photo-file-name').textContent = file.name;
                document.getElementById('photo-preview-box').classList.remove('hidden');
            }

            reader.readAsDataURL(file);
        }
    }

    function removePhoto() {
        const input = document.getElementById('foto-input');
        input.value = '';
        document.getElementById('photo-preview-box').classList.add('hidden');
    }

    // Combobox Lokasi / Ruangan Handlers
    function openLokasiDropdown() {
        closeKategoriDropdown();
        const menu = document.getElementById('lokasi-dropdown-menu');
        const chevron = document.getElementById('lokasi-chevron');
        if (menu) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    function closeLokasiDropdown() {
        const menu = document.getElementById('lokasi-dropdown-menu');
        const chevron = document.getElementById('lokasi-chevron');
        if (menu) {
            menu.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        }
    }

    function toggleLokasiDropdown(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const menu = document.getElementById('lokasi-dropdown-menu');
        if (menu && menu.classList.contains('hidden')) {
            openLokasiDropdown();
            document.getElementById('lokasi_ruangan')?.focus();
        } else {
            closeLokasiDropdown();
        }
    }

    function selectLokasiEl(el) {
        selectLokasi(el?.dataset?.value || '');
    }

    function selectLokasi(nama) {
        const input = document.getElementById('lokasi_ruangan');
        if (input) {
            input.value = nama;
        }
        closeLokasiDropdown();
    }

    function filterLokasiOptions(query) {
        openLokasiDropdown();
        const q = (query || '').toLowerCase().trim();
        const items = document.querySelectorAll('.lokasi-option-item');
        let matchCount = 0;

        items.forEach(item => {
            const nama = item.getAttribute('data-nama') || '';
            const deskripsi = item.getAttribute('data-deskripsi') || '';
            if (!q || nama.includes(q) || deskripsi.includes(q)) {
                item.classList.remove('hidden');
                matchCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        const emptyState = document.getElementById('lokasi-empty-state');
        const customText = document.getElementById('lokasi-custom-text');
        if (emptyState && customText) {
            if (matchCount === 0 && q.length > 0) {
                customText.textContent = query;
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
            }
        }
    }

    // Custom Dropdown Kategori Handlers
    function openKategoriDropdown() {
        const menu = document.getElementById('kategori-dropdown-menu');
        const chevron = document.getElementById('kategori-chevron');
        if (menu) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        }
    }

    function closeKategoriDropdown() {
        const menu = document.getElementById('kategori-dropdown-menu');
        const chevron = document.getElementById('kategori-chevron');
        if (menu) {
            menu.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        }
    }

    function toggleKategoriDropdown(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const menu = document.getElementById('kategori-dropdown-menu');
        if (menu && menu.classList.contains('hidden')) {
            closeLokasiDropdown();
            openKategoriDropdown();
        } else {
            closeKategoriDropdown();
        }
    }

    function selectKategoriEl(el) {
        if (!el) return;
        selectKategori(
            el.dataset.key || '',
            el.dataset.nama || '',
            el.dataset.deskripsi || '',
            el.dataset.icon || 'fa-solid fa-screwdriver-wrench',
            el.dataset.iconBg || 'bg-slate-100 text-slate-700'
        );
    }

    function selectKategori(key, nama, deskripsi, icon, iconBg) {
        const select = document.getElementById('jenis_perangkat');
        if (select) {
            select.value = key;
        }

        // Update trigger display
        const nameEl = document.getElementById('kategori-trigger-nama');
        const descEl = document.getElementById('kategori-trigger-desc');
        const iconBox = document.getElementById('kategori-trigger-icon-box');
        const iconEl = document.getElementById('kategori-trigger-icon');

        if (nameEl) {
            nameEl.textContent = nama;
            nameEl.className = 'text-xs sm:text-sm font-bold text-slate-800';
        }
        if (descEl) descEl.textContent = deskripsi ? `(${deskripsi})` : '';
        if (iconBox) iconBox.className = `w-7 h-7 rounded-lg ${iconBg} flex items-center justify-center shrink-0`;
        if (iconEl) iconEl.className = `${icon} text-xs`;

        // Update checkmarks in options
        document.querySelectorAll('.kategori-option-item').forEach(item => {
            const check = item.querySelector('.kategori-check-icon');
            if (item.getAttribute('data-key') === key) {
                item.classList.add('bg-teal-50/40');
                if (check) check.classList.replace('opacity-0', 'opacity-100');
            } else {
                item.classList.remove('bg-teal-50/40');
                if (check) check.classList.replace('opacity-100', 'opacity-0');
            }
        });

        // Trigger right column tips update
        onCategoryChange(key, nama);

        closeKategoriDropdown();
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const locContainer = document.getElementById('lokasi-combobox-container');
        if (locContainer && !locContainer.contains(e.target)) {
            closeLokasiDropdown();
        }

        const catContainer = document.getElementById('kategori-dropdown-container');
        if (catContainer && !catContainer.contains(e.target)) {
            closeKategoriDropdown();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLokasiDropdown();
            closeKategoriDropdown();
        }
    });

    // Initial render
    document.addEventListener('DOMContentLoaded', function() {
        const selectEl = document.getElementById('jenis_perangkat');
        const initialCat = selectEl?.value || '';
        const initialNama = selectEl?.options[selectEl.selectedIndex]?.dataset?.nama || '';
        if (initialCat) {
            onCategoryChange(initialCat, initialNama);
        } else {
            renderQuickTips('');
        }
    });
</script>
@endpush
@endsection
