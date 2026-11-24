@extends('layouts.app')

@section('title', 'Basis Pengetahuan & SOP Troubleshooting IT')

@section('content')
<div class="space-y-6">

    <!-- Header: Judul di Kiri, Tombol Aksi & Widget Tanggal/Notifikasi di Kanan (Bebas Scrollbar) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                {{ Auth::user()->isPegawai() ? 'Panduan Mandiri Pegawai' : 'Basis Pengetahuan & SOP Troubleshooting' }}
            </h1>
            <p class="text-xs text-slate-500 mt-1 font-medium">
                {{ Auth::user()->isPegawai() 
                    ? 'Panduan penanganan mandiri kendala ringan perangkat komputer dan jaringan kantor PLTU Karangkandri' 
                    : 'Panduan standar penanganan gangguan perangkat keras dan jaringan IT di lingkungan PT S2P PLTU Karangkandri' }}
            </p>
        </div>

        @if(Auth::user()->isTeknisi() || Auth::user()->isAdmin())
        <!-- Actions & Header Widget: Bersih, Rapi, Sejajar Sempurna -->
        <div class="flex items-center space-x-2.5 flex-shrink-0">
            <!-- Tombol + Tulis Panduan -->
            <a href="{{ route('knowledge.create') }}" 
               class="px-3.5 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold shadow-xs transition flex items-center space-x-1.5 flex-shrink-0 whitespace-nowrap cursor-pointer">
                <i class="fa-solid fa-plus text-[10px]"></i>
                <span>Tulis Panduan</span>
            </a>

            <!-- Header Widget: Tanggal & Lonceng Notifikasi -->
            @include('partials.header-widget')
        </div>
        @endif
    </div>

    <!-- Alert Notifikasi Flash -->
    @include('partials.flash-messages')

    <!-- Toolbar Terpadu: Filter Kategori, Target Akses, Counter & Kotak Pencarian -->
    <div class="bg-white p-3 sm:p-3.5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        
        <!-- Sisi Kiri: Filter Kategori, Target Akses, & Counter Terpadu -->
        <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
            <!-- Dropdown Kategori -->
            <div class="inline-flex items-center space-x-2 bg-slate-50/80 hover:bg-slate-50 px-3 py-2 rounded-xl border border-slate-200 text-xs">
                <i class="fa-solid fa-folder-tree text-teal-700 text-xs"></i>
                <label for="select-kategori" class="font-bold text-slate-700 whitespace-nowrap">Kategori:</label>
                <select id="select-kategori" onchange="filterCategory(this.value)" 
                        class="bg-transparent font-semibold text-slate-800 focus:outline-none cursor-pointer pr-1">
                    <option value="all">Semua SOP ({{ $guides->count() }})</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            @if(Auth::user()->isTeknisi() || Auth::user()->isAdmin())
            <!-- Dropdown Target Akses -->
            <div class="inline-flex items-center space-x-2 bg-slate-50/80 hover:bg-slate-50 px-3 py-2 rounded-xl border border-slate-200 text-xs">
                <i class="fa-solid fa-users-viewfinder text-teal-700 text-xs"></i>
                <label for="select-audience" class="font-bold text-slate-700 whitespace-nowrap">Target:</label>
                <select id="select-audience" onchange="filterAudience(this.value)" 
                        class="bg-transparent font-semibold text-slate-800 focus:outline-none cursor-pointer pr-1">
                    <option value="all">Semua Akses</option>
                    <option value="teknisi">🔒 Teknisi Saja</option>
                    <option value="all_users">👥 Dibaca Pegawai</option>
                </select>
            </div>
            @endif

            <!-- SOP Match Counter Badge -->
            <span id="sop-count-badge" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200/60">
                <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                <span id="sop-count-text">{{ $guides->count() }} SOP</span>
            </span>
        </div>

        <!-- Sisi Kanan: Kotak Pencarian Cepat -->
        <div class="flex items-center gap-2 w-full md:w-auto flex-shrink-0">
            <div class="relative w-full md:w-72">
                <input type="text" id="sop-search" value="{{ request('search') }}" onkeyup="filterSop()" placeholder="Cari SOP (kabel, ram, printer)..."
                       class="w-full pl-9 pr-3 py-2 bg-slate-50/80 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-700/20 focus:border-teal-700 transition">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                </span>
            </div>
            @if(request()->filled('search') || request()->filled('kategori'))
            <a href="{{ route('knowledge.base') }}"
               class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold whitespace-nowrap transition">
                Reset
            </a>
            @endif
        </div>

    </div>

    <!-- SOP Cards Grid (2-Column Desktop Grid Sesuai Mockup) -->
    @php
        $assignedTopicAnchors = [
            'printer' => false,
            'akun' => false,
            'jaringan' => false,
        ];
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="sop-container">
        @forelse($guides as $guide)
        @php
            $guideSearchable = strtolower($guide->judul . ' ' . $guide->kategori . ' ' . $guide->tag . ' ' . $guide->pengantar);
            $cardAnchorId = null;
            if (!$assignedTopicAnchors['printer'] && (str_contains($guideSearchable, 'printer') || str_contains($guideSearchable, 'cetak') || str_contains($guideSearchable, 'spooler'))) {
                $cardAnchorId = 'printer';
                $assignedTopicAnchors['printer'] = true;
            } elseif (!$assignedTopicAnchors['akun'] && (str_contains($guideSearchable, 'akun') || str_contains($guideSearchable, 'password') || str_contains($guideSearchable, 'sandi') || str_contains($guideSearchable, 'email'))) {
                $cardAnchorId = 'akun';
                $assignedTopicAnchors['akun'] = true;
            } elseif (!$assignedTopicAnchors['jaringan'] && (str_contains($guideSearchable, 'jaringan') || str_contains($guideSearchable, 'wi-fi') || str_contains($guideSearchable, 'wifi') || str_contains($guideSearchable, 'ip') || str_contains($guideSearchable, 'lan'))) {
                $cardAnchorId = 'jaringan';
                $assignedTopicAnchors['jaringan'] = true;
            }

            $catLower = strtolower($guide->kategori);
            $catIcon = 'fa-solid fa-book-bookmark';
            $catColor = 'bg-teal-50 text-teal-700 border border-teal-200/60';
            $iconBg = 'bg-teal-50 text-teal-700 border border-teal-100';

            if (str_contains($catLower, 'jaringan') || str_contains($catLower, 'lan')) {
                $catIcon = 'fa-solid fa-network-wired';
                $catColor = 'bg-teal-50 text-teal-700 border border-teal-200/60';
                $iconBg = 'bg-teal-50 text-teal-700 border border-teal-100';
            } elseif (str_contains($catLower, 'hardware') || str_contains($catLower, 'pc')) {
                $catIcon = 'fa-solid fa-microchip';
                $catColor = 'bg-amber-50 text-amber-700 border border-amber-200/60';
                $iconBg = 'bg-amber-50 text-amber-700 border border-amber-100';
            } elseif (str_contains($catLower, 'printer')) {
                $catIcon = 'fa-solid fa-print';
                $catColor = 'bg-purple-50 text-purple-700 border border-purple-200/60';
                $iconBg = 'bg-purple-50 text-purple-700 border border-purple-100';
            } elseif (str_contains($catLower, 'akun') || str_contains($catLower, 'keamanan')) {
                $catIcon = 'fa-solid fa-user-shield';
                $catColor = 'bg-rose-50 text-rose-700 border border-rose-200/60';
                $iconBg = 'bg-rose-50 text-rose-700 border border-rose-100';
            } elseif (str_contains($catLower, 'aplikasi') || str_contains($catLower, 'email')) {
                $catIcon = 'fa-solid fa-laptop-code';
                $catColor = 'bg-blue-50 text-blue-700 border border-blue-200/60';
                $iconBg = 'bg-blue-50 text-blue-700 border border-blue-100';
            } elseif (str_contains($catLower, 'audio') || str_contains($catLower, 'rapat')) {
                $catIcon = 'fa-solid fa-video';
                $catColor = 'bg-indigo-50 text-indigo-700 border border-indigo-200/60';
                $iconBg = 'bg-indigo-50 text-indigo-700 border border-indigo-100';
            }
        @endphp
        <div @if($cardAnchorId) id="{{ $cardAnchorId }}" @endif
             class="sop-card scroll-mt-24 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4 hover:border-teal-500/40 hover:shadow-md transition duration-150 flex flex-col justify-between"
             data-category="{{ $guide->kategori }}"
             data-audience="{{ $guide->target_audience }}"
             data-status="{{ $guide->status }}"
             data-text="{{ strtolower($guide->judul . ' ' . $guide->kategori . ' ' . $guide->area_penerapan . ' ' . $guide->pengantar . ' ' . $guide->tag . ' ' . $guide->perintah_cmd) }}">
            
            <div class="space-y-3.5">
                <!-- Card Header: Icon, Judul, Area, & Badges -->
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start space-x-3 min-w-0">
                        <!-- Category Icon -->
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm flex-shrink-0 shadow-xs {{ $iconBg }}">
                            <i class="{{ $catIcon }}"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="font-extrabold text-slate-900 text-sm leading-snug">
                                {{ $guide->judul }}
                            </h2>
                            @if($guide->area_penerapan)
                            <span class="text-[11px] text-slate-400 font-medium block truncate mt-0.5">
                                <i class="fa-solid fa-location-dot text-slate-300 mr-1 text-[10px]"></i>
                                {{ $guide->area_penerapan }}
                            </span>
                            @endif
                        </div>
                    </div>

                    <!-- Category & Audience Badges -->
                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $catColor }}">
                            {{ $guide->kategori }}
                        </span>

                        @if(Auth::user()->isTeknisi() || Auth::user()->isAdmin())
                            @if($guide->status === 'Draft')
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600 border border-slate-300">
                                    Draft
                                </span>
                            @elseif($guide->isForEveryone())
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center space-x-1" title="Panduan ini tampil di Panduan Mandiri Pegawai">
                                    <i class="fa-solid fa-users text-[8px]"></i>
                                    <span>Teknisi & Pegawai</span>
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600 border border-slate-200 flex items-center space-x-1" title="Khusus teknisi IT Support">
                                    <i class="fa-solid fa-lock text-[8px]"></i>
                                    <span>Teknisi Saja</span>
                                </span>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- Pengantar / Gejala -->
                @if($guide->pengantar)
                <p class="text-xs text-slate-600 leading-relaxed font-normal">
                    {{ $guide->pengantar }}
                </p>
                @endif

                <!-- Diagram Kabel Khusus (Jika Ada) -->
                @if(!empty($guide->diagram_kabel))
                <div class="p-3 bg-slate-50/90 rounded-xl font-mono text-[11px] text-slate-700 border border-slate-200 space-y-1">
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                        @foreach($guide->diagram_kabel as $color)
                            <div>{{ $color }}</div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Langkah-Langkah (Jika Ada) -->
                @if(!empty($guide->langkah_langkah))
                <ol class="list-decimal list-inside text-xs text-slate-700 space-y-1.5 font-normal leading-relaxed">
                    @foreach($guide->langkah_langkah as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ol>
                @endif

                <!-- Perintah Command Prompt / Terminal Script (Jika Ada) -->
                @if($guide->perintah_cmd)
                <div class="rounded-xl overflow-hidden border border-slate-800 bg-[#09121e] text-white">
                    <div class="px-3.5 py-1.5 bg-[#0e1a2b] border-b border-slate-800 flex items-center justify-between text-[11px]">
                        <span class="text-slate-300 font-mono flex items-center space-x-1.5">
                            <i class="fa-solid fa-terminal text-teal-400 text-[10px]"></i>
                            <span>{{ $guide->judul_perintah ?: 'Perintah Perbaikan (CMD)' }}</span>
                        </span>
                        <button type="button" 
                                onclick="copyCommand(this, {{ json_encode($guide->perintah_cmd) }})"
                                class="px-2 py-0.5 rounded bg-slate-800 hover:bg-teal-700 text-slate-200 hover:text-white text-[10px] font-semibold transition cursor-pointer flex items-center space-x-1">
                            <i class="fa-regular fa-copy text-[9px]"></i>
                            <span>Salin</span>
                        </button>
                    </div>
                    <pre class="p-3 font-mono text-[11px] text-teal-300 leading-relaxed overflow-x-auto selection:bg-teal-600 selection:text-white"><code>{{ $guide->perintah_cmd }}</code></pre>
                </div>
                @endif

                <!-- Peringatan / Catatan Penting -->
                @if($guide->peringatan)
                <div class="text-[11px] text-amber-900 bg-amber-50/80 p-3 rounded-xl border border-amber-200 flex items-start space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5 flex-shrink-0"></i>
                    <div class="leading-relaxed font-normal">
                        {{ $guide->peringatan }}
                    </div>
                </div>
                @endif
            </div>

            <!-- Card Footer: Tag & Actions -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                <div class="flex items-center space-x-1.5 truncate">
                    @if($guide->tag)
                        <i class="fa-solid fa-tag text-slate-300"></i>
                        <span>{{ $guide->tag }}</span>
                    @else
                        <span>PLTU Karangkandri SOP</span>
                    @endif
                </div>

                @if(Auth::user()->isTeknisi() || Auth::user()->isAdmin())
                <div class="flex items-center space-x-2 flex-shrink-0">
                    <a href="{{ route('knowledge.edit', $guide) }}"
                       class="px-2 py-1 rounded-lg bg-slate-50 hover:bg-teal-50 text-slate-600 hover:text-teal-700 border border-slate-200 hover:border-teal-200 font-semibold transition flex items-center space-x-1"
                       title="Edit SOP">
                        <i class="fa-regular fa-pen-to-square text-[10px]"></i>
                        <span>Edit</span>
                    </a>
                    <form action="{{ route('knowledge.destroy', $guide) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SOP ' + {{ Js::from($guide->judul) }} + '?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-2 py-1 rounded-lg bg-slate-50 hover:bg-rose-50 text-slate-500 hover:text-rose-600 border border-slate-200 hover:border-rose-200 font-semibold transition flex items-center space-x-1 cursor-pointer" title="Hapus SOP">
                            <i class="fa-regular fa-trash-can text-[10px]"></i>
                            <span>Hapus</span>
                        </button>
                    </form>
                </div>
                @endif
            </div>

        </div>
        @empty
        <div class="col-span-1 md:col-span-2 p-12 text-center bg-white rounded-2xl border border-slate-200/80 text-slate-400">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <div class="text-sm font-bold text-slate-800">Belum Ada Panduan SOP</div>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                {{ request()->filled('search') ? 'Tidak ada panduan yang cocok dengan pencarian "' . request('search') . '".' : 'Belum ada panduan troubleshooting yang diterbitkan untuk kategori ini.' }}
            </p>
            @if(request()->filled('search') || request()->filled('kategori'))
            <a href="{{ route('knowledge.base') }}" class="inline-block mt-3 px-4 py-1.5 rounded-lg bg-teal-700 text-white text-xs font-semibold hover:bg-teal-800 transition">
                Tampilkan Semua Panduan
            </a>
            @endif
        </div>
        @endforelse
    </div>

    <!-- Empty Search Results Placeholder (Client-Side) -->
    <div id="no-search-results" class="hidden p-12 text-center bg-white rounded-2xl border border-slate-200/80 text-slate-400">
        <div class="w-12 h-12 rounded-full bg-teal-50 text-teal-600 flex items-center justify-center mx-auto mb-3 text-lg">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <div class="text-sm font-bold text-slate-800">Tidak ada SOP yang cocok</div>
        <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
            Coba gunakan kata kunci lain seperti <em>kabel</em>, <em>ram</em>, <em>ip</em>, atau <em>printer</em>.
        </p>
        <button onclick="resetFilters()" class="mt-3 px-4 py-1.5 rounded-lg bg-teal-700 text-white text-xs font-semibold hover:bg-teal-800 transition">
            Reset Pencarian
        </button>
    </div>

    @if(Auth::user()->isPegawai() && !request()->filled('search') && (!$assignedTopicAnchors['printer'] || !$assignedTopicAnchors['akun'] || !$assignedTopicAnchors['jaringan']))
    @php
        $fallbackQuickTopics = [
            'printer' => [
                'title' => 'Printer tidak terdeteksi / offline',
                'kategori' => 'Printer',
                'steps' => [
                    'Pastikan printer menyala serta kabel power dan USB/LAN terpasang kencang.',
                    'Restart printer dan hapus antrian dokumen cetak yang macet.',
                ],
            ],
            'akun' => [
                'title' => 'Lupa password Windows / email (@s2p.co.id)',
                'kategori' => 'Akun',
                'steps' => [
                    'Periksa tombol Caps Lock dan pastikan penulisan alamat email @s2p.co.id sudah tepat.',
                    'Jika akun terkunci sementara, tunggu 5 menit lalu coba kembali atau ajukan reset password ke IT Support.',
                ],
            ],
            'jaringan' => [
                'title' => 'Tidak bisa konek Wi-Fi / LAN kantor',
                'kategori' => 'Jaringan',
                'steps' => [
                    'Pastikan Wi-Fi tidak dalam Airplane Mode dan kabel LAN tidak dipasang bersamaan dengan Wi-Fi.',
                    'Matikan lalu hidupkan ulang adaptor Wi-Fi/jaringan komputer Anda.',
                ],
            ],
        ];
    @endphp
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-4">
        <div>
            <h2 class="text-sm font-extrabold text-slate-900">Bantuan Cepat Kendala Umum</h2>
            <p class="text-[11px] text-slate-400 mt-0.5">Langkah awal sebelum mengajukan tiket bantuan ke IT Support</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($fallbackQuickTopics as $topicSlug => $topicData)
                @if(!$assignedTopicAnchors[$topicSlug])
                <div id="{{ $topicSlug }}" class="scroll-mt-24 p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2.5 transition duration-200">
                    <div class="font-bold text-xs sm:text-sm text-slate-900">{{ $topicData['title'] }}</div>
                    <ol class="list-decimal list-inside text-xs text-slate-600 space-y-1">
                        @foreach($topicData['steps'] as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                    <div class="pt-1">
                        <a href="{{ route('services.create', ['kategori' => $topicData['kategori']]) }}"
                           class="inline-flex items-center space-x-1.5 text-[11px] font-bold text-teal-700 hover:text-teal-800">
                            <span>Masih terkendala? Buat Tiket {{ $topicData['kategori'] }}</span>
                            <i class="fa-solid fa-arrow-right text-[9px]"></i>
                        </a>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
    let currentCategory = 'all';
    let currentAudience = 'all';

    function filterCategory(cat) {
        currentCategory = cat;
        const sel = document.getElementById('select-kategori');
        if (sel && sel.value !== cat) {
            sel.value = cat;
        }
        applyAllFilters();
    }

    function filterAudience(aud) {
        currentAudience = aud;
        const sel = document.getElementById('select-audience');
        if (sel && sel.value !== aud) {
            sel.value = aud;
        }
        applyAllFilters();
    }

    function filterSop() {
        applyAllFilters();
    }

    function applyAllFilters() {
        const query = document.getElementById('sop-search').value.toLowerCase().trim();
        const cards = document.querySelectorAll('.sop-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const cardCat = card.getAttribute('data-category') || '';
            const cardAud = card.getAttribute('data-audience') || '';
            const cardText = card.getAttribute('data-text') || '';

            let matchCategory = (currentCategory === 'all') || (cardCat.toLowerCase().includes(currentCategory.toLowerCase()));
            let matchAudience = (currentAudience === 'all') || 
                                (currentAudience === 'teknisi' && cardAud === 'teknisi') ||
                                (currentAudience === 'all_users' && cardAud === 'all');
            let matchSearch = !query || cardText.includes(query);

            if (matchCategory && matchAudience && matchSearch) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        // Update counter teks
        const countText = document.getElementById('sop-count-text');
        if (countText) {
            countText.textContent = visibleCount + ' SOP';
        }

        const noResults = document.getElementById('no-search-results');
        if (noResults) {
            if (visibleCount === 0 && cards.length > 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }
    }

    function resetFilters() {
        document.getElementById('sop-search').value = '';
        const selCat = document.getElementById('select-kategori');
        if (selCat) selCat.value = 'all';
        const selAud = document.getElementById('select-audience');
        if (selAud) selAud.value = 'all';
        currentCategory = 'all';
        currentAudience = 'all';
        applyAllFilters();
    }

    function copyCommand(btn, text) {
        navigator.clipboard.writeText(text).then(() => {
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check text-[9px] text-emerald-300"></i> <span class="text-emerald-300">Tersalin!</span>';
            setTimeout(() => {
                btn.innerHTML = originalHTML;
            }, 1800);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const hashId = (window.location.hash || '').replace('#', '').trim();
        if (hashId) {
            const targetEl = document.getElementById(hashId);
            if (targetEl) {
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetEl.classList.add('ring-2', 'ring-teal-600', 'shadow-lg');
                setTimeout(() => {
                    targetEl.classList.remove('ring-2', 'ring-teal-600', 'shadow-lg');
                }, 3000);
            }
        }
    });
</script>
@endpush
@endsection
