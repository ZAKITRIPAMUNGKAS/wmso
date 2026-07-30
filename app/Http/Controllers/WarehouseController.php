<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    public function index()
    {
        $search = request('search');

        $warehouses = Warehouse::query()
            ->when($search, function ($query, $search) {
                $query->where('nama', 'like', "%{$search}%")
                      ->orWhere('kode_gudang', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $lastWarehouse = Warehouse::where('kode_gudang', 'like', 'GDG-%')
            ->orderByRaw('LENGTH(kode_gudang) DESC, kode_gudang DESC')
            ->first();

        $nextNumber = $lastWarehouse ? (int) str_replace('GDG-', '', $lastWarehouse->kode_gudang) + 1 : 1;
        $nextCode = 'GDG-' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);

        return Inertia::render('MasterData/Warehouses', [
            'warehouses' => $warehouses,
            'filters' => request()->all('search'),
            'next_code' => $nextCode,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_gudang' => 'required|unique:warehouses',
            'nama' => 'required',
            'alamat' => 'required',
        ]);

        Warehouse::create($validated);

        return redirect()->back()->with('success', 'Gudang baru berhasil ditambahkan!');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'nama' => 'required',
            'alamat' => 'required',
        ]);

        $warehouse->update($validated);

        return redirect()->back()->with('success', 'Data gudang berhasil diperbarui!');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        return redirect()->back()->with('success', 'Gudang berhasil dihapus!');
    }
}
