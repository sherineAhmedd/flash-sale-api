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

    $hold = DB::transaction(function () use ($validated) {

        $product = Product::where('id', $validated['product_id'])
            ->lockForUpdate()
            ->firstOrFail();

        // Remove expired holds
        Hold::where('product_id', $product->id)
            ->where('expires_at', '<', now())
            ->delete();

        $available = $product->availableStock();

        if ($available < $validated['quantity']) {
            return null; // will be handled after transaction
        }

        $hold = Hold::create([
            'product_id' => $product->id,
            'quantity'   => $validated['quantity'],
            'expires_at' => now()->addMinutes(2),
        ]);

        // Update product cache
        $this->productService->updateCache($product);

        return $hold;
    });

    if (!$hold) {
        return [
            'error'   => true,
            'status'  => 409,
            'message' => 'Not enough stock',
        ];
    }

    return [
        'error' => false,
        'data'  => $hold,
    ];
}

} 
