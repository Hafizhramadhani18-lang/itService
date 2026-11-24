<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dasbor') - IT Service Desk PT S2P Cilacap</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-itsupport.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        teal: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                            950: '#042f2e',
                        },
                        navy: {
                            800: '#152438',
                            900: '#0e1a2b',
                            950: '#08111e',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Minimal Sleek Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Smooth Fluid Collapsible Sidebar & Content */
        #sidebar {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
        }
        #main-content-wrapper {
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), padding-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: margin-left, padding-left;
        }
        @media (min-width: 768px) {
            #sidebar.sidebar-collapsed {
                transform: translateX(-100%) !important;
            }
            #main-content-wrapper.sidebar-collapsed {
                margin-left: 0 !important;
                padding-left: 5.5rem !important; /* Ruang lega 88px di sisi kiri agar tombol Menu tidak menabrak form atau judul */
            }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-[#f8fafc] text-slate-800 antialiased min-h-screen overflow-x-hidden">

@if(Auth::check() && Auth::user()->isPegawai())
    <!-- ========================================== -->
    <!-- LAYOUT KHUSUS PEGAWAI / KARYAWAN DIVISI   -->
    <!-- (Horizontal Top Navigation Bar PLTU S2P)   -->
    <!-- ========================================== -->
    <div class="min-h-screen flex flex-col bg-[#f8fafc]">
        
        <!-- TOP NAVBAR PEGAWAI -->
        <header class="bg-[#0e1a2b] border-b border-slate-800 text-white sticky top-0 z-50 shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    
                    <!-- Left: Brand Logo -->
                    <div class="flex items-center space-x-3 flex-shrink-0">
                        <img src="{{ asset('images/logo-itsupport.png') }}" alt="IT Service Desk"
                             class="w-9 h-9 rounded-xl object-contain shadow-xs flex-shrink-0">
                        <div>
                            <div class="font-extrabold text-sm tracking-wide text-white leading-tight">IT Service Desk</div>
                            <div class="text-[10px] text-slate-400 font-medium">PT S2P - PLTU Karangkandri</div>
                        </div>
                    </div>

                    <!-- Center: Navigation Tabs (Pill Buttons) -->
                    <nav class="hidden md:flex items-center space-x-2">
                        <!-- Beranda -->
                        <a href="{{ route('dashboard') }}" 
                           class="px-4 py-1.5 rounded-full text-xs font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-teal-700 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-[#152438]' }}">
                            Beranda
                        </a>
                        <!-- Buat Tiket -->
                        <a href="{{ route('services.create') }}" 
                           class="px-4 py-1.5 rounded-full text-xs font-semibold transition {{ request()->routeIs('services.create') ? 'bg-teal-700 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-[#152438]' }}">
                            Buat Tiket
                        </a>
                        <!-- Tiket Saya -->
                        <a href="{{ route('services.index') }}" 
                           class="px-4 py-1.5 rounded-full text-xs font-semibold transition inline-flex items-center space-x-1.5 {{ request()->routeIs('services.index') || request()->routeIs('services.show') ? 'bg-teal-700 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-[#152438]' }}">
                            <span>Tiket Saya</span>
                            @if(($pegawaiNeedConfirmCount ?? 0) > 0)
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-amber-400 text-slate-900 leading-tight" title="Tiket selesai menunggu konfirmasi Anda">
                                    {{ $pegawaiNeedConfirmCount }}
                                </span>
                            @endif
                        </a>
                        <!-- Panduan Mandiri -->
                        <a href="{{ route('knowledge.base') }}" 
                           class="px-4 py-1.5 rounded-full text-xs font-semibold transition {{ request()->routeIs('knowledge.*') ? 'bg-teal-700 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-[#152438]' }}">
                            Panduan Mandiri
                        </a>
                    </nav>

                    <!-- Right: Notifications & User Profile (Pegawai) -->
                    <div class="hidden md:flex items-center space-x-3">
                        @include('partials.header-widget')

                        <div class="relative">
                            <button type="button" onclick="togglePegawaiProfileMenu()" id="btn-pegawai-profile"
                                    class="flex items-center space-x-3 p-1.5 rounded-xl hover:bg-[#152438] transition focus:outline-none focus:ring-2 focus:ring-teal-500/30 group cursor-pointer"
                                    title="Klik untuk menu profil dan akun">
                                <div class="text-right">
                                    <div class="text-xs font-bold text-white leading-tight group-hover:text-teal-200 transition">{{ Auth::user()->name }}</div>
                                    <div class="text-[10px] text-teal-300">{{ Auth::user()->divisi ?? 'Karyawan PLTU' }}</div>
                                </div>
                                <div class="w-8 h-8 rounded-lg bg-teal-700 text-white flex items-center justify-center font-bold text-xs shadow-xs overflow-hidden">
                                    @if(Auth::user()->foto_url)
                                        <img src="{{ Auth::user()->foto_url }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    @endif
                                </div>
                                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 group-hover:text-white transition"></i>
                            </button>

                            <!-- Dropdown Menu Pegawai -->
                            <div id="pegawai-profile-menu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200/90 py-2 z-50 text-slate-800">
                                <div class="px-4 py-2 border-b border-slate-100">
                                    <div class="text-xs font-extrabold text-slate-900 truncate">{{ Auth::user()->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-medium truncate mt-0.5">Pegawai &bull; {{ Auth::user()->divisi ?? 'Karyawan PLTU' }}</div>
                                </div>
                                <div class="py-1">
                                    <a href="{{ route('profile.edit') }}" 
                                       class="flex items-center space-x-2.5 px-4 py-2 text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition {{ request()->routeIs('profile*') ? 'bg-slate-50 text-teal-800' : '' }}">
                                        <i class="fa-regular fa-user text-slate-500 text-xs w-4 text-center"></i>
                                        <span>Pengaturan Profil</span>
                                    </a>
                                </div>
                                <div class="pt-1 border-t border-slate-100">
                                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                                        @csrf
                                        <button type="submit" 
                                                class="w-full flex items-center space-x-2.5 px-4 py-2 text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 transition text-left cursor-pointer">
                                            <i class="fa-solid fa-arrow-right-from-bracket text-rose-500 text-xs w-4 text-center"></i>
                                            <span>Keluar</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Actions: Bell & Hamburger Toggle -->
                    <div class="md:hidden flex items-center space-x-2">
                        <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white" title="Notifikasi">
                            <i class="fa-regular fa-bell text-base"></i>
                            @if(($headerUnreadNotifCount ?? 0) > 0)
                                <span class="absolute top-1 right-1 flex h-3.5 min-w-[14px] px-1 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white">
                                    {{ $headerUnreadNotifCount }}
                                </span>
                            @endif
                        </a>
                        <button onclick="toggleMobileNav()" class="p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white focus:outline-none">
                            <i class="fa-solid fa-bars text-lg"></i>
                        </button>
                    </div>

                </div>
            </div>

            <!-- Mobile Drawer Menu (Tampilan di HP) -->
            <div id="mobile-nav-pegawai" class="hidden md:hidden border-t border-slate-800 bg-[#09121e] px-4 pt-3 pb-4 space-y-1">
                <div class="px-3 py-2 mb-2 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-white">{{ Auth::user()->name }}</div>
                        <div class="text-[10px] text-teal-400">{{ Auth::user()->divisi }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 font-semibold flex items-center space-x-1">
                            <i class="fa-solid fa-arrow-right-from-bracket text-[10px]"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('dashboard') ? 'bg-teal-700 text-white' : 'text-slate-300 hover:bg-[#152438]' }}">
                    <i class="fa-solid fa-house w-5 mr-1 text-teal-400"></i> Beranda
                </a>
                <a href="{{ route('services.create') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('services.create') ? 'bg-teal-700 text-white' : 'text-slate-300 hover:bg-[#152438]' }}">
                    <i class="fa-solid fa-plus-circle w-5 mr-1 text-teal-400"></i> Buat Tiket
                </a>
                <a href="{{ route('services.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('services.index') || request()->routeIs('services.show') ? 'bg-teal-700 text-white' : 'text-slate-300 hover:bg-[#152438]' }}">
                    <span><i class="fa-solid fa-ticket w-5 mr-1 text-teal-400"></i> Tiket Saya</span>
                    @if(($pegawaiNeedConfirmCount ?? 0) > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-amber-400 text-slate-900">{{ $pegawaiNeedConfirmCount }}</span>
                    @endif
                </a>
                <a href="{{ route('knowledge.base') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('knowledge.*') ? 'bg-teal-700 text-white' : 'text-slate-300 hover:bg-[#152438]' }}">
                    <i class="fa-solid fa-book-bookmark w-5 mr-1 text-teal-400"></i> Panduan Mandiri
                </a>
                <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('profile*') ? 'bg-teal-700 text-white' : 'text-slate-300 hover:bg-[#152438]' }}">
                    <i class="fa-regular fa-user w-5 mr-1 text-teal-400"></i> Pengaturan Profil
                </a>
            </div>
        </header>

        <!-- Main Content Area Pegawai -->
        <main class="flex-1 p-4 sm:p-8 max-w-7xl w-full mx-auto">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 text-slate-400 py-4 px-4 sm:px-8 text-[11px] mt-auto">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-slate-600">&copy; {{ date('Y') }} PT Sumber Segara Primadaya (S2P)</span>
                    <span>&bull;</span>
                    <span>PLTU Cilacap (Karangkandri)</span>
                </div>
                <div>
                    Unit IT Support & Infrastructure &bull; Helpdesk Ext. 102
                </div>
            </div>
        </footer>

    </div>

    <script>
        function toggleMobileNav() {
            const nav = document.getElementById('mobile-nav-pegawai');
            if (nav) {
                nav.classList.toggle('hidden');
            }
        }

        function togglePegawaiProfileMenu() {
            const menu = document.getElementById('pegawai-profile-menu');
            const notif = document.getElementById('notif-dropdown');
            if (notif) notif.classList.add('hidden');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        document.addEventListener('click', function(event) {
            const btnPegawai = document.getElementById('btn-pegawai-profile');
            const menuPegawai = document.getElementById('pegawai-profile-menu');
            if (btnPegawai && menuPegawai && !btnPegawai.contains(event.target) && !menuPegawai.contains(event.target)) {
                menuPegawai.classList.add('hidden');
            }
        });
    </script>

@else
    <!-- ========================================== -->
    <!-- LAYOUT KHUSUS TEKNISI IT SUPPORT           -->
    <!-- (Left Dark-Navy Sidebar)                   -->
    <!-- ========================================== -->
    <div class="min-h-screen flex flex-col md:flex-row">

        <!-- Mobile Header Bar -->
        <div class="md:hidden bg-[#0e1a2b] border-b border-slate-800 text-white px-4 py-3 flex items-center justify-between sticky top-0 z-50">
            <div class="flex items-center space-x-2.5">
                <img src="{{ asset('images/logo-itsupport.png') }}" alt="IT Service Desk"
                     class="w-8 h-8 rounded-lg object-contain shadow-xs flex-shrink-0">
                <div>
                    <span class="font-bold text-sm text-white">IT Service Desk</span>
                    <span class="text-[10px] text-slate-400 block">PT S2P - PLTU Karangkandri</span>
                </div>
            </div>
            <button onclick="toggleSidebar()" class="p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white">
                <i class="fa-solid fa-bars text-lg"></i>
            </button>
        </div>

        <!-- Sidebar Overlay (Mobile) -->
        <div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-950/60 z-40 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

        <!-- LEFT SIDEBAR TEKNISI / ADMIN -->
        <aside id="sidebar" class="fixed top-0 left-0 h-screen w-64 bg-[#0e1a2b] border-r border-slate-800/90 text-white flex flex-col justify-between z-50 transform -translate-x-full md:translate-x-0 shadow-2xl md:shadow-none flex-shrink-0">
            <script>
                // Instant load preference to avoid layout flash
                if (window.innerWidth >= 768 && localStorage.getItem('sidebar_collapsed_pref') === '1') {
                    document.getElementById('sidebar').classList.add('sidebar-collapsed');
                }
            </script>
            
            <!-- Top: Brand Header -->
            <div class="p-4 sm:p-5 border-b border-slate-800/80">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 min-w-0">
                        <img src="{{ asset('images/logo-itsupport.png') }}" alt="IT Service Desk"
                             class="w-9 h-9 rounded-xl object-contain shadow-md shadow-teal-900/30 flex-shrink-0">
                        <div class="min-w-0">
                            <h1 class="font-extrabold text-sm tracking-wide text-white leading-tight truncate">IT Service Desk</h1>
                            <p class="text-[10px] text-slate-400 font-medium truncate">PT S2P &bull; PLTU Karangkandri</p>
                        </div>
                    </div>
                    <!-- Tombol Garis 3 (Toggle Sembunyikan Sidebar) -->
                    <button type="button" onclick="toggleSidebar()" 
                            class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition duration-150 cursor-pointer focus:outline-none flex-shrink-0 ml-2" 
                            title="Sembunyikan Menu Sidebar (Ctrl+B)">
                        <i class="fa-solid fa-bars text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Middle: Navigation Menus -->
            <div class="flex-1 overflow-y-auto px-3.5 py-4 space-y-1">
                @if(Auth::user()->isAdmin())
                    <div class="px-3 pb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        Menu Admin
                    </div>

                    <!-- Dashboard -->
                    <a href="{{ route('admin.dashboard') }}" 
                       class="group flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.dashboard') || (request()->routeIs('dashboard') && Auth::user()->isAdmin()) ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                        <i class="fa-solid fa-table-cells-large w-5 text-center mr-2.5 text-xs {{ request()->routeIs('admin.dashboard') || request()->routeIs('dashboard') ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                        <span>Dashboard</span>
                    </a>

                    <!-- Penugasan Tiket -->
                    <a href="{{ route('admin.penugasan') }}" 
                       class="group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.penugasan') ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                        <div class="flex items-center min-w-0">
                            <i class="fa-solid fa-clipboard-user w-5 text-center mr-2.5 text-xs {{ request()->routeIs('admin.penugasan') ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                            <span class="truncate">Penugasan Tiket</span>
                        </div>
                        @if(($sidebarUnassignedCount ?? 0) > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500 text-white shadow-xs ml-2 flex-shrink-0" title="{{ $sidebarUnassignedCount }} tiket belum ditugaskan">
                                {{ $sidebarUnassignedCount }}
                            </span>
                        @endif
                    </a>

                    <!-- Status Teknisi (Pantauan Live Aktivitas) -->
                    <a href="{{ route('admin.statusTeknisi') }}" 
                       class="group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.statusTeknisi') ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                        <div class="flex items-center min-w-0">
                            <i class="fa-solid fa-signal w-5 text-center mr-2.5 text-xs {{ request()->routeIs('admin.statusTeknisi') ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                            <span class="truncate">Status Teknisi</span>
                        </div>
                        @if(($sidebarTotalTechCount ?? 0) > 0)
                            <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800/90 text-slate-200 border border-slate-700/80 ml-2 flex-shrink-0" title="{{ $sidebarOnlineTechCount }} dari {{ $sidebarTotalTechCount }} teknisi aktif hari ini">
                                <span class="w-1.5 h-1.5 rounded-full {{ ($sidebarOnlineTechCount ?? 0) > 0 ? 'bg-emerald-400' : 'bg-slate-400' }}"></span>
                                <span>{{ $sidebarOnlineTechCount }}/{{ $sidebarTotalTechCount }}</span>
                            </span>
                        @endif
                    </a>

                    <!-- Semua Tiket -->
                    <a href="{{ route('services.index', ['filter' => 'all']) }}" 
                       class="group flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ (request()->routeIs('services.index') && request('filter') === 'all') ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                        <i class="fa-solid fa-layer-group w-5 text-center mr-2.5 text-xs {{ (request()->routeIs('services.index') && request('filter') === 'all') ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                        <span>Semua Tiket</span>
                    </a>

                    <!-- Kinerja Teknisi -->
                    <a href="{{ route('admin.kinerja') }}" 
                       class="group flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.kinerja') ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                        <i class="fa-solid fa-chart-line w-5 text-center mr-2.5 text-xs {{ request()->routeIs('admin.kinerja') ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                        <span>Kinerja Teknisi</span>
                    </a>

                    <div class="pt-4 mt-4 border-t border-slate-800/80">
                        <div class="px-3 pb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Manajemen
                        </div>
                        <!-- Pengguna -->
                        <a href="{{ route('admin.users') }}" 
                           class="group flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.users*') ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                            <i class="fa-solid fa-users w-5 text-center mr-2.5 text-xs {{ request()->routeIs('admin.users*') ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                            <span>Pengguna</span>
                        </a>

                        <!-- Data Master -->
                        <a href="{{ route('admin.kategori') }}" 
                           class="group flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.kategori*') || request()->routeIs('admin.master') ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                            <i class="fa-solid fa-tags w-5 text-center mr-2.5 text-xs {{ request()->routeIs('admin.kategori*') || request()->routeIs('admin.master') ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                            <span>Data Master</span>
                        </a>

                        <!-- Basis Pengetahuan -->
                        <a href="{{ route('knowledge.base') }}" 
                           class="group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('knowledge.*') ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                            <div class="flex items-center min-w-0">
                                <i class="fa-solid fa-book-bookmark w-5 text-center mr-2.5 text-xs {{ request()->routeIs('knowledge.*') ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span class="truncate">Basis Pengetahuan</span>
                            </div>
                            @if(($sidebarPendingSopCount ?? 0) > 0)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 ml-2 flex-shrink-0" title="{{ $sidebarPendingSopCount }} SOP menunggu persetujuan">
                                    {{ $sidebarPendingSopCount }}
                                </span>
                            @endif
                        </a>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-800/80">
                        <div class="px-3 pb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Pelaporan & Dokumen
                        </div>
                        <!-- Rekap Laporan -->
                        <a href="{{ route('admin.rekap') }}" 
                           class="group flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.rekap') || request()->routeIs('admin.laporan') || (request('filter') === 'report' && Auth::user()->isAdmin()) ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                            <i class="fa-solid fa-file-invoice w-5 text-center mr-2.5 text-xs {{ request()->routeIs('admin.rekap') || request()->routeIs('admin.laporan') || (request('filter') === 'report' && Auth::user()->isAdmin()) ? 'text-teal-400' : 'text-slate-500 group-hover:text-teal-400' }}"></i>
                            <span>Rekap Laporan</span>
                        </a>

                        <!-- Log Pemeliharaan Aset & Sistem IT -->
                        <a href="{{ route('maintenance.index') }}" 
                           class="group flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('maintenance.*') || request()->routeIs('admin.pemeliharaan') ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                            <i class="fa-solid fa-clipboard-check w-5 text-center mr-2.5 text-xs {{ request()->routeIs('maintenance.*') || request()->routeIs('admin.pemeliharaan') ? 'text-teal-400' : 'text-slate-500 group-hover:text-teal-400' }}"></i>
                            <span>Log Pemeliharaan</span>
                        </a>
                    </div>
                @else
                    <div class="px-3 pb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        Menu Teknisi
                    </div>

                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}" 
                       class="group flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('dashboard') && !Auth::user()->isAdmin() ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                        <i class="fa-solid fa-chart-pie w-5 text-center mr-2.5 text-xs {{ request()->routeIs('dashboard') ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                        <span>Dashboard</span>
                    </a>

                    <!-- Status Kerja Teknisi -->
                    <a href="{{ route('teknisi.status.index') }}" 
                       class="group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('teknisi.status.*') ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                        <div class="flex items-center min-w-0">
                            <i class="fa-solid fa-signal w-5 text-center mr-2.5 text-xs {{ request()->routeIs('teknisi.status.*') ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                            <span class="truncate">Status Kerja</span>
                        </div>
                        <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-slate-800/90 text-slate-200 border border-slate-700/80 ml-2 flex-shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full {{ Auth::user()->availability_dot_class }}"></span>
                            <span>{{ Auth::user()->availability_label }}</span>
                        </span>
                    </a>

                    <!-- Antrian Tiket -->
                    <a href="{{ route('services.index') }}" 
                       class="group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('services.index') && !request('filter') ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                        <div class="flex items-center min-w-0">
                            <i class="fa-solid fa-inbox w-5 text-center mr-2.5 text-xs {{ request()->routeIs('services.index') && !request('filter') ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                            <span class="truncate">Antrian Tiket</span>
                        </div>
                        @if(($sidebarQueueCount ?? 0) > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500 text-white shadow-xs ml-2 flex-shrink-0" title="{{ $sidebarQueueCount }} tiket belum diambil teknisi">
                                {{ $sidebarQueueCount }}
                            </span>
                        @endif
                    </a>

                    <!-- Tiket Saya -->
                    <a href="{{ route('services.index', ['filter' => 'my']) }}" 
                       class="group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request('filter') === 'my' ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                        <div class="flex items-center min-w-0">
                            <i class="fa-solid fa-user-check w-5 text-center mr-2.5 text-xs {{ request('filter') === 'my' ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                            <span class="truncate">Tiket Saya</span>
                        </div>
                        @if(($sidebarMyActiveCount ?? 0) > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-500/20 text-teal-300 border border-teal-500/30 ml-2 flex-shrink-0" title="{{ $sidebarMyActiveCount }} tugas aktif">
                                {{ $sidebarMyActiveCount }}
                            </span>
                        @endif
                    </a>

                    <!-- Riwayat Tiket (Arsip Selesai) -->
                    <a href="{{ route('services.index', ['filter' => 'history']) }}" 
                       class="group flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request('filter') === 'history' ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                        <i class="fa-solid fa-clock-rotate-left w-5 text-center mr-2.5 text-xs {{ request('filter') === 'history' ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                        <span>Riwayat Tiket</span>
                    </a>

                    <!-- Basis Pengetahuan -->
                    <a href="{{ route('knowledge.base') }}" 
                       class="group flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('knowledge.*') ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                        <i class="fa-solid fa-book-bookmark w-5 text-center mr-2.5 text-xs {{ request()->routeIs('knowledge.*') ? 'text-teal-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                        <span>Basis Pengetahuan</span>
                    </a>

                    <div class="pt-4 mt-4 border-t border-slate-800/80">
                        <div class="px-3 pb-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Pelaporan & Dokumen
                        </div>
                        <!-- Laporan Saya (Role Teknisi) -->
                        <a href="{{ route('services.index', ['filter' => 'report']) }}" 
                           class="group flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request('filter') === 'report' ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                            <i class="fa-solid fa-file-invoice w-5 text-center mr-2.5 text-xs {{ request('filter') === 'report' ? 'text-teal-400' : 'text-slate-500 group-hover:text-teal-400' }}"></i>
                            <span>Laporan Saya</span>
                            <span class="sr-only">Laporan Teknisi</span>
                        </a>

                        <!-- Log Pemeliharaan Aset & Sistem IT -->
                        <a href="{{ route('maintenance.index') }}" 
                           class="group flex items-center px-3.5 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('maintenance.*') ? 'bg-[#1a2f47] text-white shadow-xs' : 'text-slate-300 hover:bg-[#152438] hover:text-white' }}">
                            <i class="fa-solid fa-clipboard-check w-5 text-center mr-2.5 text-xs {{ request()->routeIs('maintenance.*') ? 'text-teal-400' : 'text-slate-500 group-hover:text-teal-400' }}"></i>
                            <span>Log Pemeliharaan</span>
                        </a>
                    </div>
                @endif
            </div>

            <!-- Bottom: User Account Profile & Interactive Popup Menu -->
            <div class="relative p-3.5 border-t border-slate-800/80 bg-[#09121e]">
                
                <!-- Popup Dropdown Menu Profil (Muncul ke atas) -->
                <div id="user-profile-menu" class="hidden absolute bottom-full left-3 right-3 mb-2 bg-white rounded-2xl shadow-2xl border border-slate-200/90 p-2.5 z-50 text-slate-800 transition-all duration-150">
                    <!-- Identity Header -->
                    <div class="flex items-center space-x-3 p-2 pb-2.5 border-b border-slate-100">
                        <div class="w-9 h-9 rounded-full bg-teal-700 text-white flex items-center justify-center font-extrabold text-sm shadow-xs flex-shrink-0 overflow-hidden">
                            @if(Auth::user()->foto_url)
                                <img src="{{ Auth::user()->foto_url }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                            @else
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-black text-slate-900 truncate">{{ Auth::user()->name }}</div>
                            <div class="text-[10px] text-slate-400 font-semibold truncate mt-0.5">
                                {{ ucfirst(Auth::user()->role) }} &bull; {{ Auth::user()->label_tim }}
                            </div>
                        </div>
                    </div>

                    <!-- Menu Link: Pengaturan Profil -->
                    <div class="py-1 space-y-0.5">
                        <a href="{{ route('profile.edit') }}" 
                           class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-100 transition {{ request()->routeIs('profile*') || request()->routeIs('admin.profile') ? 'bg-slate-100 text-teal-900' : '' }}">
                            <i class="fa-regular fa-user text-slate-500 text-sm w-4 text-center"></i>
                            <span>Pengaturan Profil</span>
                        </a>
                    </div>

                    <!-- Action: Keluar (Logout) -->
                    <div class="pt-1 border-t border-slate-100">
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit" 
                                    class="w-full flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 transition text-left cursor-pointer">
                                <i class="fa-solid fa-arrow-right-from-bracket text-rose-500 text-xs w-4 text-center"></i>
                                <span>Keluar</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tombol Trigger Profil User (Pill Bawah) -->
                <button type="button" onclick="toggleUserProfileMenu()" id="btn-user-profile"
                        class="w-full flex items-center justify-between p-2 rounded-xl bg-teal-900/25 hover:bg-teal-900/40 border border-teal-700/40 text-left transition duration-150 focus:outline-none focus:ring-2 focus:ring-teal-500/30 group cursor-pointer"
                        title="Klik untuk membuka menu profil & akun">
                    <div class="flex items-center space-x-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center font-black text-xs flex-shrink-0 shadow-xs overflow-hidden">
                            @if(Auth::user()->foto_url)
                                <img src="{{ Auth::user()->foto_url }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                            @else
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-black text-white truncate leading-tight group-hover:text-teal-200 transition">
                                {{ Auth::user()->name }}
                            </div>
                            <div class="text-[10px] text-slate-300 truncate mt-0.5 font-medium flex items-center space-x-1.5">
                                @if(Auth::user()->isTeknisi())
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ Auth::user()->availability_dot_class }}"></span>
                                    <span class="truncate">{{ Auth::user()->availability_label }} &bull; {{ Auth::user()->label_tim }}</span>
                                @else
                                    <span class="truncate">{{ Auth::user()->label_tim }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <i id="chevron-user-profile" class="fa-solid fa-chevron-up text-[10px] text-slate-400 group-hover:text-white transition-transform duration-200"></i>
                </button>
            </div>

        </aside>

        <!-- RIGHT: MAIN CONTENT WRAPPER TEKNISI / ADMIN -->
        <div id="main-content-wrapper" class="flex-1 flex flex-col min-w-0 bg-[#f8fafc] md:ml-64">
            <script>
                // Instant load preference to avoid layout flash
                if (window.innerWidth >= 768 && localStorage.getItem('sidebar_collapsed_pref') === '1') {
                    document.getElementById('main-content-wrapper').classList.add('sidebar-collapsed');
                }
            </script>

            <!-- Floating Garis 3 Button saat Sidebar Tersembunyi (Desktop) -->
            <button type="button" 
                    id="btn-sidebar-reopen" 
                    onclick="toggleSidebar()" 
                    class="fixed top-6 left-5 z-40 px-3 py-2 rounded-xl bg-[#0e1a2b] hover:bg-[#152438] text-white border border-slate-700/80 shadow-xl transition-all duration-300 cursor-pointer items-center space-x-1.5 group focus:outline-none opacity-0 pointer-events-none -translate-x-4 inline-flex"
                    title="Buka Menu Sidebar (Garis 3 / Ctrl+B)">
                <i class="fa-solid fa-bars text-sm text-teal-400 group-hover:text-teal-300 transition"></i>
                <span class="text-xs font-bold text-slate-200 group-hover:text-white transition">Menu</span>
            </button>

            <!-- Floating Tombol Logout saat Sidebar Tersembunyi (Pojok Bawah Kanan Sesuai Mockup Bersih) -->
            <div id="floating-collapsed-logout" 
                 class="fixed bottom-6 right-6 z-40 transition-all duration-300 opacity-0 pointer-events-none translate-x-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" 
                            class="flex items-center space-x-1.5 px-3.5 py-2.5 rounded-xl bg-[#0e1a2b] hover:bg-rose-950/90 text-white hover:text-rose-200 border border-slate-700/80 hover:border-rose-600/60 shadow-xl text-xs font-bold transition-all duration-200 cursor-pointer group focus:outline-none focus:ring-2 focus:ring-rose-500/50"
                            title="Keluar dari Akun (Logout)">
                        <i class="fa-solid fa-arrow-right-from-bracket text-rose-400 group-hover:translate-x-0.5 transition-transform text-xs"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>

            <script>
                // Instant load buttons state if sidebar is initially collapsed
                if (window.innerWidth >= 768 && localStorage.getItem('sidebar_collapsed_pref') === '1') {
                    const rBtn = document.getElementById('btn-sidebar-reopen');
                    const fLog = document.getElementById('floating-collapsed-logout');
                    if (rBtn) {
                        rBtn.classList.remove('opacity-0', 'pointer-events-none', '-translate-x-4');
                        rBtn.classList.add('opacity-100', 'pointer-events-auto', 'translate-x-0');
                    }
                    if (fLog) {
                        fLog.classList.remove('opacity-0', 'pointer-events-none', 'translate-x-4');
                        fLog.classList.add('opacity-100', 'pointer-events-auto', 'translate-x-0');
                    }
                }
            </script>

            <!-- Page Body Content -->
            <main class="flex-1 p-6 sm:p-8 max-w-7xl w-full">
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-slate-200 text-slate-400 py-3.5 px-6 sm:px-8 text-[11px] mt-auto">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-slate-600">&copy; {{ date('Y') }} PT Sumber Segara Primadaya (S2P)</span>
                        <span>&bull;</span>
                        <span>PLTU Cilacap (Karangkandri)</span>
                    </div>
                    <div>
                        Unit IT Support & Infrastructure
                    </div>
                </div>
            </footer>

        </div>

    </div>

    <!-- Sidebar & Dropdown Scripts -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const wrapper = document.getElementById('main-content-wrapper');
            const reopenBtn = document.getElementById('btn-sidebar-reopen');
            const floatingLogout = document.getElementById('floating-collapsed-logout');
            const overlay = document.getElementById('sidebar-overlay');
            if (!sidebar) return;

            const isMobile = window.innerWidth < 768;

            if (isMobile) {
                if (sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.remove('-translate-x-full');
                    if (overlay) overlay.classList.remove('hidden');
                } else {
                    sidebar.classList.add('-translate-x-full');
                    if (overlay) overlay.classList.add('hidden');
                }
            } else {
                const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');
                if (wrapper) {
                    wrapper.classList.toggle('sidebar-collapsed', isCollapsed);
                }
                
                if (reopenBtn) {
                    if (isCollapsed) {
                        reopenBtn.classList.remove('opacity-0', 'pointer-events-none', '-translate-x-4');
                        reopenBtn.classList.add('opacity-100', 'pointer-events-auto', 'translate-x-0');
                    } else {
                        reopenBtn.classList.remove('opacity-100', 'pointer-events-auto', 'translate-x-0');
                        reopenBtn.classList.add('opacity-0', 'pointer-events-none', '-translate-x-4');
                    }
                }

                if (floatingLogout) {
                    if (isCollapsed) {
                        floatingLogout.classList.remove('opacity-0', 'pointer-events-none', 'translate-x-4');
                        floatingLogout.classList.add('opacity-100', 'pointer-events-auto', 'translate-x-0');
                    } else {
                        floatingLogout.classList.remove('opacity-100', 'pointer-events-auto', 'translate-x-0');
                        floatingLogout.classList.add('opacity-0', 'pointer-events-none', 'translate-x-4');
                    }
                }

                const aiChatBtn = document.getElementById('btn-open-ai-chat');
                if (aiChatBtn) {
                    if (isCollapsed) {
                        aiChatBtn.classList.add('bottom-20');
                        aiChatBtn.classList.remove('bottom-6');
                    } else {
                        aiChatBtn.classList.remove('bottom-20');
                        aiChatBtn.classList.add('bottom-6');
                    }
                }

                try {
                    localStorage.setItem('sidebar_collapsed_pref', isCollapsed ? '1' : '0');
                } catch (e) {}
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth >= 768) {
                try {
                    const pref = localStorage.getItem('sidebar_collapsed_pref');
                    const sidebar = document.getElementById('sidebar');
                    const wrapper = document.getElementById('main-content-wrapper');
                    const reopenBtn = document.getElementById('btn-sidebar-reopen');
                    const floatingLogout = document.getElementById('floating-collapsed-logout');
                    const aiChatBtn = document.getElementById('btn-open-ai-chat');

                    if (pref === '1' && sidebar) {
                        sidebar.classList.add('sidebar-collapsed');
                        if (wrapper) wrapper.classList.add('sidebar-collapsed');
                        if (reopenBtn) {
                            reopenBtn.classList.remove('opacity-0', 'pointer-events-none', '-translate-x-4');
                            reopenBtn.classList.add('opacity-100', 'pointer-events-auto', 'translate-x-0');
                        }
                        if (floatingLogout) {
                            floatingLogout.classList.remove('opacity-0', 'pointer-events-none', 'translate-x-4');
                            floatingLogout.classList.add('opacity-100', 'pointer-events-auto', 'translate-x-0');
                        }
                        if (aiChatBtn) {
                            aiChatBtn.classList.add('bottom-20');
                            aiChatBtn.classList.remove('bottom-6');
                        }
                    }
                } catch (e) {}
            }
        });

        // Keyboard Shortcut: Ctrl + B or Cmd + B to toggle sidebar
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
                const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
                if (activeTag !== 'input' && activeTag !== 'textarea') {
                    e.preventDefault();
                    toggleSidebar();
                }
            }
        });

        function toggleUserProfileMenu() {
            const menu = document.getElementById('user-profile-menu');
            const chevron = document.getElementById('chevron-user-profile');
            if (menu) {
                menu.classList.toggle('hidden');
                if (chevron) {
                    chevron.classList.toggle('rotate-180');
                }
            }
        }

        document.addEventListener('click', function(event) {
            const btnProfile = document.getElementById('btn-user-profile');
            const menuProfile = document.getElementById('user-profile-menu');
            if (btnProfile && menuProfile && !btnProfile.contains(event.target) && !menuProfile.contains(event.target)) {
                menuProfile.classList.add('hidden');
                const chevron = document.getElementById('chevron-user-profile');
                if (chevron) chevron.classList.remove('rotate-180');
            }
        });
    </script>
@endif

    <!-- Script Global Lonceng Notifikasi (Berlaku untuk Pegawai, Teknisi, & Admin) -->
    <script>
        function toggleNotifDropdown() {
            const dropdown = document.getElementById('notif-dropdown');
            const btn = document.getElementById('notif-btn');
            const pegawaiMenu = document.getElementById('pegawai-profile-menu');
            if (pegawaiMenu) pegawaiMenu.classList.add('hidden');
            if (dropdown) {
                const isHidden = dropdown.classList.toggle('hidden');
                if (btn) {
                    if (!isHidden) {
                        btn.classList.add('border-teal-600/60', 'bg-teal-50/40');
                    } else {
                        btn.classList.remove('border-teal-600/60', 'bg-teal-50/40');
                    }
                }
            }
        }

        document.addEventListener('click', function(event) {
            const btnNotif = document.getElementById('notif-btn');
            const dropdownNotif = document.getElementById('notif-dropdown');
            if (btnNotif && dropdownNotif && !btnNotif.contains(event.target) && !dropdownNotif.contains(event.target)) {
                dropdownNotif.classList.add('hidden');
                btnNotif.classList.remove('border-teal-600/60', 'bg-teal-50/40');
            }
        });
    </script>

    <!-- Floating WhatsApp AI Support Chatbox & Email Dispatch Modal (24/7) -->
    @include('partials.ai-support-chatbox')

    @stack('scripts')
</body>
</html>
