<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KodeBarangGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(Role::class)) {
            Role::firstOrCreate(['name' => 'admin']);
        }

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        if (method_exists($this->admin, 'assignRole')) {
            $this->admin->assignRole('admin');
        }
        
        Queue::fake(); // Prevent dispatching SyncStockToOlshop
    }
    
    private function createProductPayload()
    {
        return [
            'nama' => 'Test Product',
            'merk' => 'Merk',
            'tipe' => 'Tipe',
            'satuan' => 'Pcs',
            'harga' => 1000,
            'stok_minimum' => 5,
        ];
    }

    public function test_first_product_gets_000001()
    {
        $this->actingAs($this->admin)->post(route('products.store'), $this->createProductPayload());
        
        $product = Product::first();
        $this->assertEquals('000001', $product->kode_barang);
    }

    public function test_second_product_gets_000002()
    {
        Product::create(array_merge($this->createProductPayload(), ['kode_barang' => '000001']));
        
        $this->actingAs($this->admin)->post(route('products.store'), $this->createProductPayload());
        
        $product = Product::orderBy('id', 'desc')->first();
        $this->assertEquals('000002', $product->kode_barang);
    }

    public function test_after_000099_gets_000100()
    {
        Product::create(array_merge($this->createProductPayload(), ['kode_barang' => '000099']));
        
        $this->actingAs($this->admin)->post(route('products.store'), $this->createProductPayload());
        
        $product = Product::orderBy('id', 'desc')->first();
        $this->assertEquals('000100', $product->kode_barang);
    }
    
    public function test_skips_non_numeric_codes()
    {
        // First product numeric
        Product::create(array_merge($this->createProductPayload(), ['kode_barang' => '000005']));
        // Another product with string code
        Product::create(array_merge($this->createProductPayload(), ['kode_barang' => 'PRD-001']));
        Product::create(array_merge($this->createProductPayload(), ['kode_barang' => 'ABC-999']));
        
        $this->actingAs($this->admin)->post(route('products.store'), $this->createProductPayload());
        
        $product = Product::orderBy('id', 'desc')->first();
        // The generation logic looks for the max numeric code, which is 000005
        $this->assertEquals('000006', $product->kode_barang);
    }
    
    public function test_up_to_999999_capacity()
    {
        Product::create(array_merge($this->createProductPayload(), ['kode_barang' => '999998']));
        
        $this->actingAs($this->admin)->post(route('products.store'), $this->createProductPayload());
        
        $product = Product::orderBy('id', 'desc')->first();
        $this->assertEquals('999999', $product->kode_barang);
    }
}
