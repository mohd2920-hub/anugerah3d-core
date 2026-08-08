<?php

namespace App\Actions\Pos;

use App\Models\Agent;
use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CreatePosSale
{
    public function handle(PosSession $session, array $data): PosSale
    {
        $paths = $this->storePictures($data);

        try {
            return DB::transaction(function () use ($session, $data, $paths): PosSale {
                $businessSite = BusinessSite::query()
                    ->open()
                    ->lockForUpdate()
                    ->findOrFail($session->business_site_id);

                $operation = BusinessSiteOperation::query()
                    ->whereBelongsTo($businessSite)
                    ->whereNull('closed_at')
                    ->lockForUpdate()
                    ->latest('opened_at')
                    ->firstOrFail();
                $items = $this->items($data['items'], $data['sales_agent_id']);
                $sale = PosSale::query()->create([
                    'sale_number' => 'POS-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                    'pos_session_id' => $session->getKey(),
                    'business_site_id' => $session->business_site_id,
                    'business_site_operation_id' => $operation->getKey(),
                    'recorded_by_agent_id' => $session->agent_id,
                    'sales_agent_id' => $data['sales_agent_id'],
                    'customer_name' => $data['customer_name'] ?? null,
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'customer_email' => $data['customer_email'] ?? null,
                    'remark' => $data['remark'] ?? null,
                    'payment_method' => $data['payment_method'],
                    'payment_remark' => $data['payment_remark'] ?? null,
                    'sale_picture_path' => $paths['sale_picture_paths'][0] ?? null,
                    'sale_picture_paths' => $paths['sale_picture_paths'],
                    'payment_proof_path' => $paths['payment_proof_paths'][0] ?? null,
                    'payment_proof_paths' => $paths['payment_proof_paths'],
                    'total_amount' => collect($items)->sum('line_total'),
                    'sold_at' => now(),
                ]);

                $sale->items()->createMany($items);

                return $sale->load(['items', 'businessSite', 'salesAgent']);
            });
        } catch (Throwable $exception) {
            $this->deleteStoredPictures([
                ...$paths['sale_picture_paths'],
                ...$paths['payment_proof_paths'],
            ]);

            throw $exception;
        }
    }

    /** @param array<int, array{product_id: int, quantity: int}> $items */
    private function productsFor(array $items): Collection
    {
        return Product::query()
            ->whereKey(collect($items)->pluck('product_id'))
            ->get(['id', 'prd_code', 'prd_name', 'price_selling', 'agent_discount_default'])
            ->keyBy('id');
    }

    /** @param array<int, array{product_id: int, quantity: int, discount_amount?: float|int|string|null}> $items */
    public function items(array $items, int $salesAgentId): array
    {
        $agentDiscount = (float) Agent::query()
            ->whereKey($salesAgentId)
            ->valueOrFail('discount_percentage');

        return $this->itemRows($items, $this->productsFor($items), $agentDiscount);
    }

    /** @param array<int, array{product_id: int, quantity: int, discount_amount?: float|int|string|null}> $items */
    private function itemRows(array $items, Collection $products, float $agentDiscount): array
    {
        return collect($items)->map(function (array $item) use ($products, $agentDiscount): array {
            $product = $products->get($item['product_id']);
            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $product->price_selling;
            $unitPriceCents = (int) round($unitPrice * 100);
            $grossTotalCents = $unitPriceCents * $quantity;
            $baselineDiscount = $agentDiscount > 0
                ? $agentDiscount
                : (float) $product->agent_discount_default;
            $agentDiscountAmountCents = (int) round($grossTotalCents * ($baselineDiscount / 100));
            $customerDiscountCents = isset($item['discount_amount'])
                ? (int) round(max(0, (float) $item['discount_amount']) * 100)
                : 0;
            $customerDiscountAppliedCents = min($grossTotalCents, $customerDiscountCents);

            return [
                'product_id' => $product->getKey(),
                'product_code' => $product->prd_code,
                'product_name' => $product->prd_name,
                'quantity' => $quantity,
                'unit_price' => $unitPriceCents / 100,
                'agent_discount_percentage' => $baselineDiscount,
                'agent_discount_amount' => $agentDiscountAmountCents / 100,
                'customer_discount_amount' => $customerDiscountAppliedCents / 100,
                'line_total' => ($grossTotalCents - $customerDiscountAppliedCents) / 100,
            ];
        })->all();
    }

    /** @return array{sale_picture_paths: array<int, string>, payment_proof_paths: array<int, string>} */
    private function storePictures(array $data): array
    {
        return [
            'sale_picture_paths' => $this->storePictureSet($data['sale_pictures'] ?? [], 'sale'),
            'payment_proof_paths' => $this->storePictureSet($data['payment_proofs'] ?? [], 'payment'),
        ];
    }

    /** @param array<int, UploadedFile>|UploadedFile|null $files */
    public function storePictureSet(array|UploadedFile|null $files, string $type): array
    {
        $uploads = $files instanceof UploadedFile
            ? [$files]
            : array_values(array_filter(is_array($files) ? $files : [], fn ($file) => $file instanceof UploadedFile));

        return collect($uploads)
            ->take(5)
            ->map(fn (UploadedFile $file): string => $this->storePicture($file, $type))
            ->values()
            ->all();
    }

    public function storePicture(UploadedFile $file, string $type): string
    {
        [$targetWidth, $targetHeight, $mime] = $this->targetDimensions($file);
        $extension = $this->extensionForMime($mime) ?? $file->guessExtension() ?? 'jpg';
        $filename = $type.'-'.Str::uuid().'.'.$extension;
        $relativePath = 'pos-sales/'.$filename;
        $diskName = $this->pictureDisk();
        $disk = Storage::disk($diskName);

        $temporaryFile = tempnam(sys_get_temp_dir(), 'pos-sale-');
        $sourcePath = $file->getRealPath();
        $outputPath = $temporaryFile ?: $sourcePath;

        if ($temporaryFile && ! $this->resizeToWidth($sourcePath, $temporaryFile, $mime, $targetWidth, $targetHeight)) {
            $outputPath = $sourcePath;
        }

        $disk->put($relativePath, File::get($outputPath), 'public');

        if ($temporaryFile && File::exists($temporaryFile)) {
            File::delete($temporaryFile);
        }

        return $relativePath;
    }

    /** @param array<int, string> $paths */
    public function deleteStoredPictures(array $paths): void
    {
        $disk = Storage::disk($this->pictureDisk());

        foreach (array_filter($paths, fn ($path) => is_string($path) && $path !== '') as $path) {
            $disk->delete($path);

            if (File::exists(public_path($path))) {
                File::delete(public_path($path));
            }
        }
    }

    private function pictureDisk(): string
    {
        $default = (string) config('filesystems.default', 'public');

        if ($default === 's3' && class_exists('League\\Flysystem\\AwsS3V3\\PortableVisibilityConverter')) {
            return 's3';
        }

        return 'public';
    }

    /** @return array{0:int,1:int,2:string} */
    private function targetDimensions(UploadedFile $file): array
    {
        $image = @getimagesize($file->getRealPath());

        if (! is_array($image)) {
            return [800, 800, 'image/jpeg'];
        }

        $sourceWidth = max(1, (int) ($image[0] ?? 1));
        $sourceHeight = max(1, (int) ($image[1] ?? 1));
        $mime = (string) ($image['mime'] ?? 'image/jpeg');

        if ($sourceWidth <= 800) {
            return [$sourceWidth, $sourceHeight, $mime];
        }

        $ratio = 800 / $sourceWidth;

        return [800, max(1, (int) round($sourceHeight * $ratio)), $mime];
    }

    private function extensionForMime(string $mime): ?string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };
    }

    private function resizeToWidth(string $sourcePath, string $targetPath, string $mime, int $width, int $height): bool
    {
        $source = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($sourcePath) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($sourcePath) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if (! $source) {
            return false;
        }

        $canvas = imagecreatetruecolor($width, $height);

        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        $saved = match ($mime) {
            'image/jpeg' => imagejpeg($canvas, $targetPath, 85),
            'image/png' => imagepng($canvas, $targetPath, 6),
            'image/webp' => imagewebp($canvas, $targetPath, 82),
            default => false,
        };

        imagedestroy($source);
        imagedestroy($canvas);

        return (bool) $saved;
    }
}
