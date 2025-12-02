<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    // Get product info + available stock with caching
    public function getProductWithStock(Product $product): array
    {
         $cacheKey = "product_{$product->id}_available";

        // Try to get from cache first
        $availableStock = Cache::get($cacheKey);

        if ($availableStock === null) {
            // Cache miss → calculate stock from DB
            $activeHoldsQty = $product->holds()->active()->sum('quantity');
            $availableStock = $product->stock - $activeHoldsQty;

            // Save to cache
            Cache::put($cacheKey, $availableStock, 60);

            // Log cache miss and calculated stock
            Log::info('Cache miss: calculated available stock from DB', [
                'product_id' => $product->id,
                'stock' => $product->stock,
                'active_holds' => $activeHoldsQty,
                'available_stock' => $availableStock,
            ]);
        }
        else {
            // Log cache hit
            Log::info('Cache hit: returned available stock from cache', [
                'product_id' => $product->id,
                'available_stock' => $availableStock,
            ]);
        }

        return [
            'id'    => $product->id,
            'name'  => $product->name,
            'price' => $product->price,
            'stock' => $availableStock,
        ];

    }

     //Invalidate cache when stock changes
    public function invalidateCache(Product $product): void
    {
        Cache::forget("product_{$product->id}_available");

         // Log cache invalidation
        Log::info('Cache invalidated for product', [
            'product_id' => $product->id,
        ]);
    }

    //helper to force update cache after hold/order changes
    public function updateCache(Product $product): void
    {
        $activeHoldsQty = $product->holds()->active()->sum('quantity');
        $availableStock = $product->stock - $activeHoldsQty;
        Cache::put("product_{$product->id}_available", $availableStock, 60);

         // Log cache update
        Log::info('Cache updated for product stock', [
            'product_id' => $product->id,
            'available_stock' => $availableStock,
            'active_holds' => $activeHoldsQty,
        ]);
    }
}
