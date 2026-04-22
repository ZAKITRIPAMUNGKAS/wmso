<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GoodsReceiptController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index()
    {
        return Inertia::render('BarangMasuk/Index', [
            'receipts' => GoodsReceipt::with(['purchaseOrder', 'warehouse', 'user', 'supplier'])->latest()->paginate(10),
        ]);
    }

    public function create()
    {
        return Inertia::render('BarangMasuk/Create', [
            'purchaseOrders' => PurchaseOrder::with('supplier')->where('status', '!=', 'received')->get(),
            'suppliers' => \App\Models\Supplier::all(),
            'warehouses' => Warehouse::all(),
            'products' => Product::all(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            // Handle empty string for nullable foreign key
            if ($request->purchase_order_id === "") {
                $request->merge(['purchase_order_id' => null]);
            }
            if ($request->supplier_id === "") {
                $request->merge(['supplier_id' => null]);
            }

            $validated = $request->validate([
                'purchase_order_id' => 'nullable|exists:purchase_orders,id',
                'supplier_id' => 'required_without:purchase_order_id|nullable|exists:suppliers,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'tanggal' => 'required|date',
                'catatan' => 'nullable|string',
                'bukti_penerimaan' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            $filePath = null;
            if ($request->hasFile('bukti_penerimaan')) {
                $filePath = $request->file('bukti_penerimaan')->store('bukti-terima', 'public');
            }

            DB::transaction(function () use ($validated, $filePath) {
                // Determine supplier_id if PO is present
                $supplierId = $validated['supplier_id'];
                if ($validated['purchase_order_id'] && !$supplierId) {
                    $po = PurchaseOrder::find($validated['purchase_order_id']);
                    $supplierId = $po->supplier_id;
                }

                $receipt = GoodsReceipt::create([
                    'no_receipt' => $this->generateNoReceipt(),
                    'purchase_order_id' => $validated['purchase_order_id'],
                    'supplier_id' => $supplierId,
                    'warehouse_id' => $validated['warehouse_id'],
                    'tanggal' => $validated['tanggal'],
                    'bukti_penerimaan' => $filePath,
                    'catatan' => $validated['catatan'],
                    'user_id' => Auth::id(),
                ]);

                foreach ($validated['items'] as $item) {
                    GoodsReceiptItem::create([
                        'goods_receipt_id' => $receipt->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);

                    $this->stockService->adjustStock(
                        $item['product_id'],
                        $receipt->warehouse_id,
                        $item['quantity'],
                        'in',
                        'goods_receipt',
                        $receipt->id,
                        Auth::id()
                    );
                }

                // Update PO status if exists
                if ($validated['purchase_order_id']) {
                    $po = PurchaseOrder::find($validated['purchase_order_id']);
                    $po->status = 'received';
                    $po->save();
                }
            });

            return redirect()->route('barang-masuk.index')->with('success', 'Barang masuk berhasil dicatat!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function show(GoodsReceipt $barang_masuk)
    {
        $barang_masuk->load(['items.product', 'purchaseOrder', 'warehouse', 'user', 'supplier']);
        
        return Inertia::render('BarangMasuk/Show', [
            'receipt' => $barang_masuk
        ]);
    }

    public function destroy(GoodsReceipt $barang_masuk)
    {
        try {
            DB::transaction(function () use ($barang_masuk) {
                // Remove stock adjustments and related movements
                $this->stockService->removeStockAdjustment('goods_receipt', $barang_masuk->id);

                // If tied to PO, reset PO status
                if ($barang_masuk->purchase_order_id) {
                    $po = PurchaseOrder::find($barang_masuk->purchase_order_id);
                    if ($po) {
                        $po->status = 'approved'; 
                        $po->save();
                    }
                }

                $barang_masuk->delete();
            });

            return redirect()->route('barang-masuk.index')->with('success', 'Data barang masuk berhasil dihapus dan stok telah diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    protected function generateNoReceipt()
    {
        $date = date('Ymd');
        $count = GoodsReceipt::whereDate('created_at', today())->count() + 1;
        return "RCP-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
