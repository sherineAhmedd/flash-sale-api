<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentWebhook;
use Illuminate\Support\Facades\DB;
use App\Services\ProductService;
use Illuminate\Support\Facades\Log;


class PaymentService
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
    $this->productService = $productService;
    }

  
   public function handleWebhook(string $idempotencyKey, array $data): PaymentWebhook
{
    // 1. Idempotency check
    $existing = PaymentWebhook::where('idempotency_key', $idempotencyKey)->first();
    if ($existing) return $existing;

    return DB::transaction(function () use ($idempotencyKey, $data) {

        $order = Order::find($data['order_id'] ?? null);

        // Log webhook first
        $webhook = PaymentWebhook::create([
            'idempotency_key' => $idempotencyKey,
            'order_id' => $order?->id,
            'status' => $data['status'],
            'payload' => json_encode($data),
        ]);

        if (!$order) return $webhook; // order not found → only log

        $hold = $order->hold; // directly get related hold
        $product = $hold?->product;

        if ($data['status'] === 'success') {
         $order->status = 'paid';
        $order->save();
        Log::info('Order marked as paid via webhook', [
        'order_id' => $order->id,
        'idempotency_key' => $idempotencyKey,
    ]);
    } else {
    $order->status = 'cancelled';
    $order->save();
    Log::warning('Order cancelled via webhook', [
        'order_id' => $order->id,
        'idempotency_key' => $idempotencyKey,
    ]);

    if ($hold && $product) {
        // Release hold
        $hold->used = false;
        $hold->save();
        Log::info('Hold released after payment failure', [
            'hold_id' => $hold->id,
            'product_id' => $product->id,
        ]);

        // Update product cache
        $this->productService->updateCache($product);
        Log::info('Product cache updated after payment failure', [
            'product_id' => $product->id,
            'available_stock' => $product->availableStock(),
        ]);
    }
}


        return $webhook;
    });
}

    
}
