<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ClientOrderWebhookController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ShipmentController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('login', [AuthController::class, 'store'])->name('login');

    // Partner-facing, not user-facing: authenticated by an HMAC signature
    // over the raw body (VerifyClientOrderSignature), not a Sanctum token.
    Route::post('webhooks/client-orders/{partner}', [ClientOrderWebhookController::class, 'store'])
        ->middleware('client-order.signature')
        ->name('webhooks.client-orders');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::delete('logout', [AuthController::class, 'destroy'])->name('logout');

        Route::apiResource('clients', ClientController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('orders', OrderController::class);
        Route::apiResource('drivers', DriverController::class);
        Route::apiResource('vehicles', VehicleController::class);
        Route::apiResource('shipments', ShipmentController::class);
        Route::post('shipments/{shipment}/telemetry', [ShipmentController::class, 'storeTelemetry'])
            ->name('shipments.telemetry');
    });
});
