<?php

namespace App\Services;

use App\Models\Hold;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use App\Services\ProductService;
use Illuminate\Support\Facades\Log;
use App\Models\Product;

use Carbon\Carbon;

class OrderService
{
    protected ProductService $productService;

public function __construct(ProductService $productService)
{
    $this->productService = $productService;
}

    public function createOrder(int $holdId)
    {
        //safe for concurrency
        return DB::transaction(function () use ($holdId) {

            // Lock the hold row to prevent double usage
            $hold = Hold::where('id', $holdId)
                        ->where('used', false)     // only unused holds
                        ->where('expires_at', '>', now()) // only unexpired
                        ->lockForUpdate()
                        ->first();

            if (!$hold) {
                return ['error' => 'Invalid or expired hold'];
            }

            // Mark hold as used
            $hold->used = true;
            $hold->save();
            Log::info('Hold marked as used', [
            'hold_id' => $hold->id,
            'product_id' => $hold->product_id,
        ]);

            // Create order
            $order = Order::create([
             'hold_id' => $hold->id,
             'status' => 'pending',
          ]);
            Log::info('Order created', [
             'order_id' => $order->id,
              'hold_id' => $hold->id,
               'status' => $order->status,
        ]);
        // Update product cache after hold used
            $product = Product::find($hold->product_id);
            if ($product) {
            $this->productService->updateCache($product);
            Log::info('Product cache updated after order creation', [
            'product_id' => $product->id,
            'available_stock' => $product->availableStock(),
        ]);
    }


            return $order;
        });
    }
}
