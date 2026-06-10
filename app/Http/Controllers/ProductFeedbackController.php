<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductFeedbackRequest;
use App\Models\Feedback;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProductFeedbackController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with('team:id,name')
            ->withCount('feedbacks')
            ->orderBy('name')
            ->get();

        return view('products.index', ['products' => $products]);
    }

    public function create(Product $product): View
    {
        return view('products.feedback', ['product' => $product->load('team:id,name')]);
    }

    public function store(StoreProductFeedbackRequest $request, Product $product): RedirectResponse
    {
        Feedback::create([
            'product_id' => $product->id,
            'user_id' => $request->user()?->id,
            'rating' => (int) $request->validated('rating'),
            'comment' => $request->validated('comment'),
        ]);

        return redirect()
            ->route('products.index')
            ->with('status', 'Feedback submitted.');
    }
}
