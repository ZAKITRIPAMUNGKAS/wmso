<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DeliveryOrderService
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function createDeliveryOrder(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Create DO
            $do = DeliveryOrder::create([
                'no_sj' => $this->generateNoSj(),
                'po_number' => $data['po_number'] ?? null,
                'customer_id' => $data['customer_id'],
                'warehouse_id' => $data['warehouse_id'],
                'tanggal' => $data['tanggal'],
                'payment_term' => $data['payment_term'],
                'total' => $data['total'],
                'status' => 'sent',
                'keterangan' => $data['keterangan'] ?? null,
                'user_id' => Auth::id()
            ]);

            // 2. Add Items and Deduct Stock
            foreach ($data['items'] as $item) {
                DeliveryOrderItem::create([
                    'delivery_order_id' => $do->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'harga' => $item['harga'],
                    'subtotal' => $item['subtotal']
                ]);

                $this->stockService->adjustStock(
                    $item['product_id'],
                    $do->warehouse_id,
                    $item['quantity'],
                    'out',
                    'delivery_order',
                    $do->id,
                    Auth::id()
                );
            }

            // 3. Auto-generate Invoice
            $jenisPembayaran = $data['jenis_pembayaran'] ?? 'tempo';
            $tempoHari       = (int) ($data['tempo_hari'] ?? 30);
            $tanggalDO       = \Carbon\Carbon::parse($do->tanggal);

            // Cash → tempo hari = 0, due_date = hari yang sama
            // Tempo → due_date = tanggal + tempo_hari
            if ($jenisPembayaran === 'cash') {
                $tempoHari = 0;
                $dueDate   = $tanggalDO->format('Y-m-d');
            } else {
                $dueDate = $data['due_date'] ?? $tanggalDO->addDays($tempoHari)->format('Y-m-d');
            }

            Invoice::create([
                'no_invoice'        => str_replace('SJ', 'INV', $do->no_sj),
                'delivery_order_id' => $do->id,
                'tanggal'           => $do->tanggal,
                'total'             => $do->total,
                'paid_amount'       => 0,
                'status'            => 'belum_lunas',
                'payment_term'      => $do->payment_term,
                'jenis_pembayaran'  => $jenisPembayaran,
                'tempo_hari'        => $tempoHari,
                'due_date'          => $dueDate,
            ]);

            return $do;
        });
    }

    public function deleteDeliveryOrder(DeliveryOrder $do)
    {
        return DB::transaction(function () use ($do) {
            // 1. Remove stock adjustments and related movements
            $this->stockService->removeStockAdjustment('delivery_order', $do->id);

            // 2. Delete Invoice if exists
            if ($do->invoice) {
                $do->invoice->delete();
            }

            // 3. Delete DO
            return $do->delete();
        });
    }

    protected function generateNoSj()
    {
        $date = date('Ymd');
        $count = DeliveryOrder::whereDate('created_at', today())->count() + 1;
        return "SJ-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
