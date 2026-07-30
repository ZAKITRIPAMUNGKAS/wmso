<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Observers\ProductObserver;
use App\Observers\ProductStockObserver;
use App\Observers\StockMovementObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Register Model Observers for 100% Automatic Real-Time Sync to Olshop
        Product::observe(ProductObserver::class);
        ProductStock::observe(ProductStockObserver::class);
        StockMovement::observe(StockMovementObserver::class);

        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
