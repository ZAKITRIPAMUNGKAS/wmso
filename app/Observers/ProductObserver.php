<?php

namespace App\Observers;

use App\Models\Product;
use App\Jobs\SyncStockToOlshop;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        SyncStockToOlshop::dispatch(
            $product->id,
            now()->format('Y-m-d\TH:i:s\Z')
        );
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        SyncStockToOlshop::dispatch(
            $product->id,
            now()->format('Y-m-d\TH:i:s\Z')
        );
    }
}
