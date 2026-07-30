<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_scope_includes_products_with_no_stock_entries(): void
    {
        // Create a product with no stock entries
        $product = Product::create([
            'kode_barang' => 'PRD-001',
            'nama' => 'Product No Stock',
            'merk' => 'Brand A',
            'tipe' => 'Type A',
            'satuan' => 'pcs',
            'harga' => 1000,
            'stok_minimum' => 10,
        ]);

        $lowStockProducts = Product::lowStock(10)->get();

        $this->assertTrue($lowStockProducts->contains($product));
        $this->assertEquals(0, $lowStockProducts->firstWhere('id', $product->id)->total_stock);
    }

    public function test_low_stock_scope_includes_products_below_threshold(): void
    {
        $warehouse = Warehouse::create([
            'kode_gudang' => 'GDG-01',
            'nama' => 'Main Warehouse',
            'alamat' => 'Jakarta',
        ]);

        // Create a product with stock below the threshold (e.g., 5 < 10)
        $product = Product::create([
            'kode_barang' => 'PRD-002',
            'nama' => 'Product Low Stock',
            'merk' => 'Brand B',
            'tipe' => 'Type B',
            'satuan' => 'pcs',
            'harga' => 2000,
            'stok_minimum' => 10,
        ]);

        ProductStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
        ]);

        $lowStockProducts = Product::lowStock(10)->get();

        $this->assertTrue($lowStockProducts->contains($product));
        $this->assertEquals(5, $lowStockProducts->firstWhere('id', $product->id)->total_stock);
    }

    public function test_low_stock_scope_excludes_products_above_or_equal_to_threshold(): void
    {
        $warehouse = Warehouse::create([
            'kode_gudang' => 'GDG-01',
            'nama' => 'Main Warehouse',
            'alamat' => 'Jakarta',
        ]);

        // Create a product with stock equal to the threshold (e.g., 10)
        $product1 = Product::create([
            'kode_barang' => 'PRD-003',
            'nama' => 'Product Stock Ten',
            'merk' => 'Brand C',
            'tipe' => 'Type C',
            'satuan' => 'pcs',
            'harga' => 3000,
            'stok_minimum' => 10,
        ]);

        ProductStock::create([
            'product_id' => $product1->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
        ]);

        // Create a product with stock above the threshold (e.g., 15)
        $product2 = Product::create([
            'kode_barang' => 'PRD-004',
            'nama' => 'Product High Stock',
            'merk' => 'Brand D',
            'tipe' => 'Type D',
            'satuan' => 'pcs',
            'harga' => 4000,
            'stok_minimum' => 10,
        ]);

        ProductStock::create([
            'product_id' => $product2->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 15,
        ]);

        $lowStockProducts = Product::lowStock(10)->get();

        $this->assertFalse($lowStockProducts->contains($product1));
        $this->assertFalse($lowStockProducts->contains($product2));
    }
}
