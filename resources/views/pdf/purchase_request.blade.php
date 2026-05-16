<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Request - {{ $pr->referensi_PR }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            padding: 30px;
        }

        /* ── Header ── */
        .header {
            border-bottom: 3px solid #c9a84c;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            font-weight: bold;
            color: #c9a84c;
            letter-spacing: 1px;
        }
        .header p {
            font-size: 11px;
            color: #666;
            margin-top: 2px;
        }

        /* ── Info Box ── */
        .info-grid {
            width: 100%;
            margin-bottom: 24px;
        }
        .info-grid td {
            padding: 4px 8px 4px 0;
            vertical-align: top;
            width: 25%;
        }
        .info-label {
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 12px;
            font-weight: bold;
            color: #1a1a1a;
            margin-top: 2px;
        }

        /* ── Status Badge ── */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-tertunda  { background: #fff3cd; color: #856404; }
        .badge-disetujui { background: #d1fae5; color: #065f46; }
        .badge-ditolak   { background: #fee2e2; color: #991b1b; }

        /* ── Section Title ── */
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #888;
            margin-bottom: 8px;
        }

        /* ── Table ── */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items thead tr {
            background-color: #c9a84c;
            color: #fff;
        }
        table.items thead th {
            padding: 8px 10px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }
        table.items thead th.right { text-align: right; }
        table.items tbody tr:nth-child(even) {
            background-color: #fafafa;
        }
        table.items tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        table.items tbody td.right { text-align: right; }

        /* ── Total Row ── */
        .total-row {
            width: 100%;
        }
        .total-row td {
            padding: 4px 0;
        }
        .total-row .total-label {
            text-align: right;
            padding-right: 16px;
            font-size: 11px;
            color: #555;
            width: 75%;
        }
        .total-row .total-value {
            text-align: right;
            font-weight: bold;
            font-size: 13px;
            color: #c9a84c;
            border-top: 2px solid #c9a84c;
            padding-top: 6px;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 12px;
            font-size: 10px;
            color: #aaa;
            text-align: center;
        }
    </style>
</head>
<body>

    {{-- ── Header ── --}}
    <div class="header">
        <h1>PURCHASE REQUEST</h1>
        <p>Universal Furniture &mdash; Dokumen Permintaan Pembelian</p>
    </div>

    {{-- ── Info Grid ── --}}
    <table class="info-grid">
        <tr>
            <td>
                <div class="info-label">No. Referensi</div>
                <div class="info-value">{{ $pr->referensi_PR }}</div>
            </td>
            <td>
                <div class="info-label">Tanggal</div>
                <div class="info-value">
                    {{ \Carbon\Carbon::parse($pr->tgl_PR)->translatedFormat('d F Y') }}
                </div>
            </td>
            <td>
                <div class="info-label">Dibuat Oleh</div>
                <div class="info-value">
                    {{ $pr->admin?->nama_admin ?? $pr->owner?->nama_owner ?? '-' }}
                </div>
            </td>
            <td>
                <div class="info-label">Status</div>
                <div class="info-value">
                    @php
                        $badgeClass = match($pr->status_PR) {
                            'disetujui' => 'badge-disetujui',
                            'ditolak'   => 'badge-ditolak',
                            default     => 'badge-tertunda',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">
                        {{ ucfirst($pr->status_PR) }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── Supplier ── --}}
    @php $supplier = $pr->details->first()?->supplier; @endphp
    @if($supplier)
    <div style="margin-bottom: 20px;">
        <div class="section-title">Supplier</div>
        <div style="font-weight: bold;">{{ $supplier->nama_supplier }}</div>
        <div style="color: #555; margin-top: 2px;">
            {{ $supplier->notelp_supplier ?? '-' }}
            @if($supplier->alamat_supplier)
                &nbsp;&middot;&nbsp; {{ $supplier->alamat_supplier }}
            @endif
        </div>
    </div>
    @endif

    {{-- ── Tabel Item ── --}}
    <div class="section-title">Detail Item</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th class="right">Harga Satuan</th>
                <th class="right">Kuantitas</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @foreach($pr->details as $i => $detail)
                @php
                    $subtotal   = $detail->hargabarangPR * $detail->kuantitasbarangPR;
                    $grandTotal += $subtotal;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $detail->barang->nama_barang ?? '-' }}</td>
                    <td>{{ $detail->barang->kategori ?? '-' }}</td>
                    <td class="right">Rp {{ number_format($detail->hargabarangPR, 0, ',', '.') }}</td>
                    <td class="right">{{ $detail->kuantitasbarangPR }} unit</td>
                    <td class="right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── Grand Total ── --}}
    <table class="total-row">
        <tr>
            <td class="total-label">Total Keseluruhan</td>
            <td class="total-value">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
        </tr>
    </table>

    {{-- ── Footer ── --}}
    <div class="footer">
        Dicetak pada {{ now()->translatedFormat('d F Y, H:i') }} WIB
        &nbsp;&middot;&nbsp; {{ $pr->referensi_PR }}
        &nbsp;&middot;&nbsp; Dokumen ini digenerate otomatis oleh sistem.
    </div>

</body>
</html>