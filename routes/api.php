<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiOrderController;

/*
|--------------------------------------------------------------------------
| API Routes — WMS FIX
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider or bootstrap/app.php.
|
*/

Route::middleware(['api.token', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::post('/orders/receive', [ApiOrderController::class, 'receive']);
});
