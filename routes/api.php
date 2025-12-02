<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\HoldController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentWebhookController;


//test route
Route::get('/test', function () {
    return response()->json(['message' => 'API works']);
});
// Product endpoint
Route::get('/products/{id}', [ProductController::class, 'show']);
//hold endpoint
Route::post('/holds', [HoldController::class, 'store']);
//order endpoint
Route::post('/orders', [OrderController::class, 'create']);
//paymentWebhook endpoint
Route::post('/payments/webhook', [PaymentWebhookController::class, 'handle']);