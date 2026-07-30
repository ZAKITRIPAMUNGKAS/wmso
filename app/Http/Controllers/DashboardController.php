<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\GoodsReceipt;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Chart Data (7 Hari Terakhir)
        $startDate = Carbon::today()->subDays(6);
        
        $movements = StockMovement::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                'type',
                DB::raw('SUM(quantity) as total')
            )
            ->groupBy('date', 'type')
            ->get();

        $chartLabels = [];
        $dataIn = [];
        $dataOut = [];

        for ($i = 6; $i >= 0; $i--) {
            $dateObj = Carbon::today()->subDays($i);
            $dateStr = $dateObj->format('Y-m-d');
            
            // Nama hari bahasa Indonesia (Senin, Selasa, dll)
            // Carbon translatedFormat('D') bergantung pada locale. Kita gunakan format standar atau map manual.
            // Lebih aman menggunakan locale config atau array sederhana jika tidak yakin locale 'id' terpasang.
            $daysMap = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];
            $chartLabels[] = $daysMap[$dateObj->format('D')];
            
            $dataIn[] = (int) $movements->where('date', $dateStr)->where('type', 'in')->sum('total');
            $dataOut[] = (int) $movements->where('date', $dateStr)->where('type', 'out')->sum('total');
        }

        $chartData = [
            'labels' => $chartLabels,
            'datasets' => [
                [
                    'label' => 'Barang Masuk',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                    'borderColor' => '#4f46e5',
                    'data' => $dataIn,
                    'fill' => true,
                    'tension' => 0.4
                ],
                [
                    'label' => 'Barang Keluar',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'borderColor' => '#f59e0b',
                    'data' => $dataOut,
                    'fill' => true,
                    'tension' => 0.4
                ]
            ]
        ];

        // 2. Recent Transactions
        $recentTransactions = StockMovement::with('product')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // 3. ABC Analysis (velocity of stock out in the last 30 days)
        $thirtyDaysAgo = Carbon::today()->subDays(30);
        
        $productOutbound = StockMovement::where('type', 'out')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select('product_id', DB::raw('SUM(quantity) as total_out'))
            ->groupBy('product_id')
            ->orderBy('total_out', 'desc')
            ->get();

        $totalOutAll = $productOutbound->sum('total_out');
        $abcCounts = ['A' => 0, 'B' => 0, 'C' => 0];
        $categoryAMembers = [];
        $allProducts = Product::all();

        if ($totalOutAll > 0) {
            $runningSum = 0;
            foreach ($productOutbound as $po) {
                $runningSum += $po->total_out;
                $percentage = ($runningSum / $totalOutAll) * 100;
                $product = $allProducts->firstWhere('id', $po->product_id);
                if ($product) {
                    if ($percentage <= 70) {
                        $abcCounts['A']++;
                        if (count($categoryAMembers) < 5) {
                            $categoryAMembers[] = [
                                'kode_barang' => $product->kode_barang,
                                'nama' => $product->nama,
                                'total_out' => (int) $po->total_out
                            ];
                        }
                    } elseif ($percentage <= 90) {
                        $abcCounts['B']++;
                    } else {
                        $abcCounts['C']++;
                    }
                }
            }
        }

        // Products with no outbound movements in last 30 days are automatically Category C
        $movedProductIds = $productOutbound->pluck('product_id')->toArray();
        $noMoveCount = Product::whereNotIn('id', $movedProductIds)->count();
        $abcCounts['C'] += $noMoveCount;

        $abcAnalysis = [
            'counts' => $abcCounts,
            'fast_moving' => $categoryAMembers,
            'total_value' => $totalOutAll
        ];

        // 4. Low Stock warnings
        $lowStockProducts = Product::lowStock()->limit(5)->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'kode_barang' => $product->kode_barang,
                'nama' => $product->nama,
                'stok_minimum' => (int) $product->stok_minimum,
                'total_stock' => (int) $product->total_stock,
            ];
        });

        // 5. Expiry Warnings (nearest 5)
        $nearExpiry = \App\Models\ProductRackStock::with(['product', 'warehouse', 'rack'])
            ->whereNotNull('expired_at')
            ->where('expired_at', '>=', Carbon::today()->toDateString())
            ->orderBy('expired_at', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($rs) {
                return [
                    'product_name' => $rs->product->nama,
                    'kode_barang' => $rs->product->kode_barang,
                    'warehouse_name' => $rs->warehouse->nama,
                    'rack_code' => $rs->rack ? $rs->rack->kode_rak : 'Tanpa Rak',
                    'batch_number' => $rs->batch_number,
                    'expired_at' => Carbon::parse($rs->expired_at)->format('d M Y'),
                    'days_left' => (int) Carbon::today()->diffInDays(Carbon::parse($rs->expired_at), false),
                    'quantity' => $rs->quantity,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_products' => Product::count(),
                'total_stock' => (int) ProductStock::sum('quantity'),
                'today_receipts' => GoodsReceipt::whereDate('tanggal', today())->count(),
                'today_orders' => DeliveryOrder::whereDate('tanggal', today())->count(),
                'pending_invoices_amount' => (float) Invoice::where('status', '!=', 'lunas')->sum(DB::raw('total - paid_amount')),
                'total_suppliers' => \App\Models\Supplier::count(),
                'total_customers' => \App\Models\Customer::count(),
            ],
            'chartData' => $chartData,
            'recentTransactions' => $recentTransactions,
            'abcAnalysis' => $abcAnalysis,
            'lowStockProducts' => $lowStockProducts,
            'nearExpiry' => $nearExpiry,
        ]);
    }
}
