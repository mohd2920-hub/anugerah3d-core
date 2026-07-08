<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Material;
use App\Models\Product;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $products = Product::query()
            ->with('materialType')
            ->when($search !== '', fn (Builder $query): Builder => $query->search($search))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $materials = collect(Material::query()->get());

        return view('admin.products.create', [
            'materials' => $materials,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::query()->create($request->validated());

        AdminActivity::record(
            request: $request,
            event: 'admin.product.created',
            description: "Product {$product->prd_code} created.",
            adminUser: $request->user('admin'),
            properties: ['page' => 'Products', 'product_id' => $product->getKey(), 'product_code' => $product->prd_code],
        );

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        $materials = collect(Material::query()->get());

        return view('admin.products.edit', [
            'product' => $product,
            'materials' => $materials,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        AdminActivity::record(
            request: $request,
            event: 'admin.product.updated',
            description: "Product {$product->prd_code} updated.",
            adminUser: $request->user('admin'),
            properties: ['page' => 'Products', 'product_id' => $product->getKey(), 'product_code' => $product->prd_code],
        );

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'delete_password' => ['required', 'string'],
        ]);

        $adminUser = $request->user('admin');

        if (! $adminUser || ! Hash::check($request->input('delete_password'), $adminUser->password)) {
            return back()
                ->withInput([
                    'delete_action' => route('admin.products.destroy', $product),
                    'delete_product_name' => $product->prd_name,
                ])
                ->withErrors(['delete_password' => 'The provided password is incorrect.']);
        }

        $productCode = $product->prd_code;
        $productId = $product->getKey();

        $product->delete();

        AdminActivity::record(
            request: $request,
            event: 'admin.product.deleted',
            description: "Product {$productCode} deleted.",
            adminUser: $adminUser,
            properties: ['page' => 'Products', 'product_id' => $productId, 'product_code' => $productCode],
        );

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
