<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedbackRequest;
use App\Http\Requests\StoreProductFeedbackRequest;
use App\Models\Feedback;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    public function index(): JsonResponse
    {
        $feedback = Feedback::query()
            ->with([
                'product:id,name,slug',
                'user:id,name,email',
            ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($feedback);
    }

    public function show(Feedback $feedback): JsonResponse
    {
        $feedback->load([
            'product:id,name,slug',
            'user:id,name,email',
        ]);

        return response()->json($feedback);
    }

    public function store(StoreFeedbackRequest $request): JsonResponse
    {
        $feedback = new Feedback();
        $feedback->product_id = (int) $request->validated('product_id');
        $feedback->user_id = $request->validated('user_id');
        $feedback->rating = (int) $request->validated('rating');
        $feedback->comment = $request->validated('comment');
        $feedback->save();

        $feedback->load([
            'product:id,name,slug',
            'user:id,name,email',
        ]);

        return response()->json($feedback, 201);
    }

    public function indexForProduct(Product $product): JsonResponse
    {
        $feedback = Feedback::query()
            ->where('product_id', $product->id)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
            ],
            'feedback' => $feedback,
        ]);
    }

    public function storeForProduct(StoreProductFeedbackRequest $request, Product $product): JsonResponse
    {
        $feedback = new Feedback();
        $feedback->product_id = $product->id;
        $feedback->user_id = $request->validated('user_id');
        $feedback->rating = (int) $request->validated('rating');
        $feedback->comment = $request->validated('comment');
        $feedback->save();

        $feedback->load('user:id,name,email');

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
            ],
            'feedback' => $feedback,
        ], 201);
    }
}

