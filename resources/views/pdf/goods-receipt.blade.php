<!DOCTYPE html>
<html>
<head>
    <title>Berita Acara Penerimaan Barang {{ $goodsReceipt->no_receipt }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #334155; line-height: 1.5; }
        .kop-surat { border-bottom: 2px solid #025cca; padding-bottom: 15px; margin-bottom: 25px; }
        .company-name { margin: 0; font-size: 20px; font-weight: bold; color: #1e293b; text-transform: uppercase; letter-spacing: -0.5px; }
        .company-info { margin: 2px 0; font-size: 10px; color: #64748b; }
        
        .info-table { width: 100%; margin-bottom: 25px; }
        .info-label { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table.items th { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 10px; text-align: left; font-size: 9px; font-weight: bold; color: #475569; text-transform: uppercase; }
        table.items td { padding: 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        
        .signature-section { margin-top: 50px; width: 100%; }
        .signature-box { text-align: center; width: 33%; }
    </style>
</head>
<body>
    <div class="kop-surat">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <h1 class="company-name">{{ \App\Models\CompanySetting::get('company_name') }}</h1>
                    <p class="company-info">{{ \App\Models\CompanySetting::getAddressFull() }}</p>
                    <p class="company-info">Telp: {{ \App\Models\CompanySetting::get('phone_primary') }}</p>
                </td>
                <td width="200" style="text-align: right; vertical-align: top;">
                    <div style="background-color: #025cca; color: white; padding: 8px 15px; display: inline-block; font-weight: bold; border-radius: 4px;">BERITA ACARA BARANG MASUK</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-table" cellpadding="0" cellspacing="0">
        <tr>
            <td width="50%">
                <div class="info-label">Supplier / Vendor:</div>
                <div style="font-size: 13px; font-weight: bold; color: #1e293b;">{{ $goodsReceipt->supplier ? $goodsReceipt->supplier->nama : ($goodsReceipt->purchaseOrder ? $goodsReceipt->purchaseOrder->supplier->nama : 'Tanpa Supplier') }}</div>
            </td>
            <td width="50%">
                <table width="100%">
                    <tr>
                        <td class="info-label">No. Penerimaan</td>
                        <td style="text-align: right; font-weight: bold;">{{ $goodsReceipt->no_receipt }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">No. Purchase Order</td>
                        <td style="text-align: right; font-weight: bold;">{{ $goodsReceipt->purchaseOrder ? $goodsReceipt->purchaseOrder->no_po : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Tanggal Masuk</td>
                        <td style="text-align: right;">{{ \Carbon\Carbon::parse($goodsReceipt->tanggal)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Gudang Tujuan</td>
                        <td style="text-align: right; font-weight: bold;">{{ $goodsReceipt->warehouse->nama }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Kode SKU</th>
                <th width="45%">Nama Produk</th>
                <th width="15%">Satuan</th>
                <th width="15%" style="text-align: right;">Qty Diterima</th>
            </tr>
        </thead>
        <tbody>
            @foreach($goodsReceipt->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-weight: bold;">{{ $item->product->sku }}</td>
                <td style="font-weight: bold; color: #1e293b;">{{ $item->product->nama }}</td>
                <td>{{ $item->satuan ?? 'PCS' }}</td>
                <td style="text-align: right; font-weight: bold;">{{ $item->quantity }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($goodsReceipt->catatan)
    <div style="background-color: #f8fafc; border: 1px border-slate-200; padding: 10px; border-radius: 6px; margin-bottom: 30px;">
        <div style="font-size: 9px; font-weight: bold; color: #64748b; uppercase">Catatan Penerimaan:</div>
        <div>{{ $goodsReceipt->catatan }}</div>
    </div>
    @endif

    <table class="signature-section" cellpadding="0" cellspacing="0">
        <tr>
            <td class="signature-box">
                <p>Diserahkan Oleh,</p>
                <div style="height: 60px;"></div>
                <p style="font-weight: bold;">( Supplier / Kurir )</p>
            </td>
            <td class="signature-box">
                <p>Petugas Gudang,</p>
                <div style="height: 60px;"></div>
                <p style="font-weight: bold;">( {{ $goodsReceipt->user->name ?? 'Staff Gudang' }} )</p>
            </td>
            <td class="signature-box">
                <p>Disetujui Kepala Gudang,</p>
                <div style="height: 60px;"></div>
                <p style="font-weight: bold;">( Supervisor Gudang )</p>
            </td>
        </tr>
    </table>
</body>
</html>
