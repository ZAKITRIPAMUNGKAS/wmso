<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function racks(): HasMany
    {
        return $this->hasMany(Rack::class);
    }

    public function rackStocks(): HasMany
    {
        return $this->hasMany(ProductRackStock::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
