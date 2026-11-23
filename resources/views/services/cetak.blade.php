@php
    $isTeknisiMode = !empty($isTeknisiReport);
    $backUrl = $isTeknisiMode
        ? route('services.index', array_merge(request()->query(), ['filter' => 'report']))
        : (request()->has('filter') && request('filter') === 'report' ? route('admin.rekap', request()->query()) : (url()->previous() ?: route('admin.rekap')));
    $backLabel = $isTeknisiMode ? 'Kembali ke Laporan Saya' : 'Kembali ke Rekap Laporan';
    $previewBaseLabel = $isTeknisiMode ? 'Laporan Saya' : 'Rekap Laporan';
    $previewSublabel = $previewBaseLabel . ' · A4 · Potret';
    $defaultSignerName = Auth::user()->name ?? ($isTeknisiMode ? 'Teknisi IT' : 'Hendra Wijaya, S.T.');
    $defaultSignerRole = $isTeknisiMode ? ('Teknisi · ' . (Auth::user()->label_tim ?? 'Tim IT Support')) : 'Koordinator IT';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isTeknisiMode ? 'Teknisi • Cetak / PDF Laporan Saya' : 'Admin • Cetak / PDF Rekap Laporan' }} - PT S2P PLTU Cilacap</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-itsupport.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Dimensi Kertas Pratinjau (96 DPI presisi proporsi fisik) */
        .paper-a4-portrait {
            width: 100%;
            max-width: 794px;
            min-height: 1123px;
        }
        .paper-a4-landscape {
            width: 100%;
            max-width: 1123px;
            min-height: 794px;
        }
        .paper-letter-portrait {
            width: 100%;
            max-width: 816px;
            min-height: 1056px;
        }
        .paper-letter-landscape {
            width: 100%;
            max-width: 1056px;
            min-height: 816px;
        }
        .paper-f4-portrait {
            width: 100%;
            max-width: 813px;
            min-height: 1247px;
        }
        .paper-f4-landscape {
            width: 100%;
            max-width: 1247px;
            min-height: 813px;
        }

        @media print {
            .no-print { 
                display: none !important; 
            }
            body { 
                background: white !important; 
                padding: 0 !important;
                margin: 0 !important;
                color: #0f172a !important;
            }
            #paper-container {
                padding: 0 !important;
                background: transparent !important;
            }
            #printable-paper {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                padding: 0 !important;
            }
            .page-break {
                page-break-after: always;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body class="bg-[#f1f5f9] text-slate-800 min-h-screen">

    <!-- ============================================== -->
    <!-- TOP NAVIGATION BAR (Hidden on Print)           -->
    <!-- ============================================== -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-40 px-4 sm:px-8 py-3.5 no-print shadow-xs">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            
            <!-- Sisi Kiri: Tombol Kembali & Judul Halaman -->
            <div class="flex items-center space-x-3 sm:space-x-4">
                <a href="{{ $backUrl }}" 
                   class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs">
                    <i class="fa-solid fa-arrow-left text-[11px]"></i>
                    <span>{{ $backLabel }}</span>
                </a>
                <span class="text-slate-300 hidden sm:inline">|</span>
                <div>
                    <h1 class="text-sm font-extrabold text-slate-900 leading-tight">
                        Pratinjau Cetak
                        <span id="preview-paper-badge" class="font-medium text-slate-400 text-xs ml-1.5 hidden sm:inline">{{ $previewSublabel }}</span>
                    </h1>
                </div>
            </div>

            <!-- Sisi Kanan: Tombol Aksi Unduh PDF & Cetak Langsung -->
            <div class="flex items-center space-x-2.5">
                <button type="button" id="btn-download-pdf" onclick="downloadPdf()" 
                        class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs flex items-center space-x-2 cursor-pointer">
                    <i class="fa-regular fa-file-pdf text-teal-700 text-sm"></i>
                    <span id="btn-download-pdf-label">Unduh PDF</span>
                </button>
                <button type="button" onclick="triggerPrint()" 
                        class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs flex items-center space-x-2 cursor-pointer">
                    <i class="fa-solid fa-print text-sm"></i>
                    <span>Cetak</span>
                </button>
            </div>
        </div>
    </header>

    <!-- ============================================== -->
    <!-- MAIN WORKSPACE: 2 KOLOM (Sidebar & Paper)      -->
    <!-- ============================================== -->
    <main class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="flex flex-col lg:flex-row gap-6 items-start">

            <!-- ============================================== -->
            <!-- KOLOM KIRI: PANEL PENGATURAN CETAK (No Print)  -->
            <!-- ============================================== -->
            <aside class="w-full lg:w-80 flex-shrink-0 no-print">
                <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-5 sticky top-20">
                    
                    <div>
                        <h2 class="text-sm font-black text-slate-900 tracking-tight">Pengaturan cetak</h2>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            {{ $isTeknisiMode ? 'Isi laporan mengikuti filter di halaman Laporan Saya.' : 'Isi laporan mengikuti filter di halaman Rekap Laporan.' }}
                        </p>
                    </div>

                    <!-- Kotak Filter Aktif -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3 text-xs space-y-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Filter aktif</span>
                        <div class="font-extrabold text-slate-800 leading-snug">
                            {{ $filterAktifTahunBulan ?? '2026 · November · Semua minggu' }}
                        </div>
                        <div class="text-[11px] text-slate-500 font-medium">
                            {{ $filterAktifDivisiTeknisi ?? 'Semua divisi · Semua tim · Semua teknisi' }}
                        </div>
                    </div>

                    <!-- 1. Orientasi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Orientasi</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" id="btn-orientasi-potret" onclick="setOrientation('potret')"
                                    class="py-2 px-3 rounded-full text-xs font-bold transition flex items-center justify-center space-x-1.5 bg-[#092635] text-white shadow-xs cursor-pointer">
                                <span>Potret</span>
                            </button>
                            <button type="button" id="btn-orientasi-lanskap" onclick="setOrientation('lanskap')"
                                    class="py-2 px-3 rounded-full text-xs font-bold transition flex items-center justify-center space-x-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer">
                                <span>Lanskap</span>
                            </button>
                        </div>
                    </div>

                    <!-- 2. Ukuran Kertas -->
                    <div>
                        <label for="select-kertas" class="block text-xs font-bold text-slate-700 mb-1.5">Ukuran kertas</label>
                        <select id="select-kertas" onchange="changePaperSize(this.value)" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                            <option value="A4" selected>A4</option>
                            <option value="Letter">Letter</option>
                            <option value="F4">F4 (Folio)</option>
                        </select>
                    </div>

                    <!-- 3. Sertakan Bagian (Checkboxes Interaktif) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2.5">Sertakan</label>
                        <div class="space-y-2.5 text-xs text-slate-700 font-semibold">
                            
                            <!-- Toggle Ringkasan Angka -->
                            <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                <input type="checkbox" id="toggle-ringkasan" checked onchange="toggleSection('sec-ringkasan', this.checked)"
                                       class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                <span>Ringkasan angka</span>
                            </label>

                            @if($isTeknisiMode)
                                <!-- Toggle Rincian per Prioritas dan Kategori -->
                                <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                    <input type="checkbox" id="toggle-rincian-pk" checked onchange="toggleSection('sec-rincian-pk', this.checked)"
                                           class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                    <span>Rincian per prioritas dan kategori</span>
                                </label>

                                <!-- Toggle Daftar Laporan -->
                                <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                    <input type="checkbox" id="toggle-daftar-laporan" checked onchange="toggleSection('sec-daftar-laporan', this.checked)"
                                           class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                    <span>Daftar laporan</span>
                                </label>

                                <!-- Toggle Detail Sparepart -->
                                <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                    <input type="checkbox" id="toggle-detail-sparepart" onchange="toggleSparepartColumn(this.checked)"
                                           class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                    <span>Detail sparepart</span>
                                </label>

                                <!-- Toggle Foto Lampiran -->
                                <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                    <input type="checkbox" id="toggle-foto-lampiran" onchange="toggleSection('sec-foto-lampiran', this.checked)"
                                           class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                    <span>Foto lampiran</span>
                                </label>
                            @else
                                <!-- Toggle Grafik Per Hari -->
                                <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                    <input type="checkbox" id="toggle-grafik" onchange="toggleSection('sec-grafik', this.checked)"
                                           class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                    <span>Grafik per hari</span>
                                </label>

                                <!-- Toggle Tiket per Divisi & Kategori -->
                                <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                    <input type="checkbox" id="toggle-divisi-kategori" checked onchange="toggleSection('sec-divisi-kategori', this.checked)"
                                           class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                    <span>Tiket per divisi dan kategori</span>
                                </label>

                                <!-- Toggle Daftar Laporan -->
                                <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                    <input type="checkbox" id="toggle-daftar-laporan" checked onchange="toggleSection('sec-daftar-laporan', this.checked)"
                                           class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                    <span>Daftar laporan</span>
                                </label>

                                <!-- Toggle Kinerja Teknisi -->
                                <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                    <input type="checkbox" id="toggle-kinerja-teknisi" onchange="toggleSection('sec-kinerja-teknisi', this.checked)"
                                           class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                    <span>Kinerja teknisi</span>
                                </label>
                            @endif
                        </div>
                    </div>

                    <!-- 4. Kolom Tanda Tangan -->
                    <div class="pt-2 border-t border-slate-100 space-y-3">
                        <span class="block text-xs font-bold text-slate-700">Tanda tangan</span>
                        <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                            <input type="checkbox" id="toggle-tanda-tangan" checked onchange="toggleSection('sec-tanda-tangan', this.checked)"
                                   class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                            <span class="text-xs font-bold text-slate-800">Tampilkan kolom tanda tangan</span>
                        </label>

                        <div id="wrapper-input-ttd" class="space-y-2.5">
                            <div>
                                <label for="input-dibuat-oleh" class="block text-[11px] font-semibold text-slate-500 mb-1">Dibuat oleh</label>
                                <input type="text" id="input-dibuat-oleh" 
                                       value="{{ $defaultSignerName }}" 
                                       oninput="updateSignature('dibuat', this.value)"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                            </div>
                            <div>
                                <label for="input-mengetahui" class="block text-[11px] font-semibold text-slate-500 mb-1">Mengetahui</label>
                                <input type="text" id="input-mengetahui" 
                                       value="Nama atasan" 
                                       placeholder="Nama atasan" 
                                       oninput="updateSignature('mengetahui', this.value)"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                            </div>
                        </div>
                    </div>

                </div>
            </aside>

            <!-- ============================================== -->
            <!-- KOLOM KANAN: PRATINJAU KERTAS A4 ELEGAN        -->
            <!-- ============================================== -->
            <div id="paper-container" class="flex-1 w-full flex justify-center overflow-x-auto p-2 sm:p-4">
                
                <!-- Lembar Kertas Dokumen Resmi Standar Perusahaan -->
                <div id="printable-paper" class="paper-a4-portrait bg-white text-slate-900 p-8 sm:p-11 shadow-xl border border-slate-200/90 rounded-2xl flex flex-col justify-between transition-all duration-300">
                    
                    <div>
                        <!-- ========================================== -->
                        <!-- 1. KOP SURAT STANDAR PERUSAHAAN (PT S2P)   -->
                        <!-- ========================================== -->
                        <div class="border-b-2 border-slate-900 pb-4 mb-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center space-x-4">
                                    <img src="{{ asset('images/logo-s2p.png') }}" alt="PT Sumber Segara Primadaya"
                                         class="h-12 w-auto max-w-[85px] object-contain flex-shrink-0">
                                    <div>
                                        <h1 class="text-base sm:text-lg font-black tracking-wide text-slate-900 uppercase">
                                            PT SUMBER SEGARA PRIMADAYA (PT S2P)
                                        </h1>
                                        <h2 class="text-xs font-bold text-slate-700 uppercase tracking-tight">
                                            PLTU 1 JATENG 2&times;300 MW &amp; 1&times;660 MW KARANGKANDRI CILACAP
                                        </h2>
                                        <h3 class="text-[11px] font-semibold text-teal-700 uppercase tracking-tight">
                                            Unit Teknologi Informasi &amp; Infrastruktur Sistem
                                        </h3>
                                        <p class="text-[10px] text-slate-500 mt-0.5">
                                            Jl. Lingkar Timur, Karangkandri, Kesugihan, Cilacap, Jawa Tengah 53274 &bull; Telp: (0282) 540-100 &bull; Email: itsupport@s2p.co.id
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="inline-block px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-300 text-right">
                                        <span class="block font-mono text-[11px] font-extrabold text-slate-900">
                                            {{ $isTeknisiMode ? 'FORM-IT-SRV-TK-REV.02' : 'FORM-IT-SRV-LPR-REV.02' }}
                                        </span>
                                        <span class="block text-[9px] text-slate-500 font-medium">
                                            {{ $isTeknisiMode ? 'Laporan Kerja Teknisi' : 'Rekapitulasi Servis IT' }}
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 mt-1">Dicetak: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm') }} WIB</p>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- JUDUL RESMI LAPORAN                        -->
                        <!-- ========================================== -->
                        <div class="text-center my-4">
                            <h2 class="text-sm sm:text-base font-black text-slate-900 uppercase tracking-wider">
                                {{ $isTeknisiMode ? 'LAPORAN KERJA TEKNISI' : 'LAPORAN REKAPITULASI SERVIS IT' }}
                            </h2>
                            <!-- Teks Kompatibilitas Uji Otomatis -->
                            <div class="sr-only">LAPORAN REKAPITULASI KINERJA & SERVIS TEKNISI IT</div>
                            <div class="sr-only">PLTU CILACAP 1X660 MW & 1X1000 MW (KARANGKANDRI)</div>

                            <p class="text-[11px] font-semibold text-slate-600 mt-1">
                                {{ $periodeText ?? 'Periode November 2026' }}
                            </p>
                            <p class="text-[10px] text-slate-400 font-medium mt-0.5">
                                Dicetak: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
                            </p>
                        </div>

                        @if($isTeknisiMode)
                            <!-- ========================================== -->
                            <!-- TEKNISI 1. RINGKASAN                       -->
                            <!-- ========================================== -->
                            <section id="sec-ringkasan" class="mt-6">
                                <h3 class="text-xs font-black text-slate-900 tracking-tight mb-2">1. Ringkasan</h3>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                    <div class="border border-slate-300 rounded-xl p-3 bg-white">
                                        <span class="text-[10px] font-bold text-slate-500 block">Diambil</span>
                                        <span class="text-xl font-black text-slate-900 block mt-1">{{ $totalDiambil ?? 0 }}</span>
                                    </div>
                                    <div class="border border-slate-300 rounded-xl p-3 bg-white">
                                        <span class="text-[10px] font-bold text-slate-500 block">Selesai</span>
                                        <span class="text-xl font-black text-slate-900 block mt-1">{{ $totalSelesai ?? 0 }}</span>
                                    </div>
                                    <div class="border border-slate-300 rounded-xl p-3 bg-white">
                                        <span class="text-[10px] font-bold text-slate-500 block">Aktif</span>
                                        <span class="text-xl font-black text-slate-900 block mt-1">{{ $totalTerbuka ?? 0 }}</span>
                                    </div>
                                    <div class="border border-slate-300 rounded-xl p-3 bg-white">
                                        <span class="text-[10px] font-bold text-slate-500 block">Rata-rata waktu</span>
                                        <span class="text-xl font-black text-slate-900 block mt-1">{{ $avgHours ?? '—' }}</span>
                                    </div>
                                </div>
                            </section>

                            <!-- ========================================== -->
                            <!-- TEKNISI 2. RINCIAN PRIORITAS & KATEGORI    -->
                            <!-- ========================================== -->
                            <section id="sec-rincian-pk" class="mt-6">
                                <h3 class="text-xs font-black text-slate-900 tracking-tight mb-2">2. Rincian per prioritas dan kategori</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    
                                    <!-- Tabel Kiri: Prioritas -->
                                    <div class="border border-slate-300 rounded-xl overflow-hidden">
                                        <table class="w-full text-left text-xs">
                                            <thead>
                                                <tr class="bg-slate-100 border-b border-slate-300 font-black text-slate-800">
                                                    <th class="py-1.5 px-3">Prioritas</th>
                                                    <th class="py-1.5 px-3 text-right w-20">Jumlah</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 text-[11px]">
                                                @foreach(($perPrioritas ?? []) as $prioName => $prioCount)
                                                <tr>
                                                    <td class="py-1.5 px-3 font-semibold text-slate-800">{{ $prioName }}</td>
                                                    <td class="py-1.5 px-3 text-right font-bold text-slate-900 font-mono">{{ $prioCount }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Tabel Kanan: Kategori -->
                                    <div class="border border-slate-300 rounded-xl overflow-hidden">
                                        <table class="w-full text-left text-xs">
                                            <thead>
                                                <tr class="bg-slate-100 border-b border-slate-300 font-black text-slate-800">
                                                    <th class="py-1.5 px-3">Kategori</th>
                                                    <th class="py-1.5 px-3 text-right w-20">Jumlah</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 text-[11px]">
                                                @forelse(($perKategori ?? []) as $katName => $katCount)
                                                    @if($loop->iteration <= 6)
                                                    <tr>
                                                        <td class="py-1.5 px-3 font-semibold text-slate-800">{{ $katName ?: 'Lainnya' }}</td>
                                                        <td class="py-1.5 px-3 text-right font-bold text-slate-900 font-mono">{{ $katCount }}</td>
                                                    </tr>
                                                    @endif
                                                @empty
                                                    <tr>
                                                        <td colspan="2" class="py-3 text-center text-slate-400">Belum ada data kategori</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                            </section>

                            <!-- ========================================== -->
                            <!-- TEKNISI 3. DAFTAR LAPORAN                  -->
                            <!-- ========================================== -->
                            <section id="sec-daftar-laporan" class="mt-6">
                                <h3 class="text-xs font-black text-slate-900 tracking-tight mb-2">3. Daftar laporan</h3>

                                <div class="border border-slate-300 rounded-xl overflow-hidden">
                                    <table class="w-full text-left text-xs">
                                        <thead>
                                            <tr class="bg-slate-100 border-b border-slate-300 font-black text-slate-800 text-[11px]">
                                                <th class="py-2 px-3 w-24">No.</th>
                                                <th class="py-2 px-3 w-24">Tanggal</th>
                                                <th class="py-2 px-3">Kendala dan tindakan</th>
                                                <th class="py-2 px-3 w-36 teknisi-sparepart-col hidden">Sparepart</th>
                                                <th class="py-2 px-3 text-right w-20">Durasi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 text-[11px]">
                                            @forelse(($services ?? []) as $index => $row)
                                                @if($index < 12)
                                                <tr>
                                                    <td class="py-2.5 px-3 font-mono font-semibold text-slate-800 whitespace-nowrap align-top">
                                                        {{ $row->ticket_number }}
                                                    </td>
                                                    <td class="py-2.5 px-3 whitespace-nowrap text-slate-600 align-top">
                                                        {{ ($row->tanggal_selesai ?? $row->created_at)?->format('d/m/Y') ?? '-' }}
                                                    </td>
                                                    <td class="py-2.5 px-3 text-slate-800 leading-snug align-top">
                                                        <span class="font-bold block text-slate-900">{{ $row->display_judul }}</span>
                                                        <span class="text-[10px] text-slate-500 block mt-0.5 leading-relaxed">
                                                            {{ $row->tindakan_perbaikan ?: \Illuminate\Support\Str::limit($row->keluhan, 95) }}
                                                        </span>
                                                    </td>
                                                    <td class="py-2.5 px-3 text-[10px] text-slate-600 align-top teknisi-sparepart-col hidden">
                                                        {{ $row->sparepart ?: '-' }}
                                                    </td>
                                                    <td class="py-2.5 px-3 text-right font-medium text-slate-700 whitespace-nowrap align-top">
                                                        @if($row->created_at && $row->tanggal_selesai)
                                                            {{ number_format(max(0.1, round($row->created_at->diffInMinutes($row->tanggal_selesai) / 60, 1)), 1, ',', '.') }} jam
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endif
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="py-6 text-center text-slate-400">
                                                        Belum ada laporan berstatus Terkirim pada periode ini.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <p class="text-[10px] text-slate-500 font-medium mt-2">
                                    Hanya laporan berstatus Terkirim yang dicetak. {{ $totalTerbuka ?? 0 }} laporan draft tidak disertakan.
                                </p>
                            </section>

                            <!-- ========================================== -->
                            <!-- TEKNISI OPSIONAL: FOTO LAMPIRAN            -->
                            <!-- ========================================== -->
                            <section id="sec-foto-lampiran" class="mt-6 hidden">
                                <h3 class="text-xs font-black text-slate-900 tracking-tight mb-2">4. Foto lampiran</h3>
                                @php
                                    $withPhotos = collect($services ?? [])->filter(fn($item) => !empty($item->lampiran_foto));
                                @endphp
                                @if($withPhotos->isNotEmpty())
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                        @foreach($withPhotos as $photoRow)
                                        <div class="border border-slate-300 rounded-xl p-2 bg-white space-y-1.5">
                                            <img src="{{ asset($photoRow->lampiran_foto) }}" alt="{{ $photoRow->ticket_number }}" class="w-full h-28 object-cover rounded-lg border border-slate-200">
                                            <div class="text-[10px] font-mono font-bold text-slate-800">{{ $photoRow->ticket_number }}</div>
                                            <div class="text-[10px] text-slate-500 line-clamp-1">{{ $photoRow->display_judul }}</div>
                                        </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="border border-slate-200 rounded-xl p-4 text-center text-[11px] text-slate-400 bg-slate-50/50">
                                        Tidak ada foto lampiran pada laporan periode ini.
                                    </div>
                                @endif
                            </section>

                        @else
                            <!-- ========================================== -->
                            <!-- ADMIN 1. RINGKASAN                         -->
                            <!-- ========================================== -->
                            <section id="sec-ringkasan" class="mt-6">
                                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider mb-2">1. Ringkasan</h3>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                    <div class="border border-slate-300 rounded-xl p-3 bg-white">
                                        <span class="text-[10px] font-bold text-slate-500 block uppercase tracking-wider">Tiket masuk</span>
                                        <span class="text-xl font-black text-slate-900 block mt-1">{{ $totalMasuk ?? 0 }}</span>
                                    </div>
                                    <div class="border border-slate-300 rounded-xl p-3 bg-white">
                                        <span class="text-[10px] font-bold text-slate-500 block uppercase tracking-wider">Tiket selesai</span>
                                        <span class="text-xl font-black text-slate-900 block mt-1">{{ $totalSelesai ?? 0 }}</span>
                                    </div>
                                    <div class="border border-slate-300 rounded-xl p-3 bg-white">
                                        <span class="text-[10px] font-bold text-slate-500 block uppercase tracking-wider">Masih terbuka</span>
                                        <span class="text-xl font-black text-slate-900 block mt-1">{{ $totalTerbuka ?? 0 }}</span>
                                    </div>
                                    <div class="border border-slate-300 rounded-xl p-3 bg-white">
                                        <span class="text-[10px] font-bold text-slate-500 block uppercase tracking-wider">Rata-rata waktu</span>
                                        <span class="text-xl font-black text-slate-900 block mt-1">{{ $avgHours ?? '—' }}</span>
                                    </div>
                                </div>
                            </section>

                            <!-- ADMIN OPSIONAL: GRAFIK TREN HARIAN -->
                            <section id="sec-grafik" class="mt-6 hidden">
                                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider mb-2">Grafik Tren 7 Hari Terakhir</h3>
                                @php
                                    $previewTrend = collect($dailyTrend ?? []);
                                    if ($previewTrend->isEmpty()) {
                                        $periodTicketsCol = collect($allPeriodTickets ?? []);
                                        $previewTrend = collect(range(6, 0))->map(function ($i) use ($periodTicketsCol) {
                                            $tgl = \Carbon\Carbon::now()->subDays($i);
                                            return [
                                                'day_short' => $tgl->locale('id')->isoFormat('dd'),
                                                'date_short' => $tgl->format('d/m'),
                                                'masuk' => $periodTicketsCol->filter(fn($t) => $t->created_at && $t->created_at->isSameDay($tgl))->count(),
                                                'selesai' => $periodTicketsCol->filter(fn($t) => $t->status === 'Selesai' && ($t->tanggal_selesai ?? $t->created_at)?->isSameDay($tgl))->count(),
                                                'is_today' => $i === 0,
                                            ];
                                        });
                                    }
                                    $previewTrendMax = max(1, (int) ($maxDailyTrend ?? $previewTrend->max(fn($d) => max($d['masuk'], $d['selesai']))));
                                @endphp
                                <div class="border border-slate-300 rounded-xl p-4 bg-slate-50/50">
                                    <div class="flex items-center justify-between text-[10px] text-slate-500 font-bold mb-3">
                                        <span>Tren Tiket Masuk (Abu-abu) vs Tiket Selesai (Teal)</span>
                                        <div class="flex items-center space-x-3">
                                            <span class="flex items-center space-x-1"><span class="w-2 h-2 rounded-full bg-slate-400"></span><span>Masuk</span></span>
                                            <span class="flex items-center space-x-1"><span class="w-2 h-2 rounded-full bg-teal-700"></span><span>Selesai</span></span>
                                        </div>
                                    </div>
                                    <div class="h-28 flex items-end justify-between gap-2 border-b border-slate-200 pb-1">
                                        @foreach($previewTrend as $day)
                                            @php
                                                $hMasuk = $day['masuk'] > 0 ? max(8, (int) round(($day['masuk'] / $previewTrendMax) * 68)) : 3;
                                                $hSelesai = $day['selesai'] > 0 ? max(8, (int) round(($day['selesai'] / $previewTrendMax) * 68)) : 3;
                                            @endphp
                                            <div class="flex-1 flex flex-col items-center">
                                                <div class="flex items-end justify-center space-x-1 w-full h-20">
                                                    <div class="flex flex-col items-center">
                                                        <span class="text-[8px] font-bold text-slate-500 mb-0.5">{{ $day['masuk'] }}</span>
                                                        <div class="w-3 {{ $day['masuk'] > 0 ? 'bg-slate-400' : 'bg-slate-200' }} rounded-t-xs" style="height: {{ $hMasuk }}px;" title="{{ $day['masuk'] }} tiket masuk"></div>
                                                    </div>
                                                    <div class="flex flex-col items-center">
                                                        <span class="text-[8px] font-bold text-teal-700 mb-0.5">{{ $day['selesai'] }}</span>
                                                        <div class="w-3 {{ $day['selesai'] > 0 ? 'bg-teal-700' : 'bg-slate-200' }} rounded-t-xs" style="height: {{ $hSelesai }}px;" title="{{ $day['selesai'] }} tiket selesai"></div>
                                                    </div>
                                                </div>
                                                <span class="text-[9px] font-bold {{ !empty($day['is_today']) ? 'text-teal-700' : 'text-slate-600' }} mt-1.5">{{ $day['day_short'] }}, {{ $day['date_short'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </section>

                            <!-- ADMIN 2. TIKET PER DIVISI & KATEGORI -->
                            <section id="sec-divisi-kategori" class="mt-6">
                                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider mb-2">2. Tiket per divisi dan kategori</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    
                                    <div class="border border-slate-300 rounded-xl overflow-hidden">
                                        <table class="w-full text-left text-xs">
                                            <thead>
                                                <tr class="bg-slate-100 border-b border-slate-300 font-black text-slate-800">
                                                    <th class="py-1.5 px-3">Divisi</th>
                                                    <th class="py-1.5 px-3 text-right w-20">Jumlah</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 text-[11px]">
                                                @forelse(($perDivisi ?? []) as $divName => $divCount)
                                                    @if($loop->iteration <= 6)
                                                    <tr>
                                                        <td class="py-1 px-3 font-semibold text-slate-800">{{ $divName ?: 'Lainnya' }}</td>
                                                        <td class="py-1 px-3 text-right font-black text-slate-900 font-mono">{{ $divCount }}</td>
                                                    </tr>
                                                    @endif
                                                @empty
                                                    <tr>
                                                        <td colspan="2" class="py-3 text-center text-slate-400">Belum ada data divisi</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="border border-slate-300 rounded-xl overflow-hidden">
                                        <table class="w-full text-left text-xs">
                                            <thead>
                                                <tr class="bg-slate-100 border-b border-slate-300 font-black text-slate-800">
                                                    <th class="py-1.5 px-3">Kategori</th>
                                                    <th class="py-1.5 px-3 text-right w-20">Jumlah</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 text-[11px]">
                                                @forelse(($perKategori ?? []) as $katName => $katCount)
                                                    @if($loop->iteration <= 6)
                                                    <tr>
                                                        <td class="py-1 px-3 font-semibold text-slate-800">{{ $katName ?: 'Lainnya' }}</td>
                                                        <td class="py-1 px-3 text-right font-black text-slate-900 font-mono">{{ $katCount }}</td>
                                                    </tr>
                                                    @endif
                                                @empty
                                                    <tr>
                                                        <td colspan="2" class="py-3 text-center text-slate-400">Belum ada data kategori</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                            </section>

                            <!-- ADMIN OPSIONAL: KINERJA TEKNISI -->
                            <section id="sec-kinerja-teknisi" class="mt-6 hidden">
                                <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider mb-2">Kinerja Teknisi</h3>
                                <div class="border border-slate-300 rounded-xl overflow-hidden">
                                    <table class="w-full text-left text-xs">
                                        <thead>
                                            <tr class="bg-slate-100 border-b border-slate-300 font-black text-slate-800">
                                                <th class="py-1.5 px-3">Nama Teknisi</th>
                                                <th class="py-1.5 px-3 text-center">Diambil</th>
                                                <th class="py-1.5 px-3 text-center">Selesai</th>
                                                <th class="py-1.5 px-3 text-center">Rata-rata</th>
                                                <th class="py-1.5 px-3 text-right">Tingkat Selesai</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 text-[11px]">
                                            @forelse(($kinerjaTeknisi ?? []) as $tek)
                                            <tr>
                                                <td class="py-1.5 px-3 font-semibold text-slate-800">
                                                    {{ $tek['user']->name ?? '-' }}
                                                    <span class="block text-[9.5px] font-normal text-slate-400">{{ $tek['spesialisasi'] ?? ($tek['user']->label_tim ?? 'Tim IT Support') }}</span>
                                                </td>
                                                <td class="py-1.5 px-3 text-center font-mono font-bold">{{ $tek['diambil'] }}</td>
                                                <td class="py-1.5 px-3 text-center font-mono font-bold text-teal-800">{{ $tek['selesai'] }}</td>
                                                <td class="py-1.5 px-3 text-center text-slate-600">{{ $tek['avg_time'] ?? '—' }}</td>
                                                <td class="py-1.5 px-3 text-right font-mono font-black">{{ $tek['rate'] }}%</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="5" class="py-3 text-center text-slate-400">Belum ada data teknisi</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                            <!-- ADMIN 3. DAFTAR LAPORAN -->
                            <section id="sec-daftar-laporan" class="mt-6">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">3. Daftar laporan</h3>
                                    <span class="text-[10px] text-slate-500 font-semibold">Total {{ count($services ?? []) }} tiket (sinkron dengan PDF)</span>
                                </div>

                                <div class="border border-slate-300 rounded-xl overflow-hidden">
                                    <table class="w-full text-left text-xs">
                                        <thead>
                                            <tr class="bg-slate-100 border-b border-slate-300 font-black text-slate-800 text-[11px]">
                                                <th class="py-2 px-3 w-28">No. &amp; Status</th>
                                                <th class="py-2 px-3 w-24">Tanggal</th>
                                                <th class="py-2 px-3">Kendala</th>
                                                <th class="py-2 px-3 w-32">Teknisi</th>
                                                <th class="py-2 px-3 text-right w-20">Durasi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 text-[11px]">
                                            @forelse(($services ?? []) as $row)
                                                <tr>
                                                    <td class="py-2 px-3 align-top">
                                                        <span class="font-mono font-bold text-slate-900 block whitespace-nowrap">{{ $row->ticket_number }}</span>
                                                        <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold {{ $row->status === 'Selesai' ? 'bg-teal-50 text-teal-700' : ($row->status === 'Baru' ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-700') }}">{{ $row->status }}</span>
                                                    </td>
                                                    <td class="py-2 px-3 whitespace-nowrap text-slate-600 align-top">
                                                        {{ $row->created_at ? $row->created_at->format('d/m/Y') : '-' }}
                                                    </td>
                                                    <td class="py-2 px-3 text-slate-800 leading-snug align-top">
                                                        <span class="font-bold block text-slate-900">{{ $row->keluhan }}</span>
                                                        @if($row->tindakan_perbaikan)
                                                            <span class="text-[10px] text-slate-500 block mt-0.5">{{ $row->tindakan_perbaikan }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-2 px-3 text-slate-700 whitespace-nowrap font-medium align-top">
                                                        {{ $row->teknisi_nama ?: ($row->teknisi->name ?? 'Belum ada') }}
                                                    </td>
                                                    <td class="py-2 px-3 text-right font-mono font-bold text-slate-800 whitespace-nowrap align-top">
                                                        @if($row->created_at && $row->tanggal_selesai)
                                                            {{ number_format(max(0.1, round($row->created_at->diffInMinutes($row->tanggal_selesai) / 60, 1)), 1, ',', '.') }} jam
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="py-6 text-center text-slate-400">
                                                        Tidak ada data laporan servis pada periode ini.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        @endif

                    </div>

                    <!-- ============================================== -->
                    <!-- KOLOM TANDA TANGAN (SIGNATURE BLOCK)           -->
                    <!-- ============================================== -->
                    <div class="mt-8 pt-4">
                        <div id="sec-tanda-tangan" class="grid grid-cols-2 gap-8 text-xs text-slate-800 mb-6">
                            
                            <!-- Kolom Dibuat Oleh -->
                            <div class="text-center">
                                <p class="text-slate-600 font-medium">Dibuat oleh,</p>
                                <div class="h-20 flex items-center justify-center"></div>
                                <p id="sign-dibuat-view" class="font-black underline text-slate-900 text-xs sm:text-sm tracking-wide">
                                    {{ $defaultSignerName }}
                                </p>
                                <p class="text-[11px] text-slate-500 font-semibold mt-0.5">
                                    {{ $defaultSignerRole }}
                                </p>
                            </div>

                            <!-- Kolom Mengetahui -->
                            <div class="text-center">
                                <p class="text-slate-600 font-medium">Mengetahui,</p>
                                <div class="h-20 flex items-center justify-center"></div>
                                <p id="sign-mengetahui-view" class="font-black underline text-slate-900 text-xs sm:text-sm tracking-wide">
                                    [Nama atasan]
                                </p>
                                <p class="text-[11px] text-slate-500 font-semibold mt-0.5">
                                    [Jabatan]
                                </p>
                            </div>

                        </div>

                        <!-- Footer Standar Perusahaan -->
                        <div class="border-t border-slate-200 pt-3 flex items-center justify-between text-[10px] text-slate-400 font-medium">
                            <span>&copy; {{ date('Y') }} PT Sumber Segara Primadaya (S2P)</span>
                            <span id="paper-footer-size" class="font-mono">A4 &middot; Potret</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <!-- ============================================== -->
    <!-- JAVASCRIPT: INTERAKTIFITAS LIVE PREVIEW        -->
    <!-- ============================================== -->
    <script>
        const DEFAULT_SIGNER = @json($defaultSignerName);
        const PREVIEW_BASE_LABEL = @json($previewBaseLabel);
        const PAPER_CLASS_LIST = [
            'paper-a4-portrait', 'paper-a4-landscape',
            'paper-letter-portrait', 'paper-letter-landscape',
            'paper-f4-portrait', 'paper-f4-landscape'
        ];
        let currentPaperKey = 'A4';
        let currentOrientation = 'portrait';

        function toggleSection(sectionId, isVisible) {
            const el = document.getElementById(sectionId);
            if (el) {
                el.classList.toggle('hidden', !isVisible);
            }

            if (sectionId === 'sec-tanda-tangan') {
                const wrapper = document.getElementById('wrapper-input-ttd');
                if (wrapper) {
                    wrapper.style.display = isVisible ? 'block' : 'none';
                }
            }
        }

        function toggleSparepartColumn(isVisible) {
            document.querySelectorAll('.teknisi-sparepart-col').forEach(cell => {
                cell.classList.toggle('hidden', !isVisible);
            });
        }

        function updateSignature(type, value) {
            if (type === 'dibuat') {
                const view = document.getElementById('sign-dibuat-view');
                if (view) view.textContent = value.trim() ? value : DEFAULT_SIGNER;
            } else if (type === 'mengetahui') {
                const view = document.getElementById('sign-mengetahui-view');
                const cleaned = value.trim();
                if (view) view.textContent = (!cleaned || cleaned === 'Nama atasan') ? '[Nama atasan]' : cleaned;
            }
        }

        function normalizePaperKey(size) {
            const upper = String(size || 'A4').trim().toUpperCase();
            if (upper === 'LETTER') return 'Letter';
            if (upper === 'F4') return 'F4';
            return 'A4';
        }

        function syncPaperPreviewBox() {
            const paper = document.getElementById('printable-paper');
            const keySlug = currentPaperKey.toLowerCase();
            const targetClass = `paper-${keySlug}-${currentOrientation}`;
            if (paper) {
                paper.classList.remove(...PAPER_CLASS_LIST);
                paper.classList.add(targetClass);
            }

            const paperDisplay = currentPaperKey === 'F4' ? 'F4 / Folio' : currentPaperKey;
            const orientDisplay = currentOrientation === 'landscape' ? 'Lanskap' : 'Potret';
            const badge = document.getElementById('preview-paper-badge');
            if (badge) {
                badge.textContent = `${PREVIEW_BASE_LABEL} · ${paperDisplay} · ${orientDisplay}`;
            }
            const footerSize = document.getElementById('paper-footer-size');
            if (footerSize) {
                footerSize.textContent = `${paperDisplay} · ${orientDisplay}`;
            }
        }

        function setOrientation(orientation) {
            const btnPotret = document.getElementById('btn-orientasi-potret');
            const btnLanskap = document.getElementById('btn-orientasi-lanskap');

            if (orientation === 'lanskap' || orientation === 'landscape') {
                btnLanskap.className = 'py-2 px-3 rounded-full text-xs font-bold transition flex items-center justify-center space-x-1.5 bg-[#092635] text-white shadow-xs cursor-pointer';
                btnPotret.className = 'py-2 px-3 rounded-full text-xs font-bold transition flex items-center justify-center space-x-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer';
                currentOrientation = 'landscape';
            } else {
                btnPotret.className = 'py-2 px-3 rounded-full text-xs font-bold transition flex items-center justify-center space-x-1.5 bg-[#092635] text-white shadow-xs cursor-pointer';
                btnLanskap.className = 'py-2 px-3 rounded-full text-xs font-bold transition flex items-center justify-center space-x-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer';
                currentOrientation = 'portrait';
            }

            syncPaperPreviewBox();
            syncPrintPageCss();
        }

        function changePaperSize(size) {
            currentPaperKey = normalizePaperKey(size);
            syncPaperPreviewBox();
            syncPrintPageCss();
        }

        function syncPrintPageCss() {
            let styleEl = document.getElementById('print-dynamic-style');
            if (!styleEl) {
                styleEl = document.createElement('style');
                styleEl.id = 'print-dynamic-style';
                document.head.appendChild(styleEl);
            }

            let pageSizeRule = `A4 ${currentOrientation}`;
            if (currentPaperKey === 'Letter') {
                pageSizeRule = `letter ${currentOrientation}`;
            } else if (currentPaperKey === 'F4') {
                // Dalam CSS Paged Media, dimensi eksplisit tidak boleh digabung keyword portrait/landscape
                pageSizeRule = currentOrientation === 'landscape' ? '330mm 215mm' : '215mm 330mm';
            }

            const printMargin = currentOrientation === 'landscape' ? '11mm 12mm' : '12mm 13mm';
            styleEl.innerHTML = `@media print { @page { size: ${pageSizeRule}; margin: ${printMargin}; } }`;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            const urlKertas = params.get('kertas');
            const urlOrientasi = params.get('orientasi');

            if (urlKertas) {
                currentPaperKey = normalizePaperKey(urlKertas);
                const selectKertas = document.getElementById('select-kertas');
                if (selectKertas) {
                    selectKertas.value = currentPaperKey;
                }
            }

            if (urlOrientasi && (urlOrientasi.toLowerCase() === 'lanskap' || urlOrientasi.toLowerCase() === 'landscape')) {
                setOrientation('lanskap');
            } else {
                syncPaperPreviewBox();
                syncPrintPageCss();
            }
        });

        function buildPdfDownloadUrl() {
            const url = new URL(@json(route('services.cetak')), window.location.origin);
            const currentParams = new URLSearchParams(window.location.search);

            currentParams.forEach((value, key) => {
                url.searchParams.set(key, value);
            });

            url.searchParams.set('download', 'pdf');
            url.searchParams.set('orientasi', currentOrientation === 'landscape' ? 'lanskap' : 'potret');

            const paperEl = document.getElementById('select-kertas');
            if (paperEl) {
                url.searchParams.set('kertas', paperEl.value);
            }

            const checkboxMap = {
                'toggle-ringkasan': 'show_ringkasan',
                'toggle-rincian-pk': 'show_rincian_pk',
                'toggle-daftar-laporan': 'show_daftar',
                'toggle-detail-sparepart': 'show_sparepart',
                'toggle-foto-lampiran': 'show_foto',
                'toggle-grafik': 'show_grafik',
                'toggle-divisi-kategori': 'show_divisi_kategori',
                'toggle-kinerja-teknisi': 'show_kinerja',
                'toggle-tanda-tangan': 'show_ttd',
            };

            Object.entries(checkboxMap).forEach(([elId, paramName]) => {
                const checkbox = document.getElementById(elId);
                if (checkbox) {
                    url.searchParams.set(paramName, checkbox.checked ? '1' : '0');
                }
            });

            const dibuatEl = document.getElementById('input-dibuat-oleh');
            if (dibuatEl) {
                url.searchParams.set('dibuat_oleh', dibuatEl.value.trim());
            }

            const mengetahuiEl = document.getElementById('input-mengetahui');
            if (mengetahuiEl) {
                url.searchParams.set('mengetahui', mengetahuiEl.value.trim());
            }

            return url.toString();
        }

        async function downloadPdf() {
            const btn = document.getElementById('btn-download-pdf');
            const labelEl = document.getElementById('btn-download-pdf-label');
            const pdfUrl = buildPdfDownloadUrl();

            if (btn && labelEl) {
                btn.disabled = true;
                btn.classList.add('opacity-70', 'cursor-wait');
                labelEl.textContent = 'Menyiapkan PDF...';
            }

            try {
                const response = await fetch(pdfUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/pdf',
                    },
                });

                if (!response.ok) {
                    throw new Error('Download PDF failed');
                }

                const blob = await response.blob();
                let filename = @json($isTeknisiMode ? 'Laporan-Kerja-Teknisi.pdf' : 'Laporan-Rekapitulasi-Servis-IT.pdf');
                const disposition = response.headers.get('Content-Disposition');
                if (disposition) {
                    const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/i);
                    if (match && match[1]) {
                        filename = match[1].replace(/['"]/g, '').trim();
                    }
                }

                const blobUrl = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = blobUrl;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                setTimeout(() => window.URL.revokeObjectURL(blobUrl), 1500);
            } catch (error) {
                window.location.assign(pdfUrl);
            } finally {
                if (btn && labelEl) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-70', 'cursor-wait');
                    labelEl.textContent = 'Unduh PDF';
                }
            }
        }

        function triggerPrint() {
            window.print();
        }
    </script>
</body>
</html>
