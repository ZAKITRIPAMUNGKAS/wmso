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
                'kode_barang' => $product->kode_barang,
                'nama' => $product->nama,
                'harga' => $product->harga,
                'stocks' => $product->stocks()->get()->pluck('quantity', 'warehouse_id'),
                'rack_stocks' => $product->rackStocks()->with('rack')->get()->map(function ($rs) {
                    return [
                        'id' => $rs->id,
                        'warehouse_id' => $rs->warehouse_id,
                        'rack_id' => $rs->rack_id,
                        'kode_rak' => $rs->rack ? $rs->rack->kode_rak : 'Tanpa Rak',
                        'batch_number' => $rs->batch_number,
                        'expired_at' => $rs->expired_at ? (\Carbon\Carbon::parse($rs->expired_at)->format('Y-m-d')) : null,
                        'serial_number' => $rs->serial_number,
                        'quantity' => $rs->quantity,
                        'created_at' => $rs->created_at ? $rs->created_at->toIso8601String() : null
                    ];
                })
            ];
        });

        return Inertia::render('BarangKeluar/Create', [
            'customers' => Customer::all(),
            'warehouses' => Warehouse::all(),
            'products' => $products,
            'racks' => \App\Models\Rack::all(),
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
            'courier_name'     => 'nullable|string',
            'tracking_number'  => 'nullable|string',
            'items'            => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.harga'      => 'required|numeric',
            'items.*.subtotal'   => 'required|numeric',
            'items.*.rack_id'    => 'nullable|exists:racks,id',
            'items.*.batch_number' => 'nullable|string',
            'items.*.expired_at'   => 'nullable|date',
            'items.*.serial_number' => 'nullable|string',
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

    public function downloadPdf(DeliveryOrder $barang_keluar)
    {
        $barang_keluar->load(['items.product', 'customer', 'warehouse', 'user']);
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.surat-jalan', ['deliveryOrder' => $barang_keluar]);
        
        return $pdf->download("surat-jalan-{$barang_keluar->no_sj}.pdf");
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
