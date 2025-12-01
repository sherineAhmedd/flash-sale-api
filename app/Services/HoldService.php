<?php

namespace App\Services;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HoldService
{
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
            return ['error'=>true,'status'=>404,'message'=>'Product not found'];
       }


            // Delete expired holds
            Hold::where('product_id', $product->id)
                ->where('expires_at', '<', now())
                ->delete();

            $available = $product->availableStock();

            if ($available < $validated['quantity']) {
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

            return [
                'error' => false,
                'data'  => $hold
            ];
        });

        return $hold;
    }
}
