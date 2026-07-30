<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $viewer;
    private Warehouse $sourceWarehouse;
    private Warehouse $destinationWarehouse;
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

        // Warehouses
        $this->sourceWarehouse = Warehouse::create([
            'kode_gudang' => 'GDG-01',
            'nama' => 'Gudang Utama',
            'alamat' => 'Jakarta',
        ]);

        $this->destinationWarehouse = Warehouse::create([
            'kode_gudang' => 'GDG-02',
            'nama' => 'Gudang Cabang',
            'alamat' => 'Surabaya',
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

    public function test_all_authenticated_users_can_view_stock_transfer_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('stock-transfers.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->staff)->get(route('stock-transfers.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->viewer)->get(route('stock-transfers.index'));
        $response->assertStatus(200);
    }

    public function test_admin_and_staff_can_view_create_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('stock-transfers.create'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->staff)->get(route('stock-transfers.create'));
        $response->assertStatus(200);
    }

    public function test_viewer_cannot_view_create_page(): void
    {
        $response = $this->actingAs($this->viewer)->get(route('stock-transfers.create'));
        $response->assertStatus(403);
    }

    public function test_admin_and_staff_can_store_draft_stock_transfer(): void
    {
        $response = $this->actingAs($this->admin)->post(route('stock-transfers.store'), [
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft',
            'catatan' => 'Draft transfer test',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                ]
            ]
        ]);

        $response->assertRedirect(route('stock-transfers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stock_transfers', [
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'status' => 'draft',
            'catatan' => 'Draft transfer test',
            'user_id' => $this->admin->id,
        ]);

        $transfer = StockTransfer::latest()->first();
        $this->assertDatabaseHas('stock_transfer_items', [
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);

        // Assert stock was not adjusted
        $sourceStock = ProductStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->sourceWarehouse->id)
            ->first();
        $this->assertNull($sourceStock);
    }

    public function test_admin_and_staff_can_store_completed_stock_transfer_with_sufficient_stock(): void
    {
        // Seed initial stock to source warehouse
        ProductStock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->sourceWarehouse->id,
            'quantity' => 20
        ]);

        $response = $this->actingAs($this->staff)->post(route('stock-transfers.store'), [
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'completed',
            'catatan' => 'Completed transfer test',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                ]
            ]
        ]);

        $response->assertRedirect(route('stock-transfers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stock_transfers', [
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'status' => 'completed',
            'user_id' => $this->staff->id,
        ]);

        // Assert stocks updated correctly
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->sourceWarehouse->id,
            'quantity' => 10, // 20 - 10
        ]);

        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->destinationWarehouse->id,
            'quantity' => 10, // 0 + 10
        ]);

        // Verify stock movements logged
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->sourceWarehouse->id,
            'type' => 'out',
            'quantity' => 10,
            'reference_type' => 'stock_transfer',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->destinationWarehouse->id,
            'type' => 'in',
            'quantity' => 10,
            'reference_type' => 'stock_transfer',
        ]);
    }

    public function test_completed_stock_transfer_validation_with_insufficient_stock(): void
    {
        // Seed low stock to source warehouse
        ProductStock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->sourceWarehouse->id,
            'quantity' => 5
        ]);

        $response = $this->actingAs($this->admin)->post(route('stock-transfers.store'), [
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10, // 10 > 5, should fail
                ]
            ]
        ]);

        $response->assertStatus(302); // Redirect back
        $response->assertSessionHas('error');

        // Verify stock didn't change
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->sourceWarehouse->id,
            'quantity' => 5,
        ]);
    }

    public function test_viewer_cannot_store_stock_transfer(): void
    {
        $response = $this->actingAs($this->viewer)->post(route('stock-transfers.store'), [
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                ]
            ]
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_edit_or_update_completed_stock_transfer(): void
    {
        $transfer = StockTransfer::create([
            'no_transfer' => 'TRF-20260522-001',
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'completed',
            'user_id' => $this->admin->id,
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => 10
        ]);

        // Attempt to edit
        $response = $this->actingAs($this->admin)->get(route('stock-transfers.edit', $transfer->id));
        $response->assertRedirect(route('stock-transfers.index'));
        $response->assertSessionHas('error');

        // Attempt to update
        $response = $this->actingAs($this->admin)->put(route('stock-transfers.update', $transfer->id), [
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft', // try to revert status
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5
                ]
            ]
        ]);
        $response->assertRedirect(route('stock-transfers.index'));
        $response->assertSessionHas('error');
    }

    public function test_cannot_delete_completed_stock_transfer(): void
    {
        $transfer = StockTransfer::create([
            'no_transfer' => 'TRF-20260522-002',
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'completed',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('stock-transfers.destroy', $transfer->id));
        $response->assertRedirect(route('stock-transfers.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id]);
    }

    public function test_admin_and_staff_can_edit_and_update_draft_stock_transfer(): void
    {
        // Seed stock so we can complete it during update
        ProductStock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->sourceWarehouse->id,
            'quantity' => 50
        ]);

        $transfer = StockTransfer::create([
            'no_transfer' => 'TRF-20260522-003',
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft',
            'user_id' => $this->admin->id,
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => 10
        ]);

        // Edit page
        $response = $this->actingAs($this->staff)->get(route('stock-transfers.edit', $transfer->id));
        $response->assertStatus(200);

        // Update to completed
        $response = $this->actingAs($this->staff)->put(route('stock-transfers.update', $transfer->id), [
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'tanggal' => '2026-05-23',
            'status' => 'completed',
            'catatan' => 'Updated and completed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 15 // change qty to 15
                ]
            ]
        ]);

        $response->assertRedirect(route('stock-transfers.index'));
        $response->assertSessionHas('success');

        $transfer->refresh();
        $this->assertEquals('completed', $transfer->status);
        $this->assertEquals('2026-05-23', $transfer->tanggal->format('Y-m-d'));
        $this->assertEquals('Updated and completed', $transfer->catatan);

        // Verify items updated
        $this->assertCount(1, $transfer->items);
        $this->assertEquals(15, $transfer->items->first()->quantity);

        // Verify stock deducted/added
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->sourceWarehouse->id,
            'quantity' => 35, // 50 - 15
        ]);

        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->destinationWarehouse->id,
            'quantity' => 15, // 0 + 15
        ]);
    }

    public function test_admin_and_staff_can_delete_draft_stock_transfer(): void
    {
        $transfer = StockTransfer::create([
            'no_transfer' => 'TRF-20260522-004',
            'source_warehouse_id' => $this->sourceWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('stock-transfers.destroy', $transfer->id));
        $response->assertRedirect(route('stock-transfers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('stock_transfers', ['id' => $transfer->id]);
    }
}
