<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
     protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
      public function show($id)
    {
       $product = Product::with(['holds' => fn($q) => $q->active()])->find($id);


        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $data = $this->productService->getProductWithStock($product);

        return response()->json($data);
    }
}
