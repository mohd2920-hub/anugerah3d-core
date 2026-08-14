<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SyncProductImages;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(private SyncProductImages $syncProductImages) {}

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
        $validated = $request->validated();

        $product = DB::transaction(function () use ($request, $validated): Product {
            $product = Product::query()->create($this->productAttributes($validated));

            $this->syncProductImages->handle(
                $product,
                $request->file('product_images', []),
                [],
                $validated['main_image'] ?? null,
            );

            $this->syncClickerData($product, $request, $validated);

            return $product;
        });

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
        $product->load('images');
        $materials = collect(Material::query()->get());
        $clickerCharacterPrices = DB::table('product_clicker_prices')
            ->where('product_id', $product->getKey())
            ->pluck('price_rm', 'character_count')
            ->mapWithKeys(fn (mixed $price, mixed $characterCount): array => [
                (int) $characterCount => number_format((float) $price, 2, '.', ''),
            ])
            ->all();

        $clickerImages = DB::table('product_clicker_images')
            ->where('product_id', $product->getKey())
            ->orderBy('image_type')
            ->orderBy('position')
            ->get()
            ->groupBy('image_type');

        return view('admin.products.edit', [
            'product' => $product,
            'materials' => $materials,
            'clickerCharacterPrices' => $clickerCharacterPrices,
            'clickerImages' => $clickerImages,
        ]);
    }

    public function show(Product $product): View
    {
        $product->load(["images", "materialType"]);

        $clickerCharacterPrices = DB::table("product_clicker_prices")
            ->where("product_id", $product->getKey())
            ->pluck("price_rm", "character_count")
            ->mapWithKeys(fn (mixed $price, mixed $characterCount): array => [
                (int) $characterCount => number_format((float) $price, 2, ".", ""),
            ])
            ->all();

        $clickerImages = DB::table("product_clicker_images")
            ->where("product_id", $product->getKey())
            ->orderBy("image_type")
            ->orderBy("position")
            ->get()
            ->groupBy("image_type");

        $orderSales = $product->orderItems()
            ->selectRaw("COALESCE(SUM(quantity), 0) as total_quantity, COALESCE(SUM(line_total), 0) as total_amount")
            ->first();

        $posSales = $product->posSaleItems()
            ->selectRaw("COALESCE(SUM(quantity), 0) as total_quantity, COALESCE(SUM(line_total), 0) as total_amount")
            ->first();

        $summary = [
            "total_sold_quantity" => (int) ($orderSales->total_quantity ?? 0) + (int) ($posSales->total_quantity ?? 0),
            "total_sales_amount" => (float) ($orderSales->total_amount ?? 0) + (float) ($posSales->total_amount ?? 0),
            "stock_balance" => (int) $product->prd_balance,
            "gallery_count" => $product->images->count() + $clickerImages->flatten(1)->count(),
            "order_sold_quantity" => (int) ($orderSales->total_quantity ?? 0),
            "order_sales_amount" => (float) ($orderSales->total_amount ?? 0),
            "pos_sold_quantity" => (int) ($posSales->total_quantity ?? 0),
            "pos_sales_amount" => (float) ($posSales->total_amount ?? 0),
        ];

        return view("admin.products.edit", [
            "product" => $product,
            "materials" => collect(),
            "clickerCharacterPrices" => $clickerCharacterPrices,
            "clickerImages" => $clickerImages,
            "summary" => $summary,
            "isReadOnly" => true,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $product, $validated): void {
            $product->update($this->productAttributes($validated));

            $this->syncProductImages->handle(
                $product,
                $request->file('product_images', []),
                $validated['remove_image_ids'] ?? [],
                $validated['main_image'] ?? null,
            );

            $this->syncClickerData($product, $request, $validated);
        });

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

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function productAttributes(array $validated): array
    {
        return Arr::except($validated, [
            'product_images',
            'remove_image_ids',
            'main_image',
            'clicker_character_prices',
            'clicker_casing_images',
            'clicker_huruf_images',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncClickerData(Product $product, Request $request, array $validated): void
    {
        $productId = $product->getKey();

        if (($validated['product_type'] ?? 'standard') !== 'clicker') {
            $existingPaths = DB::table('product_clicker_images')
                ->where('product_id', $productId)
                ->pluck('image_path')
                ->all();

            DB::table('product_clicker_prices')->where('product_id', $productId)->delete();
            DB::table('product_clicker_images')->where('product_id', $productId)->delete();
            $this->deleteManagedClickerFiles($existingPaths);

            return;
        }

        if (array_key_exists('clicker_character_prices', $validated)) {
            $prices = collect(range(1, 8))
                ->map(fn (int $characterCount): array => [
                    'product_id' => $productId,
                    'character_count' => $characterCount,
                    'price_rm' => number_format((float) data_get($validated, "clicker_character_prices.$characterCount", 0), 2, '.', ''),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all();

            DB::table('product_clicker_prices')->where('product_id', $productId)->delete();
            DB::table('product_clicker_prices')->insert($prices);
        }

        $this->replaceClickerImagesOfType($product, 'casing', array_values($request->file('clicker_casing_images', [])));
        $this->replaceClickerImagesOfType($product, 'huruf', array_values($request->file('clicker_huruf_images', [])));
    }

    /**
     * @param  array<int, UploadedFile>  $uploads
     */
    private function replaceClickerImagesOfType(Product $product, string $type, array $uploads): void
    {
        if ($uploads === []) {
            return;
        }

        $existingPaths = DB::table('product_clicker_images')
            ->where('product_id', $product->getKey())
            ->where('image_type', $type)
            ->pluck('image_path')
            ->all();

        $storedPaths = [];
        $items = [];

        try {
            foreach ($uploads as $index => $upload) {
                $path = $this->storeClickerImage($product, $type, $upload);
                $storedPaths[] = $path;
                $items[] = [
                    'product_id' => $product->getKey(),
                    'image_type' => $type,
                    'image_path' => $path,
                    'alt_text' => $product->prd_name.' '.$type.' '.($index + 1),
                    'position' => $index + 1,
                    'crop_width_px' => 600,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('product_clicker_images')
                ->where('product_id', $product->getKey())
                ->where('image_type', $type)
                ->delete();

            DB::table('product_clicker_images')->insert($items);
        } catch (\Throwable $exception) {
            $this->deleteManagedClickerFiles($storedPaths);
            throw $exception;
        }

        $this->deleteManagedClickerFiles($existingPaths);
    }

    private function storeClickerImage(Product $product, string $type, UploadedFile $upload): string
    {
        $directory = public_path("images/products/{$product->getKey()}/clicker/{$type}");
        File::ensureDirectoryExists($directory);

        if (! is_dir($directory) || ! is_writable($directory)) {
            throw ValidationException::withMessages([
                "clicker_{$type}_images" => 'Unable to save clicker images. Please contact support.',
            ]);
        }

        $extension = match ($upload->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw ValidationException::withMessages([
                "clicker_{$type}_images" => 'Only JPG, PNG, and WebP pictures are supported.',
            ]),
        };

        $filename = "product-clicker-{$type}-{$product->getKey()}-".Str::uuid().'.'.$extension;
        $upload->move($directory, $filename);

        return "images/products/{$product->getKey()}/clicker/{$type}/{$filename}";
    }

    /**
     * @param  iterable<int, string>  $paths
     */
    private function deleteManagedClickerFiles(iterable $paths): void
    {
        foreach ($paths as $path) {
            if (Str::startsWith($path, 'images/products/')) {
                File::delete(public_path($path));
            }
        }
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
