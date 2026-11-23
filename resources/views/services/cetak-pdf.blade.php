@php
    $isTeknisiMode = !empty($isTeknisiReport);
    $isLandscape = !empty($isLandscapePdf);
    $opts = $pdfOptions ?? [];
    $showRingkasan = $opts['show_ringkasan'] ?? true;
    $showRincianPk = $opts['show_rincian_pk'] ?? true;
    $showDaftar = $opts['show_daftar'] ?? true;
    $showSparepart = $opts['show_sparepart'] ?? false;
    $showFoto = $opts['show_foto'] ?? false;
    $showGrafik = $opts['show_grafik'] ?? false;
    $showDivisiKategori = $opts['show_divisi_kategori'] ?? true;
    $showKinerja = $opts['show_kinerja'] ?? false;
    $showTtd = $opts['show_ttd'] ?? true;
    $dibuatOleh = $opts['dibuat_oleh'] ?? (Auth::user()->name ?? ($isTeknisiMode ? 'Teknisi IT' : 'Hendra Wijaya, S.T.'));
    $mengetahuiDisplay = $opts['mengetahui_display'] ?? '[Nama atasan]';
    $defaultSignerRole = $isTeknisiMode ? ('Teknisi · ' . (Auth::user()->label_tim ?? 'Tim IT Support')) : 'Koordinator IT';
    $logoBoltSvg = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#22d3ee"><path d="M13 2L4.5 13.5h6l-1.5 8.5 8.5-11.5h-6L13 2z"/></svg>');
    $paperBadgeText = $paperShortLabel ?? ($isLandscape ? 'A4 · Lanskap' : 'A4 · Potret');
    $trendItems = collect($dailyTrend ?? []);
    if (!$isTeknisiMode && $trendItems->isEmpty()) {
        $periodTicketsCol = collect($allPeriodTickets ?? []);
        $trendItems = collect(range(6, 0))->map(function ($i) use ($periodTicketsCol) {
            $tgl = \Carbon\Carbon::now()->subDays($i);
            $masuk = $periodTicketsCol->filter(fn($t) => $t->created_at && $t->created_at->isSameDay($tgl))->count();
            $selesai = $periodTicketsCol->filter(fn($t) => $t->status === 'Selesai' && ($t->tanggal_selesai ?? $t->created_at)?->isSameDay($tgl))->count();
            return [
                'day_short' => $tgl->locale('id')->isoFormat('dd'),
                'date_short' => $tgl->format('d/m'),
                'date_full' => $tgl->locale('id')->isoFormat('dd, D MMM Y'),
                'masuk' => $masuk,
                'selesai' => $selesai,
                'is_today' => $i === 0,
            ];
        });
    }
    $trendMax = max(1, (int) ($maxDailyTrend ?? $trendItems->max(fn($d) => max($d['masuk'], $d['selesai']))));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $isTeknisiMode ? 'Laporan Kerja Teknisi' : 'Laporan Rekapitulasi Servis IT' }} - PT S2P PLTU Cilacap</title>
    <style>
        @page {
            margin: {{ $isLandscape ? '11mm 12mm 15mm 12mm' : '12mm 13mm 17mm 13mm' }};
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: {{ $isLandscape ? '8.5px' : '8.8px' }};
            color: #0f172a;
            line-height: 1.38;
            margin: 0;
            padding: 0;
        }
        .keep-together {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .kop-wrap {
            border-bottom: 2px solid #092635;
            padding-bottom: 7px;
            margin-bottom: 9px;
        }
        .kop-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kop-badge {
            width: 32px;
            height: 32px;
            background-color: #092635;
            border-radius: 6px;
            text-align: center;
            vertical-align: middle;
        }
        .doc-meta-pill {
            display: inline-block;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 7.5px;
            font-weight: bold;
            color: #334155;
        }
        .title-block {
            text-align: center;
            margin: {{ $isLandscape ? '4px 0 9px 0' : '6px 0 11px 0' }};
            padding: 6px 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
        }
        .title-main {
            font-size: {{ $isLandscape ? '11.5px' : '12px' }};
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #092635;
            margin: 0;
        }
        .title-sub {
            font-size: 8.5px;
            font-weight: bold;
            color: #334155;
            margin: 2px 0 0 0;
        }
        .title-meta {
            font-size: 7.5px;
            color: #64748b;
            margin: 2px 0 0 0;
        }
        .section {
            margin-top: {{ $isLandscape ? '9px' : '11px' }};
        }
        .section-title {
            font-size: 9px;
            font-weight: bold;
            color: #092635;
            margin: 0 0 5px 0;
            padding-left: 6px;
            border-left: 3px solid #0f766e;
            page-break-after: avoid;
            break-after: avoid;
        }
        .section-title-admin {
            text-transform: uppercase;
            letter-spacing: 0.45px;
        }
        .section-caption {
            font-size: 7.5px;
            font-weight: normal;
            color: #64748b;
            text-transform: none;
            letter-spacing: 0;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .stat-card {
            border: 1px solid #cbd5e1;
            border-top: 2.5px solid #092635;
            padding: {{ $isLandscape ? '5px 8px' : '6px 8px' }};
            background-color: #ffffff;
            vertical-align: top;
        }
        .stat-card-teal {
            border-top-color: #0f766e;
        }
        .stat-card-amber {
            border-top-color: #d97706;
        }
        .stat-card-sky {
            border-top-color: #0284c7;
        }
        .stat-label {
            font-size: 7.3px;
            font-weight: bold;
            color: #475569;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .stat-value {
            font-size: {{ $isLandscape ? '13.5px' : '14.5px' }};
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
            display: block;
        }
        .stat-sub {
            font-size: 7px;
            color: #64748b;
            margin-top: 2px;
            display: block;
        }
        .chart-box {
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            padding: 7px 9px 6px 9px;
        }
        .chart-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .legend-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            vertical-align: middle;
        }
        .chart-grid-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border-bottom: 1px solid #cbd5e1;
        }
        .chart-bar-wrap {
            height: 46px;
            vertical-align: bottom;
            text-align: center;
            padding: 0 2px;
        }
        .chart-labels-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .chart-labels-table td {
            text-align: center;
            padding-top: 3px;
            font-size: 7.2px;
            color: #475569;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #cbd5e1;
        }
        .data-table thead {
            display: table-header-group;
        }
        .data-table tbody tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .data-table th {
            background-color: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            padding: {{ $isLandscape ? '4px 6px' : '4.5px 6px' }};
            font-size: 7.8px;
            font-weight: bold;
            color: #0f172a;
            text-align: left;
        }
        .data-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: {{ $isLandscape ? '4px 6px' : '4.5px 6px' }};
            font-size: 8px;
            color: #1e293b;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table tr.alt-row td {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .mono {
            font-family: 'DejaVu Sans Mono', monospace;
            font-weight: bold;
        }
        .muted {
            color: #64748b;
            font-size: 7.4px;
            margin-top: 1.5px;
        }
        .status-tag {
            display: inline-block;
            font-size: 6.8px;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 3px;
            margin-top: 2px;
        }
        .status-selesai {
            background-color: #ccfbf1;
            color: #0f766e;
        }
        .status-proses {
            background-color: #fef3c7;
            color: #b45309;
        }
        .status-baru {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        .rate-pill {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        .rate-high {
            background-color: #dcfce7;
            color: #166534;
        }
        .rate-mid {
            background-color: #fef3c7;
            color: #92400e;
        }
        .rate-low {
            background-color: #f1f5f9;
            color: #475569;
        }
        .footnote {
            font-size: 7.4px;
            color: #64748b;
            margin-top: 4px;
        }
        .photo-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .photo-card {
            border: 1px solid #cbd5e1;
            padding: 5px;
            vertical-align: top;
            background-color: #ffffff;
        }
        .photo-img {
            max-width: 100%;
            max-height: 88px;
            height: auto;
            border: 1px solid #e2e8f0;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: {{ $isLandscape ? '14px' : '18px' }};
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 8.5px;
            color: #334155;
        }
        .signature-space {
            height: {{ $isLandscape ? '38px' : '44px' }};
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            color: #0f172a;
            font-size: 9px;
        }
        .signature-role {
            font-size: 7.8px;
            color: #64748b;
            margin-top: 1.5px;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT PT S2P -->
    <div class="kop-wrap">
        <table class="kop-table">
            <tr>
                <td style="width: 58px; vertical-align: middle;">
                    @php
                        $logoPngPath = file_exists(public_path('images/logo-s2p.png'))
                            ? public_path('images/logo-s2p.png')
                            : public_path('images/logo-itsupport.png');
                        $logoDataUri = file_exists($logoPngPath)
                            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPngPath))
                            : 'data:image/svg+xml;base64,' . $logoBoltSvg;
                    @endphp
                    <img src="{{ $logoDataUri }}" style="height: 38px; width: auto; max-width: 65px; display: block;" alt="S2P">
                </td>
                <td style="padding-left: 9px; vertical-align: middle;">
                    <div style="font-size: 11px; font-weight: bold; letter-spacing: 0.4px; text-transform: uppercase; color: #092635;">PT SUMBER SEGARA PRIMADAYA (PT S2P)</div>
                    <div style="font-size: 8px; font-weight: bold; color: #334155; text-transform: uppercase; margin-top: 1px;">PLTU 1 JATENG 2&times;300 MW &amp; 1&times;660 MW KARANGKANDRI CILACAP</div>
                    <div style="font-size: 7.5px; font-weight: bold; color: #0d9488; margin-top: 1px;">UNIT TEKNOLOGI INFORMASI &amp; INFRASTRUKTUR SISTEM</div>
                    <div style="font-size: 6.8px; color: #64748b; margin-top: 1.5px;">Jl. Lingkar Timur, Karangkandri, Kesugihan, Cilacap, Jawa Tengah 53274 &bull; Telp: (0282) 540-100 &bull; itsupport@s2p.co.id</div>
                </td>
                <td style="width: 175px; text-align: right; vertical-align: middle;">
                    <div style="border: 1px solid #cbd5e1; background-color: #f8fafc; border-radius: 4px; padding: 3px 6px; text-align: right; display: inline-block;">
                        <div style="font-size: 8.5px; font-weight: bold; color: #0f172a; font-family: 'DejaVu Sans Mono', monospace;">{{ $isTeknisiMode ? 'FORM-IT-SRV-TK-REV.02' : 'FORM-IT-SRV-LPR-REV.02' }}</div>
                        <div style="font-size: 7px; color: #64748b; margin-top: 1px;">{{ $isTeknisiMode ? 'Laporan Kerja Teknisi' : 'Dokumen Rekapitulasi Servis IT' }}</div>
                    </div>
                    <div style="font-size: 7.2px; color: #64748b; margin-top: 3px;">Dicetak: {{ \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->isoFormat('D MMM Y, HH:mm') }} WIB</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- JUDUL LAPORAN -->
    <div class="title-block">
        <div class="title-main">{{ $isTeknisiMode ? 'LAPORAN KERJA TEKNISI' : 'LAPORAN REKAPITULASI SERVIS IT' }}</div>
        <div class="title-sub">{{ $periodeText ?? 'Periode' }}</div>
        <div class="title-meta">Filter: {{ $filterAktifTahunBulan ?? '-' }} &bull; {{ $filterAktifDivisiTeknisi ?? '-' }}</div>
    </div>

    @if($isTeknisiMode)
        @if($showRingkasan)
            <div class="section keep-together">
                <div class="section-title">1. Ringkasan</div>
                <table class="grid-table">
                    <tr>
                        <td class="stat-card" style="width: 23.8%;">
                            <span class="stat-label">Diambil</span>
                            <span class="stat-value">{{ $totalDiambil ?? 0 }}</span>
                            <span class="stat-sub">Total ditangani</span>
                        </td>
                        <td style="width: 1.6%;"></td>
                        <td class="stat-card stat-card-teal" style="width: 23.8%;">
                            <span class="stat-label">Selesai</span>
                            <span class="stat-value">{{ $totalSelesai ?? 0 }}</span>
                            <span class="stat-sub">Laporan terkirim</span>
                        </td>
                        <td style="width: 1.6%;"></td>
                        <td class="stat-card stat-card-amber" style="width: 23.8%;">
                            <span class="stat-label">Aktif</span>
                            <span class="stat-value">{{ $totalTerbuka ?? 0 }}</span>
                            <span class="stat-sub">Sedang dikerjakan</span>
                        </td>
                        <td style="width: 1.6%;"></td>
                        <td class="stat-card stat-card-sky" style="width: 23.8%;">
                            <span class="stat-label">Rata-rata waktu</span>
                            <span class="stat-value">{{ $avgHours ?? '—' }}</span>
                            <span class="stat-sub">Dari tiket selesai</span>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        @if($showRincianPk)
            <div class="section keep-together">
                <div class="section-title">2. Rincian per prioritas dan kategori</div>
                <table class="grid-table">
                    <tr>
                        <td style="width: 49%; vertical-align: top;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Prioritas</th>
                                        <th class="text-right" style="width: 55px;">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(($perPrioritas ?? []) as $prioName => $prioCount)
                                        <tr class="{{ $loop->even ? 'alt-row' : '' }}">
                                            <td style="font-weight: bold;">{{ $prioName }}</td>
                                            <td class="text-right mono">{{ $prioCount }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                        <td style="width: 2%;"></td>
                        <td style="width: 49%; vertical-align: top;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Kategori</th>
                                        <th class="text-right" style="width: 55px;">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($perKategori ?? []) as $katName => $katCount)
                                        @if($loop->iteration <= 6)
                                            <tr class="{{ $loop->even ? 'alt-row' : '' }}">
                                                <td style="font-weight: bold;">{{ $katName ?: 'Lainnya' }}</td>
                                                <td class="text-right mono">{{ $katCount }}</td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center muted">Belum ada data kategori</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        @if($showDaftar)
            <div class="section">
                <div class="section-title">
                    3. Daftar laporan
                    <span class="section-caption">({{ count($services ?? []) }} laporan terkirim)</span>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 13%;">No.</th>
                            <th style="width: 11%;">Tanggal</th>
                            <th style="width: {{ $showSparepart ? '49%' : '65%' }};">Kendala dan tindakan</th>
                            @if($showSparepart)
                                <th style="width: 16%;">Sparepart</th>
                            @endif
                            <th class="text-right" style="width: 11%;">Durasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($services ?? []) as $row)
                            <tr class="{{ $loop->even ? 'alt-row' : '' }}">
                                <td class="mono" style="white-space: nowrap;">{{ $row->ticket_number }}</td>
                                <td style="white-space: nowrap; color: #475569;">{{ ($row->tanggal_selesai ?? $row->created_at)?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <div style="font-weight: bold; color: #0f172a;">{{ $row->display_judul }}</div>
                                    <div class="muted">{{ $row->tindakan_perbaikan ?: \Illuminate\Support\Str::limit($row->keluhan, 110) }}</div>
                                </td>
                                @if($showSparepart)
                                    <td style="color: #475569;">{{ $row->sparepart ?: '-' }}</td>
                                @endif
                                <td class="text-right mono" style="white-space: nowrap; color: #334155;">
                                    @if($row->created_at && $row->tanggal_selesai)
                                        {{ number_format(max(0.1, round($row->created_at->diffInMinutes($row->tanggal_selesai) / 60, 1)), 1, ',', '.') }} jam
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $showSparepart ? 5 : 4 }}" class="text-center muted" style="padding: 14px;">
                                    Belum ada laporan berstatus Terkirim pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="footnote">
                    Hanya laporan berstatus Terkirim yang dicetak. {{ $totalTerbuka ?? 0 }} laporan draft tidak disertakan.
                </div>
            </div>
        @endif

        @if($showFoto)
            <div class="section keep-together">
                <div class="section-title">4. Foto lampiran</div>
                @php
                    $withPhotos = collect($photoAttachments ?? []);
                @endphp
                @if($withPhotos->isNotEmpty())
                    <table class="photo-grid">
                        @foreach($withPhotos->chunk(3) as $chunk)
                            <tr>
                                @foreach($chunk as $photoItem)
                                    <td style="width: 32%; padding: 3px;">
                                        <div class="photo-card">
                                            @if(!empty($photoItem['data_uri']))
                                                <img src="{{ $photoItem['data_uri'] }}" alt="{{ $photoItem['ticket_number'] }}" class="photo-img">
                                            @else
                                                <div style="height: 54px; line-height: 54px; text-align: center; background: #f8fafc; color: #94a3b8; font-size: 7.5px;">File foto tidak ditemukan</div>
                                            @endif
                                            <div class="mono" style="font-size: 8px; margin-top: 3px;">{{ $photoItem['ticket_number'] }}</div>
                                            <div class="muted">{{ \Illuminate\Support\Str::limit($photoItem['judul'], 48) }}</div>
                                        </div>
                                    </td>
                                    @if(!$loop->last)
                                        <td style="width: 2%;"></td>
                                    @endif
                                @endforeach
                                @for($pad = $chunk->count(); $pad < 3; $pad++)
                                    <td style="width: 2%;"></td>
                                    <td style="width: 32%;"></td>
                                @endfor
                            </tr>
                        @endforeach
                    </table>
                @else
                    <div style="border: 1px solid #cbd5e1; padding: 10px; text-align: center; color: #94a3b8; font-size: 8px; background: #f8fafc;">
                        Tidak ada foto lampiran pada laporan periode ini.
                    </div>
                @endif
            </div>
        @endif
    @else
        @if($showRingkasan)
            <div class="section keep-together">
                <div class="section-title section-title-admin">1. Ringkasan</div>
                <table class="grid-table">
                    <tr>
                        <td class="stat-card" style="width: 23.8%;">
                            <span class="stat-label">Tiket masuk</span>
                            <span class="stat-value">{{ $totalMasuk ?? 0 }}</span>
                            <span class="stat-sub">Total laporan periode</span>
                        </td>
                        <td style="width: 1.6%;"></td>
                        <td class="stat-card stat-card-teal" style="width: 23.8%;">
                            <span class="stat-label">Tiket selesai</span>
                            <span class="stat-value">{{ $totalSelesai ?? 0 }}</span>
                            <span class="stat-sub">Sudah dituntaskan</span>
                        </td>
                        <td style="width: 1.6%;"></td>
                        <td class="stat-card stat-card-amber" style="width: 23.8%;">
                            <span class="stat-label">Masih terbuka</span>
                            <span class="stat-value">{{ $totalTerbuka ?? 0 }}</span>
                            <span class="stat-sub">{{ $belumDiambil ?? 0 }} belum diambil</span>
                        </td>
                        <td style="width: 1.6%;"></td>
                        <td class="stat-card stat-card-sky" style="width: 23.8%;">
                            <span class="stat-label">Rata-rata waktu</span>
                            <span class="stat-value">{{ $avgHours ?? '—' }}</span>
                            <span class="stat-sub">Penanganan selesai</span>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        @if($showGrafik)
            <div class="section keep-together">
                <div class="section-title section-title-admin">Grafik Tren 7 Hari Terakhir</div>
                <div class="chart-box">
                    <table class="chart-header-table">
                        <tr>
                            <td style="font-size: 7.8px; font-weight: bold; color: #475569;">Perbandingan Tiket Masuk vs Tiket Selesai Harian</td>
                            <td class="text-right" style="font-size: 7.5px; color: #475569;">
                                <span class="legend-dot" style="background-color: #64748b;"></span> Masuk
                                &nbsp;&nbsp;
                                <span class="legend-dot" style="background-color: #0f766e;"></span> Selesai
                            </td>
                        </tr>
                    </table>

                    <table class="chart-grid-table">
                        <tr>
                            @foreach($trendItems as $day)
                                @php
                                    $hMasuk = $day['masuk'] > 0 ? max(5, (int) round(($day['masuk'] / $trendMax) * 34)) : 1;
                                    $hSelesai = $day['selesai'] > 0 ? max(5, (int) round(($day['selesai'] / $trendMax) * 34)) : 1;
                                @endphp
                                <td class="chart-bar-wrap" style="{{ !empty($day['is_today']) ? 'background-color: #f0fdfa;' : '' }}">
                                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                                        <tr>
                                            <td style="vertical-align: bottom; text-align: center; width: 50%; padding: 0 2px;">
                                                <div style="font-size: 6.8px; font-weight: bold; color: #475569; line-height: 1;">{{ $day['masuk'] }}</div>
                                                <div style="width: 11px; height: {{ $hMasuk }}px; background-color: {{ $day['masuk'] > 0 ? '#64748b' : '#e2e8f0' }}; margin: 2px auto 0 auto;"></div>
                                            </td>
                                            <td style="vertical-align: bottom; text-align: center; width: 50%; padding: 0 2px;">
                                                <div style="font-size: 6.8px; font-weight: bold; color: #0f766e; line-height: 1;">{{ $day['selesai'] }}</div>
                                                <div style="width: 11px; height: {{ $hSelesai }}px; background-color: {{ $day['selesai'] > 0 ? '#0f766e' : '#e2e8f0' }}; margin: 2px auto 0 auto;"></div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            @endforeach
                        </tr>
                    </table>

                    <table class="chart-labels-table">
                        <tr>
                            @foreach($trendItems as $day)
                                <td style="{{ !empty($day['is_today']) ? 'font-weight: bold; color: #0f766e;' : '' }}">
                                    {{ $day['day_short'] }}, {{ $day['date_short'] }}
                                </td>
                            @endforeach
                        </tr>
                    </table>
                </div>
            </div>
        @endif

        @if($showDivisiKategori)
            <div class="section keep-together">
                <div class="section-title section-title-admin">2. Tiket per divisi dan kategori</div>
                <table class="grid-table">
                    <tr>
                        <td style="width: 49%; vertical-align: top;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Divisi</th>
                                        <th class="text-right" style="width: 55px;">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($perDivisi ?? []) as $divName => $divCount)
                                        @if($loop->iteration <= 6)
                                            <tr class="{{ $loop->even ? 'alt-row' : '' }}">
                                                <td style="font-weight: bold;">{{ $divName ?: 'Umum / Lainnya' }}</td>
                                                <td class="text-right mono">{{ $divCount }}</td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center muted">Belum ada data divisi</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                        <td style="width: 2%;"></td>
                        <td style="width: 49%; vertical-align: top;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Kategori</th>
                                        <th class="text-right" style="width: 55px;">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($perKategori ?? []) as $katName => $katCount)
                                        @if($loop->iteration <= 6)
                                            <tr class="{{ $loop->even ? 'alt-row' : '' }}">
                                                <td style="font-weight: bold;">{{ $katName ?: 'Lainnya' }}</td>
                                                <td class="text-right mono">{{ $katCount }}</td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center muted">Belum ada data kategori</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        @if($showKinerja)
            <div class="section keep-together">
                <div class="section-title section-title-admin">Kinerja Teknisi</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 34%;">Nama Teknisi</th>
                            <th style="width: 20%;">Tim</th>
                            <th class="text-center" style="width: 11%;">Diambil</th>
                            <th class="text-center" style="width: 11%;">Selesai</th>
                            <th class="text-center" style="width: 12%;">Rata-rata</th>
                            <th class="text-right" style="width: 12%;">Tingkat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($kinerjaTeknisi ?? []) as $tek)
                            @php
                                $rateVal = (int) ($tek['rate'] ?? 0);
                                $rateClass = $rateVal >= 80 ? 'rate-high' : ($rateVal >= 40 ? 'rate-mid' : 'rate-low');
                            @endphp
                            <tr class="{{ $loop->even ? 'alt-row' : '' }}">
                                <td style="font-weight: bold;">{{ $tek['user']->name ?? '-' }}</td>
                                <td style="color: #475569; font-size: 7.5px;">{{ $tek['spesialisasi'] ?? ($tek['user']->label_tim ?? 'Tim IT Support') }}</td>
                                <td class="text-center mono">{{ $tek['diambil'] ?? 0 }}</td>
                                <td class="text-center mono" style="color: #0f766e;">{{ $tek['selesai'] ?? 0 }}</td>
                                <td class="text-center" style="color: #334155;">{{ $tek['avg_time'] ?? '—' }}</td>
                                <td class="text-right"><span class="rate-pill {{ $rateClass }}">{{ $rateVal }}%</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center muted">Belum ada data teknisi</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if($showDaftar)
            <div class="section">
                <div class="section-title section-title-admin">
                    3. Daftar laporan
                    <span class="section-caption">({{ count($services ?? []) }} tiket)</span>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 13%;">No. &amp; Status</th>
                            <th style="width: 11%;">Tanggal</th>
                            <th style="width: 48%;">Kendala &amp; Tindakan</th>
                            <th style="width: 18%;">Teknisi</th>
                            <th class="text-right" style="width: 10%;">Durasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($services ?? []) as $row)
                            @php
                                $statusClass = $row->status === 'Selesai'
                                    ? 'status-selesai'
                                    : ($row->status === 'Baru' ? 'status-baru' : 'status-proses');
                            @endphp
                            <tr class="{{ $loop->even ? 'alt-row' : '' }}">
                                <td>
                                    <div class="mono" style="white-space: nowrap;">{{ $row->ticket_number }}</div>
                                    <span class="status-tag {{ $statusClass }}">{{ $row->status }}</span>
                                </td>
                                <td style="white-space: nowrap; color: #475569;">{{ $row->created_at ? $row->created_at->format('d/m/Y') : '-' }}</td>
                                <td>
                                    <div style="font-weight: bold; color: #0f172a;">{{ $row->keluhan }}</div>
                                    @if($row->tindakan_perbaikan)
                                        <div class="muted">{{ $row->tindakan_perbaikan }}</div>
                                    @endif
                                </td>
                                <td style="color: #334155;">{{ $row->teknisi_tim_label }}</td>
                                <td class="text-right mono" style="white-space: nowrap;">
                                    @if($row->created_at && $row->tanggal_selesai)
                                        {{ number_format(max(0.1, round($row->created_at->diffInMinutes($row->tanggal_selesai) / 60, 1)), 1, ',', '.') }} jam
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center muted" style="padding: 14px;">
                                    Tidak ada data laporan servis pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    @if($showTtd)
        <table class="signature-table">
            <tr>
                <td>
                    <div>Dibuat oleh,</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">{{ $dibuatOleh }}</div>
                    <div class="signature-role">{{ $defaultSignerRole }}</div>
                </td>
                <td>
                    <div>Mengetahui,</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">{{ $mengetahuiDisplay }}</div>
                    <div class="signature-role">[Jabatan]</div>
                </td>
            </tr>
        </table>
    @endif

</body>
</html>
