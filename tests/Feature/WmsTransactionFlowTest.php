<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WmsTransactionFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Warehouse $warehouse;
    private Supplier $supplier;
    private Customer $customer;
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

        $this->warehouse = Warehouse::create([
            'kode_gudang' => 'GDG-01',
            'nama' => 'Gudang Utama',
            'alamat' => 'Jakarta',
        ]);

        $this->supplier = Supplier::create([
            'nama' => 'Supplier A',
            'kontak' => '0812345678',
            'alamat' => 'Alamat Supplier A',
        ]);

        $this->customer = Customer::create([
            'nama' => 'Customer A',
            'alamat' => 'Alamat Customer A',
            'kontak' => '0812345679',
        ]);

        $this->product = Product::create([
            'kode_barang' => 'PRD-001',
            'nama' => 'Kabel UTP Cat 6',
            'merk' => 'Belden',
            'tipe' => 'Indoor',
            'satuan' => 'Roll',
            'harga' => 100000,
            'stok_minimum' => 5,
        ]);
    }

    public function test_goods_receipt_stores_successfully_and_adds_stock(): void
    {
        $response = $this->actingAs($this->admin)->post(route('barang-masuk.store'), [
            'purchase_order_id' => '',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-21',
            'catatan' => 'Penerimaan tes',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 20,
                ]
            ]
        ]);

        $response->assertRedirect(route('barang-masuk.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('goods_receipts', [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'catatan' => 'Penerimaan tes',
        ]);

        // Verify stock is added
        $stock = ProductStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(20, $stock->quantity);
    }

    public function test_delivery_order_stores_successfully_deducts_stock_and_creates_invoice(): void
    {
        // 1. First add stock via ProductStock directly to isolate DO logic
        ProductStock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 15,
        ]);

        // 2. Create Delivery Order
        $response = $this->actingAs($this->admin)->post(route('barang-keluar.store'), [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-21',
            'po_number' => 'PO-999',
            'payment_term' => 'tempo',
            'jenis_pembayaran' => 'tempo',
            'tempo_hari' => 30,
            'total' => 1000000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'harga' => 100000,
                    'subtotal' => 1000000,
                ]
            ]
        ]);

        $response->assertRedirect(route('barang-keluar.index'));
        $response->assertSessionHas('success');

        // Assert DO created
        $do = DeliveryOrder::first();
        $this->assertNotNull($do);
        $this->assertEquals('sent', $do->status);

        // Assert stock decremented (15 - 10 = 5)
        $stock = ProductStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(5, $stock->quantity);

        // Assert Invoice is generated
        $invoice = Invoice::where('delivery_order_id', $do->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('belum_lunas', $invoice->status);
        $this->assertEquals(1000000, $invoice->total);
        $this->assertEquals(0, $invoice->paid_amount);
    }

    public function test_delivery_order_fails_when_stock_is_insufficient(): void
    {
        // Zero stock in warehouse
        $response = $this->actingAs($this->admin)->post(route('barang-keluar.store'), [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-21',
            'jenis_pembayaran' => 'cash',
            'total' => 100000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'harga' => 10000,
                    'subtotal' => 100000,
                ]
            ]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseEmpty('delivery_orders');
    }

    public function test_payments_can_be_recorded_and_updates_invoice_status(): void
    {
        // Set up DO and Invoice
        $do = DeliveryOrder::create([
            'no_sj' => 'SJ-20260521-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-21',
            'payment_term' => 'tempo',
            'total' => 500000,
            'status' => 'sent',
            'user_id' => $this->admin->id,
        ]);

        $invoice = Invoice::create([
            'no_invoice' => 'INV-20260521-001',
            'delivery_order_id' => $do->id,
            'tanggal' => '2026-05-21',
            'payment_term' => 'tempo',
            'total' => 500000,
            'paid_amount' => 0,
            'status' => 'belum_lunas',
            'jenis_pembayaran' => 'tempo',
            'tempo_hari' => 30,
            'due_date' => '2026-06-20',
        ]);

        // Record Partial Payment
        $response = $this->actingAs($this->admin)->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'nominal' => 200000,
            'tanggal' => '2026-05-22',
            'metode' => 'transfer',
            'keterangan' => 'DP Pembayaran',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals(200000, $invoice->paid_amount);
        $this->assertEquals('partial', $invoice->status);

        // Record Remaining Payment
        $response = $this->actingAs($this->admin)->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'nominal' => 300000,
            'tanggal' => '2026-05-23',
            'metode' => 'transfer',
            'keterangan' => 'Pelunasan',
        ]);

        $response->assertRedirect();
        $invoice->refresh();
        $this->assertEquals(500000, $invoice->paid_amount);
        $this->assertEquals('lunas', $invoice->status);
    }

    public function test_invoice_pdf_download_compiles_successfully(): void
    {
        $do = DeliveryOrder::create([
            'no_sj' => 'SJ-20260521-002',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-21',
            'payment_term' => 'cash',
            'total' => 150000,
            'status' => 'sent',
            'user_id' => $this->admin->id,
        ]);

        $invoice = Invoice::create([
            'no_invoice' => 'INV-20260521-002',
            'delivery_order_id' => $do->id,
            'tanggal' => '2026-05-21',
            'payment_term' => 'cash',
            'total' => 150000,
            'paid_amount' => 0,
            'status' => 'belum_lunas',
            'jenis_pembayaran' => 'cash',
            'tempo_hari' => 0,
            'due_date' => '2026-05-21',
        ]);

        // Create DO Item to be rendered in the template
        \App\Models\DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'harga' => 150000,
            'subtotal' => 150000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('invoices.pdf', $invoice->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_surat_jalan_pdf_template_compiles_without_errors(): void
    {
        $do = DeliveryOrder::create([
            'no_sj' => 'SJ-20260521-003',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'tanggal' => '2026-05-21',
            'payment_term' => 'cash',
            'total' => 100000,
            'status' => 'sent',
            'user_id' => $this->admin->id,
        ]);

        \App\Models\DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'harga' => 100000,
            'subtotal' => 100000,
        ]);

        // Manually render the view to verify no date formatting or other compile errors
        $viewContent = view('pdf.surat-jalan', ['deliveryOrder' => $do->load(['customer', 'warehouse', 'items.product'])])->render();

        $this->assertStringContainsString('SURAT JALAN', $viewContent);
        $this->assertStringContainsString($do->no_sj, $viewContent);
        $this->assertStringContainsString($this->product->nama, $viewContent);
    }
}
