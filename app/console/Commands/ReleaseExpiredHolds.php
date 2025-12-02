<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hold;
use App\Services\ProductService;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredHolds extends Command
{
    protected $signature = 'holds:release-expired';
    protected $description = 'Release expired holds and update product cache';

    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        parent::__construct();
        $this->productService = $productService;
    }

    public function handle()
    {
        $expiredHolds = Hold::where('expires_at', '<', now())->get();

        if ($expiredHolds->isEmpty()) {
            Log::info('No expired holds to release');
            return 0;
        }

        foreach ($expiredHolds as $hold) {
            $product = $hold->product;

            // Delete the hold
            $hold->delete();

            // Update cache
            if ($product) {
                $this->productService->updateCache($product);

                Log::info('Expired hold released and cache updated', [
                    'hold_id' => $hold->id,
                    'product_id' => $product->id,
                    'available_stock' => $product->availableStock(),
                ]);
            }
        }

        return 0;
    }
}
