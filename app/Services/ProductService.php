<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    // Get product info + available stock with caching
    public function getProductWithStock(Product $product): array
    {
        $cacheKey = "product_{$product->id}";

        $cachedProduct = Cache::remember($cacheKey, 60, fn() => $product);

        return [
            'id'    => $cachedProduct->id,
            'name'  => $cachedProduct->name,
            'price' => $cachedProduct->price,
            'stock' => $cachedProduct->availableStock(),
        ];
    }

    // // Optional: Invalidate cache if product stock changes
    // public function invalidateCache(Product $product): void
    // {
    //     Cache::forget("product_{$product->id}");
    // }
}
