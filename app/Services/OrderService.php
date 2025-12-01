<?php

namespace App\Services;

use App\Models\Hold;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderService
{
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

            // Create order
            $order = Order::create([
                'hold_id' => $hold->id,
                'status' => 'pending',
            ]);

            return $order;
        });
    }
}
