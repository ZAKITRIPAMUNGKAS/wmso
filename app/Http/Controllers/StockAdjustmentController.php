<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\StockService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index()
    {
        return Inertia::render('StockAdjustments/Index', [
            'adjustments' => StockAdjustment::with(['warehouse', 'user'])
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create()
    {
        return Inertia::render('StockAdjustments/Create', [
            'warehouses' => Warehouse::all(),
            'products' => Product::all(),
        ]);
    }

    public function getWarehouseStocks(Warehouse $warehouse)
    {
        $stocks = ProductStock::where('warehouse_id', $warehouse->id)
            ->pluck('quantity', 'product_id');

        return response()->json($stocks);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'tanggal' => 'required|date',
            'status' => 'required|in:draft,completed',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_sistem' => 'required|integer|min:0',
            'items.*.quantity_fisik' => 'required|integer|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // If status is completed, pre-validate all stock availability for reductions
                if ($validated['status'] === 'completed') {
                    foreach ($validated['items'] as $item) {
                        $selisih = $item['quantity_fisik'] - $item['quantity_sistem'];
                        if ($selisih < 0) {
                            $absSelisih = abs($selisih);
                            $currentStock = $this->stockService->getStock($item['product_id'], $validated['warehouse_id']);
                            if ($currentStock < $absSelisih) {
                                $product = Product::find($item['product_id']);
                                throw new \Exception("Stok untuk produk '{$product->nama}' tidak mencukupi untuk pengurangan (Tersedia: {$currentStock}, Pengurangan: {$absSelisih}).");
                            }
                        }
                    }
                }

                $adjustment = StockAdjustment::create([
                    'no_adjustment' => $this->generateNoAdjustment(),
                    'warehouse_id' => $validated['warehouse_id'],
                    'tanggal' => $validated['tanggal'],
                    'status' => $validated['status'],
                    'catatan' => $validated['catatan'] ?? null,
                    'user_id' => Auth::id(),
                ]);

                foreach ($validated['items'] as $item) {
                    $selisih = $item['quantity_fisik'] - $item['quantity_sistem'];

                    StockAdjustmentItem::create([
                        'stock_adjustment_id' => $adjustment->id,
                        'product_id' => $item['product_id'],
                        'quantity_sistem' => $item['quantity_sistem'],
                        'quantity_fisik' => $item['quantity_fisik'],
                        'selisih' => $selisih,
                    ]);

                    if ($validated['status'] === 'completed' && $selisih !== 0) {
                        if ($selisih > 0) {
                            $this->stockService->adjustStock(
                                $item['product_id'],
                                $adjustment->warehouse_id,
                                $selisih,
                                'in',
                                'stock_adjustment',
                                $adjustment->id,
                                Auth::id()
                            );
                        } else {
                            $this->stockService->adjustStock(
                                $item['product_id'],
                                $adjustment->warehouse_id,
                                abs($selisih),
                                'out',
                                'stock_adjustment',
                                $adjustment->id,
                                Auth::id()
                            );
                        }
                    }
                }
            });

            return redirect()->route('stock-adjustments.index')->with('success', 'Penyesuaian stok berhasil dibuat!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat penyesuaian stok: ' . $e->getMessage());
        }
    }

    public function show(StockAdjustment $stock_adjustment)
    {
        $stock_adjustment->load(['items.product', 'warehouse', 'user']);

        return Inertia::render('StockAdjustments/Show', [
            'adjustment' => $stock_adjustment
        ]);
    }

    public function edit(StockAdjustment $stock_adjustment)
    {
        if ($stock_adjustment->status === 'completed') {
            return redirect()->route('stock-adjustments.index')->with('error', 'Penyesuaian stok yang sudah selesai tidak dapat diubah.');
        }

        $stock_adjustment->load('items');

        return Inertia::render('StockAdjustments/Edit', [
            'adjustment' => $stock_adjustment,
            'warehouses' => Warehouse::all(),
            'products' => Product::all(),
        ]);
    }

    public function update(Request $request, StockAdjustment $stock_adjustment)
    {
        if ($stock_adjustment->status === 'completed') {
            return redirect()->route('stock-adjustments.index')->with('error', 'Penyesuaian stok yang sudah selesai tidak dapat diubah.');
        }

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'tanggal' => 'required|date',
            'status' => 'required|in:draft,completed',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_sistem' => 'required|integer|min:0',
            'items.*.quantity_fisik' => 'required|integer|min:0',
        ]);

        try {
            DB::transaction(function () use ($stock_adjustment, $validated) {
                // If transitioning to completed, pre-validate all stock availability for reductions
                if ($validated['status'] === 'completed') {
                    foreach ($validated['items'] as $item) {
                        $selisih = $item['quantity_fisik'] - $item['quantity_sistem'];
                        if ($selisih < 0) {
                            $absSelisih = abs($selisih);
                            $currentStock = $this->stockService->getStock($item['product_id'], $validated['warehouse_id']);
                            if ($currentStock < $absSelisih) {
                                $product = Product::find($item['product_id']);
                                throw new \Exception("Stok untuk produk '{$product->nama}' tidak mencukupi untuk pengurangan (Tersedia: {$currentStock}, Pengurangan: {$absSelisih}).");
                            }
                        }
                    }
                }

                $stock_adjustment->update([
                    'warehouse_id' => $validated['warehouse_id'],
                    'tanggal' => $validated['tanggal'],
                    'status' => $validated['status'],
                    'catatan' => $validated['catatan'] ?? null,
                ]);

                // Delete old items and rebuild
                $stock_adjustment->items()->delete();

                foreach ($validated['items'] as $item) {
                    $selisih = $item['quantity_fisik'] - $item['quantity_sistem'];

                    StockAdjustmentItem::create([
                        'stock_adjustment_id' => $stock_adjustment->id,
                        'product_id' => $item['product_id'],
                        'quantity_sistem' => $item['quantity_sistem'],
                        'quantity_fisik' => $item['quantity_fisik'],
                        'selisih' => $selisih,
                    ]);

                    if ($validated['status'] === 'completed' && $selisih !== 0) {
                        if ($selisih > 0) {
                            $this->stockService->adjustStock(
                                $item['product_id'],
                                $stock_adjustment->warehouse_id,
                                $selisih,
                                'in',
                                'stock_adjustment',
                                $stock_adjustment->id,
                                Auth::id()
                            );
                        } else {
                            $this->stockService->adjustStock(
                                $item['product_id'],
                                $stock_adjustment->warehouse_id,
                                abs($selisih),
                                'out',
                                'stock_adjustment',
                                $stock_adjustment->id,
                                Auth::id()
                            );
                        }
                    }
                }
            });

            return redirect()->route('stock-adjustments.index')->with('success', 'Penyesuaian stok berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui penyesuaian stok: ' . $e->getMessage());
        }
    }

    public function destroy(StockAdjustment $stock_adjustment)
    {
        if ($stock_adjustment->status === 'completed') {
            return redirect()->route('stock-adjustments.index')->with('error', 'Penyesuaian stok yang sudah selesai tidak dapat dihapus.');
        }

        try {
            $stock_adjustment->delete(); // Cascade on delete handles the items
            return redirect()->route('stock-adjustments.index')->with('success', 'Penyesuaian stok berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus penyesuaian stok: ' . $e->getMessage());
        }
    }

    protected function generateNoAdjustment()
    {
        $date = date('Ymd');
        $count = StockAdjustment::whereDate('created_at', today())->count() + 1;
        return "ADJ-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
