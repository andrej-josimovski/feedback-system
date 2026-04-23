<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::query()
            ->with('team:id,name,slug')
            ->withCount('feedbacks')
            ->orderBy('name')
            ->get();

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load('team:id,name,slug');

        return response()->json($product);
    }
}


