<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockTransferController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index()
    {
        return Inertia::render('StockTransfers/Index', [
            'transfers' => StockTransfer::with(['sourceWarehouse', 'destinationWarehouse', 'user'])
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create()
    {
        return Inertia::render('StockTransfers/Create', [
            'warehouses' => Warehouse::all(),
            'products' => Product::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_warehouse_id' => 'required|exists:warehouses,id',
            'destination_warehouse_id' => 'required|exists:warehouses,id|different:source_warehouse_id',
            'tanggal' => 'required|date',
            'status' => 'required|in:draft,completed',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // If status is completed, pre-validate all stock availability
                if ($validated['status'] === 'completed') {
                    foreach ($validated['items'] as $item) {
                        $currentStock = $this->stockService->getStock($item['product_id'], $validated['source_warehouse_id']);
                        if ($currentStock < $item['quantity']) {
                            $product = Product::find($item['product_id']);
                            throw new \Exception("Stok untuk produk '{$product->nama}' tidak mencukupi di gudang asal (Tersedia: {$currentStock}, Diminta: {$item['quantity']}).");
                        }
                    }
                }

                $transfer = StockTransfer::create([
                    'no_transfer' => $this->generateNoTransfer(),
                    'source_warehouse_id' => $validated['source_warehouse_id'],
                    'destination_warehouse_id' => $validated['destination_warehouse_id'],
                    'tanggal' => $validated['tanggal'],
                    'status' => $validated['status'],
                    'catatan' => $validated['catatan'] ?? null,
                    'user_id' => Auth::id(),
                ]);

                foreach ($validated['items'] as $item) {
                    StockTransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);

                    if ($validated['status'] === 'completed') {
                        // Deduct from source warehouse
                        $this->stockService->adjustStock(
                            $item['product_id'],
                            $transfer->source_warehouse_id,
                            $item['quantity'],
                            'out',
                            'stock_transfer',
                            $transfer->id,
                            Auth::id()
                        );

                        // Add to destination warehouse
                        $this->stockService->adjustStock(
                            $item['product_id'],
                            $transfer->destination_warehouse_id,
                            $item['quantity'],
                            'in',
                            'stock_transfer',
                            $transfer->id,
                            Auth::id()
                        );
                    }
                }
            });

            return redirect()->route('stock-transfers.index')->with('success', 'Transfer stok berhasil dibuat!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat transfer stok: ' . $e->getMessage());
        }
    }

    public function show(StockTransfer $stock_transfer)
    {
        $stock_transfer->load(['items.product', 'sourceWarehouse', 'destinationWarehouse', 'user']);
        
        return Inertia::render('StockTransfers/Show', [
            'transfer' => $stock_transfer
        ]);
    }

    public function edit(StockTransfer $stock_transfer)
    {
        if ($stock_transfer->status === 'completed') {
            return redirect()->route('stock-transfers.index')->with('error', 'Transfer stok yang sudah selesai tidak dapat diubah.');
        }

        $stock_transfer->load('items');

        return Inertia::render('StockTransfers/Edit', [
            'transfer' => $stock_transfer,
            'warehouses' => Warehouse::all(),
            'products' => Product::all(),
        ]);
    }

    public function update(Request $request, StockTransfer $stock_transfer)
    {
        if ($stock_transfer->status === 'completed') {
            return redirect()->route('stock-transfers.index')->with('error', 'Transfer stok yang sudah selesai tidak dapat diubah.');
        }

        $validated = $request->validate([
            'source_warehouse_id' => 'required|exists:warehouses,id',
            'destination_warehouse_id' => 'required|exists:warehouses,id|different:source_warehouse_id',
            'tanggal' => 'required|date',
            'status' => 'required|in:draft,completed',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($stock_transfer, $validated) {
                // If transitioning to completed, pre-validate all stock availability
                if ($validated['status'] === 'completed') {
                    foreach ($validated['items'] as $item) {
                        $currentStock = $this->stockService->getStock($item['product_id'], $validated['source_warehouse_id']);
                        if ($currentStock < $item['quantity']) {
                            $product = Product::find($item['product_id']);
                            throw new \Exception("Stok untuk produk '{$product->nama}' tidak mencukupi di gudang asal (Tersedia: {$currentStock}, Diminta: {$item['quantity']}).");
                        }
                    }
                }

                $stock_transfer->update([
                    'source_warehouse_id' => $validated['source_warehouse_id'],
                    'destination_warehouse_id' => $validated['destination_warehouse_id'],
                    'tanggal' => $validated['tanggal'],
                    'status' => $validated['status'],
                    'catatan' => $validated['catatan'] ?? null,
                ]);

                // Delete old items and rebuild
                $stock_transfer->items()->delete();

                foreach ($validated['items'] as $item) {
                    StockTransferItem::create([
                        'stock_transfer_id' => $stock_transfer->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);

                    if ($validated['status'] === 'completed') {
                        // Deduct from source warehouse
                        $this->stockService->adjustStock(
                            $item['product_id'],
                            $stock_transfer->source_warehouse_id,
                            $item['quantity'],
                            'out',
                            'stock_transfer',
                            $stock_transfer->id,
                            Auth::id()
                        );

                        // Add to destination warehouse
                        $this->stockService->adjustStock(
                            $item['product_id'],
                            $stock_transfer->destination_warehouse_id,
                            $item['quantity'],
                            'in',
                            'stock_transfer',
                            $stock_transfer->id,
                            Auth::id()
                        );
                    }
                }
            });

            return redirect()->route('stock-transfers.index')->with('success', 'Transfer stok berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui transfer stok: ' . $e->getMessage());
        }
    }

    public function destroy(StockTransfer $stock_transfer)
    {
        if ($stock_transfer->status === 'completed') {
            return redirect()->route('stock-transfers.index')->with('error', 'Transfer stok yang sudah selesai tidak dapat dihapus.');
        }

        try {
            $stock_transfer->delete(); // Cascade on delete handles the items
            return redirect()->route('stock-transfers.index')->with('success', 'Transfer stok berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus transfer stok: ' . $e->getMessage());
        }
    }

    protected function generateNoTransfer()
    {
        $date = date('Ymd');
        $count = StockTransfer::whereDate('created_at', today())->count() + 1;
        return "TRF-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
