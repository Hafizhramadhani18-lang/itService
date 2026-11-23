@php
    $backUrl = route('maintenance.index', request()->query());
    $backLabel = 'Kembali ke Log Pemeliharaan';
    $previewSublabel = 'Log Pemeliharaan · A4 · Lanskap';
    $defaultSignerName = $printedBy ?? (Auth::user()->name ?? 'Teknisi IT');
    $defaultSignerRole = $printedRole ?? ((Auth::user() && Auth::user()->isTeknisi()) ? ('Teknisi IT · ' . (Auth::user()->label_tim ?? 'Tim IT Support')) : 'Koordinator IT Support & Infrastructure');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pratinjau Cetak &bull; Log Preventive Maintenance - PT S2P PLTU Cilacap</title>
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
            tr {
                page-break-inside: avoid !important;
            }
            .page-break-avoid {
                page-break-inside: avoid !important;
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
                    <span id="btn-download-pdf-label">Unduh File PDF</span>
                </button>
                <button type="button" onclick="triggerPrint()" title="Cetak Langsung (Ctrl+P)"
                        class="px-4 py-2 rounded-xl bg-white hover:bg-teal-50 text-teal-800 border border-teal-600/60 text-xs font-bold transition shadow-2xs flex items-center space-x-2 cursor-pointer">
                    <i class="fa-solid fa-print text-sm"></i>
                    <span>Cetak Langsung (Ctrl+P)</span>
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
                            Isi laporan mengikuti filter di halaman Log Pemeliharaan.
                        </p>
                    </div>

                    <!-- Kotak Filter Aktif -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3 text-xs space-y-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Filter aktif</span>
                        <div class="font-extrabold text-slate-800 leading-snug">
                            {{ $periodeLabel ?? 'Semua Periode' }}
                        </div>
                        <div class="text-[11px] text-slate-500 font-medium">
                            {{ $filterDivisiLabel ?? 'Semua Divisi' }} &bull; {{ $filterKondisiLabel ?? 'Semua Kondisi' }} &bull; {{ $filterTeknisiLabel ?? 'Seluruh Tim IT' }}
                        </div>
                    </div>

                    <!-- 1. Orientasi (Default Lanskap untuk Tabel PM yang Lebar) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Orientasi</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" id="btn-orientasi-potret" onclick="setOrientation('potret')"
                                    class="py-2 px-3 rounded-full text-xs font-bold transition flex items-center justify-center space-x-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer">
                                <span>Potret</span>
                            </button>
                            <button type="button" id="btn-orientasi-lanskap" onclick="setOrientation('lanskap')"
                                    class="py-2 px-3 rounded-full text-xs font-bold transition flex items-center justify-center space-x-1.5 bg-[#092635] text-white shadow-xs cursor-pointer">
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
                                <span>Ringkasan angka &amp; kelaikan</span>
                            </label>

                            <!-- Toggle Rincian Kondisi & Kategori -->
                            <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                <input type="checkbox" id="toggle-rincian-pk" checked onchange="toggleSection('sec-rincian-pk', this.checked)"
                                       class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                <span>Rincian kondisi &amp; kategori aset</span>
                            </label>

                            <!-- Toggle Daftar Log Pemeliharaan -->
                            <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                <input type="checkbox" id="toggle-daftar-laporan" checked onchange="toggleSection('sec-daftar-laporan', this.checked)"
                                       class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                <span>Daftar log pemeliharaan</span>
                            </label>

                            <!-- Toggle Catatan Rekomendasi -->
                            <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                <input type="checkbox" id="toggle-rekomendasi" checked onchange="toggleRekomendasiColumn(this.checked)"
                                       class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                <span>Kolom catatan &amp; rekomendasi</span>
                            </label>

                        </div>
                    </div>

                    <!-- 4. Pengesahan & Tanda Tangan -->
                    <div class="pt-2 border-t border-slate-100 space-y-3">
                        <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                            <input type="checkbox" id="toggle-tanda-tangan" checked onchange="toggleSection('sec-tanda-tangan', this.checked)"
                                   class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                            <span class="text-xs font-bold text-slate-800">Tampilkan kolom tanda tangan</span>
                        </label>

                        <div id="wrapper-input-signer" class="space-y-3 pt-1">
                            <div>
                                <label for="input-dibuat-oleh" class="block text-[11px] font-bold text-slate-600 mb-1">Dibuat oleh</label>
                                <input type="text" id="input-dibuat-oleh" value="{{ $defaultSignerName }}" 
                                       oninput="updateSigner('pelaksana', this.value)"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                            </div>

                            <div>
                                <label for="input-mengetahui" class="block text-[11px] font-bold text-slate-600 mb-1">Mengetahui</label>
                                <input type="text" id="input-mengetahui" value="{{ $coordinatorName ?? 'Hendra Wijaya, S.T.' }}" 
                                       oninput="updateSigner('atasan', this.value)"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                            </div>
                        </div>
                    </div>

                </div>
            </aside>

            <!-- ============================================== -->
            <!-- KOLOM KANAN: LEMBAR KERTAS PRATINJAU (Paper)   -->
            <!-- ============================================== -->
            <section id="paper-container" class="flex-1 w-full flex justify-center overflow-x-auto pb-12">
                <div id="printable-paper" 
                     class="paper-a4-landscape bg-white shadow-md border border-slate-200/90 rounded-2xl p-6 sm:p-8 md:p-10 transition-all duration-200 relative">

                    <!-- ========================================== -->
                    <!-- 1. KOP SURAT RESMI PT S2P PLTU CILACAP     -->
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
                                        FORM-IT-PM-REV.02
                                    </span>
                                    <span class="block text-[9px] text-slate-500 font-medium">
                                        Dokumen Operasional IT
                                    </span>
                                </div>
                                <p class="text-[10px] text-slate-500 mt-1">Dicetak: {{ $printedAt ?? now()->translatedFormat('d F Y H:i') . ' WIB' }}</p>
                            </div>
                        </div>

                        <!-- Judul Laporan -->
                        <div class="mt-4 pt-3 border-t border-slate-200 text-center">
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">
                                LAPORAN PELAKSANAAN PREVENTIVE MAINTENANCE
                            </h3>
                            <h4 class="text-xs font-extrabold text-teal-800 uppercase tracking-wide mt-0.5">
                                ASET &amp; SISTEM IT PLTU KARANGKANDRI
                            </h4>
                            <p class="text-xs text-slate-600 font-medium mt-0.5">
                                Periode: <span class="font-bold text-slate-900">{{ $periodeLabel ?? ($selectedPeriod === 'all' ? 'Seluruh Periode' : $selectedPeriod) }}</span>
                                &bull; Klasifikasi: Pemeliharaan Rutin, Inspeksi Fisik &amp; Keandalan Perangkat
                            </p>
                        </div>
                    </div>

                    <!-- Filter Parameters Strip -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] bg-slate-50 border border-slate-200 rounded-xl p-2.5 mb-5 text-slate-600">
                        <div><span class="font-bold text-slate-700">Periode:</span> {{ $periodeLabel ?? ($selectedPeriod === 'all' ? 'Seluruh Periode' : $selectedPeriod) }}</div>
                        <div><span class="font-bold text-slate-700">Divisi / Unit:</span> {{ $filterDivisiLabel ?? 'Semua Divisi' }}</div>
                        <div><span class="font-bold text-slate-700">Kondisi:</span> {{ $filterKondisiLabel ?? 'Semua Kondisi' }}</div>
                        <div><span class="font-bold text-slate-700">Status Log:</span> {{ $filterStatusLabel ?? 'Semua Status' }}</div>
                    </div>

                    <!-- ========================================== -->
                    <!-- 2. RINGKASAN EKSEKUTIF & KELAIKAN ASET     -->
                    <!-- ========================================== -->
                    <div id="sec-ringkasan" class="mb-6 space-y-2">
                        <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">1. Ringkasan Kelaikan Aset IT</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center text-xs">
                            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50/80">
                                <span class="text-[10px] text-slate-500 font-bold uppercase block tracking-wider">Total Diperiksa</span>
                                <span class="text-lg font-black text-slate-900">{{ $totalAset }} Unit</span>
                            </div>
                            <div class="p-3 rounded-xl border border-emerald-200 bg-emerald-50/80">
                                <span class="text-[10px] text-emerald-700 font-bold uppercase block tracking-wider">Kondisi Prima</span>
                                <span class="text-lg font-black text-emerald-800">{{ $totalPrima }} Unit</span>
                            </div>
                            <div class="p-3 rounded-xl border border-amber-200 bg-amber-50/80">
                                <span class="text-[10px] text-amber-700 font-bold uppercase block tracking-wider">Perlu Perhatian</span>
                                <span class="text-lg font-black text-amber-800">{{ $totalPerhatian }} Unit</span>
                            </div>
                            <div class="p-3 rounded-xl border border-rose-200 bg-rose-50/80">
                                <span class="text-[10px] text-rose-700 font-bold uppercase block tracking-wider">Kritis / Rusak</span>
                                <span class="text-lg font-black text-rose-800">{{ $totalKritis }} Unit</span>
                            </div>
                            <div class="p-3 rounded-xl border border-teal-200 bg-teal-50/80 col-span-2 sm:col-span-1">
                                <span class="text-[10px] text-teal-700 font-bold uppercase block tracking-wider">Tingkat Kelaikan</span>
                                <span class="text-lg font-black text-teal-800">{{ $persenKelaikan ?? ($totalAset > 0 ? round(($totalPrima / $totalAset) * 100, 1) : 100) }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- 3. RINCIAN PER KONDISI & KATEGORI ASET    -->
                    <!-- ========================================== -->
                    <div id="sec-rincian-pk" class="mb-6 space-y-2">
                        <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">2. Rincian per Kondisi dan Kategori</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            
                            <!-- Tabel Kondisi -->
                            <div class="border border-slate-200 rounded-xl overflow-hidden bg-white">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-100/90 text-slate-700 font-bold text-[11px] border-b border-slate-200">
                                            <th class="py-2 px-3">Kondisi Aset</th>
                                            <th class="py-2 px-3 text-right">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($kondisiDistribution as $kName => $kVal)
                                            <tr>
                                                <td class="py-2 px-3 text-slate-800 font-medium">{{ $kName }}</td>
                                                <td class="py-2 px-3 text-right font-bold text-slate-900">{{ $kVal }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Tabel Kategori Aset -->
                            <div class="border border-slate-200 rounded-xl overflow-hidden bg-white">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-100/90 text-slate-700 font-bold text-[11px] border-b border-slate-200">
                                            <th class="py-2 px-3">Kategori Perangkat</th>
                                            <th class="py-2 px-3 text-right">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse($kategoriDistribution as $catName => $catVal)
                                            <tr>
                                                <td class="py-2 px-3 text-slate-800 font-medium">{{ $catName }}</td>
                                                <td class="py-2 px-3 text-right font-bold text-slate-900">{{ $catVal }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="py-2 px-3 text-center text-slate-400 italic">Tidak ada data kategori</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- 4. TABEL DAFTAR LOG PEMELIHARAAN           -->
                    <!-- ========================================== -->
                    <div id="sec-daftar-laporan" class="mb-6 space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">3. Daftar Log Pemeriksaan Pemeliharaan</h4>
                            <span class="text-[11px] text-slate-500 font-medium">{{ $logs->count() }} Data</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse border border-slate-300 text-[11px]">
                                <thead>
                                    <tr class="bg-slate-900 text-white font-extrabold uppercase text-[10px] tracking-wide">
                                        <th class="border border-slate-700 p-2 text-center w-8">No</th>
                                        <th class="border border-slate-700 p-2 w-28">No. Log &amp; Tgl</th>
                                        <th class="border border-slate-700 p-2 w-48">Kode &amp; Nama Perangkat</th>
                                        <th class="border border-slate-700 p-2 w-32">Divisi &amp; Lokasi</th>
                                        <th class="border border-slate-700 p-2 text-center w-24">Kondisi</th>
                                        <th class="border border-slate-700 p-2">Tindakan Pemeliharaan</th>
                                        <th class="col-rekomendasi border border-slate-700 p-2 w-36">Rekomendasi</th>
                                        <th class="border border-slate-700 p-2 w-28">Teknisi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @forelse($logs as $idx => $log)
                                        <tr class="{{ $idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }}">
                                            <td class="border border-slate-300 p-2 text-center font-bold text-slate-700">{{ $idx + 1 }}</td>
                                            <td class="border border-slate-300 p-2 whitespace-nowrap">
                                                <span class="font-mono font-bold text-slate-900">{{ $log->log_number }}</span><br>
                                                <span class="text-slate-500 text-[10px]">{{ $log->tanggal_cek ? $log->tanggal_cek->format('d/m/Y') : '-' }}</span>
                                            </td>
                                            <td class="border border-slate-300 p-2">
                                                <span class="font-bold text-slate-900">{{ $log->nama_perangkat }}</span><br>
                                                <span class="font-mono text-[10px] text-slate-600">{{ $log->kode_perangkat }} &bull; {{ $log->jenis_perangkat }}</span>
                                            </td>
                                            <td class="border border-slate-300 p-2">
                                                <span class="font-semibold text-slate-800">{{ $log->divisi }}</span><br>
                                                <span class="text-slate-500 text-[10px]">{{ $log->lokasi_ruangan ?: '-' }}</span>
                                            </td>
                                            <td class="border border-slate-300 p-2 text-center whitespace-nowrap">
                                                @if($log->isPrima())
                                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                        PRIMA
                                                    </span>
                                                @elseif($log->isPerluPerhatian())
                                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                        PERHATIAN
                                                    </span>
                                                @elseif($log->isKritis())
                                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                                        KRITIS
                                                    </span>
                                                @else
                                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-300">
                                                        {{ strtoupper($log->kondisi ?? 'NORMAL') }}
                                                    </span>
                                                @endif
                                                <div class="text-[9px] text-slate-500 mt-0.5">
                                                    {{ $log->status === 'Selesai' ? 'Selesai' : ($log->status ?? 'Terjadwal') }}
                                                </div>
                                            </td>
                                            <td class="border border-slate-300 p-2 leading-relaxed text-slate-800">
                                                <div>{{ $log->tindakan ?: ($log->kegiatan ?: 'Pemeriksaan preventif rutin') }}</div>
                                                @if($log->service)
                                                    <div class="text-[10px] font-mono text-sky-700 mt-0.5">
                                                        <i class="fa-solid fa-link text-[9px] mr-1"></i>Tiket: {{ $log->service->ticket_number }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="col-rekomendasi border border-slate-300 p-2 leading-tight text-slate-600 text-[10px]">
                                                <div>{{ $log->catatan_rekomendasi ?: '-' }}</div>
                                                @if($log->status_tindak_lanjut && $log->status_tindak_lanjut !== 'Selesai')
                                                    <div class="text-[9px] font-bold text-amber-700 mt-0.5">
                                                        {{ $log->status_tindak_lanjut }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="border border-slate-300 p-2 whitespace-nowrap">
                                                <span class="font-semibold text-slate-800">{{ $log->teknisi_lengkap }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="p-8 text-center text-slate-400 italic">
                                                Tidak ada data pemeliharaan untuk parameter filter yang dipilih.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- 5. LEMBAR PENGESAHAN / TANDA TANGAN FORMAL -->
                    <!-- ========================================== -->
                    <div id="sec-tanda-tangan" class="page-break-avoid grid grid-cols-2 gap-8 text-xs pt-6 border-t border-slate-200">
                        <div class="text-center p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                            <p class="text-slate-500 text-[11px] mb-1">Disiapkan Oleh:</p>
                            <p class="font-bold text-slate-800">Teknisi IT Support &amp; Pemeliharaan</p>
                            <div class="h-16"></div>
                            <p id="signer-pelaksana-name" class="font-black text-slate-900 underline uppercase tracking-wide">
                                {{ $defaultSignerName }}
                            </p>
                            <p class="text-[10px] text-slate-500 mt-0.5">{{ $defaultSignerRole }} &bull; Tgl: {{ $printedDateOnly ?? now()->translatedFormat('d F Y') }}</p>
                        </div>

                        <div class="text-center p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                            <p class="text-slate-500 text-[11px] mb-1">Mengetahui &amp; Menyetujui:</p>
                            <p class="font-bold text-slate-800">{{ $coordinatorTitle ?? 'Koordinator IT Support & Infrastructure' }}</p>
                            <div class="h-16"></div>
                            <p id="signer-atasan-name" class="font-black text-slate-900 underline uppercase tracking-wide">
                                {{ $coordinatorName ?? 'Hendra Wijaya, S.T.' }}
                            </p>
                            <p class="text-[10px] text-slate-500 mt-0.5">Koordinator IT Support PLTU Karangkandri &bull; Tgl: {{ $printedDateOnly ?? now()->translatedFormat('d F Y') }}</p>
                        </div>
                    </div>

                    <!-- Footer Identitas Dokumen Cetak -->
                    <div class="mt-6 pt-3 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                        <span>FORM-IT-PM-REV.02 &bull; PT Sumber Segara Primadaya (PLTU Karangkandri Cilacap)</span>
                        <span>Dokumen ini diterbitkan secara sah melalui Sistem IT Service Desk &amp; PM</span>
                    </div>

                </div>
            </section>
        </div>
    </main>

    <!-- ============================================== -->
    <!-- JAVASCRIPT: INTERACTIVE CONTROLS               -->
    <!-- ============================================== -->
    <script>
        let currentOrientation = 'landscape';
        let currentPaperKey = 'A4';

        function normalizePaperKey(val) {
            if (!val) return 'A4';
            const upper = val.toUpperCase();
            if (upper.includes('LETTER')) return 'Letter';
            if (upper.includes('F4') || upper.includes('FOLIO')) return 'F4';
            return 'A4';
        }

        function setOrientation(mode) {
            currentOrientation = mode === 'potret' ? 'portrait' : 'landscape';
            
            const btnPotret = document.getElementById('btn-orientasi-potret');
            const btnLanskap = document.getElementById('btn-orientasi-lanskap');

            if (currentOrientation === 'portrait') {
                btnPotret.className = 'py-2 px-3 rounded-full text-xs font-bold transition flex items-center justify-center space-x-1.5 bg-[#092635] text-white shadow-xs cursor-pointer';
                btnLanskap.className = 'py-2 px-3 rounded-full text-xs font-bold transition flex items-center justify-center space-x-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer';
            } else {
                btnLanskap.className = 'py-2 px-3 rounded-full text-xs font-bold transition flex items-center justify-center space-x-1.5 bg-[#092635] text-white shadow-xs cursor-pointer';
                btnPotret.className = 'py-2 px-3 rounded-full text-xs font-bold transition flex items-center justify-center space-x-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 cursor-pointer';
            }

            syncPaperPreviewBox();
            syncPrintPageCss();
        }

        function syncPaperPreviewBox() {
            const paper = document.getElementById('printable-paper');
            if (!paper) return;

            const baseClass = 'bg-white shadow-md border border-slate-200/90 rounded-2xl p-6 sm:p-8 md:p-10 transition-all duration-200 relative';
            const suffix = currentOrientation === 'portrait' ? 'portrait' : 'landscape';
            const paperClass = `paper-${currentPaperKey.toLowerCase()}-${suffix}`;

            paper.className = `${baseClass} ${paperClass}`;

            const badge = document.getElementById('preview-paper-badge');
            if (badge) {
                const orientLabel = currentOrientation === 'portrait' ? 'Potret' : 'Lanskap';
                badge.textContent = `Log Pemeliharaan · ${currentPaperKey} · ${orientLabel}`;
            }
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
                pageSizeRule = currentOrientation === 'landscape' ? '330mm 215mm' : '215mm 330mm';
            }

            const printMargin = currentOrientation === 'landscape' ? '10mm 12mm' : '12mm 12mm';
            styleEl.innerHTML = `@media print { @page { size: ${pageSizeRule}; margin: ${printMargin}; } }`;
        }

        function toggleSection(sectionId, isChecked) {
            const section = document.getElementById(sectionId);
            if (section) {
                if (isChecked) {
                    section.classList.remove('hidden');
                } else {
                    section.classList.add('hidden');
                }
            }

            if (sectionId === 'sec-tanda-tangan') {
                const signerInputs = document.getElementById('wrapper-input-signer');
                if (signerInputs) {
                    signerInputs.style.display = isChecked ? 'block' : 'none';
                }
            }
        }

        function toggleRekomendasiColumn(isChecked) {
            const cols = document.querySelectorAll('.col-rekomendasi');
            cols.forEach(el => {
                el.style.display = isChecked ? '' : 'none';
            });
        }

        function updateSigner(role, value) {
            const text = value.trim();
            if (role === 'pelaksana') {
                const el = document.getElementById('signer-pelaksana-name');
                if (el) el.textContent = text !== '' ? text : 'Teknisi IT';
            } else if (role === 'atasan') {
                const el = document.getElementById('signer-atasan-name');
                if (el) el.textContent = text !== '' ? text : 'Koordinator IT';
            }
        }

        function buildPdfDownloadUrl() {
            const url = new URL(@json(route('maintenance.cetak')), window.location.origin);
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
                let filename = 'Laporan-PM-Aset-IT-PT-S2P.pdf';
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
                    labelEl.textContent = 'Unduh File PDF';
                }
            }
        }

        function triggerPrint() {
            window.print();
        }

        document.addEventListener('DOMContentLoaded', function() {
            setOrientation('lanskap');
        });
    </script>
</body>
</html>
