<?php

namespace Tests\Feature;

use App\Mail\LowStockAlert;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProductStockAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_dynamic_low_stock_scope(): void
    {
        // Product 1: stock = 5, min = 10 (should be low stock)
        $product1 = Product::create([
            'kode_barang' => 'PRD-001',
            'nama' => 'Product A',
            'merk' => 'Brand A',
            'tipe' => 'Type A',
            'satuan' => 'pcs',
            'harga' => 1000,
            'stok_minimum' => 10,
        ]);

        // Product 2: stock = 12, min = 10 (should not be low stock)
        $product2 = Product::create([
            'kode_barang' => 'PRD-002',
            'nama' => 'Product B',
            'merk' => 'Brand B',
            'tipe' => 'Type B',
            'satuan' => 'pcs',
            'harga' => 2000,
            'stok_minimum' => 10,
        ]);

        $warehouse = Warehouse::create([
            'kode_gudang' => 'GDG-01',
            'nama' => 'Main Warehouse',
            'alamat' => 'Jakarta',
        ]);

        ProductStock::create([
            'product_id' => $product1->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
        ]);

        ProductStock::create([
            'product_id' => $product2->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 12,
        ]);

        // Call scope with no parameters (dynamic check against database stok_minimum)
        $lowStockProducts = Product::lowStock()->get();

        $this->assertTrue($lowStockProducts->contains($product1));
        $this->assertFalse($lowStockProducts->contains($product2));
    }

    public function test_low_stock_alert_sent_on_transition_only(): void
    {
        Mail::fake();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@wms.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $product = Product::create([
            'kode_barang' => 'PRD-001',
            'nama' => 'Product A',
            'merk' => 'Brand A',
            'tipe' => 'Type A',
            'satuan' => 'pcs',
            'harga' => 1000,
            'stok_minimum' => 10,
        ]);

        $warehouse = Warehouse::create([
            'kode_gudang' => 'GDG-01',
            'nama' => 'Main Warehouse',
            'alamat' => 'Jakarta',
        ]);

        // Saldo awal = 15 (>= min 10)
        ProductStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 15,
        ]);

        $stockService = new StockService();

        // 1. First transaction: out 3. New stock = 12 (>= min 10). Should NOT send email.
        $stockService->adjustStock(
            $product->id,
            $warehouse->id,
            3,
            'out',
            'delivery_order',
            1,
            $admin->id
        );

        Mail::assertNothingQueued();

        // 2. Second transaction: out 4. New stock = 8 (< min 10). This transitions below minimum, so it SHOULD send email.
        $stockService->adjustStock(
            $product->id,
            $warehouse->id,
            4,
            'out',
            'delivery_order',
            2,
            $admin->id
        );

        Mail::assertQueued(LowStockAlert::class, function ($mail) use ($product) {
            return $mail->product->id === $product->id && $mail->newGlobalStock === 8;
        });

        // Reset mail fakes to check if subsequent out transactions do NOT send email.
        Mail::fake();

        // 3. Third transaction: out 2. New stock = 6 (< min 10). Already below minimum, so it should NOT send email.
        $stockService->adjustStock(
            $product->id,
            $warehouse->id,
            2,
            'out',
            'delivery_order',
            3,
            $admin->id
        );

        Mail::assertNothingQueued();
    }

    public function test_low_stock_alert_not_sent_on_stock_transfers(): void
    {
        Mail::fake();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@wms.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $product = Product::create([
            'kode_barang' => 'PRD-001',
            'nama' => 'Product A',
            'merk' => 'Brand A',
            'tipe' => 'Type A',
            'satuan' => 'pcs',
            'harga' => 1000,
            'stok_minimum' => 10,
        ]);

        $warehouse = Warehouse::create([
            'kode_gudang' => 'GDG-01',
            'nama' => 'Main Warehouse',
            'alamat' => 'Jakarta',
        ]);

        // Saldo awal = 15
        ProductStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 15,
        ]);

        $stockService = new StockService();

        // Perform adjustment with 'stock_transfer' type. Outward transfer should NOT send email.
        $stockService->adjustStock(
            $product->id,
            $warehouse->id,
            8,
            'out',
            'stock_transfer',
            1,
            $admin->id
        );

        Mail::assertNothingQueued();
    }
}
