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

        return Inertia::render('MasterData/Products', [
            'products' => $products,
            'filters' => request()->all('search'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|unique:products',
            'nama' => 'required',
            'merk' => 'required',
            'tipe' => 'required',
            'satuan' => 'required',
            'harga' => 'required|numeric',
            'stok_minimum' => 'required|integer',
        ]);

        Product::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'nama' => 'required',
            'merk' => 'required',
            'tipe' => 'required',
            'satuan' => 'required',
            'harga' => 'required|numeric',
            'stok_minimum' => 'required|integer',
        ]);

        $product->update($validated);

        return redirect()->back();
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->back();
    }
}
