<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OrderService;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
     public function create(Request $request)
    {
        $request->validate([
            'hold_id' => 'required|integer|exists:holds,id',
        ]);

        $result = $this->orderService->createOrder($request->hold_id);

        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return response()->json([
            'order_id' => $result->id,
            'hold_id' => $result->hold_id,
            'status' => $result->status,
        ]);
    }
    
}
