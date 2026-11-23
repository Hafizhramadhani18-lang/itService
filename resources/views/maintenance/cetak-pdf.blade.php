@php
    $logoBase64 = '';
    $logoPath = public_path('images/logo-s2p.png');
    if (!file_exists($logoPath)) {
        $logoPath = public_path('images/logo-itsupport.png');
    }
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Preventive Maintenance Aset IT - PT S2P</title>
    <style>
        @page {
            margin: 10mm 12mm 14mm 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #0f172a;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .keep-together {
            page-break-inside: avoid;
        }
        table {
            border-collapse: collapse;
        }

        /* Kop Surat Resmi PLTU Karangkandri */
        .kop-table {
            width: 100%;
            margin-bottom: 4px;
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-logo {
            height: 38px;
            width: auto;
            max-width: 65px;
            display: block;
        }
        .kop-company {
            font-size: 11.5px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .kop-plant {
            font-size: 8.5px;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            margin-top: 1px;
        }
        .kop-unit {
            font-size: 7.5px;
            font-weight: bold;
            color: #0d9488;
            margin-top: 1px;
        }
        .kop-address {
            font-size: 7px;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-meta-card {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 4px 8px;
            text-align: right;
        }
        .doc-code {
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
            font-family: 'DejaVu Sans Mono', monospace, sans-serif;
        }
        .doc-meta-sub {
            font-size: 7px;
            color: #64748b;
            margin-top: 1px;
        }
        .kop-divider {
            border-top: 2px solid #0f172a;
            border-bottom: 0.5px solid #94a3b8;
            height: 2px;
            margin: 4px 0 8px 0;
        }

        /* Title Banner */
        .report-title-card {
            background-color: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 4px;
            padding: 5px 8px;
            text-align: center;
            margin-bottom: 8px;
        }
        .report-title-text {
            font-size: 11px;
            font-weight: bold;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .report-subtitle-text {
            font-size: 7.5px;
            color: #334155;
            margin-top: 2px;
        }

        /* Filter Parameters Strip */
        .params-table {
            width: 100%;
            margin-bottom: 8px;
            background-color: #f8fafc;
            border: 0.5px solid #e2e8f0;
            border-radius: 4px;
        }
        .params-table td {
            padding: 3px 6px;
            font-size: 7.5px;
            color: #334155;
        }
        .params-label {
            font-weight: bold;
            color: #64748b;
        }

        /* Executive Metrics */
        .metrics-table {
            width: 100%;
            margin-bottom: 9px;
        }
        .metrics-table td {
            text-align: center;
            padding: 4px 6px;
            border-radius: 4px;
            border: 0.5px solid #e2e8f0;
        }
        .metric-val {
            font-size: 11px;
            font-weight: bold;
            display: block;
        }
        .metric-lbl {
            font-size: 6.5px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 12px;
        }
        .data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 4px;
            border: 0.5px solid #334155;
            text-align: left;
            vertical-align: middle;
        }
        .data-table td {
            font-size: 7.5px;
            padding: 4px 4px;
            border: 0.5px solid #cbd5e1;
            vertical-align: top;
            word-wrap: break-word;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .data-table tr {
            page-break-inside: avoid;
        }

        /* Status & Badges */
        .badge {
            display: inline-block;
            padding: 1.5px 4px;
            border-radius: 3px;
            font-size: 6.8px;
            font-weight: bold;
            text-align: center;
        }
        .badge-prima {
            background-color: #dcfce7;
            color: #15803d;
            border: 0.5px solid #86efac;
        }
        .badge-perhatian {
            background-color: #fef3c7;
            color: #b45309;
            border: 0.5px solid #fde68a;
        }
        .badge-kritis {
            background-color: #ffe4e6;
            color: #be123c;
            border: 0.5px solid #fecdd3;
        }
        .badge-done {
            background-color: #ecfdf5;
            color: #047857;
            border: 0.5px solid #a7f3d0;
        }
        .badge-scheduled {
            background-color: #f1f5f9;
            color: #475569;
            border: 0.5px solid #cbd5e1;
        }

        /* Signatures Block */
        .sig-table {
            width: 100%;
            margin-top: 8px;
            page-break-inside: avoid;
        }
        .sig-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 20px;
        }
        .sig-box {
            text-align: center;
            border: 0.5px solid #e2e8f0;
            background-color: #ffffff;
            border-radius: 4px;
            padding: 8px 12px;
        }
        .sig-title {
            font-size: 7.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }
        .sig-role {
            font-size: 8px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 1px;
        }
        .sig-spacer {
            height: 44px;
        }
        .sig-name {
            font-size: 8.5px;
            font-weight: bold;
            color: #0f172a;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .sig-nip {
            font-size: 7px;
            color: #64748b;
            margin-top: 1px;
        }
    </style>
</head>
<body>

    <!-- Kop Surat Resmi PT Sumber Segara Primadaya -->
    <table class="kop-table">
        <tr>
            <td style="width: 72px; vertical-align: middle;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="kop-logo" alt="Logo PT S2P">
                @else
                    <div style="width: 55px; height: 38px; background: #0f766e; border-radius: 4px; text-align: center; color: #fff; font-weight: bold; line-height: 38px; font-size: 13px;">S2P</div>
                @endif
            </td>
            <td style="vertical-align: middle;">
                <div class="kop-company">PT SUMBER SEGARA PRIMADAYA (PT S2P)</div>
                <div class="kop-plant">PLTU 1 JATENG 2×300 MW & 1×660 MW KARANGKANDRI CILACAP</div>
                <div class="kop-unit">UNIT TEKNOLOGI INFORMASI & INFRASTRUKTUR SISTEM</div>
                <div class="kop-address">Jl. Lingkar Timur, Karangkandri, Kesugihan, Cilacap, Jawa Tengah 53274 &bull; Telp: (0282) 540-100 &bull; itsupport@s2p.co.id</div>
            </td>
            <td style="width: 220px;">
                <div class="doc-meta-card">
                    <div class="doc-code">FORM-IT-PM-REV.02</div>
                    <div class="doc-meta-sub">Dokumen Operasional Preventive Maintenance</div>
                    <div class="doc-meta-sub">Waktu Cetak: {{ $printedAt }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="kop-divider"></div>

    <!-- Title Banner -->
    <div class="report-title-card">
        <div class="report-title-text">Laporan Pelaksanaan Preventive Maintenance (PM) Aset & Sistem IT</div>
        <div class="report-subtitle-text">
            Periode: <strong>{{ $periodeLabel }}</strong> &bull; Klasifikasi: Pemeliharaan Rutin, Inspeksi Fisik, & Keandalan Perangkat
        </div>
    </div>

    <!-- Parameters Filter Strip -->
    <table class="params-table">
        <tr>
            <td style="width: 25%;"><span class="params-label">Periode:</span> {{ $periodeLabel }}</td>
            <td style="width: 25%;"><span class="params-label">Divisi / Unit:</span> {{ $filterDivisiLabel }}</td>
            <td style="width: 25%;"><span class="params-label">Kondisi:</span> {{ $filterKondisiLabel }}</td>
            <td style="width: 25%;"><span class="params-label">Status Log:</span> {{ $filterStatusLabel }}</td>
        </tr>
    </table>

    <!-- Executive Metrics KPI -->
    <table class="metrics-table">
        <tr>
            <td style="width: 20%; background-color: #f8fafc; border-color: #cbd5e1;">
                <span class="metric-val" style="color: #0f172a;">{{ $totalAset }}</span>
                <span class="metric-lbl" style="color: #475569;">Total Diperiksa</span>
            </td>
            <td style="width: 20%; background-color: #f0fdf4; border-color: #bbf7d0;">
                <span class="metric-val" style="color: #166534;">{{ $totalPrima }}</span>
                <span class="metric-lbl" style="color: #15803d;">Prima / Normal</span>
            </td>
            <td style="width: 20%; background-color: #fffbeb; border-color: #fde68a;">
                <span class="metric-val" style="color: #92400e;">{{ $totalPerhatian }}</span>
                <span class="metric-lbl" style="color: #b45309;">Perlu Perhatian</span>
            </td>
            <td style="width: 20%; background-color: #fff1f2; border-color: #fecdd3;">
                <span class="metric-val" style="color: #9f1239;">{{ $totalKritis }}</span>
                <span class="metric-lbl" style="color: #be123c;">Kritis / Rusak</span>
            </td>
            <td style="width: 20%; background-color: #f0fdfa; border-color: #99f6e4;">
                <span class="metric-val" style="color: #0f766e;">{{ $persenKelaikan }}%</span>
                <span class="metric-lbl" style="color: #0d9488;">Tingkat Kelaikan</span>
            </td>
        </tr>
    </table>

    <!-- Data Table Pemeliharaan Rutin Aset IT -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%; text-align: center;">No</th>
                <th style="width: 13%;">No. Log & Tgl</th>
                <th style="width: 18%;">Kode & Nama Perangkat</th>
                <th style="width: 15%;">Divisi & Lokasi</th>
                <th style="width: 10%; text-align: center;">Kondisi</th>
                <th style="width: 20%;">Tindakan Pemeliharaan</th>
                <th style="width: 11%;">Rekomendasi</th>
                <th style="width: 10%;">Teknisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $idx => $log)
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $idx + 1 }}</td>
                    <td>
                        <strong style="color: #0f172a;">{{ $log->log_number }}</strong><br>
                        <span style="color: #64748b; font-size: 7px;">
                            {{ $log->tanggal_cek ? $log->tanggal_cek->format('d/m/Y') : '-' }}
                        </span>
                    </td>
                    <td>
                        <strong style="color: #0f172a;">{{ $log->nama_perangkat }}</strong><br>
                        <span style="color: #475569; font-size: 7px;">
                            {{ $log->kode_perangkat }} &bull; {{ $log->jenis_perangkat }}
                        </span>
                    </td>
                    <td>
                        <strong style="color: #334155;">{{ $log->divisi }}</strong><br>
                        <span style="color: #64748b; font-size: 7px;">{{ $log->lokasi_ruangan ?: '-' }}</span>
                    </td>
                    <td style="text-align: center;">
                        @if($log->isPrima())
                            <span class="badge badge-prima">PRIMA</span>
                        @elseif($log->isPerluPerhatian())
                            <span class="badge badge-perhatian">PERHATIAN</span>
                        @elseif($log->isKritis())
                            <span class="badge badge-kritis">KRITIS</span>
                        @else
                            <span class="badge badge-scheduled">{{ strtoupper($log->kondisi ?? 'NORMAL') }}</span>
                        @endif
                        <br>
                        <span style="font-size: 6.5px; color: #64748b;">
                            {{ $log->status === 'Selesai' ? 'Selesai' : ($log->status ?? 'Terjadwal') }}
                        </span>
                    </td>
                    <td>
                        <div style="color: #1e293b;">{{ $log->tindakan ?: ($log->kegiatan ?: 'Pemeriksaan preventif rutin') }}</div>
                        @if($log->service)
                            <div style="font-size: 6.8px; color: #0284c7; margin-top: 1px;">
                                Tiket: {{ $log->service->ticket_number }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <span style="color: #475569;">{{ $log->catatan_rekomendasi ?: '-' }}</span>
                        @if($log->status_tindak_lanjut && $log->status_tindak_lanjut !== 'Selesai')
                            <div style="font-size: 6.8px; color: #d97706; font-weight: bold; margin-top: 1px;">
                                {{ $log->status_tindak_lanjut }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <strong style="color: #334155;">{{ $log->teknisi_lengkap }}</strong>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 16px; color: #94a3b8; font-style: italic;">
                        Tidak ada catatan preventive maintenance untuk parameter filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Lembar Pengesahan Formal (Signatures Block) -->
    <table class="sig-table keep-together">
        <tr>
            <td>
                <div class="sig-box">
                    <div class="sig-title">Disiapkan Oleh:</div>
                    <div class="sig-role">Teknisi IT Support & Pemeliharaan</div>
                    <div class="sig-spacer"></div>
                    <div class="sig-name">{{ $printedBy }}</div>
                    <div class="sig-nip">{{ $printedRole }} &bull; Tgl: {{ $printedDateOnly }}</div>
                </div>
            </td>
            <td>
                <div class="sig-box">
                    <div class="sig-title">Mengetahui & Menyetujui:</div>
                    <div class="sig-role">{{ $coordinatorTitle }}</div>
                    <div class="sig-spacer"></div>
                    <div class="sig-name">{{ $coordinatorName }}</div>
                    <div class="sig-nip">Koordinator IT Support PLTU Karangkandri &bull; Tgl: {{ $printedDateOnly }}</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
