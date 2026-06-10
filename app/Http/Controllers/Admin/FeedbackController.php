<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $feedback = Feedback::query()
            ->with(['product:id,name,slug', 'user:id,name,email'])
            ->when($request->integer('product_id'), fn ($query, int $productId) => $query->where('product_id', $productId))
            ->when($request->integer('rating'), fn ($query, int $rating) => $query->where('rating', $rating))
            ->latest()
            ->get();

        return view('admin.feedback.index', [
            'feedback' => $feedback,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['product_id', 'rating']),
        ]);
    }
}
