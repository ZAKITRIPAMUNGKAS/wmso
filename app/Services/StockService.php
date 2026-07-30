<?php

namespace App\Services;

use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function adjustStock(int $productId, int $warehouseId, int $quantity, string $type, string $referenceType, int $referenceId, int $userId, array $details = [])
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $type, $referenceType, $referenceId, $userId, $details) {
            $product = \App\Models\Product::find($productId);
            
            $stock = ProductStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                $stock = ProductStock::create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0
                ]);
            }

            $saldoSebelum = $stock->quantity;
            
            if ($type === 'in') {
                $stock->quantity += $quantity;
            } else {
                if ($stock->quantity < $quantity) {
                    throw new \Exception("Stok tidak mencukupi di gudang terpilih.");
                }
                $stock->quantity -= $quantity;
            }

            $stock->save();

            $saldoSesudah = $stock->quantity;

            // Extract details
            $rackId = $details['rack_id'] ?? null;
            $batchNumber = $details['batch_number'] ?? null;
            $expiredAt = $details['expired_at'] ?? null;
            $serialNumber = $details['serial_number'] ?? null;

            // Record stock movement with details
            StockMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => $type,
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'user_id' => $userId,
                'rack_id' => $rackId,
                'batch_number' => $batchNumber,
                'expired_at' => $expiredAt,
                'serial_number' => $serialNumber
            ]);

            // Update detailed stock (product_rack_stocks)
            if ($type === 'in') {
                $rackStock = \App\Models\ProductRackStock::where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('rack_id', $rackId)
                    ->where('batch_number', $batchNumber)
                    ->where('expired_at', $expiredAt)
                    ->where('serial_number', $serialNumber)
                    ->lockForUpdate()
                    ->first();

                if (!$rackStock) {
                    \App\Models\ProductRackStock::create([
                        'product_id' => $productId,
                        'warehouse_id' => $warehouseId,
                        'rack_id' => $rackId,
                        'batch_number' => $batchNumber,
                        'expired_at' => $expiredAt,
                        'serial_number' => $serialNumber,
                        'quantity' => $quantity
                    ]);
                } else {
                    $rackStock->quantity += $quantity;
                    $rackStock->save();
                }
            } else {
                // Outbound adjustment
                if ($rackId || $batchNumber || $serialNumber) {
                    $rackStock = \App\Models\ProductRackStock::where('product_id', $productId)
                        ->where('warehouse_id', $warehouseId)
                        ->where('rack_id', $rackId)
                        ->where('batch_number', $batchNumber)
                        ->where('expired_at', $expiredAt)
                        ->where('serial_number', $serialNumber)
                        ->lockForUpdate()
                        ->first();

                    if (!$rackStock || $rackStock->quantity < $quantity) {
                        throw new \Exception("Stok detail tidak mencukupi untuk lokasi/batch/serial terpilih.");
                    }

                    $rackStock->quantity -= $quantity;
                    if ($rackStock->quantity === 0) {
                        $rackStock->delete();
                    } else {
                        $rackStock->save();
                    }
                } else {
                    // Fallback FIFO: deduct from available stocks
                    $remainingToDeduct = $quantity;
                    $availableRackStocks = \App\Models\ProductRackStock::where('product_id', $productId)
                        ->where('warehouse_id', $warehouseId)
                        ->orderBy('expired_at', 'asc')
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->get();

                    foreach ($availableRackStocks as $rackStock) {
                        if ($remainingToDeduct <= 0) break;

                        if ($rackStock->quantity <= $remainingToDeduct) {
                            $remainingToDeduct -= $rackStock->quantity;
                            $rackStock->delete();
                        } else {
                            $rackStock->quantity -= $remainingToDeduct;
                            $rackStock->save();
                            $remainingToDeduct = 0;
                        }
                    }
                    
                    // Note: If remainingToDeduct > 0, it means some stock is legacy/untracted in racks.
                    // We allow it because global stock has already been verified and deducted.
                }
            }

            // Sync with OLSHOP is now handled asynchronously via StockMovementObserver and API.
            // We no longer query the database connection directly.

            // Transition-based low stock email alert check
            if ($type === 'out' && $referenceType !== 'stock_transfer') {
                if ($product) {
                    $minStock = (int) $product->stok_minimum;
                    $globalStockAfter = (int) \App\Models\ProductStock::where('product_id', $productId)->sum('quantity');
                    $globalStockBefore = $globalStockAfter + $quantity;

                    if ($globalStockBefore >= $minStock && $globalStockAfter < $minStock) {
                        $admins = \App\Models\User::where('role', 'admin')->get();
                        foreach ($admins as $admin) {
                            if ($admin->email) {
                                \Illuminate\Support\Facades\Mail::to($admin->email)->queue(new \App\Mail\LowStockAlert($product, $globalStockAfter));
                            }
                        }
                    }
                }
            }

            return $stock;
        });
    }

    public function removeStockAdjustment(string $referenceType, int $referenceId)
    {
        return DB::transaction(function () use ($referenceType, $referenceId) {
            $movements = StockMovement::where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->get();

            foreach ($movements as $movement) {
                $stock = ProductStock::where('product_id', $movement->product_id)
                    ->where('warehouse_id', $movement->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    // Reverse the movement
                    if ($movement->type === 'in') {
                        $stock->quantity -= $movement->quantity;
                    } else {
                        $stock->quantity += $movement->quantity;
                    }
                    $stock->save();
                }

                // Reverse detailed rack stock
                if ($movement->type === 'in') {
                    $rackStock = \App\Models\ProductRackStock::where('product_id', $movement->product_id)
                        ->where('warehouse_id', $movement->warehouse_id)
                        ->where('rack_id', $movement->rack_id)
                        ->where('batch_number', $movement->batch_number)
                        ->where('expired_at', $movement->expired_at)
                        ->where('serial_number', $movement->serial_number)
                        ->lockForUpdate()
                        ->first();
                    if ($rackStock) {
                        $rackStock->quantity -= $movement->quantity;
                        if ($rackStock->quantity <= 0) {
                            $rackStock->delete();
                        } else {
                            $rackStock->save();
                        }
                    }
                } else {
                    $rackStock = \App\Models\ProductRackStock::where('product_id', $movement->product_id)
                        ->where('warehouse_id', $movement->warehouse_id)
                        ->where('rack_id', $movement->rack_id)
                        ->where('batch_number', $movement->batch_number)
                        ->where('expired_at', $movement->expired_at)
                        ->where('serial_number', $movement->serial_number)
                        ->lockForUpdate()
                        ->first();
                    if (!$rackStock) {
                        \App\Models\ProductRackStock::create([
                            'product_id' => $movement->product_id,
                            'warehouse_id' => $movement->warehouse_id,
                            'rack_id' => $movement->rack_id,
                            'batch_number' => $movement->batch_number,
                            'expired_at' => $movement->expired_at,
                            'serial_number' => $movement->serial_number,
                            'quantity' => $movement->quantity
                        ]);
                    } else {
                        $rackStock->quantity += $movement->quantity;
                        $rackStock->save();
                    }
                }

                // Delete the movement record
                $movement->delete();

                // Sync with OLSHOP is now handled asynchronously via StockMovementObserver and API.
                // We no longer query the database connection directly.
            }

            return true;
        });
    }

    public function getStock(int $productId, int $warehouseId)
    {
        $stock = ProductStock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $stock ? $stock->quantity : 0;
    }
}
