<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the products with search and pagination.
     */
    public function index(Request $request): View
    {
        $query = Product::query();

        // Search functionality
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prd_code', 'like', "%{$search}%")
                    ->orWhere('prd_name', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        return view('admin.products.create');
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prd_code' => ['required', 'string', 'unique:products'],
            'prd_name' => ['required', 'string', 'max:255'],
            'weight_g' => ['required', 'numeric', 'min:0'],
            'width_mm' => ['required', 'numeric', 'min:0'],
            'height_mm' => ['required', 'numeric', 'min:0'],
            'prd_balance' => ['required', 'integer', 'min:0'],
            'cost_rm' => ['required', 'numeric', 'min:0'],
            'price_selling' => ['required', 'numeric', 'min:0'],
            'agent_discount_default' => ['required', 'numeric', 'min:0', 'max:100'],
            'prd_picture' => ['nullable', 'url'],
        ]);

        Product::create($validated);

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        return view('admin.products.edit', compact('product'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'prd_code' => ['required', 'string', 'unique:products,prd_code,'.$product->id],
            'prd_name' => ['required', 'string', 'max:255'],
            'weight_g' => ['required', 'numeric', 'min:0'],
            'width_mm' => ['required', 'numeric', 'min:0'],
            'height_mm' => ['required', 'numeric', 'min:0'],
            'prd_balance' => ['required', 'integer', 'min:0'],
            'cost_rm' => ['required', 'numeric', 'min:0'],
            'price_selling' => ['required', 'numeric', 'min:0'],
            'agent_discount_default' => ['required', 'numeric', 'min:0', 'max:100'],
            'prd_picture' => ['nullable', 'url'],
        ]);

        $product->update($validated);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
