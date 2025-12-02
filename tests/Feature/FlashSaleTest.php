<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Hold;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class FlashSaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed one product with finite stock
        Product::factory()->create([
            'name' => 'Flash Sale Product',
            'price' => 100,
            'stock' => 5, // finite stock for testing oversell
        ]);
    }

    /**
     * Test parallel hold attempts to prevent overselling
     */
    public function test_parallel_holds_prevent_oversell()
    {
        $product = Product::first();

        $responses = [];

        // Simulate 10 concurrent requests trying to hold 1 unit each
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->postJson('/api/holds', [
                'product_id' => $product->id,
                'quantity' => 1,
            ]);
        }

        $successful = collect($responses)->filter(fn($r) => $r->status() === 200);

        // Only 5 should succeed because stock = 5
        $this->assertCount(5, $successful);

        // Active holds should equal 5
        $this->assertEquals(5, Hold::active()->count());
    }

    /**
     * Test hold expiry releases stock
     */
    public function test_hold_expiry_releases_stock()
    {
        $product = Product::first();

        $response = $this->postJson('/api/holds', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $holdId = $response->json('data.hold_id');

        // Simulate hold expiry
        Hold::where('id', $holdId)->update(['expires_at' => now()->subMinute()]);

        // Run release-expired command
        Artisan::call('holds:release-expired');

        // Stock should return to full
        $this->assertEquals(5, $product->fresh()->availableStock());
    }

    /**
     * Test webhook idempotency
     */
    public function test_webhook_idempotency()
    {
        $product = Product::first();

        $hold = Hold::create([
            'product_id' => $product->id,
            'quantity' => 1,
            'expires_at' => now()->addMinutes(2),
        ]);

        $order = Order::create([
            'hold_id' => $hold->id,
            'status' => 'pending',
        ]);

        $webhookData = [
            'order_id' => $order->id,
            'status' => 'success',
        ];

        $service = app(\App\Services\PaymentService::class);

        // Call webhook twice with same idempotency key
        $first = $service->handleWebhook('webhook-key-123', $webhookData);
        $second = $service->handleWebhook('webhook-key-123', $webhookData);

        // Only one webhook processed
        $this->assertEquals('paid', $order->fresh()->status);
        $this->assertEquals($first->id, $second->id);
    }

    /**
     * Test webhook arriving before order creation
     */
    public function test_webhook_before_order_creation()
    {
        $webhookData = [
            'order_id' => 999, // order not created yet
            'status' => 'success',
        ];

        $service = app(\App\Services\PaymentService::class);

        $webhook = $service->handleWebhook('preorder-key', $webhookData);

        // Webhook logged even if order does not exist yet
        $this->assertDatabaseHas('payment_webhooks', [
            'idempotency_key' => 'preorder-key',
        ]);
    }
}
