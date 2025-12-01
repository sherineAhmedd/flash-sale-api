<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    
    public function run()
    {
        Product::create([
            'name' => 'Flash Sale Item',
            'price' => 99.99,
            'stock' => 100
]);
    }
}
