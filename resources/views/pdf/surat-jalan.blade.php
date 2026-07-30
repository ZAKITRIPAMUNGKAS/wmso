<!DOCTYPE html>
<html>
<head>
    <title>Surat Jalan {{ $deliveryOrder->no_sj }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #334155; line-height: 1.5; }
        .kop-surat { border-bottom: 2px solid #025cca; padding-bottom: 15px; margin-bottom: 25px; }
        .company-name { margin: 0; font-size: 20px; font-weight: bold; color: #1e293b; text-transform: uppercase; letter-spacing: -0.5px; }
        .company-info { margin: 2px 0; font-size: 10px; color: #64748b; }
        
        .title { text-align: center; font-size: 18px; font-weight: 900; margin-top: 20px; margin-bottom: 5px; color: #1e293b; text-transform: uppercase; }
        .no-sj { text-align: center; font-size: 12px; font-weight: bold; color: #025cca; margin-bottom: 30px; }

        .info-table { width: 100%; margin-bottom: 30px; }
        .info-label { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table.items th { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 12px 10px; text-align: left; font-size: 9px; font-weight: bold; color: #475569; text-transform: uppercase; }
        table.items td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        
        .signature-section { margin-top: 60px; width: 100%; }
        .signature-box { text-align: center; width: 30%; }
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
                    <div style="background-color: #1e293b; color: white; padding: 8px 15px; display: inline-block; font-weight: bold; border-radius: 4px;">SURAT JALAN</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-table" cellpadding="0" cellspacing="0">
        <tr>
            <td width="60%">
                <div class="info-label">Dikirim Kepada:</div>
                <div style="font-size: 13px; font-weight: bold; color: #1e293b;">{{ $deliveryOrder->customer->nama }}</div>
                <div style="margin-top: 5px; width: 250px; color: #64748b;">{{ $deliveryOrder->customer->alamat }}</div>
            </td>
            <td width="40%">
                <table width="100%">
                    <tr>
                        <td class="info-label">No. Surat Jalan</td>
                        <td style="text-align: right; font-weight: bold;">{{ $deliveryOrder->no_sj }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Tanggal Kirim</td>
                        <td style="text-align: right;">{{ $deliveryOrder->tanggal->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Gudang Asal</td>
                        <td style="text-align: right; font-weight: bold; color: #025cca;">{{ $deliveryOrder->warehouse->nama }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th width="40">No</th>
                <th width="150">Kode Barang</th>
                <th>Nama Produk</th>
                <th width="100" style="text-align: center;">Kuantitas</th>
                <th width="100">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deliveryOrder->items as $index => $item)
            <tr>
                <td style="text-align: center; color: #94a3b8;">{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-weight: bold;">{{ $item->product->kode_barang }}</td>
                <td>
                    <div style="font-weight: bold; color: #1e293b;">{{ $item->product->nama }}</div>
                    <div style="font-size: 9px; color: #94a3b8; margin-top: 2px;">Merk: {{ $item->product->merk }} / Tipe: {{ $item->product->tipe }}</div>
                </td>
                <td style="text-align: center; font-weight: bold; font-size: 13px;">{{ $item->quantity }}</td>
                <td style="color: #94a3b8; font-style: italic;">{{ $item->product->satuan }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 10px; color: #64748b;">
        <strong>Keterangan:</strong> Barang telah diperiksa dan diterima dalam kondisi baik dan cukup.
    </div>

    <div class="signature-section">
        <table width="100%">
            <tr>
                <td class="signature-box">
                    <p style="margin-bottom: 60px;">Penerima / Customer,</p>
                    <p style="font-weight: bold;">( ................................. )</p>
                </td>
                <td class="signature-box">
                    <p style="margin-bottom: 60px;">Sopir / Pengirim,</p>
                    <p style="font-weight: bold;">( ................................. )</p>
                </td>
                <td class="signature-box">
                    <p style="margin-bottom: 60px;">Hormat Kami,</p>
                    <p style="font-weight: bold;">{{ \App\Models\CompanySetting::get('company_name') }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div style="position: fixed; bottom: 0; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 8px; color: #94a3b8; text-align: center;">
        Dokumen ini adalah bukti pengiriman yang sah dari sistem WMS {{ \App\Models\CompanySetting::get('company_short_name') }}
    </div>
</body>
</html>
