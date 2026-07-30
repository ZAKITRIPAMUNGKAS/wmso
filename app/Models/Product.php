<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

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
