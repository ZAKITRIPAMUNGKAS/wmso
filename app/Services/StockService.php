<?php

namespace App\Services;

use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function adjustStock(int $productId, int $warehouseId, int $quantity, string $type, string $referenceType, int $referenceId, int $userId)
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $type, $referenceType, $referenceId, $userId) {
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

            StockMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => $type,
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'user_id' => $userId
            ]);

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

                // Delete the movement record
                $movement->delete();
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
