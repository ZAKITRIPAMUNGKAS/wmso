<?php

namespace App\Http\Controllers;

use App\Models\Rack;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RackController extends Controller
{
    public function index()
    {
        $search = request('search');

        $racks = Rack::with('warehouse')
            ->when($search, function ($query, $search) {
                $query->where('nama', 'like', "%{$search}%")
                      ->orWhere('kode_rak', 'like', "%{$search}%")
                      ->orWhereHas('warehouse', function ($q) use ($search) {
                          $q->where('nama', 'like', "%{$search}%");
                      });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/Racks', [
            'racks' => $racks,
            'warehouses' => Warehouse::all(),
            'filters' => request()->all('search'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'kode_rak' => 'required|string',
            'nama' => 'required|string',
            'keterangan' => 'nullable|string',
        ]);

        // Check uniqueness for warehouse_id + kode_rak
        $exists = Rack::where('warehouse_id', $validated['warehouse_id'])
            ->where('kode_rak', $validated['kode_rak'])
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors([
                'kode_rak' => 'Kode Rak sudah terdaftar di gudang ini.'
            ]);
        }

        Rack::create($validated);

        return redirect()->back()->with('success', 'Rak baru berhasil ditambahkan!');
    }

    public function update(Request $request, Rack $rack)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'kode_rak' => 'required|string',
            'nama' => 'required|string',
            'keterangan' => 'nullable|string',
        ]);

        // Check uniqueness for warehouse_id + kode_rak except this rack
        $exists = Rack::where('warehouse_id', $validated['warehouse_id'])
            ->where('kode_rak', $validated['kode_rak'])
            ->where('id', '!=', $rack->id)
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors([
                'kode_rak' => 'Kode Rak sudah terdaftar di gudang ini.'
            ]);
        }

        $rack->update($validated);

        return redirect()->back()->with('success', 'Data rak berhasil diperbarui!');
    }

    public function destroy(Rack $rack)
    {
        $rack->delete();
        return redirect()->back()->with('success', 'Rak berhasil dihapus!');
    }
}
