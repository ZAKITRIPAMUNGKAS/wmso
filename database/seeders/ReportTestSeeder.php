<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportTestSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan ada warehouse, supplier, product
        $warehouseId = DB::table('warehouses')->value('id');
        $userId = DB::table('users')->value('id');

        if (!$warehouseId || !$userId) {
            $this->command->warn('Tidak ada warehouse/user. Buat dulu via seeder atau UI.');
            return;
        }

        // Suppliers
        $suppliers = [
            ['nama' => 'PT Teknologi Maju', 'email' => 'order@teknologimaju.co.id', 'telepon' => '021-5551001', 'alamat' => 'Jl. Industri No. 1, Jakarta'],
            ['nama' => 'CV Elektrika Nusantara', 'email' => 'sales@elektrika.co.id', 'telepon' => '021-5552002', 'alamat' => 'Jl. Elektronik No. 5, Bekasi'],
            ['nama' => 'PT Sinar Kabel Indonesia', 'email' => 'info@sinarkabel.com', 'telepon' => '021-5553003', 'alamat' => 'Jl. Kabel Raya No. 12, Tangerang'],
            ['nama' => 'UD Lampu Terang', 'email' => 'order@lamputerang.id', 'telepon' => '021-5554004', 'alamat' => 'Jl. Lampu No. 8, Depok'],
        ];

        $supplierIds = [];
        foreach ($suppliers as $s) {
            $exists = DB::table('suppliers')->where('nama', $s['nama'])->value('id');
            if ($exists) {
                $supplierIds[] = $exists;
            } else {
                $supplierIds[] = DB::table('suppliers')->insertGetId(array_merge($s, [
                    'created_at' => now(), 'updated_at' => now()
                ]));
            }
        }

        // Products
        $products = [
            ['nama' => 'Kabel UTP Cat 6', 'sku' => 'KBL-UTP-C6', 'harga' => 1200000, 'satuan' => 'roll'],
            ['nama' => 'Kabel NYM 3x2.5mm', 'sku' => 'KBL-NYM-325', 'harga' => 850000, 'satuan' => 'roll'],
            ['nama' => 'MCB 1 Phase 10A', 'sku' => 'MCB-1P-10A', 'harga' => 75000, 'satuan' => 'pcs'],
            ['nama' => 'MCB 3 Phase 32A', 'sku' => 'MCB-3P-32A', 'harga' => 285000, 'satuan' => 'pcs'],
            ['nama' => 'Panel Distribusi 12 Group', 'sku' => 'PNL-DIST-12', 'harga' => 1750000, 'satuan' => 'unit'],
            ['nama' => 'Saklar Ganda Broco', 'sku' => 'SKL-GND-BRC', 'harga' => 35000, 'satuan' => 'pcs'],
            ['nama' => 'Stop Kontak 3 Lubang', 'sku' => 'SK-3LBG', 'harga' => 42000, 'satuan' => 'pcs'],
            ['nama' => 'Lampu LED 18W Philips', 'sku' => 'LMP-LED-18W', 'harga' => 95000, 'satuan' => 'pcs'],
            ['nama' => 'Lampu TL 40W Osram', 'sku' => 'LMP-TL-40W', 'harga' => 65000, 'satuan' => 'pcs'],
            ['nama' => 'Conduit PVC 20mm', 'sku' => 'CDT-PVC-20', 'harga' => 28000, 'satuan' => 'btg'],
        ];

        $productIds = [];
        foreach ($products as $p) {
            $exists = DB::table('products')->where('sku', $p['sku'])->value('id');
            if ($exists) {
                $productIds[$p['sku']] = $exists;
            } else {
                $id = DB::table('products')->insertGetId([
                    'nama' => $p['nama'],
                    'sku' => $p['sku'],
                    'harga' => $p['harga'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $productIds[$p['sku']] = $id;
            }
        }

        // 25 Transaksi dummy April 2026
        $transactions = [
            ['day' => 1,  'sup' => 0, 'items' => [['KBL-UTP-C6', 10, 'roll'], ['KBL-NYM-325', 5, 'roll']]],
            ['day' => 2,  'sup' => 1, 'items' => [['MCB-1P-10A', 50, 'pcs'], ['MCB-3P-32A', 20, 'pcs']]],
            ['day' => 3,  'sup' => 2, 'items' => [['KBL-UTP-C6', 15, 'roll']]],
            ['day' => 4,  'sup' => 3, 'items' => [['LMP-LED-18W', 100, 'pcs'], ['LMP-TL-40W', 50, 'pcs']]],
            ['day' => 5,  'sup' => 0, 'items' => [['PNL-DIST-12', 3, 'unit']]],
            ['day' => 7,  'sup' => 1, 'items' => [['SKL-GND-BRC', 200, 'pcs'], ['SK-3LBG', 150, 'pcs']]],
            ['day' => 8,  'sup' => 2, 'items' => [['CDT-PVC-20', 100, 'btg'], ['KBL-NYM-325', 8, 'roll']]],
            ['day' => 9,  'sup' => 3, 'items' => [['LMP-LED-18W', 60, 'pcs']]],
            ['day' => 10, 'sup' => 0, 'items' => [['KBL-UTP-C6', 20, 'roll'], ['MCB-1P-10A', 30, 'pcs']]],
            ['day' => 11, 'sup' => 1, 'items' => [['PNL-DIST-12', 2, 'unit'], ['MCB-3P-32A', 15, 'pcs']]],
            ['day' => 12, 'sup' => 2, 'items' => [['SKL-GND-BRC', 300, 'pcs']]],
            ['day' => 14, 'sup' => 3, 'items' => [['LMP-TL-40W', 80, 'pcs'], ['CDT-PVC-20', 50, 'btg']]],
            ['day' => 15, 'sup' => 0, 'items' => [['KBL-NYM-325', 12, 'roll']]],
            ['day' => 16, 'sup' => 1, 'items' => [['MCB-1P-10A', 100, 'pcs'], ['SK-3LBG', 200, 'pcs']]],
            ['day' => 17, 'sup' => 2, 'items' => [['KBL-UTP-C6', 8, 'roll'], ['CDT-PVC-20', 80, 'btg']]],
            ['day' => 18, 'sup' => 3, 'items' => [['LMP-LED-18W', 120, 'pcs']]],
            ['day' => 19, 'sup' => 0, 'items' => [['PNL-DIST-12', 5, 'unit'], ['MCB-3P-32A', 25, 'pcs']]],
            ['day' => 21, 'sup' => 1, 'items' => [['KBL-NYM-325', 6, 'roll'], ['SKL-GND-BRC', 150, 'pcs']]],
            ['day' => 22, 'sup' => 0, 'items' => [['KBL-UTP-C6', 200, 'roll']]],
            ['day' => 23, 'sup' => 2, 'items' => [['LMP-TL-40W', 100, 'pcs'], ['CDT-PVC-20', 60, 'btg']]],
            ['day' => 24, 'sup' => 3, 'items' => [['MCB-1P-10A', 80, 'pcs'], ['MCB-3P-32A', 30, 'pcs']]],
            ['day' => 25, 'sup' => 0, 'items' => [['PNL-DIST-12', 4, 'unit']]],
            ['day' => 26, 'sup' => 1, 'items' => [['KBL-UTP-C6', 12, 'roll'], ['LMP-LED-18W', 90, 'pcs']]],
            ['day' => 28, 'sup' => 2, 'items' => [['SK-3LBG', 250, 'pcs'], ['SKL-GND-BRC', 180, 'pcs']]],
            ['day' => 29, 'sup' => 3, 'items' => [['LMP-TL-40W', 60, 'pcs'], ['KBL-NYM-325', 10, 'roll']]],
        ];

        foreach ($transactions as $i => $t) {
            $tanggal = Carbon::create(2026, 4, $t['day'])->format('Y-m-d');
            $supplierId = $supplierIds[$t['sup']];
            $count = DB::table('goods_receipts')->whereDate('created_at', $tanggal)->count();
            $noReceipt = 'RCP-' . str_replace('-', '', $tanggal) . '-' . str_pad($count + $i + 1, 3, '0', STR_PAD_LEFT);

            $receiptId = DB::table('goods_receipts')->insertGetId([
                'no_receipt'        => $noReceipt,
                'purchase_order_id' => null,
                'supplier_id'       => $supplierId,
                'warehouse_id'      => $warehouseId,
                'tanggal'           => $tanggal,
                'catatan'           => 'Seed data dummy',
                'user_id'           => $userId,
                'created_at'        => Carbon::create(2026, 4, $t['day'], 9, 0, 0),
                'updated_at'        => Carbon::create(2026, 4, $t['day'], 9, 0, 0),
            ]);

            foreach ($t['items'] as [$sku, $qty, $satuan]) {
                $productId = $productIds[$sku];
                $harga = collect($products)->firstWhere('sku', $sku)['harga'];

                DB::table('goods_receipt_items')->insert([
                    'goods_receipt_id' => $receiptId,
                    'product_id'       => $productId,
                    'quantity'         => $qty,
                    'satuan'           => $satuan,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                // Adjust stock
                $stock = DB::table('product_stocks')
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->first();

                if ($stock) {
                    DB::table('product_stocks')
                        ->where('id', $stock->id)
                        ->increment('quantity', $qty);
                } else {
                    DB::table('product_stocks')->insert([
                        'product_id'   => $productId,
                        'warehouse_id' => $warehouseId,
                        'quantity'     => $qty,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }

                $saldoBefore = $stock ? $stock->quantity : 0;
                DB::table('stock_movements')->insert([
                    'product_id'     => $productId,
                    'warehouse_id'   => $warehouseId,
                    'type'           => 'in',
                    'quantity'       => $qty,
                    'reference_type' => 'goods_receipt',
                    'reference_id'   => $receiptId,
                    'saldo_sebelum'  => $saldoBefore,
                    'saldo_sesudah'  => $saldoBefore + $qty,
                    'user_id'        => $userId,
                    'created_at'     => Carbon::create(2026, 4, $t['day'], 9, 0, 0),
                    'updated_at'     => Carbon::create(2026, 4, $t['day'], 9, 0, 0),
                ]);
            }
        }

        $this->command->info('✅ 25 transaksi dummy April 2026 berhasil dibuat.');
    }
}
