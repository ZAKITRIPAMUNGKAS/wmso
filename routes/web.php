<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\RackController;



Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- SHARED ROUTES (ALL AUTHENTICATED) ---
    // Master Data Index & Show
    Route::get('/master-data/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/master-data/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/master-data/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/master-data/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/master-data/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::get('/master-data/racks', [RackController::class, 'index'])->name('racks.index');

    // Transaction Index
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('/barang-keluar', [DeliveryOrderController::class, 'index'])->name('barang-keluar.index');
    Route::get('/barang-keluar/{barang_keluar}/pdf', [DeliveryOrderController::class, 'downloadPdf'])->name('barang-keluar.pdf');
    Route::get('/barang-masuk', [GoodsReceiptController::class, 'index'])->name('barang-masuk.index');
    Route::get('/barang-masuk/{barang_masuk}/pdf', [GoodsReceiptController::class, 'downloadPdf'])->name('barang-masuk.pdf');
    Route::get('/stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::get('/stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');

    // --- STAFF & ADMIN ROUTES (TRANSACTIONS) ---
    // User with role 'viewer' cannot access these
    Route::middleware('role:admin,staff_gudang')->group(function () {
        // Create & Store Actions (Must be before Show routes to avoid wildcard conflict)
        Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('/purchase-orders/{purchase_order}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
        Route::put('/purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
        Route::delete('/purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');

        Route::get('/stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
        Route::post('/stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');
        Route::get('/stock-transfers/{stock_transfer}/edit', [StockTransferController::class, 'edit'])->name('stock-transfers.edit');
        Route::put('/stock-transfers/{stock_transfer}', [StockTransferController::class, 'update'])->name('stock-transfers.update');
        Route::delete('/stock-transfers/{stock_transfer}', [StockTransferController::class, 'destroy'])->name('stock-transfers.destroy');

        Route::get('/stock-adjustments/warehouse/{warehouse}/stocks', [StockAdjustmentController::class, 'getWarehouseStocks'])->name('stock-adjustments.warehouse-stocks');
        Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
        Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
        Route::get('/stock-adjustments/{stock_adjustment}/edit', [StockAdjustmentController::class, 'edit'])->name('stock-adjustments.edit');
        Route::put('/stock-adjustments/{stock_adjustment}', [StockAdjustmentController::class, 'update'])->name('stock-adjustments.update');
        Route::delete('/stock-adjustments/{stock_adjustment}', [StockAdjustmentController::class, 'destroy'])->name('stock-adjustments.destroy');

        Route::get('/barang-masuk/create', [GoodsReceiptController::class, 'create'])->name('barang-masuk.create');
        Route::post('/barang-masuk', [GoodsReceiptController::class, 'store'])->name('barang-masuk.store');
        
        Route::get('/barang-keluar/create', [DeliveryOrderController::class, 'create'])->name('barang-keluar.create');
        Route::post('/barang-keluar', [DeliveryOrderController::class, 'store'])->name('barang-keluar.store');

        // Master Data Actions
        Route::post('/master-data/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/master-data/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/master-data/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        
        Route::post('/master-data/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::put('/master-data/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/master-data/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        Route::post('/master-data/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('/master-data/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/master-data/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

        Route::post('/master-data/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::put('/master-data/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
        Route::delete('/master-data/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');

        Route::post('/master-data/racks', [RackController::class, 'store'])->name('racks.store');
        Route::put('/master-data/racks/{rack}', [RackController::class, 'update'])->name('racks.update');
        Route::delete('/master-data/racks/{rack}', [RackController::class, 'destroy'])->name('racks.destroy');

        Route::delete('/barang-masuk/{barang_masuk}', [GoodsReceiptController::class, 'destroy'])->name('barang-masuk.destroy');
        Route::delete('/barang-keluar/{barang_keluar}', [DeliveryOrderController::class, 'destroy'])->name('barang-keluar.destroy');
    });

    // --- VIEW ROUTES (SHOW) ---
    Route::get('/purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::get('/stock-transfers/{stock_transfer}', [StockTransferController::class, 'show'])->name('stock-transfers.show');
    Route::get('/stock-adjustments/{stock_adjustment}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show');

    Route::get('/barang-masuk/{barang_masuk}', [GoodsReceiptController::class, 'show'])->name('barang-masuk.show');
    Route::get('/barang-keluar/{barang_keluar}', [DeliveryOrderController::class, 'show'])->name('barang-keluar.show');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

    // --- ADMIN ONLY ROUTES (PAYMENTS, REPORTS, SETTINGS, USERS) ---
    Route::middleware('role:admin')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');

        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/export', [ReportController::class, 'exportExcel'])->name('reports.export');
        Route::get('/laporan/print', [ReportController::class, 'print'])->name('reports.print');

        Route::get('/settings/company', [SettingsController::class, 'company'])->name('settings.company');
        Route::post('/settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company.update');
        Route::get('/settings/sync-logs', [\App\Http\Controllers\SyncLogController::class, 'index'])->name('settings.sync-logs.index');
        Route::post('/settings/sync-logs/{log}/retry', [\App\Http\Controllers\SyncLogController::class, 'retry'])->name('settings.sync-logs.retry');
        Route::post('/settings/sync-all-stock', [\App\Http\Controllers\SyncLogController::class, 'syncAll'])->name('settings.sync-all-stock');
        Route::delete('/settings/sync-logs/{log}', [\App\Http\Controllers\SyncLogController::class, 'destroy'])->name('settings.sync-logs.destroy');
        Route::resource('users', UserController::class);
    });

    // Notifications
    Route::post('/notifications/mark-as-read', function () {
        $userId = auth()->id();
        \Illuminate\Support\Facades\Cache::put('notifications_dismissed_at_' . $userId, now(), now()->addHours(24));
        \Illuminate\Support\Facades\Cache::forget('user_notifications_' . $userId);
        return back();
    })->name('notifications.mark-as-read');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
