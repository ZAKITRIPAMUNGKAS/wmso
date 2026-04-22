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
            'recentTransactions' => $recentTransactions
        ]);
    }
}
