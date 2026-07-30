<!DOCTYPE html>
<html>
<head>
    <title>Invoice {{ $invoice->no_invoice }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #334155; line-height: 1.5; }
        .kop-surat { border-bottom: 2px solid #025cca; padding-bottom: 15px; margin-bottom: 25px; }
        .company-name { margin: 0; font-size: 20px; font-weight: bold; color: #1e293b; text-transform: uppercase; letter-spacing: -0.5px; }
        .company-info { margin: 2px 0; font-size: 10px; color: #64748b; }
        
        .invoice-title { text-align: center; font-size: 18px; font-weight: 900; margin-top: 20px; margin-bottom: 5px; color: #1e293b; }
        .invoice-no { text-align: center; font-size: 12px; font-weight: bold; color: #025cca; margin-bottom: 30px; }

        .info-table { width: 100%; margin-bottom: 30px; }
        .info-label { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table.items th { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 12px 10px; text-align: left; font-size: 9px; font-weight: bold; color: #475569; text-transform: uppercase; }
        table.items td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        
        .total-box { float: right; width: 250px; background-color: #1e293b; color: white; padding: 15px; border-radius: 8px; margin-top: 20px; }
        .total-label { font-size: 10px; font-weight: bold; color: #94a3b8; text-transform: uppercase; }
        .total-value { font-size: 18px; font-weight: 900; float: right; }

        .signature-section { margin-top: 60px; width: 100%; }
        .signature-box { text-align: center; width: 40%; }
    </style>
</head>
<body>
    <div class="kop-surat">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <h1 class="company-name">{{ \App\Models\CompanySetting::get('company_name') }}</h1>
                    <p class="company-info">{{ \App\Models\CompanySetting::getAddressFull() }}</p>
                    <p class="company-info">
                        Telp: {{ \App\Models\CompanySetting::get('phone_primary') }} 
                        @if(\App\Models\CompanySetting::get('phone_secondary')) / {{ \App\Models\CompanySetting::get('phone_secondary') }} @endif
                    </p>
                </td>
                <td width="150" style="text-align: right; vertical-align: top;">
                    <div style="background-color: #025cca; color: white; padding: 8px 15px; display: inline-block; font-weight: bold; border-radius: 4px;">INVOICE</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-table" cellpadding="0" cellspacing="0">
        <tr>
            <td width="60%">
                <div class="info-label">Tagihan Kepada:</div>
                <div style="font-size: 13px; font-weight: bold; color: #1e293b;">{{ $invoice->deliveryOrder->customer->nama }}</div>
                <div style="margin-top: 5px; width: 250px; color: #64748b;">{{ $invoice->deliveryOrder->customer->alamat }}</div>
            </td>
            <td width="40%">
                <table width="100%">
                    <tr>
                        <td class="info-label">No. Invoice</td>
                        <td style="text-align: right; font-weight: bold;">{{ $invoice->no_invoice }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Tanggal</td>
                        <td style="text-align: right;">{{ $invoice->tanggal }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Jatuh Tempo</td>
                        <td style="text-align: right; color: #ef4444; font-weight: bold;">{{ $invoice->due_date }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th width="40">No</th>
                <th>Deskripsi Produk</th>
                <th width="80" style="text-align: center;">Qty</th>
                <th width="120" style="text-align: right;">Harga Satuan</th>
                <th width="120" style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->deliveryOrder->items as $index => $item)
            <tr>
                <td style="text-align: center; color: #94a3b8;">{{ $index + 1 }}</td>
                <td>
                    <div style="font-weight: bold; color: #1e293b;">{{ $item->product->nama }}</div>
                    <div style="font-size: 9px; color: #94a3b8; margin-top: 2px;">{{ $item->product->kode_barang }} - {{ $item->product->merk }}</div>
                </td>
                <td style="text-align: center;">{{ $item->quantity }} {{ $item->product->satuan }}</td>
                <td style="text-align: right;">Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                <td style="text-align: right; font-weight: bold;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="overflow: hidden;">
        <div class="total-box">
            <span class="total-label">Total Tagihan</span>
            <span class="total-value">Rp {{ number_format($invoice->total, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="signature-section">
        <table width="100%">
            <tr>
                <td class="signature-box">
                    <p style="margin-bottom: 60px;">Diterima Oleh,</p>
                    <p style="font-weight: bold; text-decoration: underline;">( ................................. )</p>
                    <p style="font-size: 8px; color: #94a3b8; margin-top: 5px;">Tanda Tangan & Nama Terang</p>
                </td>
                <td width="20%"></td>
                <td class="signature-box">
                    <p style="margin-bottom: 60px;">Hormat Kami,</p>
                    <p style="font-weight: bold;">{{ \App\Models\CompanySetting::get('company_name') }}</p>
                    <p style="font-size: 8px; color: #94a3b8; margin-top: 5px;">Bagian Keuangan</p>
                </td>
            </tr>
        </table>
    </div>

    <div style="position: fixed; bottom: 0; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 8px; color: #94a3b8; text-align: center;">
        Dokumen ini dibuat secara otomatis oleh sistem WMS {{ \App\Models\CompanySetting::get('company_short_name') }} pada {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>
