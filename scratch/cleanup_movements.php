<?php

use App\Models\StockMovement;
use App\Models\GoodsReceipt;
use App\Models\DeliveryOrder;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting cleanup...\n";

// 1. Delete deletion movements
$deletedCount = StockMovement::whereIn('reference_type', ['goods_receipt_deletion', 'delivery_order_deletion'])->delete();
echo "Deleted $deletedCount reversal records.\n";

// 2. Delete orphaned Goods Receipt movements
$receiptMovements = StockMovement::where('reference_type', 'goods_receipt')->get();
$orphanedReceipts = 0;
foreach($receiptMovements as $m) {
    if (!GoodsReceipt::find($m->reference_id)) {
        $m->delete();
        $orphanedReceipts++;
    }
}
echo "Deleted $orphanedReceipts orphaned Goods Receipt movements.\n";

// 3. Delete orphaned Delivery Order movements
$doMovements = StockMovement::where('reference_type', 'delivery_order')->get();
$orphanedDos = 0;
foreach($doMovements as $m) {
    if (!DeliveryOrder::find($m->reference_id)) {
        $m->delete();
        $orphanedDos++;
    }
}
echo "Deleted $orphanedDos orphaned Delivery Order movements.\n";

echo "Cleanup finished.\n";
