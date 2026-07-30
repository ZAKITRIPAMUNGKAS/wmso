<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class ExportWmsSkus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sku:export-wms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export WMS products to a CSV file for mapping';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Mengekspor produk WMS...");

        $products = Product::all();
        if ($products->isEmpty()) {
            $this->error("Tidak ada produk di database WMS.");
            return 1;
        }

        $filename = "wms_products_export.csv";
        $filepath = base_path($filename);
        $file = fopen($filepath, 'w');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV Header
        fputcsv($file, ['kode_barang', 'nama', 'merk', 'tipe', 'harga']);

        foreach ($products as $product) {
            fputcsv($file, [
                $product->kode_barang,
                $product->nama,
                $product->merk,
                $product->tipe,
                $product->harga,
            ]);
        }

        fclose($file);

        $this->info("Sukses mengekspor {$products->count()} produk ke: {$filepath}");
        return 0;
    }
}
