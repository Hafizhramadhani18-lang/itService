@php
    $backUrl = route('maintenance.show', $maintenanceLog);
    $backLabel = 'Kembali ke Detail Log';
    $previewSublabel = 'Berita Acara PM · A4 · Potret';
    $defaultSignerName = $printedBy ?? ($maintenanceLog->teknisi_nama ?: 'Teknisi IT');
    $defaultSignerRole = $printedRole ?? 'Teknisi IT Pelaksana';
    $checklistItems = is_array($maintenanceLog->checklist) ? $maintenanceLog->checklist : [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pratinjau Cetak &bull; Berita Acara PM {{ $maintenanceLog->log_number }} - PT S2P</title>
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
                            Format Berita Acara &amp; Lembar Hasil Pemeliharaan Aset IT.
                        </p>
                    </div>

                    <!-- Kotak Info Log -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3 text-xs space-y-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Identitas Log</span>
                        <div class="font-mono font-extrabold text-slate-900 leading-snug">
                            {{ $maintenanceLog->log_number }}
                        </div>
                        <div class="font-bold text-slate-800 text-[11px]">
                            {{ $maintenanceLog->nama_perangkat }}
                        </div>
                        <div class="text-[11px] text-slate-500 font-medium">
                            {{ $maintenanceLog->tanggal_cek ? $maintenanceLog->tanggal_cek->format('d/m/Y') : '-' }} &bull; {{ $maintenanceLog->lokasi_ruangan }}
                        </div>
                    </div>

                    <!-- 1. Orientasi (Default Potret untuk Dokumen Berita Acara Tunggal) -->
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
                            
                            <!-- Toggle Rincian Aset -->
                            <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                <input type="checkbox" id="toggle-info-aset" checked onchange="toggleSection('sec-info-aset', this.checked)"
                                       class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                <span>Informasi aset &amp; pelaksanaan</span>
                            </label>

                            <!-- Toggle Checklist Kegiatan -->
                            <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                <input type="checkbox" id="toggle-checklist" checked onchange="toggleSection('sec-checklist', this.checked)"
                                       class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                <span>Checklist pemeriksaan lapangan</span>
                            </label>

                            <!-- Toggle Hasil & Rekomendasi -->
                            <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                <input type="checkbox" id="toggle-hasil" checked onchange="toggleSection('sec-hasil', this.checked)"
                                       class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                <span>Hasil kondisi &amp; rekomendasi</span>
                            </label>

                            @if($maintenanceLog->foto_dokumentasi)
                            <!-- Toggle Foto Dokumentasi -->
                            <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                                <input type="checkbox" id="toggle-foto" checked onchange="toggleSection('sec-foto', this.checked)"
                                       class="w-4 h-4 accent-teal-700 rounded border-slate-300 focus:ring-teal-600 cursor-pointer">
                                <span>Foto dokumentasi fisik</span>
                            </label>
                            @endif

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
                                <label for="input-dibuat-oleh" class="block text-[11px] font-bold text-slate-600 mb-1">Dibuat / Dikerjakan oleh</label>
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
                     class="paper-a4-portrait bg-white shadow-md border border-slate-200/90 rounded-2xl p-6 sm:p-8 md:p-10 transition-all duration-200 relative">

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
                                        FORM-IT-PM-BA-REV.02
                                    </span>
                                    <span class="block text-[9px] text-slate-500 font-medium">
                                        Berita Acara PM Aset IT
                                    </span>
                                </div>
                                <p class="text-[10px] text-slate-500 mt-1">Dicetak: {{ $printedAt }}</p>
                            </div>
                        </div>

                        <!-- Judul Dokumen -->
                        <div class="mt-4 pt-3 border-t border-slate-200 text-center">
                            <h3 class="text-base font-black text-slate-900 uppercase tracking-wider">
                                BERITA ACARA PREVENTIVE MAINTENANCE
                            </h3>
                            <h4 class="text-xs font-extrabold text-teal-800 uppercase tracking-wide mt-0.5">
                                FORMULIR PELAKSANAAN &amp; PEMERIKSAAN FISIK ASET IT
                            </h4>
                            <p class="text-xs text-slate-600 font-medium mt-0.5">
                                No. Registrasi: <span class="font-mono font-bold text-slate-900">{{ $maintenanceLog->log_number }}</span>
                                &bull; Tanggal Pelaksanaan: <span class="font-bold text-slate-900">{{ $maintenanceLog->tanggal_cek ? $maintenanceLog->tanggal_cek->translatedFormat('d F Y') : '-' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- 2. INFORMASI ASET & PELAKSANAAN            -->
                    <!-- ========================================== -->
                    <div id="sec-info-aset" class="mb-5 space-y-2">
                        <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">1. Informasi Aset &amp; Pelaksanaan</h4>
                        <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50/40 text-xs">
                            <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-y divide-slate-200 border-b border-slate-200">
                                <div class="p-2.5">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase block">Nama Perangkat</span>
                                    <span class="font-extrabold text-slate-900">{{ $maintenanceLog->nama_perangkat }}</span>
                                </div>
                                <div class="p-2.5">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase block">Kode Perangkat</span>
                                    <span class="font-mono font-extrabold text-slate-900">{{ $maintenanceLog->kode_perangkat ?: '-' }}</span>
                                </div>
                                <div class="p-2.5">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase block">Jenis / Kategori</span>
                                    <span class="font-bold text-slate-800">{{ $maintenanceLog->jenis_perangkat ?: 'Perangkat IT' }}</span>
                                </div>
                                <div class="p-2.5">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase block">Status Log</span>
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold border {{ $maintenanceLog->status_badge_class }}">
                                        {{ $maintenanceLog->computed_status }}
                                    </span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-y divide-slate-200">
                                <div class="p-2.5">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase block">Lokasi Ruangan</span>
                                    <span class="font-semibold text-slate-800">{{ $maintenanceLog->lokasi_ruangan ?: '-' }}</span>
                                </div>
                                <div class="p-2.5">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase block">Divisi Pengguna</span>
                                    <span class="font-semibold text-slate-800">{{ $maintenanceLog->divisi ?: 'Operasional PLTU' }}</span>
                                </div>
                                <div class="p-2.5">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase block">Waktu / Jam</span>
                                    <span class="font-mono font-bold text-slate-800">{{ $maintenanceLog->formatted_jam ?: '09.00 WIB' }}</span>
                                </div>
                                <div class="p-2.5">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase block">Teknisi Pelaksana</span>
                                    <span class="font-extrabold text-slate-900">{{ $maintenanceLog->teknisi_lengkap }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- 3. CHECKLIST KEGIATAN PEMERIKSAAN LAPANGAN -->
                    <!-- ========================================== -->
                    <div id="sec-checklist" class="mb-5 space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">2. Lembar Checklist Kegiatan Pemeriksaan</h4>
                            <span class="text-[11px] text-slate-500 font-medium">{{ count($checklistItems) }} Poin Pemeriksaan</span>
                        </div>

                        <div class="border border-slate-200 rounded-xl overflow-hidden bg-white text-xs">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-700 font-bold text-[11px] border-b border-slate-200">
                                        <th class="py-2 px-3 text-center w-10">No</th>
                                        <th class="py-2 px-3">Item Pemeriksaan &amp; Pengujian Fisik</th>
                                        <th class="py-2 px-3 text-center w-28">Status Verifikasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($checklistItems as $cIdx => $cItem)
                                        @php
                                            $itemLabel = is_array($cItem) ? ($cItem['item'] ?? '') : (string) $cItem;
                                            $isChecked = is_array($cItem) ? (!empty($cItem['checked']) || $maintenanceLog->status === 'Selesai') : ($maintenanceLog->status === 'Selesai');
                                        @endphp
                                        <tr>
                                            <td class="py-2.5 px-3 text-center font-bold text-slate-500">{{ $cIdx + 1 }}</td>
                                            <td class="py-2.5 px-3 text-slate-800 font-medium">
                                                {{ $itemLabel }}
                                            </td>
                                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                                @if($isChecked)
                                                    <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                        <i class="fa-solid fa-check text-[9px] text-emerald-600"></i>
                                                        <span>Dikerjakan</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                                        <span>Tertunda</span>
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-3 px-3 text-slate-700 font-medium leading-relaxed">
                                                {{ $maintenanceLog->kegiatan ?: 'Pemeriksaan rutin pemeliharaan perangkat, kalibrasi sistem, dan pembersihan komponen fisik.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- 4. HASIL KONDISI & CATATAN REKOMENDASI     -->
                    <!-- ========================================== -->
                    <div id="sec-hasil" class="mb-5 space-y-2">
                        <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">3. Hasil Pemeriksaan &amp; Catatan Teknis</h4>
                        <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/40 text-xs space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-slate-200">
                                <div class="flex items-center space-x-2">
                                    <span class="text-slate-500 font-bold uppercase text-[10px]">Kondisi Setelah Pemeliharaan:</span>
                                    @if($maintenanceLog->isPrima())
                                        <span class="inline-block px-3 py-0.5 rounded-md text-xs font-black bg-emerald-100 text-emerald-900 border border-emerald-300">
                                            PRIMA / BAIK
                                        </span>
                                    @elseif($maintenanceLog->isPerluPerhatian())
                                        <span class="inline-block px-3 py-0.5 rounded-md text-xs font-black bg-amber-100 text-amber-900 border border-amber-300">
                                            PERLU PEMANTAUAN
                                        </span>
                                    @elseif($maintenanceLog->isKritis())
                                        <span class="inline-block px-3 py-0.5 rounded-md text-xs font-black bg-rose-100 text-rose-900 border border-rose-300">
                                            KRITIS / PERLU PERBAIKAN
                                        </span>
                                    @else
                                        <span class="inline-block px-3 py-0.5 rounded-md text-xs font-black bg-slate-100 text-slate-800 border border-slate-300">
                                            {{ strtoupper($maintenanceLog->kondisi ?? 'NORMAL') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-500 font-medium">
                                    Status Tindak Lanjut: <strong class="text-slate-800">{{ $maintenanceLog->status_tindak_lanjut ?: 'Selesai' }}</strong>
                                </div>
                            </div>

                            <div>
                                <span class="text-[10px] text-slate-500 font-bold uppercase block mb-1">Tindakan Teknis yang Dilakukan:</span>
                                <p class="text-slate-800 font-medium leading-relaxed bg-white p-2.5 rounded-lg border border-slate-200">
                                    {{ $maintenanceLog->tindakan ?: ($maintenanceLog->kegiatan ?: 'Pemeliharaan rutin dilaksanakan sesuai standar checklist pemeriksaan.') }}
                                </p>
                            </div>

                            @if($maintenanceLog->catatan_rekomendasi)
                            <div>
                                <span class="text-[10px] text-slate-500 font-bold uppercase block mb-1">Catatan Rekomendasi / Tindak Lanjut:</span>
                                <p class="text-slate-800 font-medium leading-relaxed bg-white p-2.5 rounded-lg border border-slate-200 text-amber-800">
                                    <i class="fa-solid fa-circle-exclamation mr-1.5 text-amber-600"></i>
                                    {{ $maintenanceLog->catatan_rekomendasi }}
                                </p>
                            </div>
                            @endif

                            @if($maintenanceLog->service)
                            <div class="text-[11px] text-sky-800 bg-sky-50/80 p-2 rounded-lg border border-sky-200 flex items-center space-x-2">
                                <i class="fa-solid fa-ticket"></i>
                                <span>Terhubung dengan Tiket Servis Resmi: <strong>{{ $maintenanceLog->service->ticket_number }}</strong> ({{ $maintenanceLog->service->display_judul }})</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- 5. FOTO DOKUMENTASI FISIK (JIKA ADA)       -->
                    <!-- ========================================== -->
                    @if($maintenanceLog->foto_dokumentasi)
                    <div id="sec-foto" class="mb-5 space-y-2">
                        <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">4. Dokumentasi Visual Pemeriksaan</h4>
                        <div class="border border-slate-200 rounded-xl p-3 bg-white max-w-sm">
                            <img src="{{ asset('storage/' . $maintenanceLog->foto_dokumentasi) }}" 
                                 alt="Dokumentasi Pemeliharaan" 
                                 class="w-full h-48 object-cover rounded-lg border border-slate-100">
                            <p class="text-[10px] text-slate-500 text-center mt-1.5 font-medium">
                                Foto kondisi unit setelah pemeliharaan &bull; {{ $maintenanceLog->log_number }}
                            </p>
                        </div>
                    </div>
                    @endif

                    <!-- ========================================== -->
                    <!-- 6. LEMBAR PENGESAHAN / TANDA TANGAN FORMAL -->
                    <!-- ========================================== -->
                    <div id="sec-tanda-tangan" class="page-break-avoid grid grid-cols-2 gap-8 text-xs pt-6 border-t border-slate-200 mt-6">
                        <div class="text-center p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                            <p class="text-slate-500 text-[11px] mb-1">Dikerjakan &amp; Diverifikasi Oleh:</p>
                            <p class="font-bold text-slate-800">Teknisi Pelaksana</p>
                            <div class="h-16"></div>
                            <p id="signer-pelaksana-name" class="font-black text-slate-900 underline uppercase tracking-wide">
                                {{ $defaultSignerName }}
                            </p>
                            <p class="text-[10px] text-slate-500 mt-0.5">{{ $defaultSignerRole }} &bull; Tgl: {{ $printedDateOnly }}</p>
                        </div>

                        <div class="text-center p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                            <p class="text-slate-500 text-[11px] mb-1">Mengetahui &amp; Menyetujui:</p>
                            <p class="font-bold text-slate-800">{{ $coordinatorTitle }}</p>
                            <div class="h-16"></div>
                            <p id="signer-atasan-name" class="font-black text-slate-900 underline uppercase tracking-wide">
                                {{ $coordinatorName }}
                            </p>
                            <p class="text-[10px] text-slate-500 mt-0.5">Koordinator IT Support PLTU Karangkandri &bull; Tgl: {{ $printedDateOnly }}</p>
                        </div>
                    </div>

                    <!-- Footer Identitas Dokumen Cetak -->
                    <div class="mt-6 pt-3 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                        <span>FORM-IT-PM-BA-REV.02 &bull; PT Sumber Segara Primadaya (PLTU Karangkandri Cilacap)</span>
                        <span>Dokumen Berita Acara Sah &bull; IT Service Desk &amp; PM</span>
                    </div>

                </div>
            </section>
        </div>
    </main>

    <!-- ============================================== -->
    <!-- JAVASCRIPT: INTERACTIVE CONTROLS               -->
    <!-- ============================================== -->
    <script>
        let currentOrientation = 'portrait';
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
                badge.textContent = `Berita Acara PM · ${currentPaperKey} · ${orientLabel}`;
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
            const url = new URL(@json(route('maintenance.cetakSingle', $maintenanceLog)), window.location.origin);
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
                let filename = 'Berita-Acara-PM-{{ $maintenanceLog->log_number }}.pdf';
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
            setOrientation('potret');
        });
    </script>
</body>
</html>
