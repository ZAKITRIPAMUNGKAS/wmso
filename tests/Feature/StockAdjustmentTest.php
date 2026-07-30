<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $viewer;
    private Warehouse $warehouse;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Spatie Roles if Spatie exists
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'staff_gudang']);
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'viewer']);
        }

        // Admin User
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        if (method_exists($this->admin, 'assignRole')) {
            $this->admin->assignRole('admin');
        }

        // Staff User
        $this->staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'role' => 'staff_gudang',
            'email_verified_at' => now(),
        ]);
        if (method_exists($this->staff, 'assignRole')) {
            $this->staff->assignRole('staff_gudang');
        }

        // Viewer User
        $this->viewer = User::create([
            'name' => 'Viewer User',
            'email' => 'viewer@test.com',
            'password' => bcrypt('password'),
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);
        if (method_exists($this->viewer, 'assignRole')) {
            $this->viewer->assignRole('viewer');
        }

        // Warehouse
        $this->warehouse = Warehouse::create([
            'kode_gudang' => 'GDG-01',
            'nama' => 'Gudang Utama',
            'alamat' => 'Jakarta',
        ]);

        // Product
        $this->product = Product::create([
            'kode_barang' => 'PRD-T01',
            'nama' => 'Product Test',
            'merk' => 'Merk Test',
            'tipe' => 'Tipe Test',
            'satuan' => 'Pcs',
            'harga' => 50000,
            'stok_minimum' => 5,
        ]);
    }

    public function test_all_authenticated_users_can_view_stock_adjustment_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('stock-adjustments.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->staff)->get(route('stock-adjustments.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->viewer)->get(route('stock-adjustments.index'));
        $response->assertStatus(200);
    }

    public function test_admin_and_staff_can_view_create_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('stock-adjustments.create'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->staff)->get(route('stock-adjustments.create'));
        $response->assertStatus(200);
    }

    public function test_viewer_cannot_view_create_page(): void
    {
        $response = $this->actingAs($this->viewer)->get(route('stock-adjustments.create'));
        $response->assertStatus(403);
    }

    public function test_can_fetch_warehouse_stocks_via_json_endpoint(): void
    {
        ProductStock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 15
        ]);

        $response = $this->actingAs($this->staff)->get(route('stock-adjustments.warehouse-stocks', $this->warehouse->id));
        $response->assertStatus(200);
        $response->assertJson([
            $this->product->id => 15
        ]);
    }

    public function test_admin_and_staff_can_store_draft_stock_adjustment(): void
    {
        $response = $this->actingAs($this->admin)->post(route('stock-adjustments.store'), [
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft',
            'catatan' => 'Draft adjustment test',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_sistem' => 10,
                    'quantity_fisik' => 15,
                ]
            ]
        ]);

        $response->assertRedirect(route('stock-adjustments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stock_adjustments', [
            'warehouse_id' => $this->warehouse->id,
            'status' => 'draft',
            'catatan' => 'Draft adjustment test',
            'user_id' => $this->admin->id,
        ]);

        $adjustment = StockAdjustment::latest()->first();
        $this->assertDatabaseHas('stock_adjustment_items', [
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $this->product->id,
            'quantity_sistem' => 10,
            'quantity_fisik' => 15,
            'selisih' => 5,
        ]);

        // Assert stock was not adjusted
        $stock = ProductStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertNull($stock);
    }

    public function test_admin_and_staff_can_store_completed_stock_adjustment_adding_stock(): void
    {
        // System says 10, physical says 15. We add 5.
        $response = $this->actingAs($this->staff)->post(route('stock-adjustments.store'), [
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'completed',
            'catatan' => 'Adding stock',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_sistem' => 10,
                    'quantity_fisik' => 15,
                ]
            ]
        ]);

        $response->assertRedirect(route('stock-adjustments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stock_adjustments', [
            'warehouse_id' => $this->warehouse->id,
            'status' => 'completed',
            'user_id' => $this->staff->id,
        ]);

        // Assert stock is now 5 (as it started at 0 in DB, and we added 5)
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
        ]);

        // Verify stock movement logged
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'in',
            'quantity' => 5,
            'reference_type' => 'stock_adjustment',
        ]);
    }

    public function test_admin_and_staff_can_store_completed_stock_adjustment_deducting_stock(): void
    {
        // Seed stock first
        ProductStock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 20
        ]);

        // System says 20, physical says 12. We deduct 8.
        $response = $this->actingAs($this->admin)->post(route('stock-adjustments.store'), [
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_sistem' => 20,
                    'quantity_fisik' => 12,
                ]
            ]
        ]);

        $response->assertRedirect(route('stock-adjustments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 12, // 20 - 8
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'out',
            'quantity' => 8,
            'reference_type' => 'stock_adjustment',
        ]);
    }

    public function test_completed_stock_adjustment_validation_fails_on_insufficient_stock(): void
    {
        // Seed small stock
        ProductStock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 3
        ]);

        // System says 10, physical says 5. We want to deduct 5. But available stock is only 3. Should fail.
        $response = $this->actingAs($this->admin)->post(route('stock-adjustments.store'), [
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_sistem' => 10,
                    'quantity_fisik' => 5,
                ]
            ]
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        // Verify stock didn't change
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 3,
        ]);
    }

    public function test_viewer_cannot_store_stock_adjustment(): void
    {
        $response = $this->actingAs($this->viewer)->post(route('stock-adjustments.store'), [
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_sistem' => 10,
                    'quantity_fisik' => 10,
                ]
            ]
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_edit_or_update_completed_stock_adjustment(): void
    {
        $adjustment = StockAdjustment::create([
            'no_adjustment' => 'ADJ-20260522-001',
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'completed',
            'user_id' => $this->admin->id,
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $this->product->id,
            'quantity_sistem' => 10,
            'quantity_fisik' => 12,
            'selisih' => 2,
        ]);

        // Attempt to edit
        $response = $this->actingAs($this->admin)->get(route('stock-adjustments.edit', $adjustment->id));
        $response->assertRedirect(route('stock-adjustments.index'));
        $response->assertSessionHas('error');

        // Attempt to update
        $response = $this->actingAs($this->admin)->put(route('stock-adjustments.update', $adjustment->id), [
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft', // try to revert status
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_sistem' => 10,
                    'quantity_fisik' => 10,
                ]
            ]
        ]);
        $response->assertRedirect(route('stock-adjustments.index'));
        $response->assertSessionHas('error');
    }

    public function test_cannot_delete_completed_stock_adjustment(): void
    {
        $adjustment = StockAdjustment::create([
            'no_adjustment' => 'ADJ-20260522-002',
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'completed',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('stock-adjustments.destroy', $adjustment->id));
        $response->assertRedirect(route('stock-adjustments.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('stock_adjustments', ['id' => $adjustment->id]);
    }

    public function test_admin_and_staff_can_edit_and_update_draft_stock_adjustment(): void
    {
        $adjustment = StockAdjustment::create([
            'no_adjustment' => 'ADJ-20260522-003',
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft',
            'user_id' => $this->admin->id,
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $this->product->id,
            'quantity_sistem' => 10,
            'quantity_fisik' => 10,
            'selisih' => 0,
        ]);

        // Edit page
        $response = $this->actingAs($this->staff)->get(route('stock-adjustments.edit', $adjustment->id));
        $response->assertStatus(200);

        // Update to completed, physical says 15. We add 5.
        $response = $this->actingAs($this->staff)->put(route('stock-adjustments.update', $adjustment->id), [
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-23',
            'status' => 'completed',
            'catatan' => 'Updated and completed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_sistem' => 10,
                    'quantity_fisik' => 15,
                ]
            ]
        ]);

        $response->assertRedirect(route('stock-adjustments.index'));
        $response->assertSessionHas('success');

        $adjustment->refresh();
        $this->assertEquals('completed', $adjustment->status);
        $this->assertEquals('2026-05-23', $adjustment->tanggal->format('Y-m-d'));
        $this->assertEquals('Updated and completed', $adjustment->catatan);

        // Verify items updated
        $this->assertCount(1, $adjustment->items);
        $this->assertEquals(5, $adjustment->items->first()->selisih);

        // Verify stock added
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
        ]);
    }

    public function test_admin_and_staff_can_delete_draft_stock_adjustment(): void
    {
        $adjustment = StockAdjustment::create([
            'no_adjustment' => 'ADJ-20260522-004',
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('stock-adjustments.destroy', $adjustment->id));
        $response->assertRedirect(route('stock-adjustments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('stock_adjustments', ['id' => $adjustment->id]);
    }
}
