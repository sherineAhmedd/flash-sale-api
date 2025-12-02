<?php

namespace App\Services;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\ProductService;

class HoldService
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function createHold(array $data)
    {
        // Validate input
        $validator = Validator::make($data, [
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return [
                'error'   => true,
                'status'  => 422,
                'message' => $validator->errors(),
            ];
        }

        $validated = $validator->validated();

        // Main stock logic inside DB transaction
        $hold = DB::transaction(function () use ($validated) {

            $product = Product::where('id', $validated['product_id'])
                ->lockForUpdate()
                ->first();

            if (!$product) {
                return [
                    'error' => true,
                    'status' => 404,
                    'message' => 'Product not found',
                ];
            }

            // Delete expired holds
            $expiredCount = Hold::where('product_id', $product->id)
                ->where('expires_at', '<', now())
                ->delete();

            if ($expiredCount > 0) {
                Log::info('Expired holds deleted', [
                    'product_id' => $product->id,
                    'expired_count' => $expiredCount,
                ]);
            }

            // Calculate available stock
            $available = $product->availableStock();

            if ($available < $validated['quantity']) {
                Log::warning('Not enough stock for hold', [
                    'product_id' => $product->id,
                    'requested_qty' => $validated['quantity'],
                    'available' => $available,
                ]);

                return [
                    'error'   => true,
                    'status'  => 409,
                    'message' => 'Not enough stock',
                ];
            }

            // Create hold
            $hold = Hold::create([
                'product_id' => $product->id,
                'quantity'   => $validated['quantity'],
                'expires_at' => now()->addMinutes(2),
            ]);

            Log::info('Hold created', [
                'hold_id' => $hold->id,
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
                'expires_at' => $hold->expires_at,
            ]);

            // Update Product cache
            $this->productService->updateCache($product);

            return [
                'error' => false,
                'data'  => [
                    'hold_id' => $hold->id,
                    'expires_at' => $hold->expires_at,
                ],
            ];
        }); // <-- closes DB::transaction

        return $hold;
    }
} // <-- closes HoldService class
