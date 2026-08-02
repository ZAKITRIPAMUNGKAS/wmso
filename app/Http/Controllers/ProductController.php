<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        $search = request('search');
        
        $products = Product::query()
            ->withSum('stocks as total_stock', 'quantity')
            ->when($search, function ($query, $search) {
                $query->where('nama', 'like', "%{$search}%")
                      ->orWhere('kode_barang', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';
        if ($isSqlite) {
            $lastProduct = Product::whereRaw("kode_barang GLOB '[0-9]*'")
                ->selectRaw('CAST(kode_barang AS INTEGER) as num_code')
                ->orderBy('num_code', 'desc')
                ->first();
        } else {
            $lastProduct = Product::whereRaw("kode_barang REGEXP '^[0-9]+$'")
                ->selectRaw('CAST(kode_barang AS UNSIGNED) as num_code')
                ->orderBy('num_code', 'desc')
                ->first();
        }

        $nextNumber = $lastProduct ? ((int) $lastProduct->num_code + 1) : 1;
        $nextCode = sprintf('%06d', $nextNumber);

        return Inertia::render('MasterData/Products', [
            'products' => $products,
            'filters' => request()->all('search'),
            'next_code' => $nextCode,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_barang'  => 'nullable|string|unique:products,kode_barang|max:50',
            'nama'         => 'required',
            'merk'         => 'required',
            'tipe'         => 'required',
            'satuan'       => 'required',
            'harga'        => 'required|numeric',
            'stok_minimum' => 'required|integer',
            'deskripsi'    => 'nullable|string',
            'image'        => 'nullable|image|max:2048',
            'images'       => 'nullable|array',
            'images.*'     => 'image|max:2048',
        ]);

        if (empty($validated['kode_barang'])) {
            $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';
            if ($isSqlite) {
                $lastProduct = Product::whereRaw("kode_barang GLOB '[0-9]*'")
                    ->selectRaw('CAST(kode_barang AS INTEGER) as num_code')
                    ->orderBy('num_code', 'desc')
                    ->first();
            } else {
                $lastProduct = Product::whereRaw("kode_barang REGEXP '^[0-9]+$'")
                    ->selectRaw('CAST(kode_barang AS UNSIGNED) as num_code')
                    ->orderBy('num_code', 'desc')
                    ->first();
            }
            $nextNumber = $lastProduct ? ((int) $lastProduct->num_code + 1) : 1;
            $validated['kode_barang'] = sprintf('%06d', $nextNumber);
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $galleryFiles = $request->file('images');
        unset($validated['images']);

        $product = Product::create($validated);

        if ($request->hasFile('images')) {
            foreach ($galleryFiles as $idx => $img) {
                $path = $img->store('products', 'public');
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => ($idx === 0),
                    'sort_order' => $idx,
                ]);
            }
        }

        // Dispatch stock sync to Olshop
        \App\Jobs\SyncStockToOlshop::dispatch($product->id, now()->format('Y-m-d\TH:i:s\Z'));

        return redirect()->back()->with('success', 'Produk baru berhasil ditambahkan!');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'kode_barang'  => 'nullable|string|unique:products,kode_barang,' . $product->id . '|max:50',
            'nama'         => 'required',
            'merk'         => 'required',
            'tipe'         => 'required',
            'satuan'       => 'required',
            'harga'        => 'required|numeric',
            'stok_minimum' => 'required|integer',
            'deskripsi'    => 'nullable|string',
            'image'        => 'nullable|image|max:2048',
            'images'       => 'nullable|array',
            'images.*'     => 'image|max:2048',
        ]);

        if (empty($validated['kode_barang'])) {
            $validated['kode_barang'] = $product->kode_barang;
        }

        if ($request->hasFile('image')) {
            if ($product->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($product->image)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $galleryFiles = $request->file('images');
        unset($validated['images']);

        $product->update($validated);

        if ($request->hasFile('images')) {
            foreach ($product->images as $oldImg) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldImg->image_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldImg->image_path);
                }
            }
            $product->images()->delete();

            foreach ($galleryFiles as $idx => $img) {
                $path = $img->store('products', 'public');
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => ($idx === 0),
                    'sort_order' => $idx,
                ]);
            }
        }

        // Dispatch stock sync to Olshop
        \App\Jobs\SyncStockToOlshop::dispatch($product->id, now()->format('Y-m-d\TH:i:s\Z'));

        return redirect()->back()->with('success', 'Data produk berhasil diperbarui!');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->back()->with('success', 'Produk berhasil dihapus!');
    }

    public function show(Product $product)
    {
        // Load warehouse stocks
        $stocks = \App\Models\ProductStock::where('product_id', $product->id)
            ->with('warehouse')
            ->get();

        // Retrieve paginated stock movements
        $movements = \App\Models\StockMovement::where('product_id', $product->id)
            ->with(['warehouse', 'user'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        // Resolve references in bulk
        $goodsReceiptIds = [];
        $deliveryOrderIds = [];
        $stockTransferIds = [];
        $stockAdjustmentIds = [];

        foreach ($movements as $m) {
            if ($m->reference_type === 'goods_receipt') $goodsReceiptIds[] = $m->reference_id;
            if ($m->reference_type === 'delivery_order') $deliveryOrderIds[] = $m->reference_id;
            if ($m->reference_type === 'stock_transfer') $stockTransferIds[] = $m->reference_id;
            if ($m->reference_type === 'stock_adjustment') $stockAdjustmentIds[] = $m->reference_id;
        }

        $goodsReceipts = \App\Models\GoodsReceipt::whereIn('id', $goodsReceiptIds)->get()->keyBy('id');
        $deliveryOrders = \App\Models\DeliveryOrder::whereIn('id', $deliveryOrderIds)->get()->keyBy('id');
        $stockTransfers = \App\Models\StockTransfer::whereIn('id', $stockTransferIds)->get()->keyBy('id');
        $stockAdjustments = \App\Models\StockAdjustment::whereIn('id', $stockAdjustmentIds)->get()->keyBy('id');

        $movements->getCollection()->transform(function ($m) use ($goodsReceipts, $deliveryOrders, $stockTransfers, $stockAdjustments) {
            $code = "Ref #{$m->reference_id}";
            $route = null;
            if ($m->reference_type === 'goods_receipt') {
                $ref = $goodsReceipts->get($m->reference_id);
                $code = $ref ? $ref->no_receipt : "Receipt #{$m->reference_id}";
                $route = $ref ? route('barang-masuk.show', $m->reference_id) : null;
            } elseif ($m->reference_type === 'delivery_order') {
                $ref = $deliveryOrders->get($m->reference_id);
                $code = $ref ? $ref->no_delivery : "DO #{$m->reference_id}";
                $route = $ref ? route('barang-keluar.show', $m->reference_id) : null;
            } elseif ($m->reference_type === 'stock_transfer') {
                $ref = $stockTransfers->get($m->reference_id);
                $code = $ref ? $ref->no_transfer : "Transfer #{$m->reference_id}";
                $route = $ref ? route('stock-transfers.show', $m->reference_id) : null;
            } elseif ($m->reference_type === 'stock_adjustment') {
                $ref = $stockAdjustments->get($m->reference_id);
                $code = $ref ? $ref->no_adjustment : "Adjustment #{$m->reference_id}";
                $route = $ref ? route('stock-adjustments.show', $m->reference_id) : null;
            }
            $m->reference_code = $code;
            $m->reference_route = $route;
            return $m;
        });

        $totalStock = $stocks->sum('quantity');

        return Inertia::render('MasterData/ProductShow', [
            'product' => $product,
            'stocks' => $stocks,
            'totalStock' => $totalStock,
            'movements' => $movements,
        ]);
    }
}
