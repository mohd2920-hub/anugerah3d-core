<?php

namespace App\Actions\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class SyncProductImages
{
    public function __construct(private StoreResizedProductImage $storeResizedProductImage) {}

    /**
     * @param  array<int, UploadedFile>  $uploads
     * @param  array<int, int|string>  $removeImageIds
     */
    public function handle(Product $product, array $uploads, array $removeImageIds, ?string $mainImage): void
    {
        $product->load('images');

        $removeIds = collect($removeImageIds)->map(fn (int|string $id): int => (int) $id);
        $removedImages = $product->images->whereIn('id', $removeIds);
        $items = $product->images
            ->whereNotIn('id', $removeIds)
            ->map(fn (ProductImage $image): array => [
                'key' => 'existing-'.$image->getKey(),
                'path' => $image->image_path,
                'alt' => $image->alt_text,
            ])
            ->values();

        $storedPaths = [];

        try {
            foreach (array_values($uploads) as $index => $upload) {
                $path = $this->store($upload, $product);
                $storedPaths[] = $path;
                $items->push([
                    'key' => 'new-'.$index,
                    'path' => $path,
                    'alt' => $product->prd_name.' view '.($items->count() + 1),
                ]);
            }

            if ($items->isEmpty() && $product->images->isEmpty() && $product->prd_picture) {
                $items->push([
                    'key' => 'legacy',
                    'path' => $product->prd_picture,
                    'alt' => $product->prd_name.' main view',
                ]);
            }

            if ($items->count() > ProductImage::MAX_IMAGES_PER_PRODUCT) {
                throw ValidationException::withMessages([
                    'product_images' => 'A product can have a maximum of 5 pictures.',
                ]);
            }

            $selectedMain = $items->firstWhere('key', $mainImage);

            if ($selectedMain) {
                $items = $items
                    ->reject(fn (array $item): bool => $item['key'] === $mainImage)
                    ->prepend($selectedMain)
                    ->values();
            }

            DB::transaction(function () use ($product, $items): void {
                $product->images()->delete();

                foreach ($items as $index => $item) {
                    $product->images()->create([
                        'image_path' => $item['path'],
                        'alt_text' => $item['alt'],
                        'position' => $index + 1,
                    ]);
                }

                $product->forceFill([
                    'prd_picture' => $items->first()['path'] ?? null,
                ])->save();
            });
        } catch (Throwable $exception) {
            $this->deleteManagedFiles($storedPaths);

            throw $exception;
        }

        foreach ($removedImages as $removedImage) {
            if (! $items->contains('path', $removedImage->image_path)) {
                $this->deleteManagedFiles([$removedImage->image_path]);
            }
        }
    }

    private function store(UploadedFile $upload, Product $product): string
    {
        return $this->storeResizedProductImage->handle(
            $upload,
            'images/products',
            'product-'.$product->getKey().'-',
            'product_images',
        );
    }

    /**
     * @param  iterable<int, string>  $paths
     */
    private function deleteManagedFiles(iterable $paths): void
    {
        foreach ($paths as $path) {
            if (Str::startsWith($path, 'images/products/product-')) {
                File::delete(public_path($path));
            }
        }
    }
}
