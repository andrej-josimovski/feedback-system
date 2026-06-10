<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Team;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with('team:id,name')
            ->withCount('feedbacks')
            ->orderBy('name')
            ->get();

        return view('admin.products.index', [
            'products' => $products,
            'teams' => Team::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Product::create([
            'team_id' => $validated['team_id'],
            'name' => $validated['name'],
            'slug' => $this->slugFromInput($validated),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product added.');
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        $product->update([
            'team_id' => $validated['team_id'],
            'name' => $validated['name'],
            'slug' => $this->slugFromInput($validated),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product deleted.');
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function slugFromInput(array $validated): string
    {
        return Str::slug((string) ($validated['slug'] ?? $validated['name']));
    }
}
