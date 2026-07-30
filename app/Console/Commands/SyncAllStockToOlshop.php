<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductStock;
use App\Jobs\SyncStockToOlshop;

class SyncAllStockToOlshop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:all-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi seluruh stok produk dari WMS ke Toko Online (Olshop)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Memulai sinkronisasi stok massal dari WMS ke Olshop...");

        $products = Product::all();
        $count = 0;

        foreach ($products as $product) {
            $totalStock = ProductStock::where('product_id', $product->id)->sum('quantity');
            
            // Dispatch Sync Stock Job
            SyncStockToOlshop::dispatch($product->id, now()->format('Y-m-d\TH:i:s\Z'));
            
            $this->line("Synced [{$product->kode_barang}] {$product->nama} -> Qty: {$totalStock}");
            $count++;
        }

        $this->info("Berhasil menjadwalkan sinkronisasi {$count} produk ke Olshop!");
        return 0;
    }
}
