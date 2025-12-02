<?php

namespace Database\Factories;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class HoldFactory extends Factory
{
     protected $model = Hold::class;

    public function definition()
    {
        return [
            'product_id' => Product::factory(), // automatically create a product if needed
            'quantity' => $this->faker->numberBetween(1, 5),
            'expires_at' => now()->addMinutes(2),
            'used' => false,
        ];
    }
}
