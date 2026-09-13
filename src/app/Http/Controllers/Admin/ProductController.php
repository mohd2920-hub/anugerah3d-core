<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\StoreResizedProductImage;
use App\Actions\Admin\SyncProductImages;
use App\Actions\Admin\SyncProductVideo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductDiscontinuationRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\CustomerOrderItem;
use App\Models\Material;
use App\Models\Product;
use App\Support\AdminActivity;
use App\Support\CasingStock;
use App\Support\ProductStockOverview;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(
        private SyncProductImages $syncProductImages,
        private SyncProductVideo $syncProductVideo,
        private StoreResizedProductImage $storeResizedProductImage,
    ) {}

    public function index(Request $request, ProductStockOverview $overview): View
    {
        $search = $request->string('search')->trim()->toString();

        $filter = (string) $request->query('stock', 'all');
        if (! in_array($filter, ['all', 'catalogue', 'hidden', 'empty', 'critical', 'healthy', 'low', 'discontinued', 'discontinued-stock'], true)) {
            $filter = 'all';
        }
        $discontinuationAvailable = (new Product)->discontinuationAvailable();
        $includeHidden = $request->boolean('include_hidden');
        $stockStats = $overview->statistics($includeHidden, $search);
        $stockRows = in_array($filter, ['empty', 'critical', 'healthy', 'low'], true)
            ? $overview->filtered($filter, $search, $includeHidden, productSearchOnly: true)->paginate(20)->withQueryString()
            : null;

        $products = Product::query()
            ->with('materialType')
            ->when($filter === 'catalogue', fn (Builder $query) => $query->visibleToAgents())
            ->when(in_array($filter, ['discontinued', 'discontinued-stock'], true), fn (Builder $query) => $discontinuationAvailable ? $query->whereNotNull('discontinued_at') : $query->whereRaw('1 = 0'))
            ->when($filter === 'discontinued-stock', fn (Builder $query) => $discontinuationAvailable ? $query->where('prd_balance', '>', 0) : $query->whereRaw('1 = 0'))
            ->when($filter === 'hidden', fn (Builder $query) => $query->where('is_visible_to_agents', false)->when($discontinuationAvailable, fn (Builder $query) => $query->whereNull('discontinued_at')))
            ->when($search !== '', fn (Builder $query): Builder => $query->search($search))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'discontinuationAvailable' => $discontinuationAvailable,
            'discontinuedStockCount' => $stockStats['discontinued_stock'],
            'search' => $search,
            'stockFilter' => $filter,
            'includeHidden' => $includeHidden,
            'stockRows' => $stockRows,
            'stockStats' => $stockStats,
            'lowStockCounts' => $stockRows === null
                ? $overview->rows(true)->whereIn('product_id', $products->modelKeys())->where('balance', '<', 5)->groupBy('product_id')->selectRaw('product_id, COUNT(*) as low_count')->pluck('low_count', 'product_id')
                : collect(),
        ]);
    }

    public function statistics(Request $request, ProductStockOverview $overview): View
    {
        $search = $request->string('search')->trim()->toString();
        $includeHidden = $request->boolean('include_hidden');
        $stockStats = $overview->statistics($includeHidden, $search);

        return view('admin.products.statistics', [
            'search' => $search,
            'includeHidden' => $includeHidden,
            'stockStats' => $stockStats,
            'stockFilter' => '',
            'discontinuationAvailable' => (new Product)->discontinuationAvailable(),
            'discontinuedStockCount' => $stockStats['discontinued_stock'],
        ]);
    }

    public function create(): View
    {
        $materials = collect(Material::query()->get());

        return view('admin.products.create', [
            'materials' => $materials,
            'casingStockAvailable' => Schema::hasTable('product_clicker_stocks'),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $product = DB::transaction(function () use ($request, $validated): Product {
            $product = Product::query()->create($this->productAttributes($validated));

            app(CasingStock::class)->save($product, $validated, $request, validateOnly: true);

            $this->syncProductImages->handle(
                $product,
                $request->file('product_images', []),
                [],
                $validated['main_image'] ?? null,
            );

            $this->syncClickerData($product, $request, $validated);
            app(CasingStock::class)->save($product, $validated, $request);
            $this->syncProductVideo->handle($product, $request->file('product_video'), (bool) ($validated['remove_product_video'] ?? false));

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
        $clickerCharacterPrices = $this->clickerCharacterPricing($product->getKey());

        $clickerImages = DB::table('product_clicker_images')
            ->where('product_id', $product->getKey())
            ->orderBy('image_type')
            ->orderBy('position')
            ->get()
            ->groupBy('image_type');
        $clickerResults = $this->clickerResults($product->getKey());

        return view('admin.products.edit', [
            'product' => $product,
            'materials' => $materials,
            'clickerCharacterPrices' => $clickerCharacterPrices,
            'clickerImages' => $clickerImages,
            'clickerResults' => $clickerResults,
            'casingStockAvailable' => Schema::hasTable('product_clicker_stocks'),
            'casingStocks' => app(CasingStock::class)->quantities(collect($clickerImages->get('casing', collect()))->pluck('id')->all()),
        ]);
    }

    public function show(Product $product): View
    {
        $product->load(['images', 'materialType']);

        $clickerCharacterPrices = $this->clickerCharacterPricing($product->getKey());

        $clickerImages = DB::table('product_clicker_images')
            ->where('product_id', $product->getKey())
            ->orderBy('image_type')
            ->orderBy('position')
            ->get()
            ->groupBy('image_type');
        $clickerResults = $this->clickerResults($product->getKey());

        $orderSales = $product->orderItems()
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_quantity, COALESCE(SUM(line_total), 0) as total_amount')
            ->first();

        $posSales = $product->posSaleItems()
            ->whereHas('posSale', fn ($query) => $query->notVoided())
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_quantity, COALESCE(SUM(line_total), 0) as total_amount')
            ->first();

        $summary = [
            'total_sold_quantity' => (int) ($orderSales->total_quantity ?? 0) + (int) ($posSales->total_quantity ?? 0),
            'total_sales_amount' => (float) ($orderSales->total_amount ?? 0) + (float) ($posSales->total_amount ?? 0),
            'stock_balance' => (int) $product->prd_balance,
            'gallery_count' => $product->images->count() + $clickerImages->flatten(1)->count() + $clickerResults->count(),
            'order_sold_quantity' => (int) ($orderSales->total_quantity ?? 0),
            'order_sales_amount' => (float) ($orderSales->total_amount ?? 0),
            'pos_sold_quantity' => (int) ($posSales->total_quantity ?? 0),
            'pos_sales_amount' => (float) ($posSales->total_amount ?? 0),
        ];

        return view('admin.products.edit', [
            'product' => $product,
            'materials' => collect(),
            'clickerCharacterPrices' => $clickerCharacterPrices,
            'clickerImages' => $clickerImages,
            'clickerResults' => $clickerResults,
            'casingStockAvailable' => Schema::hasTable('product_clicker_stocks'),
            'casingStocks' => app(CasingStock::class)->quantities(collect($clickerImages->get('casing', collect()))->pluck('id')->all()),
            'summary' => $summary,
            'isReadOnly' => true,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $product, $validated): void {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            if (CasingStock::enabled($product) && $validated['product_type'] !== 'clicker') {
                throw ValidationException::withMessages(['product_type' => 'A product using casing stock must remain a Clicker product.']);
            }
            $attributes = $this->productAttributes($validated);
            if ($validated['product_type'] === 'clicker') {
                unset($attributes['prd_balance']);
            }
            $product->update($attributes);

            app(CasingStock::class)->save($product, $validated, $request, validateOnly: true);

            $this->syncProductImages->handle(
                $product,
                $request->file('product_images', []),
                $validated['remove_image_ids'] ?? [],
                $validated['main_image'] ?? null,
            );

            $this->syncClickerData($product, $request, $validated);
            app(CasingStock::class)->save($product, $validated, $request);
            $this->syncProductVideo->handle($product, $request->file('product_video'), (bool) ($validated['remove_product_video'] ?? false));
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
        $attributes = Arr::except($validated, [
            'product_images',
            'remove_image_ids',
            'product_video',
            'remove_product_video',
            'main_image',
            'clicker_character_prices',
            'clicker_images',
            'clicker_result',
            'enable_casing_stock',
            'casing_stock',
            'casing_stock_reason',
        ]);

        if (($validated['product_type'] ?? 'standard') === 'clicker') {
            $attributes = array_replace($attributes, array_fill_keys([
                'weight_g',
                'width_mm',
                'height_mm',
                'length_mm',
                'color',
                'material_id',
                'cost_rm',
                'price_selling',
            ], null));
        }

        $attributes['prd_balance'] ??= 0;

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncClickerData(Product $product, Request $request, array $validated): void
    {
        $productId = $product->getKey();

        if (($validated['product_type'] ?? 'standard') !== 'clicker') {
            $existingResultPaths = DB::table('product_clicker_results')
                ->where('product_id', $productId)
                ->pluck('image_path')
                ->all();
            $existingPaths = DB::table('product_clicker_images')
                ->where('product_id', $productId)
                ->pluck('image_path')
                ->all();

            DB::table('product_clicker_prices')->where('product_id', $productId)->delete();
            DB::table('product_clicker_results')->where('product_id', $productId)->delete();
            DB::table('product_clicker_images')->where('product_id', $productId)->delete();
            $this->deleteManagedClickerFiles([...$existingResultPaths, ...$existingPaths]);

            return;
        }

        if (array_key_exists('clicker_character_prices', $validated)) {
            $prices = collect(range(1, 8))
                ->map(function (int $characterCount) use ($productId, $validated): array {
                    $pricing = data_get($validated, "clicker_character_prices.$characterCount", []);

                    return [
                        'product_id' => $productId,
                        'character_count' => $characterCount,
                        'price_rm' => $this->decimalValue(data_get($pricing, 'price_rm')),
                        'cost_rm' => $this->decimalValue(data_get($pricing, 'cost_rm')),
                        'weight_g' => $this->decimalValue(data_get($pricing, 'weight_g')),
                        'width_mm' => $this->decimalValue(data_get($pricing, 'width_mm')),
                        'height_mm' => $this->decimalValue(data_get($pricing, 'height_mm')),
                        'length_mm' => $this->decimalValue(data_get($pricing, 'length_mm')),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })
                ->all();

            DB::table('product_clicker_prices')->where('product_id', $productId)->delete();
            DB::table('product_clicker_prices')->insert($prices);
        }

        $this->syncClickerImages($product, $request, $validated);
        $this->syncClickerResult($product, $request, $validated);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function clickerCharacterPricing(int $productId): array
    {
        return DB::table('product_clicker_prices')
            ->where('product_id', $productId)
            ->orderBy('character_count')
            ->get([
                'character_count',
                'price_rm',
                'cost_rm',
                'weight_g',
                'width_mm',
                'height_mm',
                'length_mm',
            ])
            ->mapWithKeys(fn (object $pricing): array => [
                (int) $pricing->character_count => collect([
                    'price_rm' => $pricing->price_rm,
                    'cost_rm' => $pricing->cost_rm,
                    'weight_g' => $pricing->weight_g,
                    'width_mm' => $pricing->width_mm,
                    'height_mm' => $pricing->height_mm,
                    'length_mm' => $pricing->length_mm,
                ])->map(fn (mixed $value): string => $value === null ? '' : number_format((float) $value, 2, '.', ''))->all(),
            ])
            ->all();
    }

    private function clickerResults(int $productId): Collection
    {
        return DB::table('product_clicker_results')
            ->where('product_id', $productId)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    private function decimalValue(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncClickerImages(Product $product, Request $request, array $validated): void
    {
        $storedPaths = [];
        $replacedPaths = [];

        try {
            foreach (['casing', 'huruf'] as $type) {
                foreach (range(1, 25) as $position) {
                    $row = data_get($validated, "clicker_images.$type.$position", []);
                    $imageId = (int) data_get($row, 'id', 0);
                    $name = trim((string) data_get($row, 'name', ''));
                    $upload = $request->file("clicker_images.$type.$position.image");

                    if ($imageId > 0) {
                        $existingImage = DB::table('product_clicker_images')
                            ->where('id', $imageId)
                            ->where('product_id', $product->getKey())
                            ->where('image_type', $type)
                            ->lockForUpdate()
                            ->first();

                        if (! $existingImage) {
                            continue;
                        }

                        if ($row['remove'] ?? false) {
                            $errorKey = "clicker_images.$type.$position.remove";
                            if ($type === 'casing') {
                                $stock = DB::table('product_clicker_stocks')->where('casing_image_id', $imageId)->lockForUpdate()->get();
                                if ($stock->contains(fn ($size): bool => (int) $size->quantity !== 0)) {
                                    throw ValidationException::withMessages([$errorKey => 'Casing masih mempunyai stok. Selaraskan stok dan simpan sebelum mengosongkan slot.']);
                                }
                                foreach (['order_items' => 'clicker_casing_image_id', 'customer_order_items' => 'clicker_casing_image_id', 'pos_sale_items' => 'stock_casing_image_id'] as $table => $column) {
                                    if (DB::table($table)->where($column, $imageId)->exists()) {
                                        throw ValidationException::withMessages([$errorKey => 'Casing dirujuk oleh pesanan atau sejarah jualan dan tidak boleh dipadam.']);
                                    }
                                }
                            }
                            if ((int) data_get($validated, "clicker_result.{$type}_image_id") === $imageId) {
                                throw ValidationException::withMessages([$errorKey => 'Batalkan pilihan hasil gabungan untuk slot yang hendak dipadam.']);
                            }
                            // Historical order and POS snapshots may still use these image files.
                            DB::table('product_clicker_images')->where('id', $imageId)->delete();

                            continue;
                        }

                        $attributes = [
                            'alt_text' => $name,
                            'position' => $position,
                            'updated_at' => now(),
                        ];

                        if ($upload instanceof UploadedFile) {
                            $path = $this->storeClickerImage(
                                $product,
                                $type,
                                $upload,
                                "clicker_images.$type.$position.image",
                            );
                            $storedPaths[] = $path;
                            $replacedPaths[] = $existingImage->image_path;
                            $attributes['image_path'] = $path;
                            $attributes['crop_width_px'] = StoreResizedProductImage::MAX_WIDTH;
                        }

                        DB::table('product_clicker_images')->where('id', $imageId)->update($attributes);

                        continue;
                    }

                    if (! $upload instanceof UploadedFile) {
                        continue;
                    }

                    $path = $this->storeClickerImage(
                        $product,
                        $type,
                        $upload,
                        "clicker_images.$type.$position.image",
                    );
                    $storedPaths[] = $path;

                    DB::table('product_clicker_images')->insert([
                        'product_id' => $product->getKey(),
                        'image_type' => $type,
                        'image_path' => $path,
                        'alt_text' => $name,
                        'position' => $position,
                        'crop_width_px' => StoreResizedProductImage::MAX_WIDTH,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        } catch (\Throwable $exception) {
            $this->deleteManagedClickerFiles($storedPaths);
            throw $exception;
        }

        $this->deleteManagedClickerFiles($replacedPaths);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncClickerResult(Product $product, Request $request, array $validated): void
    {
        $upload = $request->file('clicker_result.image');

        if (! $upload instanceof UploadedFile) {
            return;
        }

        $casingImageId = (int) data_get($validated, 'clicker_result.casing_image_id');
        $hurufImageId = (int) data_get($validated, 'clicker_result.huruf_image_id');
        $existingResult = DB::table('product_clicker_results')
            ->where('product_id', $product->getKey())
            ->where('casing_image_id', $casingImageId)
            ->where('huruf_image_id', $hurufImageId)
            ->first();
        $name = trim((string) data_get($validated, 'clicker_result.name', ''));

        if ($name === '') {
            $imageNames = DB::table('product_clicker_images')
                ->whereIn('id', [$casingImageId, $hurufImageId])
                ->orderByRaw('FIELD(id, ?, ?)', [$casingImageId, $hurufImageId])
                ->pluck('alt_text')
                ->filter()
                ->implode(' + ');
            $name = $imageNames !== '' ? $imageNames : 'Clicker result';
        }

        $path = $this->storeClickerImage(
            $product,
            'result',
            $upload,
            'clicker_result.image',
        );

        try {
            if ($existingResult) {
                DB::table('product_clicker_results')
                    ->where('id', $existingResult->id)
                    ->update(['name' => $name, 'image_path' => $path, 'updated_at' => now()]);
            } else {
                DB::table('product_clicker_results')->insert([
                    'product_id' => $product->getKey(),
                    'casing_image_id' => $casingImageId,
                    'huruf_image_id' => $hurufImageId,
                    'name' => $name,
                    'image_path' => $path,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $exception) {
            $this->deleteManagedClickerFiles([$path]);
            throw $exception;
        }

        if ($existingResult) {
            $this->deleteManagedClickerFiles([$existingResult->image_path]);
        }
    }

    private function storeClickerImage(
        Product $product,
        string $type,
        UploadedFile $upload,
        string $validationAttribute,
    ): string {
        return $this->storeResizedProductImage->handle(
            $upload,
            "images/products/{$product->getKey()}/clicker/$type",
            "product-clicker-$type-{$product->getKey()}-",
            $validationAttribute,
        );
    }

    /**
     * @param  iterable<int, string>  $paths
     */
    private function deleteManagedClickerFiles(iterable $paths): void
    {
        foreach ($paths as $path) {
            $isUsedByOrder = DB::table('order_items')
                ->where('clicker_casing_image_path', $path)
                ->orWhere('clicker_huruf_image_path', $path)
                ->exists();

            if ($isUsedByOrder) {
                continue;
            }

            if (Str::startsWith($path, 'images/products/')) {
                File::delete(public_path($path));
            }
        }
    }

    public function updateDiscontinuation(UpdateProductDiscontinuationRequest $request, Product $product): RedirectResponse
    {
        abort_unless($product->discontinuationAvailable(), 503);
        $data = $request->validated();
        DB::transaction(function () use ($request, $product, $data): void {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $discontinued = (bool) $data['discontinued'];
            $product->forceFill([
                'discontinued_at' => $discontinued ? ($product->discontinued_at ?? now()) : null,
                'discontinuation_reason' => $discontinued ? ($data['reason'] ?? null) : null,
            ])->save();
            AdminActivity::record(request: $request, event: 'admin.product.discontinuation.updated',
                description: $discontinued ? "Product {$product->prd_code} discontinued; sell remaining stock." : "Product {$product->prd_code} reactivated.",
                adminUser: $request->user('admin'), properties: ['page' => 'Products', 'product_id' => $product->id,
                    'discontinued' => $discontinued, 'reason' => $data['reason'] ?? null, 'balance' => $product->prd_balance]);
        });

        return back()->with('success', $data['discontinued'] ? 'Produk dihentikan. Baki stok masih boleh dijual; pre-order disekat.' : 'Produk diaktifkan semula.');
    }

    public function toggleAgentVisibility(Request $request, Product $product): RedirectResponse
    {
        $product->forceFill([
            'is_visible_to_agents' => ! $product->is_visible_to_agents,
        ])->save();

        $visibility = $product->is_visible_to_agents ? 'visible' : 'hidden';

        AdminActivity::record(
            request: $request,
            event: 'admin.product.agent_visibility_updated',
            description: "Product {$product->prd_code} is now {$visibility} in the agent catalog.",
            adminUser: $request->user('admin'),
            properties: [
                'page' => 'Products',
                'product_id' => $product->getKey(),
                'product_code' => $product->prd_code,
                'is_visible_to_agents' => $product->is_visible_to_agents,
            ],
        );

        return back()->with(
            'success',
            "{$product->prd_name} is now {$visibility} in the agent catalog.",
        );
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

        $historyMessage = 'Produk ini mempunyai rekod pesanan atau jualan dan tidak boleh dipadam. Gunakan Hentikan Produk untuk menjual baki stok, atau Hide from Agent Catalog untuk menyembunyikannya.';
        if ($product->posSaleItems()->withTrashed()->exists()
            || $product->orderItems()->exists()
            || CustomerOrderItem::query()->where('product_id', $product->id)->exists()) {
            return redirect()->route('admin.products.index')->withErrors(['product' => $historyMessage]);
        }

        $productCode = $product->prd_code;
        $productId = $product->getKey();

        try {
            $product->delete();
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) !== '23000' || (int) ($exception->errorInfo[1] ?? 0) !== 1451) {
                throw $exception;
            }

            return redirect()->route('admin.products.index')->withErrors(['product' => $historyMessage]);
        }

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
