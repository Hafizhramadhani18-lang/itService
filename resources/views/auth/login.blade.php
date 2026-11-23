<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - IT Service Desk PT S2P PLTU Karangkandri</title>
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
                            200: '#99f6e4',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                            950: '#042f2e',
                        },
                        navy: {
                            900: '#0e1a2b',
                            950: '#092635',
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
    </style>
</head>
<body class="bg-white min-h-screen text-slate-800 antialiased selection:bg-teal-500 selection:text-white">

    <div class="min-h-screen w-full flex flex-col md:flex-row">
        
        <!-- ============================================================== -->
        <!-- LEFT COLUMN: Identitas Visual & Ilustrasi PLTU Karangkandri    -->
        <!-- (Diperluas ke Kanan agar Background Putih Bergeser Lebih Kanan) -->
        <!-- ============================================================== -->
        <div class="w-full md:w-7/12 lg:w-8/12 xl:w-[68%] bg-gradient-to-br from-[#071d29] via-[#0d3444] to-[#0a1e2b] text-white p-6 sm:p-8 md:p-12 lg:p-14 relative flex flex-col justify-end overflow-hidden flex-shrink-0 min-h-[180px] sm:min-h-[220px] md:min-h-screen">
            
            <!-- Ambient Lighting Glows -->
            <div class="absolute -top-20 -left-20 w-96 h-96 bg-teal-500/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-cyan-400/10 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Visual Silhouette Cerobong & Turbin PLTU Karangkandri -->
            <!-- Di Desktop: Membentang elegan dari dasar hingga ke atas -->
            <!-- Di HP: Menjadi siluet watermark rapi di sisi kanan banner -->
            <div class="absolute right-0 bottom-0 w-44 sm:w-60 md:w-[85%] lg:w-[80%] max-w-xl z-0 md:z-10 opacity-75 md:opacity-85 pointer-events-none select-none">
                <svg viewBox="0 0 550 300" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto drop-shadow-xl">
                    <!-- Base Foundation / Tanah Area PLTU -->
                    <path d="M0 270 C150 265 380 275 550 270 V300 H0 V270 Z" fill="#04121a" fill-opacity="0.95"/>
                    
                    <!-- Gedung Boiler Unit 1 & 2 -->
                    <rect x="60" y="160" width="140" height="110" rx="3" fill="#0b3d52" fill-opacity="0.65"/>
                    <rect x="75" y="140" width="110" height="20" rx="2" fill="#0f4a63" fill-opacity="0.75"/>
                    <rect x="105" y="115" width="50" height="25" rx="2" fill="#135d7c" fill-opacity="0.8"/>

                    <!-- Cerobong Utama 1 (Tall Chimney PLTU Karangkandri) -->
                    <path d="M270 270 L277 40 H290 L297 270 H270 Z" fill="#176d8d" fill-opacity="0.9"/>
                    <circle cx="283.5" cy="38" r="9" fill="#1ab2d4" fill-opacity="0.95"/>
                    <!-- Garis Merah-Putih Khas Cerobong Pembangkit -->
                    <line x1="278" y1="65" x2="289" y2="65" stroke="#f43f5e" stroke-width="3.5" stroke-linecap="round"/>
                    <line x1="277.5" y1="95" x2="289.5" y2="95" stroke="#ffffff" stroke-width="3.5" stroke-linecap="round"/>
                    <line x1="277" y1="125" x2="290" y2="125" stroke="#f43f5e" stroke-width="3.5" stroke-linecap="round"/>

                    <!-- Cerobong Utama 2 (Twin Chimney PLTU Karangkandri) -->
                    <path d="M330 270 L335 75 H346 L351 270 H330 Z" fill="#125a75" fill-opacity="0.85"/>
                    <circle cx="340.5" cy="73" r="7" fill="#189ebc" fill-opacity="0.9"/>
                    <line x1="336" y1="98" x2="345" y2="98" stroke="#f43f5e" stroke-width="3" stroke-linecap="round"/>
                    <line x1="335.5" y1="122" x2="345.5" y2="122" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>

                    <!-- Gedung Turbin Generator & Workshop Lantai 1 -->
                    <rect x="215" y="185" width="170" height="85" rx="3" fill="#0a3242" fill-opacity="0.85"/>
                    <rect x="235" y="200" width="22" height="16" rx="2" fill="#38bdf8" fill-opacity="0.45"/>
                    <rect x="268" y="200" width="22" height="16" rx="2" fill="#38bdf8" fill-opacity="0.45"/>
                    <rect x="301" y="200" width="22" height="16" rx="2" fill="#38bdf8" fill-opacity="0.45"/>
                    <rect x="334" y="200" width="22" height="16" rx="2" fill="#38bdf8" fill-opacity="0.45"/>

                    <!-- Transmisi Gardu Induk & Switchyard Tower -->
                    <line x1="420" y1="270" x2="445" y2="160" stroke="#2dd4bf" stroke-width="2.2" stroke-opacity="0.6"/>
                    <line x1="470" y1="270" x2="445" y2="160" stroke="#2dd4bf" stroke-width="2.2" stroke-opacity="0.6"/>
                    <line x1="430" y1="195" x2="460" y2="195" stroke="#2dd4bf" stroke-width="2.2" stroke-opacity="0.6"/>
                    <line x1="418" y1="160" x2="472" y2="160" stroke="#2dd4bf" stroke-width="2.8" stroke-opacity="0.75"/>

                    <!-- Asap Halus Ramah Lingkungan (Eco Steam Plumes) -->
                    <circle cx="285" cy="22" r="15" fill="#ffffff" fill-opacity="0.12" filter="blur(5px)"/>
                    <circle cx="295" cy="8" r="22" fill="#ffffff" fill-opacity="0.08" filter="blur(7px)"/>
                </svg>
            </div>

            <!-- Bottom Left: Judul & Informasi SOP Pembangkit -->
            <div class="relative z-10 max-w-lg pt-6">
                <h1 class="text-lg sm:text-xl md:text-2xl lg:text-3xl font-extrabold text-white leading-tight tracking-tight">
                    Layanan IT terpadu untuk PLTU Karangkandri
                </h1>
                
                <p class="hidden md:block text-xs sm:text-sm text-teal-100/80 mt-2 font-normal leading-relaxed">
                    Laporkan kendala, pantau penanganan, dan temukan solusi mandiri.
                </p>
                
                <div class="hidden md:flex items-center space-x-2 text-[11px] text-teal-300/80 mt-3.5 font-medium">
                    <i class="fa-solid fa-industry text-teal-400"></i>
                    <span>PT Sumber Segara Primadaya (S2P) &bull; Unit IT Support & Infrastructure</span>
                </div>
            </div>

        </div>

        <!-- ============================================================== -->
        <!-- RIGHT COLUMN: Formulir Masuk (Bergeser Lebih ke Kanan)         -->
        <!-- Panel putih kini ramping dan berlabuh rapi di sisi kanan       -->
        <!-- ============================================================== -->
        <div class="w-full md:w-5/12 lg:w-4/12 xl:w-[32%] bg-white flex flex-col justify-center px-6 py-8 sm:px-8 md:px-8 lg:px-10 xl:px-12 flex-shrink-0">
            <div class="w-full max-w-sm sm:max-w-md mx-auto space-y-4 sm:space-y-5">

                <!-- Header Form: Logo Resmi & Judul -->
                <div>
                    <img src="{{ asset('images/logo-itsupport.png') }}" alt="IT Service Desk PT S2P"
                         class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl object-contain shadow-md shadow-teal-900/20 mb-3.5 sm:mb-4">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Masuk
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                        Masukkan email dan password Anda untuk mengakses layanan IT
                    </p>
                </div>

                <!-- Alert Feedback / Error -->
                @if(session('info'))
                <div class="p-3 rounded-xl bg-teal-50 border border-teal-200 text-teal-900 text-xs flex items-center space-x-2">
                    <i class="fa-solid fa-circle-info text-teal-600 flex-shrink-0"></i>
                    <span>{{ session('info') }}</span>
                </div>
                @endif

                @if($errors->any())
                <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start space-x-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 mt-0.5 flex-shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <!-- Form Masuk -->
                <form action="{{ route('login') }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- Field 1: Email -->
                    <div>
                        <label for="login" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Email
                        </label>
                        <input type="text" 
                               name="login" 
                               id="login" 
                               value="{{ old('login', old('email')) }}" 
                               required 
                               autocomplete="username"
                               placeholder="Masukkan email Anda (contoh: nama@s2p.co.id)"
                               class="w-full px-4 py-2.5 sm:py-3 bg-slate-50/70 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-700/20 focus:border-teal-700 transition">
                    </div>

                    <!-- Field 2: Kata Sandi with Eye Icon Toggle -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Kata sandi
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   required 
                                   autocomplete="current-password"
                                   placeholder="••••••••"
                                   class="w-full pl-4 pr-11 py-2.5 sm:py-3 bg-slate-50/70 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-700/20 focus:border-teal-700 transition">
                            
                            <!-- Toggle Button Ikon Mata Modern -->
                            <button type="button" 
                                    id="toggle-btn"
                                    onclick="togglePasswordVisibility()" 
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-teal-700 focus:outline-none cursor-pointer transition"
                                    title="Tampilkan kata sandi">
                                <i id="toggle-icon" class="fa-solid fa-eye text-sm"></i>
                                <span class="sr-only" id="toggle-sr-text">Tampilkan</span>
                            </button>
                        </div>
                    </div>

                    <!-- Row: Ingat saya & Lupa kata sandi -->
                    <div class="flex items-center justify-between text-xs pt-0.5">
                        <label class="flex items-center space-x-2 text-slate-600 cursor-pointer font-medium">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-teal-700 focus:ring-teal-600 border-slate-300 transition">
                            <span>Ingat saya</span>
                        </label>

                        <a href="javascript:void(0)" 
                           onclick="alert('Lupa kata sandi?\n\nSilakan hubungi Unit IT Support & Infrastructure PLTU Karangkandri melalui Ext. 102 atau datang ke Ruang IT Gedung Admin Lantai 1.')"
                           class="font-semibold text-teal-700 hover:text-teal-800">
                            Lupa kata sandi?
                        </a>
                    </div>

                    <!-- Main Submit Button: Masuk -->
                    <button type="submit" 
                            class="w-full py-2.5 sm:py-3 px-4 bg-teal-700 hover:bg-teal-800 active:scale-[0.99] text-white font-bold rounded-xl text-sm shadow-md shadow-teal-900/20 transition duration-150 flex items-center justify-center space-x-2 cursor-pointer">
                        <span>Masuk</span>
                    </button>
                </form>

                <!-- ============================================================== -->
                <!-- TOMBOL AKSES CEPAT DEMO (TIM IT & RINA PEGAWAI)                -->
                <!-- Didesain elegan dan rapi agar selaras dengan SOP formulir      -->
                <!-- ============================================================== -->
                <div class="pt-1">
                    <div class="relative my-3">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-200/80"></div>
                        </div>
                        <div class="relative flex justify-center text-[10px] uppercase font-bold tracking-wider text-slate-400">
                            <span class="bg-white px-2.5">Akses Cepat Demo</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <!-- Tombol Masuk Admin (Koordinator IT) -->
                        @if(!empty($adminUser))
                        <a href="{{ route('quick.login', $adminUser) }}" 
                           class="py-2.5 px-3 rounded-xl bg-[#092635] hover:bg-[#0f384c] active:scale-95 text-white text-xs font-bold flex items-center justify-center space-x-2 shadow-xs transition duration-150 group sm:col-span-2 border border-teal-800/40">
                            <i class="fa-solid fa-user-shield text-teal-400 group-hover:scale-110 transition"></i>
                            <span>Masuk Admin (Koordinator IT)</span>
                        </a>
                        @endif

                        <!-- Tombol Masuk Hafizh (Teknisi Lapangan) -->
                        @if($hafizhUser)
                        <a href="{{ route('quick.login', $hafizhUser) }}" 
                           class="py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 active:scale-95 text-white text-xs font-bold flex items-center justify-center space-x-1.5 shadow-xs transition duration-150 group">
                            <i class="fa-solid fa-wrench text-amber-400 group-hover:scale-110 transition text-[11px]"></i>
                            <span>Masuk Tim IT (Hafizh)</span>
                        </a>
                        @endif

                        <!-- Tombol Masuk Giska (Teknisi Support / Aplikasi) -->
                        @if(!empty($giskaUser))
                        <a href="{{ route('quick.login', $giskaUser) }}" 
                           class="py-2.5 px-3 rounded-xl bg-indigo-950 hover:bg-indigo-900 active:scale-95 text-white text-xs font-bold flex items-center justify-center space-x-1.5 shadow-xs transition duration-150 group border border-indigo-800/40">
                            <i class="fa-solid fa-laptop-code text-cyan-300 group-hover:scale-110 transition text-[11px]"></i>
                            <span>Giska (IT Aplikasi)</span>
                        </a>
                        @elseif($itUser)
                        <a href="{{ route('quick.login', $itUser) }}" 
                           class="py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 active:scale-95 text-white text-xs font-bold flex items-center justify-center space-x-2 shadow-xs transition duration-150 group">
                            <i class="fa-solid fa-user-gear text-teal-400 group-hover:scale-110 transition"></i>
                            <span>Masuk Tim IT</span>
                        </a>
                        @endif

                        <!-- Tombol Masuk Rina (Pegawai) -->
                        @if($rinaUser)
                        <a href="{{ route('quick.login', $rinaUser) }}" 
                           class="py-2.5 px-3 rounded-xl bg-teal-50 hover:bg-teal-100/90 active:scale-95 border border-teal-200/80 text-teal-900 text-xs font-bold flex items-center justify-center space-x-2 shadow-xs transition duration-150 group sm:col-span-2">
                            <i class="fa-solid fa-user text-teal-700 group-hover:scale-110 transition"></i>
                            <span>Masuk Rina (Pegawai)</span>
                        </a>
                        @endif
                    </div>
                </div>

                <!-- Belum punya akun Notice Box -->
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-center text-xs text-slate-500 font-medium">
                    Belum punya akun? Hubungi IT Support divisi Anda.
                </div>

                <!-- Footer Hak Cipta -->
                <div class="text-center text-xs text-slate-400 font-medium pt-1">
                    &copy; 2026 PT S2P &bull; PLTU Karangkandri
                    <span class="block text-[10px] text-slate-400/80 mt-0.5">PT Sumber Segara Primadaya</span>
                </div>

            </div>
        </div>

    </div>

    <!-- Toggle Password Visibility Script -->
    <script>
        function togglePasswordVisibility() {
            const pwInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggle-icon');
            const toggleBtn = document.getElementById('toggle-btn');
            const toggleSrText = document.getElementById('toggle-sr-text');
            
            if (pwInput.type === 'password') {
                pwInput.type = 'text';
                if (toggleIcon) {
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                    toggleIcon.classList.add('text-teal-700');
                }
                if (toggleBtn) toggleBtn.setAttribute('title', 'Sembunyikan kata sandi');
                if (toggleSrText) toggleSrText.textContent = 'Sembunyikan';
            } else {
                pwInput.type = 'password';
                if (toggleIcon) {
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.remove('text-teal-700');
                    toggleIcon.classList.add('fa-eye');
                }
                if (toggleBtn) toggleBtn.setAttribute('title', 'Tampilkan kata sandi');
                if (toggleSrText) toggleSrText.textContent = 'Tampilkan';
            }
        }
    </script>

</body>
</html>
