<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('products.index', [
            'categories' => Category::query()
                ->where('is_active', true)
                ->withCount(['products' => fn ($query) => $query->active()])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->status === 'active', 404);

        $product->load('category');

        return view('products.show', [
            'product' => $product,
            'relatedProducts' => Product::query()
                ->active()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->latest()
                ->limit(4)
                ->get(),
        ]);
    }
}
