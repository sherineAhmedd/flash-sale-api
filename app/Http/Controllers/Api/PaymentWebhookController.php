<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
     public function handle(Request $request)
    {
        // Validate input
        $request->validate([
            'idempotency_key' => 'required|string',
            'order_id' => 'required|integer',
            'status' => 'required|in:success,failed',
        ]);

        $webhook = $this->paymentService->handleWebhook(
            $request->idempotency_key,
            $request->only('order_id', 'status')
        );

        return response()->json([
            'message' => 'Webhook processed',
            'webhook_id' => $webhook->id
        ]);
    }

}
