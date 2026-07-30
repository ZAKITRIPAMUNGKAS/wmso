<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Rack;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with(['product', 'warehouse', 'rack', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($pq) use ($search) {
                    $pq->where('nama', 'like', "%{$search}%")
                       ->orWhere('sku', 'like', "%{$search}%");
                })
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('reference_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        $movements = $query->paginate(20)->withQueryString();

        $products = Product::select('id', 'nama', 'sku')->orderBy('nama')->get();
        $warehouses = Warehouse::select('id', 'nama')->orderBy('nama')->get();
        $racks = Rack::select('id', 'nama_rak', 'warehouse_id')->orderBy('nama_rak')->get();

        return Inertia::render('StockMovements/Index', [
            'movements' => $movements,
            'products' => $products,
            'warehouses' => $warehouses,
            'racks' => $racks,
            'filters' => $request->only(['search', 'type', 'warehouse_id', 'product_id', 'start_date', 'end_date'])
        ]);
    }
}
