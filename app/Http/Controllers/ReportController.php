<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $type      = $request->input('type', 'masuk');
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $data    = collect();
        $summary = ['total_nilai' => 0, 'total_qty' => 0, 'total_transaksi' => 0, 'avg_per_transaksi' => 0, 'label_nilai' => 'Total Nilai', 'label_qty' => 'Total Qty'];
        $chartData  = [];
        $top5Pihak  = [];
        $top5Produk = [];

        if ($type === 'masuk') {
            $baseQuery = fn() => DB::table('goods_receipt_items')
                ->join('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
                ->leftJoin('purchase_orders', 'goods_receipts.purchase_order_id', '=', 'purchase_orders.id')
                ->join('products', 'goods_receipt_items.product_id', '=', 'products.id')
                ->leftJoin('suppliers', function ($join) {
                    $join->on('suppliers.id', '=', 'goods_receipts.supplier_id')
                         ->orOn('suppliers.id', '=', 'purchase_orders.supplier_id');
                })
                ->whereBetween('goods_receipts.tanggal', [$startDate, $endDate]);

            $summary['total_qty']        = (int) $baseQuery()->sum('goods_receipt_items.quantity');
            $summary['total_nilai']      = (float) $baseQuery()->sum(DB::raw('goods_receipt_items.quantity * products.harga'));
            $summary['total_transaksi']  = (int) DB::table('goods_receipts')->whereBetween('tanggal', [$startDate, $endDate])->count();
            $summary['avg_per_transaksi'] = $summary['total_transaksi'] > 0
                ? round($summary['total_nilai'] / $summary['total_transaksi'])
                : 0;
            $summary['label_nilai'] = 'Estimasi Nilai Barang Masuk';
            $summary['label_qty']   = 'Total Qty Masuk';

            $data = $baseQuery()->select(
                'goods_receipts.tanggal as tgl',
                'goods_receipts.no_receipt as ref',
                DB::raw("COALESCE(suppliers.nama, 'Tanpa Supplier') as pihak"),
                'products.nama as product',
                'goods_receipt_items.quantity as qty',
                'goods_receipt_items.satuan as satuan',
                DB::raw('products.harga as harga_satuan'),
                DB::raw('(goods_receipt_items.quantity * products.harga) as total')
            )->orderBy('tgl', 'desc')->get();

            // Chart: distribusi harian
            $daily = $baseQuery()
                ->select(DB::raw('DATE(goods_receipts.tanggal) as tgl'), DB::raw('SUM(goods_receipt_items.quantity * products.harga) as nilai'))
                ->groupBy('tgl')->orderBy('tgl')->get();
            $chartData = $this->buildDailyChart($daily, $startDate, $endDate, 'nilai');

            // Top 5 Supplier by nilai
            $top5Pihak = $baseQuery()
                ->select(DB::raw("COALESCE(suppliers.nama, 'Tanpa Supplier') as nama"), DB::raw('SUM(goods_receipt_items.quantity * products.harga) as total'))
                ->groupBy('nama')->orderByDesc('total')->limit(5)->get();

            // Top 5 Produk by qty
            $top5Produk = $baseQuery()
                ->select('products.nama', DB::raw('SUM(goods_receipt_items.quantity) as total'))
                ->groupBy('products.nama')->orderByDesc('total')->limit(5)->get();

        } elseif ($type === 'keluar') {
            $baseQuery = fn() => DB::table('delivery_order_items')
                ->join('delivery_orders', 'delivery_order_items.delivery_order_id', '=', 'delivery_orders.id')
                ->join('products', 'delivery_order_items.product_id', '=', 'products.id')
                ->join('customers', 'delivery_orders.customer_id', '=', 'customers.id')
                ->whereBetween('delivery_orders.tanggal', [$startDate, $endDate]);

            $summary['total_qty']        = (int) $baseQuery()->sum('delivery_order_items.quantity');
            $summary['total_nilai']      = (float) $baseQuery()->sum('delivery_order_items.subtotal');
            $summary['total_transaksi']  = (int) DB::table('delivery_orders')->whereBetween('tanggal', [$startDate, $endDate])->count();
            $summary['avg_per_transaksi'] = $summary['total_transaksi'] > 0
                ? round($summary['total_nilai'] / $summary['total_transaksi'])
                : 0;
            $summary['label_nilai'] = 'Total Penjualan';
            $summary['label_qty']   = 'Total Brg Keluar';

            $data = $baseQuery()->select(
                'delivery_orders.tanggal as tgl',
                'delivery_orders.no_sj as ref',
                'customers.nama as pihak',
                'products.nama as product',
                'delivery_order_items.quantity as qty',
                DB::raw("'pcs' as satuan"),
                DB::raw('(delivery_order_items.subtotal / delivery_order_items.quantity) as harga_satuan'),
                'delivery_order_items.subtotal as total'
            )->orderBy('tgl', 'desc')->get();

            $daily = $baseQuery()
                ->select(DB::raw('DATE(delivery_orders.tanggal) as tgl'), DB::raw('SUM(delivery_order_items.subtotal) as nilai'))
                ->groupBy('tgl')->orderBy('tgl')->get();
            $chartData = $this->buildDailyChart($daily, $startDate, $endDate, 'nilai');

            $top5Pihak = $baseQuery()
                ->select('customers.nama', DB::raw('SUM(delivery_order_items.subtotal) as total'))
                ->groupBy('customers.nama')->orderByDesc('total')->limit(5)->get();

            $top5Produk = $baseQuery()
                ->select('products.nama', DB::raw('SUM(delivery_order_items.quantity) as total'))
                ->groupBy('products.nama')->orderByDesc('total')->limit(5)->get();

        } elseif ($type === 'stok') {
            $items = DB::table('product_stocks')
                ->join('products', 'product_stocks.product_id', '=', 'products.id')
                ->join('warehouses', 'product_stocks.warehouse_id', '=', 'warehouses.id')
                ->where('product_stocks.quantity', '>', 0)
                ->select('products.nama as product', 'products.harga as harga_satuan', 'warehouses.nama as pihak', 'product_stocks.quantity as qty', DB::raw('"unit" as satuan'))
                ->get();

            $totalNilai = 0;
            $totalQty   = 0;
            $formatted  = [];

            foreach ($items as $item) {
                $val = $item->qty * $item->harga_satuan;
                $totalNilai += $val;
                $totalQty   += $item->qty;
                $formatted[] = [
                    'tgl' => Carbon::today()->format('Y-m-d'), 'ref' => 'STOCK',
                    'pihak' => $item->pihak, 'product' => $item->product,
                    'qty' => $item->qty, 'satuan' => $item->satuan,
                    'harga_satuan' => $item->harga_satuan, 'total' => $val,
                ];
            }

            $data = collect($formatted);
            $summary = array_merge($summary, [
                'total_nilai' => $totalNilai, 'total_qty' => $totalQty,
                'total_transaksi' => count($formatted),
                'avg_per_transaksi' => count($formatted) > 0 ? round($totalNilai / count($formatted)) : 0,
                'label_nilai' => 'Valuasi Aset Stok', 'label_qty' => 'Total Stok Gudang',
            ]);

            $top5Produk = $items->sortByDesc('qty')->take(5)->values()->map(fn($i) => (object)['nama' => $i->product, 'total' => $i->qty]);
            $top5Pihak  = $items->groupBy('pihak')->map(fn($g) => (object)['nama' => $g->first()->pihak, 'total' => $g->sum(fn($i) => $i->qty * $i->harga_satuan)])->sortByDesc('total')->take(5)->values();
        }

        return Inertia::render('Reports/Index', [
            'reportData'  => $data,
            'summary'     => $summary,
            'chartData'   => $chartData,
            'top5Pihak'   => $top5Pihak,
            'top5Produk'  => $top5Produk,
            'filters'     => ['type' => $type, 'start_date' => $startDate, 'end_date' => $endDate],
        ]);
    }

    // ─────────────────────────────────────────────
    private function buildDailyChart($daily, $startDate, $endDate, $valueKey): array
    {
        $map    = $daily->keyBy('tgl');
        $labels = [];
        $values = [];

        $current = Carbon::parse($startDate);
        $end     = Carbon::parse($endDate);

        while ($current->lte($end)) {
            $key      = $current->format('Y-m-d');
            $labels[] = $current->format('d');
            $values[] = isset($map[$key]) ? (float) $map[$key]->$valueKey : 0;
            $current->addDay();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    // ─────────────────────────────────────────────
    public function print(Request $request)
    {
        $type      = $request->input('type', 'masuk');
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $data   = collect();
        $summary = ['total_nilai' => 0, 'total_qty' => 0, 'total_transaksi' => 0, 'label_nilai' => 'Total Nilai', 'label_qty' => 'Total Qty'];
        $typeLabel = match($type) { 'masuk' => 'LAPORAN BARANG MASUK', 'keluar' => 'LAPORAN BARANG KELUAR', default => 'LAPORAN STOK GUDANG' };

        if ($type === 'masuk') {
            $q = DB::table('goods_receipt_items')
                ->join('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
                ->leftJoin('purchase_orders', 'goods_receipts.purchase_order_id', '=', 'purchase_orders.id')
                ->join('products', 'goods_receipt_items.product_id', '=', 'products.id')
                ->leftJoin('suppliers', function ($join) {
                    $join->on('suppliers.id', '=', 'goods_receipts.supplier_id')
                         ->orOn('suppliers.id', '=', 'purchase_orders.supplier_id');
                })
                ->whereBetween('goods_receipts.tanggal', [$startDate, $endDate]);

            $data = $q->select(
                'goods_receipts.tanggal as tgl', 'goods_receipts.no_receipt as ref',
                DB::raw("COALESCE(suppliers.nama, 'Tanpa Supplier') as pihak"),
                'products.nama as product', 'goods_receipt_items.quantity as qty',
                'goods_receipt_items.satuan as satuan',
                DB::raw('products.harga as harga_satuan'),
                DB::raw('(goods_receipt_items.quantity * products.harga) as total')
            )->orderBy('tgl')->get();

            $summary = [
                'total_qty'       => (int) $q->sum('goods_receipt_items.quantity'),
                'total_nilai'     => (float) $q->sum(DB::raw('goods_receipt_items.quantity * products.harga')),
                'total_transaksi' => (int) DB::table('goods_receipts')->whereBetween('tanggal', [$startDate, $endDate])->count(),
                'label_nilai'     => 'Estimasi Nilai',
                'label_qty'       => 'Total Qty',
            ];

        } elseif ($type === 'keluar') {
            $q = DB::table('delivery_order_items')
                ->join('delivery_orders', 'delivery_order_items.delivery_order_id', '=', 'delivery_orders.id')
                ->join('products', 'delivery_order_items.product_id', '=', 'products.id')
                ->join('customers', 'delivery_orders.customer_id', '=', 'customers.id')
                ->whereBetween('delivery_orders.tanggal', [$startDate, $endDate]);

            $data = $q->select(
                'delivery_orders.tanggal as tgl', 'delivery_orders.no_sj as ref',
                'customers.nama as pihak', 'products.nama as product',
                'delivery_order_items.quantity as qty', DB::raw("'pcs' as satuan"),
                DB::raw('ROUND(delivery_order_items.subtotal / delivery_order_items.quantity) as harga_satuan'),
                'delivery_order_items.subtotal as total'
            )->orderBy('tgl')->get();

            $summary = [
                'total_qty'       => (int) $q->sum('delivery_order_items.quantity'),
                'total_nilai'     => (float) $q->sum('delivery_order_items.subtotal'),
                'total_transaksi' => (int) DB::table('delivery_orders')->whereBetween('tanggal', [$startDate, $endDate])->count(),
                'label_nilai'     => 'Total Penjualan',
                'label_qty'       => 'Total Qty',
            ];

        } elseif ($type === 'stok') {
            $data = DB::table('product_stocks')
                ->join('products', 'product_stocks.product_id', '=', 'products.id')
                ->join('warehouses', 'product_stocks.warehouse_id', '=', 'warehouses.id')
                ->where('product_stocks.quantity', '>', 0)
                ->select(
                    DB::raw("'" . Carbon::today()->format('Y-m-d') . "' as tgl"),
                    DB::raw("'STOCK' as ref"), 'warehouses.nama as pihak', 'products.nama as product',
                    'product_stocks.quantity as qty', DB::raw("'unit' as satuan"),
                    'products.harga as harga_satuan',
                    DB::raw('(product_stocks.quantity * products.harga) as total')
                )->get();

            $summary = [
                'total_qty'       => (int) $data->sum('qty'),
                'total_nilai'     => (float) $data->sum('total'),
                'total_transaksi' => $data->count(),
                'label_nilai'     => 'Valuasi Stok',
                'label_qty'       => 'Total Stok',
            ];
        }

        return Inertia::render('Reports/Print', [
            'reportData' => $data,
            'summary'    => $summary,
            'typeLabel'  => $typeLabel,
            'filters'    => ['type' => $type, 'start_date' => $startDate, 'end_date' => $endDate],
        ]);
    }

    // ─────────────────────────────────────────────
    public function exportExcel(Request $request)
    {
        $type      = $request->input('type', 'masuk');
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $typeLabel = match($type) { 'masuk' => 'BARANG MASUK', 'keluar' => 'BARANG KELUAR', default => 'STOK GUDANG' };
        $filename  = "Laporan_{$typeLabel}_{$startDate}_sd_{$endDate}.csv";

        $data = collect();

        if ($type === 'masuk') {
            $data = DB::table('goods_receipt_items')
                ->join('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
                ->leftJoin('purchase_orders', 'goods_receipts.purchase_order_id', '=', 'purchase_orders.id')
                ->join('products', 'goods_receipt_items.product_id', '=', 'products.id')
                ->leftJoin('suppliers', function ($join) {
                    $join->on('suppliers.id', '=', 'goods_receipts.supplier_id')
                         ->orOn('suppliers.id', '=', 'purchase_orders.supplier_id');
                })
                ->whereBetween('goods_receipts.tanggal', [$startDate, $endDate])
                ->select(
                    'goods_receipts.tanggal as Tanggal',
                    'goods_receipts.no_receipt as No_Referensi',
                    DB::raw("COALESCE(suppliers.nama, 'Tanpa Supplier') as Supplier"),
                    'products.nama as Produk',
                    'goods_receipt_items.quantity as Qty',
                    'goods_receipt_items.satuan as Satuan',
                    DB::raw('products.harga as Harga_Satuan'),
                    DB::raw('(goods_receipt_items.quantity * products.harga) as Total')
                )->orderBy('goods_receipts.tanggal', 'desc')->get();

        } elseif ($type === 'keluar') {
            $data = DB::table('delivery_order_items')
                ->join('delivery_orders', 'delivery_order_items.delivery_order_id', '=', 'delivery_orders.id')
                ->join('products', 'delivery_order_items.product_id', '=', 'products.id')
                ->join('customers', 'delivery_orders.customer_id', '=', 'customers.id')
                ->whereBetween('delivery_orders.tanggal', [$startDate, $endDate])
                ->select(
                    'delivery_orders.tanggal as Tanggal', 'delivery_orders.no_sj as No_Referensi',
                    'customers.nama as Customer', 'products.nama as Produk',
                    'delivery_order_items.quantity as Qty', DB::raw("'pcs' as Satuan"),
                    DB::raw('ROUND(delivery_order_items.subtotal / delivery_order_items.quantity) as Harga_Satuan'),
                    'delivery_order_items.subtotal as Total'
                )->orderBy('delivery_orders.tanggal', 'desc')->get();

        } elseif ($type === 'stok') {
            $data = DB::table('product_stocks')
                ->join('products', 'product_stocks.product_id', '=', 'products.id')
                ->join('warehouses', 'product_stocks.warehouse_id', '=', 'warehouses.id')
                ->where('product_stocks.quantity', '>', 0)
                ->select(
                    DB::raw("'" . Carbon::today()->format('Y-m-d') . "' as Tanggal"),
                    DB::raw("'STOCK' as No_Referensi"), 'warehouses.nama as Gudang',
                    'products.nama as Produk', 'product_stocks.quantity as Qty',
                    DB::raw("'unit' as Satuan"), 'products.harga as Harga_Satuan',
                    DB::raw('(product_stocks.quantity * products.harga) as Total')
                )->get();
        }

        $companyName = company('company_name') ?? 'CV. Listrindo Jaya Elektrik';
        $filename    = "Laporan_{$typeLabel}_{$startDate}_sd_{$endDate}.xlsx";

        return Excel::download(
            new ReportExport($data, $typeLabel, $startDate, $endDate, $companyName),
            $filename
        );
    }
}
