<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['image_url', 'gallery_image_urls'];

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function getImageUrlAttribute(): ?string
    {
        // Bug fix: use $this->images (eager-loaded collection) instead of $this->images()
        // to avoid N+1 queries and infinite recursion when image_url is in $appends
        $images = $this->relationLoaded('images')
            ? $this->getRelation('images')
            : $this->images()->get();

        $primary = $images->firstWhere('is_primary', true) ?? $images->first();
        if ($primary) {
            return $primary->image_url;
        }

        if ($this->image) {
            if (filter_var($this->image, FILTER_VALIDATE_URL)) {
                return $this->image;
            }
            return asset('storage/' . $this->image);
        }
        return null;
    }

    public function getGalleryImageUrlsAttribute(): array
    {
        // Use already-loaded relation to prevent N+1 queries
        $images = $this->relationLoaded('images')
            ? $this->getRelation('images')
            : $this->images()->get();

        $urls = $images->map(fn($img) => $img->image_url)->filter()->values()->toArray();

        if (empty($urls) && $this->image) {
            // Fallback: use the single image column
            $url = filter_var($this->image, FILTER_VALIDATE_URL)
                ? $this->image
                : asset('storage/' . $this->image);
            $urls[] = $url;
        }

        return array_values(array_unique($urls));
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function rackStocks(): HasMany
    {
        return $this->hasMany(ProductRackStock::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceiptItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function deliveryOrderItems(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Scope for products with low stock.
     */
    public function scopeLowStock($query, $threshold = null)
    {
        $query->select('products.*')
            ->withSum('stocks as total_stock', 'quantity');

        if ($threshold !== null) {
            return $query->whereRaw('(select COALESCE(sum(quantity), 0) from product_stocks where product_stocks.product_id = products.id) < ?', [$threshold]);
        }

        return $query->whereRaw('(select COALESCE(sum(quantity), 0) from product_stocks where product_stocks.product_id = products.id) < COALESCE(products.stok_minimum, 0)');
    }
}
