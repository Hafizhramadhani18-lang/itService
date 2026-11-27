@extends('layouts.app')

@section('title', 'Ruang Kerja Asisten AI & SOP IT Support')

@section('content')
<div class="space-y-3 flex-1 flex flex-col min-h-0">

    <!-- Header: Judul di Kiri, Tombol Aksi & Widget Tanggal/Notifikasi di Kanan -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 flex-shrink-0">
        <div>
            <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full bg-gradient-to-r from-teal-50 to-emerald-50 border border-teal-200/80 text-[10px] font-extrabold text-teal-800 mb-1">
                <i class="fa-solid fa-wand-magic-sparkles text-amber-500 animate-pulse"></i>
                <span>Ruang Kerja Asisten AI IT Support PT S2P PLTU Karangkandri</span>
            </div>
            <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                <span>{{ Auth::user()->isPegawai() ? 'Asisten Virtual AI (Gemini)' : 'Asisten AI & Katalog SOP IT' }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-black bg-emerald-100 text-emerald-800 border border-emerald-300">Online 24/7</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5 font-medium">
                {{ Auth::user()->isPegawai() 
                    ? 'Tanyakan apa saja kepada Asisten AI Gemini—mulai dari keseharian, hobi, pekerjaan, projek, hingga kendala IT kantor.' 
                    : 'Pusat interaksi Asisten AI (Gemini) & katalog standar operasional prosedur penanganan gangguan IT PT S2P PLTU Karangkandri.' }}
            </p>
        </div>

        @if(Auth::user()->isTeknisi() || Auth::user()->isAdmin())
        <div class="flex items-center space-x-2.5 flex-shrink-0">
            <a href="{{ route('knowledge.create') }}" 
               class="px-3.5 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold shadow-xs transition flex items-center space-x-1.5 flex-shrink-0 whitespace-nowrap cursor-pointer">
                <i class="fa-solid fa-plus text-[10px]"></i>
                <span>Tulis Panduan</span>
            </a>
            @include('partials.header-widget')
        </div>
        @endif
    </div>

    <!-- Alert Notifikasi Flash -->
    @include('partials.flash-messages')

    <!-- DUAL LAYOUT: WORKSPACE AI CHAT DI KIRI & KATALOG SOP RESMI DI KANAN (Tinggi pas 1 layar, tidak memanjang ke bawah) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch flex-1 min-h-0">
        
        <!-- ============================================================== -->
        <!-- KOLOM KIRI: RUANG KERJA ASISTEN AI INTERAKTIF (GEMINI AI)      -->
        <!-- ============================================================== -->
        <div class="{{ Auth::user()->isPegawai() ? 'lg:col-span-12' : 'lg:col-span-7 xl:col-span-8' }} flex flex-col bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden h-full">
            
            <!-- Workspace Top Bar -->
            <div class="px-5 py-3.5 bg-[#0e1a2b] text-white flex items-center justify-between border-b border-slate-800 flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white shadow font-black">
                            <i class="fa-solid fa-robot text-base"></i>
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 border-2 border-[#0e1a2b] rounded-full"></span>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h2 class="font-extrabold text-xs sm:text-sm text-white">Asisten Virtual AI (Gemini)</h2>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Cerdas &amp; Aktif</span>
                        </div>
                        <p class="text-[10px] text-slate-300">Siap menjawab pertanyaan keseharian, hobi, pekerjaan, projek, &amp; kendala IT</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button type="button" onclick="clearWorkspaceChat()" 
                            class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 text-xs transition cursor-pointer" 
                            title="Bersihkan Percakapan">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                    <a href="https://wa.me/6281234567890?text={{ urlencode('Halo Helpdesk IT PT S2P PLTU Karangkandri, saya butuh bantuan teknisi.') }}" 
                       target="_blank" 
                       class="inline-flex items-center space-x-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-600/90 hover:bg-emerald-600 text-white text-[11px] font-bold transition shadow-xs">
                        <i class="fa-brands fa-whatsapp text-xs"></i>
                        <span class="hidden sm:inline">WA Teknisi</span>
                    </a>
                </div>
            </div>

            <!-- Workspace Messages History (Scrollable Area) -->
            <div id="workspace-chat-messages" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-3.5 bg-slate-50/60 text-xs leading-relaxed">
                
                <!-- Executive AI Greeting Card & Quick Diagnostic Hub (Tampilan Penuh, Rapi & Seimbang) -->
                <div class="space-y-4 animate-fade-in max-w-2xl mx-auto py-1" id="workspace-hero-greeting">
                    <!-- Hero Banner -->
                    <div class="bg-gradient-to-br from-[#0e1a2b] via-[#122238] to-[#152e4d] text-white p-4 sm:p-5 rounded-2xl shadow-sm border border-slate-800 relative overflow-hidden">
                        <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-teal-500/10 rounded-full blur-2xl pointer-events-none"></div>
                        <div class="relative z-10 flex items-start space-x-3.5">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-white flex items-center justify-center text-lg flex-shrink-0 shadow-md shadow-teal-950/40">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            </div>
                            <div class="space-y-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <h3 class="font-extrabold text-sm sm:text-base text-white truncate">Halo, {{ Auth::user()->name }}! 👋</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex-shrink-0">Online 24/7</span>
                                </div>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    Saya <strong>Asisten AI (Google Gemini) PT S2P</strong>. Anda bebas bertanya <strong>apa saja</strong>—mulai dari obrolan keseharian, hobi, ide projek, pekerjaan, rumus Excel, hingga kendala IT kantor:
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 6 Kartu Diagnosa Cepat Populer (Tampilan Proporsional & Mengisi Ruang dengan Rapi) -->
                    <div class="space-y-2">
                        <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider px-1 flex items-center justify-between">
                            <span class="flex items-center space-x-1.5">
                                <i class="fa-solid fa-bolt text-amber-500"></i>
                                <span>Pilih Topik Bantuan Populer</span>
                            </span>
                            <span class="text-teal-700 font-bold">atau ketik di bawah &darr;</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                            <!-- 1. Printer Error -->
                            <button type="button" 
                                    onclick="askWorkspaceAi('Printer kantor saya mendadak offline dan antrian cetak macet, bagaimana cara memperbaikinya?')"
                                    class="p-3 rounded-xl bg-white hover:bg-teal-50/70 border border-slate-200/90 hover:border-teal-400 shadow-2xs hover:shadow-xs transition text-left group cursor-pointer flex items-start space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-700 group-hover:bg-teal-700 group-hover:text-white flex items-center justify-center text-xs flex-shrink-0 transition">
                                    <i class="fa-solid fa-print"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-slate-800 group-hover:text-teal-900 truncate">Printer Error</div>
                                    <div class="text-[10px] text-slate-500 leading-tight mt-0.5">Offline, jam, & spooler</div>
                                </div>
                            </button>

                            <!-- 2. Jaringan LAN / Wi-Fi -->
                            <button type="button" 
                                    onclick="askWorkspaceAi('Koneksi kabel LAN kantor tanda seru kuning No Internet, langkah diagnosanya apa saja?')"
                                    class="p-3 rounded-xl bg-white hover:bg-teal-50/70 border border-slate-200/90 hover:border-teal-400 shadow-2xs hover:shadow-xs transition text-left group cursor-pointer flex items-start space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-700 group-hover:bg-sky-700 group-hover:text-white flex items-center justify-center text-xs flex-shrink-0 transition">
                                    <i class="fa-solid fa-network-wired"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-slate-800 group-hover:text-teal-900 truncate">Jaringan LAN / Wi-Fi</div>
                                    <div class="text-[10px] text-slate-500 leading-tight mt-0.5">Kabel lepas & limited IP</div>
                                </div>
                            </button>

                            <!-- 3. PC Hang / BSOD -->
                            <button type="button" 
                                    onclick="askWorkspaceAi('Komputer Windows mendadak freeze dan layar biru BSOD, bagaimana penanganannya?')"
                                    class="p-3 rounded-xl bg-white hover:bg-teal-50/70 border border-slate-200/90 hover:border-teal-400 shadow-2xs hover:shadow-xs transition text-left group cursor-pointer flex items-start space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 group-hover:bg-indigo-700 group-hover:text-white flex items-center justify-center text-xs flex-shrink-0 transition">
                                    <i class="fa-solid fa-desktop"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-slate-800 group-hover:text-teal-900 truncate">PC Lambat / Freeze</div>
                                    <div class="text-[10px] text-slate-500 leading-tight mt-0.5">RAM penuh & Blue Screen</div>
                                </div>
                            </button>

                            <!-- 4. Reset Password -->
                            <button type="button" 
                                    onclick="askWorkspaceAi('Akun domain Windows saya terkunci dan lupa kata sandi login, bagaimana prosedurnya?')"
                                    class="p-3 rounded-xl bg-white hover:bg-teal-50/70 border border-slate-200/90 hover:border-teal-400 shadow-2xs hover:shadow-xs transition text-left group cursor-pointer flex items-start space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 group-hover:bg-amber-700 group-hover:text-white flex items-center justify-center text-xs flex-shrink-0 transition">
                                    <i class="fa-solid fa-key"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-slate-800 group-hover:text-teal-900 truncate">Reset Password</div>
                                    <div class="text-[10px] text-slate-500 leading-tight mt-0.5">Domain Windows PLTU</div>
                                </div>
                            </button>

                            <!-- 5. Email Outlook -->
                            <button type="button" 
                                    onclick="askWorkspaceAi('Aplikasi Microsoft Outlook tidak bisa kirim atau terima email baru, bagaimana cara mengatasinya?')"
                                    class="p-3 rounded-xl bg-white hover:bg-teal-50/70 border border-slate-200/90 hover:border-teal-400 shadow-2xs hover:shadow-xs transition text-left group cursor-pointer flex items-start space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-700 group-hover:bg-rose-700 group-hover:text-white flex items-center justify-center text-xs flex-shrink-0 transition">
                                    <i class="fa-solid fa-envelope"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-slate-800 group-hover:text-teal-900 truncate">Email Outlook</div>
                                    <div class="text-[10px] text-slate-500 leading-tight mt-0.5">Gagal kirim & kuota penuh</div>
                                </div>
                            </button>

                            <!-- 6. Rumus Excel -->
                            <button type="button" 
                                    onclick="askWorkspaceAi('Bagaimana rumus VLOOKUP atau XLOOKUP di Excel untuk mencocokkan data ID karyawan?')"
                                    class="p-3 rounded-xl bg-white hover:bg-teal-50/70 border border-slate-200/90 hover:border-teal-400 shadow-2xs hover:shadow-xs transition text-left group cursor-pointer flex items-start space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 group-hover:bg-emerald-700 group-hover:text-white flex items-center justify-center text-xs flex-shrink-0 transition">
                                    <i class="fa-solid fa-file-excel"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-slate-800 group-hover:text-teal-900 truncate">Rumus Excel</div>
                                    <div class="text-[10px] text-slate-500 leading-tight mt-0.5">VLOOKUP, IF, & formula</div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Typing Indicator (Hidden by default) -->
            <div id="workspace-typing-indicator" class="hidden px-5 py-2 bg-slate-50 border-t border-slate-100 flex items-center space-x-2 text-slate-500 text-xs flex-shrink-0">
                <div class="flex space-x-1">
                    <span class="w-1.5 h-1.5 bg-teal-600 rounded-full animate-bounce"></span>
                    <span class="w-1.5 h-1.5 bg-teal-600 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 bg-teal-600 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                </div>
                <span class="text-[10px] italic">Asisten AI sedang menyusun solusi teknis...</span>
            </div>

            <!-- Workspace Chat Input Area (Form Rapi Standar Korporat) -->
            <div class="p-3 bg-white border-t border-slate-200/90 flex-shrink-0">
                <form id="workspace-chat-form" onsubmit="handleWorkspaceSubmit(event)" class="space-y-2">
                    <div class="flex items-center space-x-2 bg-slate-50/90 hover:bg-white focus-within:bg-white rounded-xl border border-slate-300 focus-within:border-teal-600 focus-within:ring-2 focus-within:ring-teal-500/20 px-3 py-1.5 transition-all shadow-2xs">
                        <i class="fa-solid fa-wand-magic-sparkles text-teal-600 text-xs flex-shrink-0"></i>
                        <input type="text" 
                               id="workspace-user-input" 
                               autocomplete="off"
                               placeholder="Tanyakan kendala IT, diagnosa printer/LAN, atau rumus Excel di sini..."
                               class="flex-1 bg-transparent border-0 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none py-1">
                        <button type="submit" 
                                id="workspace-send-btn"
                                class="px-3.5 py-1.5 rounded-lg bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs shadow-xs transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer flex items-center space-x-1.5 flex-shrink-0"
                                title="Kirim Pertanyaan (Enter)">
                            <span>Kirim</span>
                            <i class="fa-solid fa-paper-plane text-[10px]"></i>
                        </button>
                    </div>

                    <!-- Footer Helpdesk Contacts Info -->
                    <div class="flex items-center justify-between text-[10px] text-slate-500 px-1 pt-0.5">
                        <div class="flex items-center space-x-2 sm:space-x-3 text-slate-500">
                            <span class="flex items-center space-x-1"><i class="fa-solid fa-phone text-teal-600 text-[9px]"></i><span>Ext. 102</span></span>
                            <span>&bull;</span>
                            <span class="flex items-center space-x-1"><i class="fa-brands fa-whatsapp text-emerald-600 text-[11px]"></i><span>0812-3456-7890</span></span>
                            <span class="hidden sm:inline">&bull;</span>
                            <span class="hidden sm:inline flex items-center space-x-1"><i class="fa-solid fa-envelope text-slate-400 text-[10px]"></i><span>itsupport@s2p.co.id</span></span>
                        </div>
                        <a href="{{ route('services.create') }}" class="font-extrabold text-teal-700 hover:text-teal-900 underline flex items-center space-x-1">
                            <span>Buat Tiket Servis</span>
                            <i class="fa-solid fa-arrow-right text-[9px]"></i>
                        </a>
                    </div>
                </form>
            </div>

        </div>

        @if(Auth::user()->isTeknisi() || Auth::user()->isAdmin())
        <!-- ============================================================== -->
        <!-- KOLOM KANAN (4-5 Kolom): KATALOG DOKUMEN SOP RESMI PLTU        -->
        <!-- ============================================================== -->
        <div class="lg:col-span-5 xl:col-span-4 flex flex-col bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden h-full">
            
            <!-- SOP Card Header & Search (Desain Ringkas & Rapi) -->
            <div class="p-3.5 bg-slate-50/90 border-b border-slate-200/90 space-y-2 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 rounded-lg bg-teal-100 text-teal-800 flex items-center justify-center text-xs shadow-2xs">
                            <i class="fa-solid fa-book-bookmark"></i>
                        </div>
                        <div>
                            <h2 class="font-extrabold text-xs sm:text-sm text-slate-900 leading-tight">
                                Dokumen SOP Resmi PLTU
                            </h2>
                        </div>
                    </div>
                    <span id="sop-count-badge" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-teal-50 text-teal-800 border border-teal-200">
                        <span id="sop-count-text">{{ $guides->count() }} SOP</span>
                    </span>
                </div>

                <!-- Input Cari SOP -->
                <div class="relative">
                    <input type="text" id="sop-search" value="{{ request('search') }}" onkeyup="filterSopCatalog()" placeholder="Cari SOP (printer, lan, email, sap)..."
                           class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-700/20 focus:border-teal-700 transition">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-[10px]"></i>
                    </span>
                </div>

                <!-- Category & Target Filter Controls (1 Baris Rapi) -->
                <div class="flex items-center space-x-2">
                    <div class="flex-1">
                        <select id="select-kategori" onchange="filterCatalogCategory(this.value)" 
                                class="w-full text-[11px] font-semibold bg-white border border-slate-200 rounded-lg px-2.5 py-1 text-slate-800 focus:outline-none focus:border-teal-700">
                            <option value="all">Semua Kategori ({{ $guides->count() }})</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if(Auth::user()->isTeknisi() || Auth::user()->isAdmin())
                    <div class="flex-shrink-0">
                        <select id="select-audience" onchange="filterAudience(this.value)" 
                                class="text-[11px] font-semibold bg-white border border-slate-200 rounded-lg px-2 py-1 text-slate-800 focus:outline-none">
                            <option value="all">Semua Target</option>
                            <option value="teknisi">🔒 Teknisi</option>
                            <option value="all_users">👥 Pegawai</option>
                        </select>
                    </div>
                    @endif
                </div>

                <!-- Category Filter Pills (Horizontal Scrollable, Tidak Menumpuk ke Bawah) -->
                <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar py-0.5">
                    <button type="button" onclick="filterCatalogCategory('all')" 
                            class="cat-pill active px-2.5 py-1 rounded-md text-[9.5px] font-bold bg-teal-800 text-white transition cursor-pointer flex-shrink-0">
                        Semua
                    </button>
                    @foreach($categories as $cat)
                    <button type="button" onclick="filterCatalogCategory('{{ $cat }}')" 
                            class="cat-pill px-2.5 py-1 rounded-md text-[9.5px] font-semibold bg-white hover:bg-slate-200 border border-slate-200 text-slate-700 transition cursor-pointer flex-shrink-0"
                            data-cat="{{ $cat }}">
                        {{ $cat }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Scrollable SOP Document Cards List (Sinkron penuh dengan tinggi kolom kiri) -->
            @php
                $assignedTopicAnchors = [
                    'printer' => false,
                    'akun' => false,
                    'jaringan' => false,
                ];
            @endphp
            <div id="sop-catalog-list" class="flex-1 space-y-2.5 overflow-y-auto p-3 bg-slate-50/50">
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
                @endphp
                <div @if($cardAnchorId) id="{{ $cardAnchorId }}" @endif
                     class="sop-catalog-card bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs hover:shadow-sm hover:border-teal-400 transition flex flex-col justify-between"
                     data-category="{{ $guide->kategori }}"
                     data-audience="{{ $guide->target_audience }}"
                     data-search="{{ strtolower($guide->judul . ' ' . $guide->kategori . ' ' . $guide->tag . ' ' . $guide->pengantar) }}"
                     data-id="{{ $guide->id }}">
                    
                    <div>
                        <div class="flex items-center justify-between gap-1.5 mb-1.5">
                            <span class="px-2 py-0.5 rounded text-[8.5px] font-black uppercase tracking-wider {{ $guide->kategori_badge_class }}">
                                {{ $guide->kategori }}
                            </span>
                            <span class="text-[9.5px] text-slate-400 font-medium flex items-center space-x-1">
                                <i class="fa-regular fa-clock mr-0.5 text-[9px]"></i>
                                <span>{{ $guide->estimasi_menit ?? 5 }} mnt</span>
                            </span>
                        </div>

                        <h3 class="text-xs font-extrabold text-slate-900 hover:text-teal-800 transition line-clamp-2 leading-snug">
                            {{ $guide->judul }}
                        </h3>

                        <p class="text-[10.5px] text-slate-500 mt-1 line-clamp-2 leading-relaxed">
                            {{ $guide->pengantar }}
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between space-x-2 pt-2.5 mt-2.5 border-t border-slate-100">
                        <div class="flex items-center space-x-1.5 flex-1">
                            <button type="button" 
                                    onclick="openSopDetailModal({{ $guide->id }})"
                                    class="flex-1 py-1.5 px-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold transition text-center cursor-pointer flex items-center justify-center space-x-1">
                                <i class="fa-regular fa-file-lines text-[9.5px]"></i>
                                <span>Detail SOP</span>
                            </button>

                            <button type="button" 
                                    onclick="askWorkspaceAi('Bantu saya panduan langkah untuk SOP: {{ addslashes($guide->judul) }}')"
                                    class="py-1.5 px-3 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 text-[10.5px] font-bold border border-teal-200/60 transition cursor-pointer flex items-center space-x-1 flex-shrink-0"
                                    title="Tanyakan ke Asisten AI">
                                <i class="fa-solid fa-wand-magic-sparkles text-[9.5px] text-teal-600"></i>
                                <span>Tanya AI</span>
                            </button>
                        </div>

                        @if(Auth::user()->isTeknisi() || Auth::user()->isAdmin())
                        <div class="flex items-center space-x-0.5 ml-1">
                            <a href="{{ route('knowledge.edit', $guide->id) }}" 
                               class="p-1 rounded-md text-slate-400 hover:text-teal-700 hover:bg-teal-50 transition" 
                               title="Edit SOP">
                                <i class="fa-regular fa-pen-to-square text-[10px]"></i>
                            </a>
                            <form action="{{ route('knowledge.destroy', $guide->id) }}" method="POST" onsubmit="return confirm('Hapus SOP {{ addslashes($guide->judul) }}?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="Hapus SOP">
                                    <i class="fa-regular fa-trash-can text-[10px]"></i>
                                    <span class="sr-only">Hapus</span>
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>

                    <!-- Hidden JSON data for modal -->
                    <script type="application/json" id="sop-json-{{ $guide->id }}">
                        {!! json_encode([
                            'id' => $guide->id,
                            'judul' => $guide->judul,
                            'kategori' => $guide->kategori,
                            'kategori_badge' => $guide->kategori_badge_class,
                            'pengantar' => $guide->pengantar,
                            'area_penerapan' => $guide->area_penerapan,
                            'estimasi' => $guide->estimasi_menit ?? 5,
                            'langkah' => $guide->langkah_langkah ?? [],
                            'skema_kabel' => $guide->skema_kabel,
                            'judul_perintah' => $guide->judul_perintah,
                            'perintah_cmd' => $guide->perintah_cmd,
                            'peringatan' => $guide->peringatan,
                        ]) !!}
                    </script>
                </div>
                @empty
                <div class="bg-white p-6 rounded-xl border border-slate-200 text-center text-slate-500">
                    <i class="fa-solid fa-folder-open text-2xl text-slate-300 mb-1"></i>
                    <p class="text-[11px]">Belum ada panduan SOP yang terbit.</p>
                </div>
                @endforelse

                <div id="no-catalog-results" class="hidden bg-white p-6 rounded-xl border border-dashed border-slate-300 text-center text-slate-500">
                    <i class="fa-solid fa-magnifying-glass text-xl text-slate-300 mb-1"></i>
                    <p class="text-[11px] font-semibold">Tidak ada SOP yang cocok dengan pencarian.</p>
                    <button type="button" onclick="resetCatalogFilters()" class="mt-1.5 text-teal-700 underline text-[10px] font-bold">Tampilkan Semua</button>
                </div>
            </div>

        </div>
        @endif

    </div>

</div>

<!-- ============================================================== -->
<!-- MODAL: DETAIL DOKUMEN SOP LENGKAP                              -->
<!-- ============================================================== -->
<div id="sop-detail-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden animate-fade-in">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-[#0e1a2b] text-white flex items-center justify-between">
            <div class="min-w-0 pr-3">
                <span id="modal-sop-kategori" class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-teal-500/20 text-teal-300 border border-teal-500/30">
                    Kategori
                </span>
                <h3 id="modal-sop-judul" class="text-sm sm:text-base font-extrabold text-white mt-1 truncate">
                    Judul SOP
                </h3>
            </div>
            <button type="button" onclick="closeSopDetailModal()" class="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 overflow-y-auto space-y-4 text-xs text-slate-700 leading-relaxed">
            
            <!-- Pengantar -->
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Diagnosa & Tujuan SOP:</div>
                <p id="modal-sop-pengantar" class="text-slate-800 font-medium"></p>
                <div id="modal-sop-area-wrapper" class="mt-2 text-[11px] text-teal-800 font-semibold flex items-center">
                    <i class="fa-solid fa-location-dot mr-1.5 text-teal-600"></i>
                    <span>Area: <span id="modal-sop-area"></span></span>
                </div>
            </div>

            <!-- Langkah-Langkah -->
            <div>
                <div class="text-[11px] font-extrabold text-slate-900 mb-2 flex items-center space-x-1.5">
                    <i class="fa-solid fa-list-ol text-teal-700"></i>
                    <span>Langkah-Langkah Penanganan:</span>
                </div>
                <ol id="modal-sop-steps" class="space-y-2 list-none pl-0"></ol>
            </div>

            <!-- Skema Kabel / Diagram (jika ada) -->
            <div id="modal-sop-skema-container" class="hidden bg-slate-900 text-slate-200 p-3.5 rounded-xl font-mono text-[11px] space-y-1">
                <div class="text-[10px] font-bold text-teal-400 uppercase tracking-wider">Skema Kabel / Referensi Pin:</div>
                <pre id="modal-sop-skema" class="whitespace-pre-wrap font-mono"></pre>
            </div>

            <!-- Perintah CMD (jika ada) -->
            <div id="modal-sop-cmd-container" class="hidden bg-slate-950 text-slate-200 p-3.5 rounded-xl space-y-1.5">
                <div class="flex items-center justify-between text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                    <span id="modal-sop-cmd-title">Perintah CMD:</span>
                    <button type="button" onclick="copyModalCmd()" id="btn-copy-modal-cmd" class="text-teal-400 hover:text-white transition flex items-center space-x-1 cursor-pointer">
                        <i class="fa-regular fa-copy"></i>
                        <span>Salin</span>
                    </button>
                </div>
                <code id="modal-sop-cmd" class="block font-mono text-emerald-400 text-xs bg-black/40 p-2 rounded-lg break-all select-all"></code>
            </div>

            <!-- Peringatan K3 / Sistem -->
            <div id="modal-sop-warning-container" class="hidden bg-rose-50 border border-rose-200 p-3.5 rounded-xl text-rose-900">
                <div class="flex items-center space-x-2 font-bold text-xs text-rose-800 mb-1">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Peringatan K3 & Keamanan Sistem:</span>
                </div>
                <p id="modal-sop-warning" class="text-[11px]"></p>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
            <button type="button" onclick="closeSopDetailModal()" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold transition cursor-pointer">
                Tutup
            </button>
            <button type="button" id="btn-modal-ask-ai" class="px-4 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold transition flex items-center space-x-1.5 cursor-pointer">
                <i class="fa-solid fa-wand-magic-sparkles text-xs text-amber-300"></i>
                <span>Tanyakan ke AI Workspace</span>
            </button>
        </div>

    </div>
</div>

@push('scripts')
<script>
    let currentCatalogCategory = 'all';
    let currentAudience = 'all';

    // Filter Katalog SOP
    function filterCatalogCategory(cat) {
        currentCatalogCategory = cat;
        const selCat = document.getElementById('select-kategori');
        if (selCat && selCat.value !== cat) {
            selCat.value = cat;
        }

        document.querySelectorAll('.cat-pill').forEach(btn => {
            const btnCat = btn.getAttribute('data-cat') || 'all';
            if ((cat === 'all' && btnCat === 'all') || (cat !== 'all' && btnCat === cat)) {
                btn.classList.add('bg-teal-800', 'text-white');
                btn.classList.remove('bg-slate-100', 'text-slate-700');
            } else {
                btn.classList.remove('bg-teal-800', 'text-white');
                btn.classList.add('bg-slate-100', 'text-slate-700');
            }
        });
        filterSopCatalog();
    }

    function filterAudience(aud) {
        currentAudience = aud;
        filterSopCatalog();
    }

    function filterSopCatalog() {
        const query = (document.getElementById('sop-search').value || '').toLowerCase().trim();
        const cards = document.querySelectorAll('.sop-catalog-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const cardCat = (card.getAttribute('data-category') || '').toLowerCase();
            const cardAud = (card.getAttribute('data-audience') || '').toLowerCase();
            const cardSearch = (card.getAttribute('data-search') || '').toLowerCase();

            const matchCat = (currentCatalogCategory === 'all') || cardCat.includes(currentCatalogCategory.toLowerCase());
            const matchAud = (currentAudience === 'all') || 
                             (currentAudience === 'teknisi' && cardAud === 'teknisi') ||
                             (currentAudience === 'all_users' && cardAud === 'all');
            const matchSearch = !query || cardSearch.includes(query);

            if (matchCat && matchAud && matchSearch) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        const badgeText = document.getElementById('sop-count-text');
        if (badgeText) badgeText.textContent = visibleCount + ' SOP';

        const noRes = document.getElementById('no-catalog-results');
        if (noRes) {
            if (visibleCount === 0 && cards.length > 0) {
                noRes.classList.remove('hidden');
            } else {
                noRes.classList.add('hidden');
            }
        }
    }

    function resetCatalogFilters() {
        document.getElementById('sop-search').value = '';
        currentAudience = 'all';
        const selAud = document.getElementById('select-audience');
        if (selAud) selAud.value = 'all';
        filterCatalogCategory('all');
    }

    // Modal SOP Detail
    let currentModalCmd = '';
    function openSopDetailModal(sopId) {
        const scriptEl = document.getElementById('sop-json-' + sopId);
        if (!scriptEl) return;
        const data = JSON.parse(scriptEl.textContent);

        document.getElementById('modal-sop-judul').textContent = data.judul;
        document.getElementById('modal-sop-kategori').textContent = data.kategori;
        document.getElementById('modal-sop-pengantar').textContent = data.pengantar;

        if (data.area_penerapan) {
            document.getElementById('modal-sop-area-wrapper').classList.remove('hidden');
            document.getElementById('modal-sop-area').textContent = data.area_penerapan;
        } else {
            document.getElementById('modal-sop-area-wrapper').classList.add('hidden');
        }

        const stepsOl = document.getElementById('modal-sop-steps');
        stepsOl.innerHTML = '';
        if (Array.isArray(data.langkah) && data.langkah.length > 0) {
            data.langkah.forEach((st, idx) => {
                const li = document.createElement('li');
                li.className = 'flex items-start space-x-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200/70 text-xs';
                li.innerHTML = `<span class="flex-shrink-0 w-5 h-5 rounded-full bg-teal-700 text-white font-black text-[10px] flex items-center justify-center mt-0.5">${idx+1}</span><span class="text-slate-800">${escapeHtml(st)}</span>`;
                stepsOl.appendChild(li);
            });
        }

        // Skema kabel
        const skemaBox = document.getElementById('modal-sop-skema-container');
        if (data.skema_kabel) {
            skemaBox.classList.remove('hidden');
            document.getElementById('modal-sop-skema').textContent = data.skema_kabel;
        } else {
            skemaBox.classList.add('hidden');
        }

        // CMD
        const cmdBox = document.getElementById('modal-sop-cmd-container');
        if (data.perintah_cmd) {
            cmdBox.classList.remove('hidden');
            currentModalCmd = data.perintah_cmd;
            document.getElementById('modal-sop-cmd-title').textContent = data.judul_perintah || 'Perintah CMD:';
            document.getElementById('modal-sop-cmd').textContent = data.perintah_cmd;
        } else {
            cmdBox.classList.add('hidden');
            currentModalCmd = '';
        }

        // Warning
        const warnBox = document.getElementById('modal-sop-warning-container');
        if (data.peringatan) {
            warnBox.classList.remove('hidden');
            document.getElementById('modal-sop-warning').textContent = data.peringatan;
        } else {
            warnBox.classList.add('hidden');
        }

        // Tombol Tanya ke AI Workspace
        const askBtn = document.getElementById('btn-modal-ask-ai');
        askBtn.onclick = function() {
            closeSopDetailModal();
            askWorkspaceAi('Bantu saya implementasi panduan: ' + data.judul);
        };

        document.getElementById('sop-detail-modal').classList.remove('hidden');
    }

    function closeSopDetailModal() {
        document.getElementById('sop-detail-modal').classList.add('hidden');
    }

    function copyModalCmd() {
        if (!currentModalCmd) return;
        navigator.clipboard.writeText(currentModalCmd).then(() => {
            const btn = document.getElementById('btn-copy-modal-cmd');
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check text-emerald-400"></i> <span class="text-emerald-400">Tersalin!</span>';
            setTimeout(() => { btn.innerHTML = orig; }, 1800);
        });
    }

    // ==============================================================
    // WORKSPACE AI CHAT CLIENT-SIDE LOGIC
    // ==============================================================
    function handleWorkspaceKeydown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleWorkspaceSubmit(e);
        }
    }

    let workspaceChatHistory = [];

    function clearWorkspaceChat() {
        if (!confirm('Bersihkan riwayat percakapan asisten AI?')) return;
        workspaceChatHistory = [];
        const msgContainer = document.getElementById('workspace-chat-messages');
        const firstBubble = msgContainer.firstElementChild;
        msgContainer.innerHTML = '';
        if (firstBubble) {
            msgContainer.appendChild(firstBubble);
        }
    }

    function askWorkspaceAi(promptText) {
        const input = document.getElementById('workspace-user-input');
        if (input) {
            input.value = promptText;
            handleWorkspaceSubmit(new Event('submit'));
        }
    }

    async function handleWorkspaceSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('workspace-user-input');
        const sendBtn = document.getElementById('workspace-send-btn');
        const text = (input.value || '').trim();
        if (!text) return;

        // 1. Tampilkan bubble pesan pengguna di chat history
        appendUserMessage(text);
        input.value = '';
        input.style.height = 'auto';

        // 2. Aktifkan typing indicator & disable send
        const typingIndicator = document.getElementById('workspace-typing-indicator');
        if (typingIndicator) typingIndicator.classList.remove('hidden');
        if (sendBtn) sendBtn.disabled = true;

        scrollToBottomWorkspace();

        try {
            const response = await fetch("{{ route('ai.chat') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: text, history: workspaceChatHistory })
            });

            const data = await response.json();

            if (data.success) {
                if (data.reply) {
                    workspaceChatHistory.push({ role: 'user', text: text });
                    workspaceChatHistory.push({ role: 'model', text: data.reply });
                    if (workspaceChatHistory.length > 16) workspaceChatHistory = workspaceChatHistory.slice(-16);
                }
                appendBotResponse(data);
            } else {
                appendErrorMessage('Maaf, asisten AI sedang sibuk. Silakan coba beberapa saat lagi.');
            }
        } catch (err) {
            console.error('Chat error:', err);
            appendErrorMessage('Terjadi gangguan jaringan saat menghubungi server AI.');
        } finally {
            if (typingIndicator) typingIndicator.classList.add('hidden');
            if (sendBtn) sendBtn.disabled = false;
            scrollToBottomWorkspace();
        }
    }

    function appendUserMessage(text) {
        const container = document.getElementById('workspace-chat-messages');
        const bubble = document.createElement('div');
        bubble.className = 'flex items-start justify-end space-x-2.5 animate-fade-in';
        bubble.innerHTML = `
            <div class="max-w-[85%] bg-teal-800 text-white p-3.5 rounded-2xl rounded-tr-sm shadow-xs space-y-1">
                <div class="text-[10px] text-teal-200 font-bold flex items-center justify-between gap-3">
                    <span>Anda</span>
                    <span>Baru saja</span>
                </div>
                <p class="whitespace-pre-wrap leading-relaxed">${escapeHtml(text)}</p>
            </div>
            <div class="w-8 h-8 rounded-xl bg-teal-700 text-white flex items-center justify-center font-bold text-xs flex-shrink-0 mt-0.5">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
        `;
        container.appendChild(bubble);
    }

    function appendBotResponse(data) {
        const container = document.getElementById('workspace-chat-messages');
        const bubble = document.createElement('div');
        bubble.className = 'flex items-start space-x-3.5 animate-fade-in';

        let stepsHtml = '';
        if (Array.isArray(data.steps) && data.steps.length > 0) {
            stepsHtml = '<div class="mt-2.5 space-y-1.5"><div class="font-extrabold text-slate-800 text-[11px] flex items-center space-x-1.5"><i class="fa-solid fa-list-check text-teal-700"></i><span>Langkah Penanganan:</span></div><ol class="space-y-1 pl-0 list-none">';
            data.steps.forEach((st, idx) => {
                stepsHtml += `<li class="flex items-start space-x-2 text-slate-700 bg-slate-50 p-2 rounded-lg border border-slate-200/60"><span class="w-4 h-4 rounded-full bg-teal-700 text-white text-[9px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">${idx+1}</span><span class="leading-relaxed">${formatMarkdown(st)}</span></li>`;
            });
            stepsHtml += '</ol></div>';
        }

        let cmdHtml = '';
        if (data.cmd) {
            cmdHtml = `
                <div class="mt-2.5 bg-slate-900 text-slate-200 p-2.5 rounded-xl text-[11px] space-y-1">
                    <div class="flex items-center justify-between text-[10px] text-teal-400 font-bold uppercase">
                        <span>${data.cmd_title || 'Perintah CMD:'}</span>
                        <button type="button" onclick="navigator.clipboard.writeText('${data.cmd}')" class="text-slate-400 hover:text-white transition cursor-pointer">
                            <i class="fa-regular fa-copy"></i> Salin
                        </button>
                    </div>
                    <code class="block font-mono text-emerald-300 break-all">${escapeHtml(data.cmd)}</code>
                </div>
            `;
        }

        let warningHtml = '';
        if (data.warning) {
            warningHtml = `
                <div class="mt-2.5 bg-rose-50 border border-rose-200 p-2.5 rounded-xl text-[11px] text-rose-900 flex items-start space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 mt-0.5 flex-shrink-0"></i>
                    <span><strong>Peringatan:</strong> ${escapeHtml(data.warning)}</span>
                </div>
            `;
        }

        let actionsHtml = '';
        if (data.wa_url || data.ticket_url) {
            const waUrl = data.wa_url || 'https://wa.me/6281234567890';
            const ticketUrl = data.ticket_url || '{{ route("services.create") }}';
            actionsHtml = `
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex flex-wrap items-center gap-2">
                    <a href="${waUrl}" target="_blank" 
                       class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition shadow-xs">
                        <i class="fa-brands fa-whatsapp text-xs"></i>
                        <span>Hubungi Teknisi Lapangan</span>
                    </a>
                    <a href="${ticketUrl}" 
                       class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition">
                        <i class="fa-solid fa-ticket text-xs"></i>
                        <span>Buat Tiket Servis</span>
                    </a>
                </div>
            `;
        }

        bubble.innerHTML = `
            <div class="w-8 h-8 rounded-xl bg-teal-800 text-white flex items-center justify-center text-xs flex-shrink-0 mt-0.5 shadow-xs">
                <i class="fa-solid fa-robot text-sm"></i>
            </div>
            <div class="flex-1 bg-white p-4 sm:p-5 rounded-2xl rounded-tl-sm border border-slate-200/90 shadow-xs space-y-2">
                <div class="flex items-center justify-between text-[10px] text-slate-400">
                    <span class="font-bold text-teal-800 flex items-center space-x-1">
                        <i class="fa-solid fa-wand-magic-sparkles text-amber-500"></i>
                        <span>${data.sop_title || (data.source === 'gemini_ai' ? 'Gemini AI' : 'Asisten AI')}</span>
                    </span>
                    <span>Baru saja</span>
                </div>
                <div class="text-slate-800 leading-relaxed">${formatMarkdown(data.reply)}</div>
                ${stepsHtml}
                ${cmdHtml}
                ${warningHtml}
                ${actionsHtml}
            </div>
        `;

        container.appendChild(bubble);
    }

    function appendErrorMessage(msg) {
        const container = document.getElementById('workspace-chat-messages');
        const bubble = document.createElement('div');
        bubble.className = 'flex items-start space-x-3 animate-fade-in';
        bubble.innerHTML = `
            <div class="w-8 h-8 rounded-xl bg-rose-600 text-white flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="bg-rose-50 text-rose-800 p-3.5 rounded-2xl border border-rose-200 text-xs">
                ${escapeHtml(msg)}
            </div>
        `;
        container.appendChild(bubble);
    }

    function scrollToBottomWorkspace() {
        const container = document.getElementById('workspace-chat-messages');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatMarkdown(text) {
        if (!text) return '';
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code class="bg-slate-100 text-slate-900 px-1 py-0.5 rounded text-[11px]">$1</code>')
            .replace(/\n/g, '<br>');
    }
</script>
@endpush
@endsection
