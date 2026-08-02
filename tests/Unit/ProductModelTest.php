<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_image_url_attribute_prioritizes_primary_image()
    {
        $product = Product::create([
            'kode_barang' => '000001',
            'nama' => 'Test',
            'merk' => 'Test',
            'tipe' => 'Test',
            'satuan' => 'Pcs',
            'harga' => 100,
            'stok_minimum' => 1,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/second.jpg',
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/first.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->assertEquals(asset('storage/products/first.jpg'), $product->image_url);
    }

    public function test_get_image_url_attribute_falls_back_to_image_column()
    {
        $product = Product::create([
            'kode_barang' => '000002',
            'nama' => 'Test',
            'merk' => 'Test',
            'tipe' => 'Test',
            'satuan' => 'Pcs',
            'harga' => 100,
            'stok_minimum' => 1,
            'image' => 'products/fallback.jpg',
        ]);

        $this->assertEquals(asset('storage/products/fallback.jpg'), $product->image_url);
    }
    
    public function test_get_image_url_returns_null_if_no_image()
    {
        $product = Product::create([
            'kode_barang' => '000002',
            'nama' => 'Test',
            'merk' => 'Test',
            'tipe' => 'Test',
            'satuan' => 'Pcs',
            'harga' => 100,
            'stok_minimum' => 1,
        ]);

        $this->assertNull($product->image_url);
    }

    public function test_get_gallery_image_urls_returns_all_images()
    {
        $product = Product::create([
            'kode_barang' => '000003',
            'nama' => 'Test',
            'merk' => 'Test',
            'tipe' => 'Test',
            'satuan' => 'Pcs',
            'harga' => 100,
            'stok_minimum' => 1,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/img1.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/img2.jpg',
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        $gallery = $product->gallery_image_urls;
        $this->assertCount(2, $gallery);
        $this->assertEquals(asset('storage/products/img1.jpg'), $gallery[0]);
        $this->assertEquals(asset('storage/products/img2.jpg'), $gallery[1]);
    }
    
    public function test_get_gallery_image_urls_falls_back_to_image_url()
    {
        $product = Product::create([
            'kode_barang' => '000003',
            'nama' => 'Test',
            'merk' => 'Test',
            'tipe' => 'Test',
            'satuan' => 'Pcs',
            'harga' => 100,
            'stok_minimum' => 1,
            'image' => 'http://example.com/img.jpg'
        ]);
        
        $gallery = $product->gallery_image_urls;
        $this->assertCount(1, $gallery);
        $this->assertEquals('http://example.com/img.jpg', $gallery[0]);
    }
}
