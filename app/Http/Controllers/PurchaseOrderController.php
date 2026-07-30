<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => PurchaseOrder::with(['supplier', 'user'])->latest()->paginate(10),
        ]);
    }

    public function create()
    {
        return Inertia::render('PurchaseOrders/Create', [
            'suppliers' => Supplier::all(),
            'products' => Product::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'tanggal' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.harga' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $total = 0;
                foreach ($validated['items'] as $item) {
                    $total += $item['quantity'] * $item['harga'];
                }

                $po = PurchaseOrder::create([
                    'no_po' => $this->generateNoPo(),
                    'supplier_id' => $validated['supplier_id'],
                    'tanggal' => $validated['tanggal'],
                    'status' => 'draft',
                    'total' => $total,
                    'user_id' => Auth::id(),
                ]);

                foreach ($validated['items'] as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'harga' => $item['harga'],
                        'subtotal' => $item['quantity'] * $item['harga'],
                    ]);
                }
            });

            return redirect()->route('purchase-orders.index')->with('success', 'Purchase Order berhasil dibuat!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat Purchase Order: ' . $e->getMessage());
        }
    }

    public function show(PurchaseOrder $purchase_order)
    {
        $purchase_order->load(['items.product', 'supplier', 'user']);
        
        return Inertia::render('PurchaseOrders/Show', [
            'purchaseOrder' => $purchase_order
        ]);
    }

    public function edit(PurchaseOrder $purchase_order)
    {
        if ($purchase_order->status === 'received') {
            return redirect()->route('purchase-orders.index')->with('error', 'Purchase Order yang sudah diterima tidak dapat diubah.');
        }

        $purchase_order->load('items');

        return Inertia::render('PurchaseOrders/Edit', [
            'purchaseOrder' => $purchase_order,
            'suppliers' => Supplier::all(),
            'products' => Product::all(),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchase_order)
    {
        if ($purchase_order->status === 'received') {
            return redirect()->route('purchase-orders.index')->with('error', 'Purchase Order yang sudah diterima tidak dapat diubah.');
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'tanggal' => 'required|date',
            'status' => 'required|in:draft,confirmed',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.harga' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($purchase_order, $validated) {
                $total = 0;
                foreach ($validated['items'] as $item) {
                    $total += $item['quantity'] * $item['harga'];
                }

                $purchase_order->update([
                    'supplier_id' => $validated['supplier_id'],
                    'tanggal' => $validated['tanggal'],
                    'status' => $validated['status'],
                    'total' => $total,
                ]);

                // Recreate items
                $purchase_order->items()->delete();

                foreach ($validated['items'] as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchase_order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'harga' => $item['harga'],
                        'subtotal' => $item['quantity'] * $item['harga'],
                    ]);
                }
            });

            return redirect()->route('purchase-orders.index')->with('success', 'Purchase Order berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui Purchase Order: ' . $e->getMessage());
        }
    }

    public function destroy(PurchaseOrder $purchase_order)
    {
        if ($purchase_order->status === 'received') {
            return redirect()->route('purchase-orders.index')->with('error', 'Purchase Order yang sudah diterima tidak dapat dihapus.');
        }

        try {
            $purchase_order->delete();
            return redirect()->route('purchase-orders.index')->with('success', 'Purchase Order berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus Purchase Order: ' . $e->getMessage());
        }
    }

    protected function generateNoPo()
    {
        $date = date('Ymd');
        $count = PurchaseOrder::whereDate('created_at', today())->count() + 1;
        return "PO-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
