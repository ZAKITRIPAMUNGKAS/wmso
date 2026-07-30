<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ApiOrderController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Receive a paid order from Olshop and create a Delivery Order.
     *
     * Endpoint: POST /api/v1/orders/receive
     */
    public function receive(Request $request)
    {
        $data = $request->validate([
            'order_number'      => 'required|string|max:100',
            'tanggal'           => 'required|date_format:Y-m-d',
            'customer.name'     => 'required|string|max:255',
            'customer.email'    => 'required|email',
            'customer.phone'    => 'required|string|max:20',
            'customer.address'  => 'required|string',
            'courier_name'      => 'required|string|max:100',
            'total_payment'     => 'required|numeric|min:0',
            'items'             => 'required|array|min:1',
            'items.*.sku'       => 'required|string|max:100',
            'items.*.quantity'  => 'required|integer|min:1',
            'items.*.price'     => 'required|numeric|min:0',
        ]);

        $orderNumber = $data['order_number'];
        $logger = Log::channel('api_sync');

        // 1. Check idempotency
        $existingDo = DeliveryOrder::where('olshop_order_number', $orderNumber)->first();
        if ($existingDo) {
            $logger->info("Order Receive Skipped (Idempotent): Order #{$orderNumber} already processed. DO Number: {$existingDo->no_sj}");
            return response()->json([
                'message'   => 'Already processed',
                'do_number' => $existingDo->no_sj
            ], 200);
        }

        // 2. Validate all SKUs in items
        $invalidSkus = [];
        $validatedItems = [];

        foreach ($data['items'] as $item) {
            $product = Product::where('kode_barang', $item['sku'])->first();
            if (!$product) {
                $invalidSkus[] = $item['sku'];
            } else {
                $validatedItems[] = [
                    'product'  => $product,
                    'quantity' => $item['quantity'],
                    'price'    => $item['price'],
                ];
            }
        }

        if (!empty($invalidSkus)) {
            $logger->warning("Order Receive Failed: Missing SKUs in WMS: " . implode(', ', $invalidSkus));
            return response()->json([
                'error'        => 'SKU not found in WMS',
                'invalid_skus' => $invalidSkus
            ], 422);
        }

        // 3. Process transaksional DO creation
        try {
            $do = DB::transaction(function () use ($data, $validatedItems, $orderNumber) {
                $customerData = $data['customer'];
                
                // Find or create Customer
                $customer = Customer::where('email', $customerData['email'])->first();
                if (!$customer) {
                    $customer = Customer::create([
                        'nama'    => $customerData['name'],
                        'email'   => $customerData['email'],
                        'kontak'  => $customerData['phone'],
                        'alamat'  => $customerData['address'],
                    ]);
                }

                // Determine default warehouse
                $warehouseId = config('services.api.default_warehouse_id', 1);

                // Get a default user ID for the creator
                $adminUser = User::where('role', 'admin')->first();
                $userId = $adminUser ? $adminUser->id : (User::first() ? User::first()->id : 1);

                // Generate no_sj using same standard
                $date = date('Ymd');
                $count = DeliveryOrder::whereDate('created_at', today())->count() + 1;
                $noSj = "SJ-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);

                // Create DeliveryOrder
                $deliveryOrder = DeliveryOrder::create([
                    'no_sj'               => $noSj,
                    'po_number'           => null,
                    'customer_id'         => $customer->id,
                    'warehouse_id'        => $warehouseId,
                    'tanggal'             => $data['tanggal'],
                    'payment_term'        => 'cash',
                    'total'               => $data['total_payment'],
                    'status'              => 'draft', // Draft status as per specification
                    'keterangan'          => "Pesanan dari website: {$orderNumber}",
                    'user_id'             => $userId,
                    'courier_name'        => $data['courier_name'],
                    'tracking_number'     => null,
                    'olshop_order_number' => $orderNumber,
                ]);

                // Create Items and Adjust Stock
                foreach ($validatedItems as $valItem) {
                    $prod = $valItem['product'];
                    $qty  = $valItem['quantity'];
                    $price = $valItem['price'];

                    DeliveryOrderItem::create([
                        'delivery_order_id' => $deliveryOrder->id,
                        'product_id'        => $prod->id,
                        'quantity'          => $qty,
                        'harga'             => $price,
                        'subtotal'          => $qty * $price,
                    ]);

                    // Call stockService outbound adjustment (FIFO deduction)
                    $this->stockService->adjustStock(
                        $prod->id,
                        $warehouseId,
                        $qty,
                        'out',
                        'delivery_order',
                        $deliveryOrder->id,
                        $userId
                    );
                }

                // Create Invoice (marked as fully paid since it is paid online)
                Invoice::create([
                    'no_invoice'        => str_replace('SJ', 'INV', $noSj),
                    'delivery_order_id' => $deliveryOrder->id,
                    'tanggal'           => $deliveryOrder->tanggal,
                    'total'             => $deliveryOrder->total,
                    'paid_amount'       => $deliveryOrder->total,
                    'status'            => 'lunas',
                    'payment_term'      => 'cash',
                    'jenis_pembayaran'  => 'cash',
                    'tempo_hari'        => 0,
                    'due_date'          => $deliveryOrder->tanggal,
                ]);

                return $deliveryOrder;
            });

            $logger->info("Order Receive Success: Order #{$orderNumber} processed. DO: {$do->no_sj}");

            return response()->json([
                'message'   => 'Order received',
                'do_number' => $do->no_sj
            ], 201);

        } catch (Exception $e) {
            $logger->error("Order Receive Failed: Exception occurred on Order #{$orderNumber}. Error: " . $e->getMessage());

            return response()->json([
                'error'   => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
