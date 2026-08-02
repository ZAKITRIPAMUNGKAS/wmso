<?php

namespace Tests\Unit;

use App\Models\ProductImage;
use Tests\TestCase;

class ProductImageModelTest extends TestCase
{
    public function test_get_image_url_attribute_returns_url_as_is()
    {
        $image = new ProductImage([
            'image_path' => 'http://example.com/image.jpg'
        ]);

        $this->assertEquals('http://example.com/image.jpg', $image->image_url);
    }

    public function test_get_image_url_attribute_returns_asset_url_for_relative_path()
    {
        $image = new ProductImage([
            'image_path' => 'products/image.jpg'
        ]);

        $this->assertEquals(asset('storage/products/image.jpg'), $image->image_url);
    }
}
