<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductControllerTest extends TestCase
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
    }

    public function test_index_returns_products_paginated()
    {
        Product::create([
            'kode_barang' => '000001',
            'nama' => 'Test Product',
            'merk' => 'Test',
            'tipe' => 'Test',
            'satuan' => 'Pcs',
            'harga' => 100,
            'stok_minimum' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('products.index'));
        $response->assertOk();
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->actingAs($this->admin)->post(route('products.store'), []);
        
        $response->assertSessionHasErrors(['nama', 'merk', 'tipe', 'satuan', 'harga', 'stok_minimum']);
    }

    public function test_store_generates_kode_barang_and_dispatches_job()
    {
        Queue::fake();
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post(route('products.store'), [
            'nama' => 'New Product',
            'merk' => 'Merk',
            'tipe' => 'Tipe',
            'satuan' => 'Pcs',
            'harga' => 1000,
            'stok_minimum' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $product = Product::first();
        $this->assertEquals('000001', $product->kode_barang);
        $this->assertEquals('New Product', $product->nama);

        Queue::assertPushed(\App\Jobs\SyncStockToOlshop::class);
    }

    public function test_store_respects_manual_kode_barang()
    {
        Queue::fake();
        
        $response = $this->actingAs($this->admin)->post(route('products.store'), [
            'kode_barang' => 'MANUAL-123',
            'nama' => 'New Product',
            'merk' => 'Merk',
            'tipe' => 'Tipe',
            'satuan' => 'Pcs',
            'harga' => 1000,
            'stok_minimum' => 5,
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('products', [
            'kode_barang' => 'MANUAL-123',
            'nama' => 'New Product'
        ]);
    }

    public function test_store_handles_single_image_and_multiple_images()
    {
        Queue::fake();
        Storage::fake('public');

        $singleImage = UploadedFile::fake()->image('photo.jpg');
        $galleryImage1 = UploadedFile::fake()->image('gallery1.jpg');
        $galleryImage2 = UploadedFile::fake()->image('gallery2.jpg');

        $response = $this->actingAs($this->admin)->post(route('products.store'), [
            'nama' => 'New Product',
            'merk' => 'Merk',
            'tipe' => 'Tipe',
            'satuan' => 'Pcs',
            'harga' => 1000,
            'stok_minimum' => 5,
            'image' => $singleImage,
            'images' => [$galleryImage1, $galleryImage2]
        ]);

        $product = Product::with('images')->first();
        
        $this->assertNotNull($product->image);
        Storage::disk('public')->assertExists($product->image);
        
        $this->assertCount(2, $product->images);
        $this->assertTrue((bool)$product->images[0]->is_primary);
        $this->assertFalse((bool)$product->images[1]->is_primary);
    }

    public function test_update_updates_fields_and_replaces_images()
    {
        Queue::fake();
        Storage::fake('public');

        $product = Product::create([
            'kode_barang' => '000001',
            'nama' => 'Old Product',
            'merk' => 'Merk',
            'tipe' => 'Tipe',
            'satuan' => 'Pcs',
            'harga' => 1000,
            'stok_minimum' => 5,
        ]);

        $newImage = UploadedFile::fake()->image('newphoto.jpg');
        $newGallery = UploadedFile::fake()->image('newgallery.jpg');

        $response = $this->actingAs($this->admin)->put(route('products.update', $product->id), [
            'nama' => 'Updated Product',
            'merk' => 'Merk2',
            'tipe' => 'Tipe2',
            'satuan' => 'Pcs2',
            'harga' => 2000,
            'stok_minimum' => 10,
            'image' => $newImage,
            'images' => [$newGallery]
        ]);

        $response->assertRedirect();
        
        $product->refresh();
        $this->assertEquals('Updated Product', $product->nama);
        
        $this->assertNotNull($product->image);
        $this->assertCount(1, $product->images);

        Queue::assertPushed(\App\Jobs\SyncStockToOlshop::class);
    }

    public function test_destroy_deletes_product()
    {
        $product = Product::create([
            'kode_barang' => '000001',
            'nama' => 'To Be Deleted',
            'merk' => 'Merk',
            'tipe' => 'Tipe',
            'satuan' => 'Pcs',
            'harga' => 1000,
            'stok_minimum' => 5,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('products.destroy', $product->id));
        $response->assertRedirect();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
