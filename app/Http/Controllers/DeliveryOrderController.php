<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Product;
use App\Services\DeliveryOrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryOrderController extends Controller
{
    protected $doService;

    public function __construct(DeliveryOrderService $doService)
    {
        $this->doService = $doService;
    }

    public function index()
    {
        return Inertia::render('BarangKeluar/Index', [
            'deliveryOrders' => DeliveryOrder::with(['customer', 'warehouse', 'user'])->latest()->paginate(10),
        ]);
    }

    public function create()
    {
        $products = Product::all()->map(function ($product) {
            return [
                'id' => $product->id,
                'nama' => $product->nama,
                'harga' => $product->harga,
                'stocks' => $product->stocks()->get()->pluck('quantity', 'warehouse_id')
            ];
        });

        return Inertia::render('BarangKeluar/Create', [
            'customers' => Customer::all(),
            'warehouses' => Warehouse::all(),
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'      => 'required|exists:customers,id',
            'warehouse_id'     => 'required|exists:warehouses,id',
            'po_number'        => 'nullable|string|max:50',
            'tanggal'          => 'required|date',
            'payment_term'     => 'nullable|string',
            'jenis_pembayaran' => 'required|in:cash,tempo',
            'tempo_hari'       => 'nullable|integer|min:1|max:365',
            'total'            => 'required|numeric',
            'due_date'         => 'nullable|date',
            'keterangan'       => 'nullable|string',
            'items'            => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.harga'      => 'required|numeric',
            'items.*.subtotal'   => 'required|numeric',
        ]);

        try {
            $this->doService->createDeliveryOrder($validated);
            return redirect()->route('barang-keluar.index')->with('success', 'Surat Jalan berhasil dibuat!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat Surat Jalan: ' . $e->getMessage());
        }
    }

    public function show(DeliveryOrder $barang_keluar)
    {
        $barang_keluar->load(['items.product', 'customer', 'warehouse', 'user', 'invoice']);
        
        return Inertia::render('BarangKeluar/Show', [
            'deliveryOrder' => $barang_keluar
        ]);
    }

    public function destroy(DeliveryOrder $barang_keluar)
    {
        try {
            $this->doService->deleteDeliveryOrder($barang_keluar);
            return redirect()->route('barang-keluar.index')->with('success', 'Surat Jalan dan Invoice terkait berhasil dihapus, stok telah dikembalikan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
