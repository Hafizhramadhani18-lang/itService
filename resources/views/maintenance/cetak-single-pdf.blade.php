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

    $fotoBase64 = '';
    if (!empty($maintenanceLog->foto_dokumentasi)) {
        $diskPath = storage_path('app/public/' . $maintenanceLog->foto_dokumentasi);
        if (file_exists($diskPath)) {
            $fotoData = file_get_contents($diskPath);
            $mime = mime_content_type($diskPath) ?: 'image/jpeg';
            $fotoBase64 = 'data:' . $mime . ';base64,' . base64_encode($fotoData);
        }
    }

    $checklistItems = is_array($maintenanceLog->checklist) ? $maintenanceLog->checklist : [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara PM {{ $maintenanceLog->log_number }} - PT S2P</title>
    <style>
        @page {
            margin: 12mm 14mm 14mm 14mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5px;
            color: #0f172a;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .keep-together {
            page-break-inside: avoid;
        }
        table {
            border-collapse: collapse;
            width: 100%;
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
            height: 42px;
            width: auto;
            max-width: 75px;
            display: block;
        }
        .kop-company {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .kop-plant {
            font-size: 8px;
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
            font-size: 8.5px;
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

        /* Judul Dokumen */
        .doc-title-box {
            text-align: center;
            margin-bottom: 10px;
        }
        .doc-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 2px;
        }
        .doc-subtitle {
            font-size: 8.5px;
            font-weight: bold;
            color: #0d9488;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .doc-reg {
            font-size: 8px;
            color: #475569;
        }

        /* Section Header */
        .section-title {
            font-size: 8.5px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-left: 3px solid #0d9488;
            padding-left: 5px;
            margin: 9px 0 5px 0;
        }

        /* Data Tables */
        .info-table {
            width: 100%;
            border: 1px solid #cbd5e1;
            margin-bottom: 8px;
        }
        .info-table td {
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            font-size: 8px;
            vertical-align: top;
        }
        .info-label {
            font-size: 7px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            display: block;
            margin-bottom: 1px;
        }
        .info-val {
            font-weight: bold;
            color: #0f172a;
        }

        /* Checklist Table */
        .checklist-table {
            width: 100%;
            border: 1px solid #cbd5e1;
            margin-bottom: 8px;
        }
        .checklist-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        .checklist-table td {
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            font-size: 8px;
            vertical-align: middle;
        }
        .checklist-badge {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }
        .badge-success {
            background-color: #ecfdf5;
            color: #065f46;
            border: 0.5px solid #a7f3d0;
        }
        .badge-pending {
            background-color: #f1f5f9;
            color: #475569;
            border: 0.5px solid #cbd5e1;
        }

        /* Hasil Box */
        .result-box {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 6px 8px;
            margin-bottom: 8px;
        }
        .result-item {
            margin-bottom: 4px;
        }
        .result-title {
            font-size: 7px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .result-content {
            font-size: 8px;
            color: #1e293b;
            line-height: 1.35;
            background: #ffffff;
            border: 0.5px solid #e2e8f0;
            border-radius: 3px;
            padding: 4px 6px;
        }

        /* Badges Kondisi */
        .badge-prima {
            background-color: #d1fae5;
            color: #065f46;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
        }
        .badge-perhatian {
            background-color: #fef3c7;
            color: #92400e;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
        }
        .badge-kritis {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
        }

        /* Foto Section */
        .photo-box {
            text-align: center;
            margin-bottom: 8px;
            padding: 4px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            background: #ffffff;
            max-width: 250px;
        }
        .photo-box img {
            max-width: 100%;
            height: auto;
            max-height: 120px;
            display: block;
            margin: 0 auto;
        }
        .photo-caption {
            font-size: 7px;
            color: #64748b;
            margin-top: 3px;
        }

        /* Signature Table */
        .sig-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: separate;
            border-spacing: 12px 0;
        }
        .sig-card {
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            border-radius: 4px;
            padding: 8px;
            text-align: center;
            vertical-align: top;
            width: 50%;
        }
        .sig-role-label {
            font-size: 7px;
            color: #64748b;
            margin-bottom: 2px;
        }
        .sig-role-title {
            font-size: 8.5px;
            font-weight: bold;
            color: #0f172a;
        }
        .sig-space {
            height: 48px;
        }
        .sig-name {
            font-size: 8.5px;
            font-weight: bold;
            color: #0f172a;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .sig-sub {
            font-size: 7px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Footer */
        .doc-footer {
            margin-top: 10px;
            padding-top: 4px;
            border-top: 0.5px solid #cbd5e1;
            font-size: 6.5px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <!-- ============================================== -->
    <!-- 1. KOP SURAT RESMI PT S2P PLTU CILACAP         -->
    <!-- ============================================== -->
    <table class="kop-table">
        <tr>
            <td style="width: 55px; vertical-align: top;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="kop-logo" alt="Logo PT S2P">
                @else
                    <div style="font-size: 14px; font-weight: bold; color: #0d9488;">S2P</div>
                @endif
            </td>
            <td style="padding-left: 8px; vertical-align: top;">
                <div class="kop-company">PT SUMBER SEGARA PRIMADAYA (PT S2P)</div>
                <div class="kop-plant">PLTU 1 JATENG 2&times;300 MW &amp; 1&times;660 MW KARANGKANDRI CILACAP</div>
                <div class="kop-unit">Unit Teknologi Informasi &amp; Infrastruktur Sistem</div>
                <div class="kop-address">
                    Jl. Lingkar Timur, Karangkandri, Kesugihan, Cilacap, Jawa Tengah 53274 &bull; Telp: (0282) 540-100 &bull; Email: itsupport@s2p.co.id
                </div>
            </td>
            <td style="width: 145px; text-align: right; vertical-align: top;">
                <div class="doc-meta-card">
                    <div class="doc-code">FORM-IT-PM-BA-REV.02</div>
                    <div class="doc-meta-sub">Berita Acara PM Aset IT</div>
                    <div class="doc-meta-sub">Dicetak: {{ $printedAt }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="kop-divider"></div>

    <!-- ============================================== -->
    <!-- 2. JUDUL DOKUMEN BERITA ACARA                  -->
    <!-- ============================================== -->
    <div class="doc-title-box">
        <div class="doc-title">BERITA ACARA PREVENTIVE MAINTENANCE</div>
        <div class="doc-subtitle">FORMULIR PELAKSANAAN &amp; PEMERIKSAAN FISIK ASET IT</div>
        <div class="doc-reg">
            No. Registrasi: <strong>{{ $maintenanceLog->log_number }}</strong> &bull;
            Tanggal Pelaksanaan: <strong>{{ $maintenanceLog->tanggal_cek ? $maintenanceLog->tanggal_cek->translatedFormat('d F Y') : '-' }}</strong> &bull;
            Waktu: <strong>{{ $maintenanceLog->formatted_jam ?: '09.00 WIB' }}</strong>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 3. INFORMASI ASET & PELAKSANAAN                -->
    <!-- ============================================== -->
    <div class="section-title">1. Informasi Aset &amp; Pelaksanaan</div>
    <table class="info-table">
        <tr>
            <td style="width: 25%;">
                <span class="info-label">Nama Perangkat</span>
                <span class="info-val">{{ $maintenanceLog->nama_perangkat }}</span>
            </td>
            <td style="width: 25%;">
                <span class="info-label">Kode Perangkat</span>
                <span class="info-val" style="font-family: 'DejaVu Sans Mono', monospace;">{{ $maintenanceLog->kode_perangkat ?: '-' }}</span>
            </td>
            <td style="width: 25%;">
                <span class="info-label">Jenis / Kategori</span>
                <span class="info-val">{{ $maintenanceLog->jenis_perangkat ?: 'Perangkat IT' }}</span>
            </td>
            <td style="width: 25%;">
                <span class="info-label">Status Log</span>
                <span class="info-val">{{ $maintenanceLog->computed_status }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="info-label">Lokasi Ruangan</span>
                <span class="info-val">{{ $maintenanceLog->lokasi_ruangan ?: '-' }}</span>
            </td>
            <td>
                <span class="info-label">Divisi Pengguna</span>
                <span class="info-val">{{ $maintenanceLog->divisi ?: 'Operasional PLTU' }}</span>
            </td>
            <td>
                <span class="info-label">Periode Bulan</span>
                <span class="info-val">{{ $maintenanceLog->periode_label }}</span>
            </td>
            <td>
                <span class="info-label">Teknisi Pelaksana</span>
                <span class="info-val">{{ $maintenanceLog->teknisi_lengkap }}</span>
            </td>
        </tr>
    </table>

    <!-- ============================================== -->
    <!-- 4. CHECKLIST PEMERIKSAAN LAPANGAN              -->
    <!-- ============================================== -->
    <div class="section-title">2. Lembar Checklist Kegiatan Pemeriksaan ({{ count($checklistItems) }} Poin)</div>
    <table class="checklist-table">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">No</th>
                <th>Item Pemeriksaan &amp; Pengujian Fisik</th>
                <th style="width: 90px; text-align: center;">Status Verifikasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($checklistItems as $idx => $cItem)
                @php
                    $itemLabel = is_array($cItem) ? ($cItem['item'] ?? '') : (string) $cItem;
                    $isChecked = is_array($cItem) ? (!empty($cItem['checked']) || $maintenanceLog->status === 'Selesai') : ($maintenanceLog->status === 'Selesai');
                @endphp
                <tr>
                    <td style="text-align: center; color: #64748b; font-weight: bold;">{{ $idx + 1 }}</td>
                    <td style="color: #1e293b; font-weight: 500;">{{ $itemLabel }}</td>
                    <td style="text-align: center;">
                        @if($isChecked)
                            <span class="checklist-badge badge-success">&#10003; Dikerjakan</span>
                        @else
                            <span class="checklist-badge badge-pending">Tertunda</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="color: #475569; padding: 6px;">
                        {{ $maintenanceLog->kegiatan ?: 'Pemeriksaan berkala perangkat, kalibrasi sistem, dan pembersihan komponen fisik.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- ============================================== -->
    <!-- 5. HASIL PEMERIKSAAN & CATATAN TEKNIS          -->
    <!-- ============================================== -->
    <div class="keep-together">
        <div class="section-title">3. Hasil Pemeriksaan &amp; Rekomendasi Teknis</div>
        <div class="result-box">
            <table style="width: 100%; margin-bottom: 5px;">
                <tr>
                    <td style="width: 50%; vertical-align: middle;">
                        <span style="font-size: 7px; color: #64748b; text-transform: uppercase; font-weight: bold;">Kondisi Pasca Pemeliharaan: </span>
                        @if($maintenanceLog->isPrima())
                            <span class="badge-prima">PRIMA / BAIK</span>
                        @elseif($maintenanceLog->isPerluPerhatian())
                            <span class="badge-perhatian">PERLU PEMANTAUAN</span>
                        @elseif($maintenanceLog->isKritis())
                            <span class="badge-kritis">KRITIS / PERLU PERBAIKAN</span>
                        @else
                            <span style="font-weight: bold; font-size: 8px;">{{ strtoupper($maintenanceLog->kondisi ?? 'NORMAL') }}</span>
                        @endif
                    </td>
                    <td style="width: 50%; text-align: right; vertical-align: middle; font-size: 7.5px; color: #64748b;">
                        Status Tindak Lanjut: <strong style="color: #0f172a;">{{ $maintenanceLog->status_tindak_lanjut ?: 'Selesai' }}</strong>
                    </td>
                </tr>
            </table>

            <div class="result-item">
                <div class="result-title">Tindakan Teknis yang Dilakukan:</div>
                <div class="result-content">
                    {{ $maintenanceLog->tindakan ?: ($maintenanceLog->kegiatan ?: 'Pemeliharaan rutin dilaksanakan sesuai standar checklist pemeriksaan.') }}
                </div>
            </div>

            @if($maintenanceLog->catatan_rekomendasi)
            <div class="result-item" style="margin-top: 4px;">
                <div class="result-title" style="color: #b45309;">Catatan Rekomendasi / Rekomendasi Tindak Lanjut:</div>
                <div class="result-content" style="border-color: #fde68a; background-color: #fffbeb; color: #92400e;">
                    {{ $maintenanceLog->catatan_rekomendasi }}
                </div>
            </div>
            @endif

            @if($maintenanceLog->service)
            <div style="font-size: 7.5px; color: #0369a1; background: #f0f9ff; border: 0.5px solid #bae6fd; padding: 3px 6px; border-radius: 3px; margin-top: 4px;">
                Terhubung dengan Tiket Servis: <strong>{{ $maintenanceLog->service->ticket_number }}</strong> ({{ $maintenanceLog->service->display_judul }})
            </div>
            @endif
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 6. FOTO DOKUMENTASI (JIKA ADA)                 -->
    <!-- ============================================== -->
    @if($fotoBase64)
    <div class="keep-together">
        <div class="section-title">4. Dokumentasi Visual Fisik</div>
        <div class="photo-box">
            <img src="{{ $fotoBase64 }}" alt="Dokumentasi Pemeliharaan">
            <div class="photo-caption">Foto dokumentasi hasil pemeliharaan unit {{ $maintenanceLog->nama_perangkat }}</div>
        </div>
    </div>
    @endif

    <!-- ============================================== -->
    <!-- 7. LEMBAR PENGESAHAN / TANDA TANGAN FORMAL     -->
    <!-- ============================================== -->
    <div class="keep-together">
        <table class="sig-table">
            <tr>
                <td class="sig-card">
                    <div class="sig-role-label">Dikerjakan &amp; Diverifikasi Oleh:</div>
                    <div class="sig-role-title">Teknisi Pelaksana</div>
                    <div class="sig-space"></div>
                    <div class="sig-name">{{ $printedBy }}</div>
                    <div class="sig-sub">{{ $printedRole }} &bull; Tgl: {{ $printedDateOnly }}</div>
                </td>
                <td class="sig-card">
                    <div class="sig-role-label">Mengetahui &amp; Menyetujui:</div>
                    <div class="sig-role-title">{{ $coordinatorTitle }}</div>
                    <div class="sig-space"></div>
                    <div class="sig-name">{{ $coordinatorName }}</div>
                    <div class="sig-sub">Koordinator IT Support PLTU Karangkandri &bull; Tgl: {{ $printedDateOnly }}</div>
                </td>
            </tr>
        </table>

        <!-- Footer Identitas Dokumen Cetak -->
        <div class="doc-footer">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: left;">FORM-IT-PM-BA-REV.02 &bull; PT Sumber Segara Primadaya (PLTU Karangkandri Cilacap)</td>
                    <td style="text-align: right;">Dokumen Berita Acara Sah &bull; IT Service Desk &amp; PM</td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>
