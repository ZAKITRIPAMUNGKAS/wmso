<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $viewer;
    private Supplier $supplier;
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

        // Supplier
        $this->supplier = Supplier::create([
            'nama' => 'Supplier Test',
            'kontak' => '0812345678',
            'alamat' => 'Alamat Supplier Test',
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

    public function test_all_authenticated_users_can_view_purchase_order_index(): void
    {
        // Admin
        $response = $this->actingAs($this->admin)->get(route('purchase-orders.index'));
        $response->assertStatus(200);

        // Staff
        $response = $this->actingAs($this->staff)->get(route('purchase-orders.index'));
        $response->assertStatus(200);

        // Viewer
        $response = $this->actingAs($this->viewer)->get(route('purchase-orders.index'));
        $response->assertStatus(200);
    }

    public function test_admin_and_staff_can_view_create_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('purchase-orders.create'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->staff)->get(route('purchase-orders.create'));
        $response->assertStatus(200);
    }

    public function test_viewer_cannot_view_create_page(): void
    {
        $response = $this->actingAs($this->viewer)->get(route('purchase-orders.create'));
        $response->assertStatus(403);
    }

    public function test_admin_and_staff_can_store_purchase_order(): void
    {
        $response = $this->actingAs($this->admin)->post(route('purchase-orders.store'), [
            'supplier_id' => $this->supplier->id,
            'tanggal' => '2026-05-22',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'harga' => 45000,
                ]
            ]
        ]);

        $response->assertRedirect(route('purchase-orders.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $this->supplier->id,
            'status' => 'draft',
            'total' => 450000,
            'user_id' => $this->admin->id,
        ]);

        $po = PurchaseOrder::latest()->first();
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'harga' => 45000,
            'subtotal' => 450000,
        ]);
    }

    public function test_viewer_cannot_store_purchase_order(): void
    {
        $response = $this->actingAs($this->viewer)->post(route('purchase-orders.store'), [
            'supplier_id' => $this->supplier->id,
            'tanggal' => '2026-05-22',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'harga' => 45000,
                ]
            ]
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseEmpty('purchase_orders');
    }

    public function test_users_can_view_purchase_order_details(): void
    {
        $po = PurchaseOrder::create([
            'no_po' => 'PO-20260522-001',
            'supplier_id' => $this->supplier->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft',
            'total' => 450000,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->viewer)->get(route('purchase-orders.show', $po->id));
        $response->assertStatus(200);
    }

    public function test_admin_and_staff_can_edit_draft_or_confirmed_purchase_order(): void
    {
        $po = PurchaseOrder::create([
            'no_po' => 'PO-20260522-002',
            'supplier_id' => $this->supplier->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft',
            'total' => 450000,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->staff)->get(route('purchase-orders.edit', $po->id));
        $response->assertStatus(200);
    }

    public function test_viewer_cannot_edit_purchase_order(): void
    {
        $po = PurchaseOrder::create([
            'no_po' => 'PO-20260522-003',
            'supplier_id' => $this->supplier->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft',
            'total' => 450000,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->viewer)->get(route('purchase-orders.edit', $po->id));
        $response->assertStatus(403);
    }

    public function test_cannot_edit_received_purchase_order(): void
    {
        $po = PurchaseOrder::create([
            'no_po' => 'PO-20260522-004',
            'supplier_id' => $this->supplier->id,
            'tanggal' => '2026-05-22',
            'status' => 'received',
            'total' => 450000,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('purchase-orders.edit', $po->id));
        $response->assertRedirect(route('purchase-orders.index'));
        $response->assertSessionHas('error');
    }

    public function test_admin_and_staff_can_update_purchase_order(): void
    {
        $po = PurchaseOrder::create([
            'no_po' => 'PO-20260522-005',
            'supplier_id' => $this->supplier->id,
            'tanggal' => '2026-05-22',
            'status' => 'draft',
            'total' => 450000,
            'user_id' => $this->admin->id,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'harga' => 45000,
            'subtotal' => 450000,
        ]);

        $response = $this->actingAs($this->admin)->put(route('purchase-orders.update', $po->id), [
            'supplier_id' => $this->supplier->id,
            'tanggal' => '2026-05-23',
            'status' => 'confirmed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'harga' => 50000,
                ]
            ]
        ]);

        $response->assertRedirect(route('purchase-orders.index'));
        $response->assertSessionHas('success');

        $po->refresh();
        $this->assertEquals('confirmed', $po->status);
        $this->assertEquals('2026-05-23', $po->tanggal->format('Y-m-d'));
        $this->assertEquals(250000, $po->total);

        // Verify items updated
        $this->assertCount(1, $po->items);
        $this->assertEquals(5, $po->items->first()->quantity);
        $this->assertEquals(50000, $po->items->first()->harga);
    }

    public function test_cannot_update_received_purchase_order(): void
    {
        $po = PurchaseOrder::create([
            'no_po' => 'PO-20260522-006',
            'supplier_id' => $this->supplier->id,
            'tanggal' => '2026-05-22',
            'status' => 'received',
            'total' => 450000,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('purchase-orders.update', $po->id), [
            'supplier_id' => $this->supplier->id,
            'tanggal' => '2026-05-22',
            'status' => 'confirmed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'harga' => 50000,
                ]
            ]
        ]);

        $response->assertRedirect(route('purchase-orders.index'));
        $response->assertSessionHas('error');
    }

    public function test_admin_and_staff_can_delete_draft_or_confirmed_purchase_order(): void
    {
        $po = PurchaseOrder::create([
            'no_po' => 'PO-20260522-007',
            'supplier_id' => $this->supplier->id,
            'tanggal' => '2026-05-22',
            'status' => 'confirmed',
            'total' => 450000,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('purchase-orders.destroy', $po->id));
        $response->assertRedirect(route('purchase-orders.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('purchase_orders', ['id' => $po->id]);
    }

    public function test_cannot_delete_received_purchase_order(): void
    {
        $po = PurchaseOrder::create([
            'no_po' => 'PO-20260522-008',
            'supplier_id' => $this->supplier->id,
            'tanggal' => '2026-05-22',
            'status' => 'received',
            'total' => 450000,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('purchase-orders.destroy', $po->id));
        $response->assertRedirect(route('purchase-orders.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id]);
    }
}
