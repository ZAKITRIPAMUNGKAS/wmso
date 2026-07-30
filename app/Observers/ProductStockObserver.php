<?php

namespace App\Observers;

use App\Models\ProductStock;
use App\Jobs\SyncStockToOlshop;

class ProductStockObserver
{
    /**
     * Handle the ProductStock "saved" event.
     */
    public function saved(ProductStock $stock): void
    {
        SyncStockToOlshop::dispatch(
            $stock->product_id,
            now()->format('Y-m-d\TH:i:s\Z')
        );
    }

    /**
     * Handle the ProductStock "deleted" event.
     */
    public function deleted(ProductStock $stock): void
    {
        SyncStockToOlshop::dispatch(
            $stock->product_id,
            now()->format('Y-m-d\TH:i:s\Z')
        );
    }
}
