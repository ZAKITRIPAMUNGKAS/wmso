<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\FailedSyncLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncStockToOlshop implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $productId;
    public $calculatedAt;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 20;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->productId;
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(int $productId, string $calculatedAt)
    {
        $this->productId = $productId;
        $this->calculatedAt = $calculatedAt;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $product = Product::find($this->productId);
        if (!$product) {
            Log::channel('api_sync')->warning("SyncStockToOlshop: Product ID {$this->productId} not found in WMS.");
            return;
        }

        // Calculate total stock from all warehouses
        $totalStock = ProductStock::where('product_id', $this->productId)->sum('quantity');

        $olshopUrl = config('services.api.olshop_url') . '/api/v1/products/sync-stock';
        $token = config('services.api.wms_token');

        if (empty($token)) {
            throw new \Exception("SyncStockToOlshop: WMS_API_TOKEN / services.api.wms_token is empty or not configured.");
        }

        // Send POST request with timeout and retries
        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->retry(2, 100)
                ->post($olshopUrl, [
                    'kode_barang'   => $product->kode_barang,
                    'nama'          => $product->nama,
                    'harga'         => (float) $product->harga,
                    'total_stock'   => (int) $totalStock,
                    'calculated_at' => $this->calculatedAt,
                ]);

            if ($response->failed()) {
                Log::channel('api_sync')->warning("SyncStockToOlshop HTTP Warning [Status {$response->status()}]: " . $response->body());
                FailedSyncLog::create([
                    'type'          => 'stock_sync',
                    'payload'       => [
                        'product_id'    => $this->productId,
                        'kode_barang'   => $product->kode_barang,
                        'total_stock'   => $totalStock,
                        'calculated_at' => $this->calculatedAt,
                        'status'        => $response->status(),
                        'error'         => $response->body()
                    ],
                    'error_message' => 'Olshop API Status ' . $response->status()
                ]);
            } else {
                Log::channel('api_sync')->info("SyncStockToOlshop Success: Product [{$product->kode_barang}] synced to Olshop. Qty: {$totalStock}");
            }
        } catch (Throwable $e) {
            Log::channel('api_sync')->error("SyncStockToOlshop Exception: " . $e->getMessage());
            FailedSyncLog::create([
                'type'          => 'stock_sync',
                'payload'       => [
                    'product_id'    => $this->productId,
                    'kode_barang'   => $product->kode_barang,
                    'total_stock'   => $totalStock,
                    'calculated_at' => $this->calculatedAt,
                ],
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::channel('api_sync')->error("SyncStockToOlshop Failed: Product ID {$this->productId}. Error: " . $exception->getMessage());

        try {
            $product = Product::find($this->productId);
            $kodeBarang = $product ? $product->kode_barang : 'Unknown';
            $totalStock = ProductStock::where('product_id', $this->productId)->sum('quantity');

            FailedSyncLog::create([
                'type'          => 'stock_sync',
                'payload'       => [
                    'product_id'    => $this->productId,
                    'kode_barang'   => $kodeBarang,
                    'total_stock'   => $totalStock,
                    'calculated_at' => $this->calculatedAt,
                ],
                'error_message' => $exception->getMessage(),
                'attempts'      => $this->attempts(),
            ]);
        } catch (\Exception $e) {
            Log::channel('api_sync')->error("SyncStockToOlshop: Gagal menyimpan log kegagalan ke database: " . $e->getMessage());
        }
    }
}
