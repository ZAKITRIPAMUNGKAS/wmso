<?php

namespace App\Observers;

use App\Models\StockMovement;
use App\Jobs\SyncStockToOlshop;

class StockMovementObserver
{
    /**
     * Handle the StockMovement "created" event.
     */
    public function created(StockMovement $stockMovement): void
    {
        // Set calculated_at when the observer triggers
        $calculatedAt = now()->format('Y-m-d\TH:i:s\Z');
        
        SyncStockToOlshop::dispatch(
            $stockMovement->product_id, 
            $calculatedAt
        );
    }
}
