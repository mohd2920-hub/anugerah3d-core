<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class PosClicker
{
    public static function rules(): array
    {
        return [
            'items.*.clicker_casing_image_id' => ['nullable', 'integer', 'min:1'],
            'items.*.clicker_huruf_image_id' => ['nullable', 'integer', 'min:1'],
            'items.*.clicker_character_count' => ['nullable', 'integer', 'min:1', 'max:8'],
            'items.*.clicker_characters' => ['nullable', 'array', 'max:8'],
            'items.*.clicker_characters.*' => ['required', 'string', 'size:1'],
        ];
    }

    public static function validateStandardDuplicates(array $items, Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }
        $standardIds = Product::query()->whereKey(array_column($items, 'product_id'))->where('product_type', '!=', 'clicker')->pluck('id');
        foreach (collect($items)->groupBy('product_id') as $id => $rows) {
            if ($rows->count() > 1 && $standardIds->contains((int) $id)) {
                foreach ($items as $index => $item) {
                    if ((int) $item['product_id'] === (int) $id) {
                        $validator->errors()->add("items.{$index}.product_id", 'Each standard product may appear only once. Increase its quantity instead.');
                    }
                }
            }
        }
    }

    public function catalog(Collection $products): array
    {
        if (! Schema::hasColumn('pos_sale_items', 'clicker_configuration')) {
            return [];
        }
        $clickers = $products->where('product_type', 'clicker');
        $images = DB::table('product_clicker_images')->whereIn('product_id', $clickers->pluck('id')->all())->get()->groupBy('product_id');
        $stocks = app(CasingStock::class)->quantities($images->flatten(1)->pluck('id')->all());
        $prices = DB::table('product_clicker_prices')->whereIn('product_id', $clickers->pluck('id')->all())->get()->groupBy('product_id');
        $results = DB::table('product_clicker_results')->whereIn('product_id', $clickers->pluck('id')->all())->get()->groupBy('product_id');

        return $clickers->mapWithKeys(fn (Product $product): array => [$product->id => [
            'images' => collect($images->get($product->id, []))->map(fn ($image): array => [
                'id' => $image->id, 'type' => $image->image_type, 'name' => $image->alt_text,
                'src' => $this->url($image->image_path),
                'stock' => CasingStock::enabled($product) && $image->image_type === 'casing' ? ($stocks[$image->id] ?? array_fill(1, 8, 0)) : null,
            ])->all(),
            'prices' => collect($prices->get($product->id, []))->pluck('price_rm', 'character_count')->all(),
            'results' => collect($results->get($product->id, []))->map(fn ($image): array => [
                'casing' => $image->casing_image_id, 'huruf' => $image->huruf_image_id, 'name' => $image->name, 'src' => $this->url($image->image_path),
            ])->all(),
        ]])->all();
    }

    public function matches(?array $configuration, array $input): bool
    {
        if (! $configuration) {
            return empty($input['clicker_character_count']) && empty($input['clicker_casing_image_id']);
        }

        return (int) $configuration['casing_id'] === (int) ($input['clicker_casing_image_id'] ?? 0)
            && (int) $configuration['huruf_id'] === (int) ($input['clicker_huruf_image_id'] ?? 0)
            && (int) $configuration['character_count'] === (int) ($input['clicker_character_count'] ?? 0)
            && $configuration['characters'] === array_map(fn ($value): string => mb_strtoupper(trim($value)), $input['clicker_characters'] ?? []);
    }

    public function resolve(Product $product, array $input, string $field): ?array
    {
        if ($product->product_type !== 'clicker') {
            if (! empty($input['clicker_character_count']) || ! empty($input['clicker_casing_image_id'])) {
                throw ValidationException::withMessages([$field => 'Clicker choices are only available for Clicker products.']);
            }

            return null;
        }
        if (! Schema::hasColumn('pos_sale_items', 'clicker_configuration')) {
            throw ValidationException::withMessages([$field => 'Clicker POS setup is awaiting activation.']);
        }
        $count = (int) ($input['clicker_character_count'] ?? 0);
        $characters = array_values(array_map(fn ($value): string => mb_strtoupper(trim($value)), $input['clicker_characters'] ?? []));
        if ($count < 1 || $count > 8 || count($characters) !== $count || collect($characters)->contains(fn ($value): bool => mb_strlen($value) !== 1)) {
            throw ValidationException::withMessages([$field => 'Choose 1–8 characters and enter one character in each box.']);
        }
        $configuration = ['character_count' => $count, 'characters' => $characters];
        foreach (['casing', 'huruf'] as $type) {
            $image = DB::table('product_clicker_images')->where('product_id', $product->id)->where('image_type', $type)->where('id', $input["clicker_{$type}_image_id"] ?? null)->first();
            if (! $image) {
                throw ValidationException::withMessages([$field => "Choose a valid {$type} image for this product."]);
            }
            $configuration[$type.'_id'] = (int) $image->id;
            $configuration[$type.'_name'] = $image->alt_text;
            $configuration[$type.'_image'] = $this->url($image->image_path);
        }
        $result = DB::table('product_clicker_results')->where('product_id', $product->id)->where('casing_image_id', $configuration['casing_id'])->where('huruf_image_id', $configuration['huruf_id'])->first();
        $configuration['result_image'] = $result ? $this->url($result->image_path) : null;
        $price = DB::table('product_clicker_prices')->where('product_id', $product->id)->where('character_count', $count)->first();
        if (! $price) {
            throw ValidationException::withMessages([$field => 'The price for this character count is not configured.']);
        }

        return ['clicker_configuration' => $configuration, 'stock_casing_image_id' => CasingStock::enabled($product) ? $configuration['casing_id'] : null,
            'unit_price' => (float) $price->price_rm, 'unit_cost' => (float) $price->cost_rm];
    }

    /** @return array<int, array<string, int>> */
    public function inventory(array $before, array $after, bool $apply = false): array
    {
        $deltas = [];
        foreach ([[$before, 1], [$after, -1]] as [$items, $direction]) {
            foreach ($items as $item) {
                $id = $item['stock_casing_image_id'] ?? null;
                if (! $id && empty($item['uses_product_stock'])) {
                    continue;
                }
                $count = $id ? (int) $item['clicker_configuration']['character_count'] : 0;
                $key = $item['product_id'].':'.$id.':'.$count;
                $deltas[$key] ??= ['product_id' => (int) $item['product_id'], 'casing_id' => (int) $id, 'character_count' => $count, 'change' => 0];
                $deltas[$key]['change'] += $direction * (int) $item['quantity'];
            }
        }
        $products = Product::query()->whereKey(array_column($deltas, 'product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $changes = [];
        ksort($deltas);
        foreach ($deltas as $delta) {
            if ($delta['change'] === 0) {
                continue;
            }
            $product = $products->get($delta['product_id']);
            if ($delta['casing_id'] === 0) {
                if (! $product || (int) $product->prd_balance + $delta['change'] < 0) {
                    throw ValidationException::withMessages(['items' => 'Baki stok pusat tidak mencukupi. Kurangkan kuantiti jualan.']);
                }
                $changes[] = $delta + ['before' => (int) $product->prd_balance, 'after' => (int) $product->prd_balance + $delta['change']];
                if ($apply) {
                    $product->increment('prd_balance', $delta['change']);
                }

                continue;
            }
            $casing = DB::table('product_clicker_stocks')->where('casing_image_id', $delta['casing_id'])->where('character_count', $delta['character_count'])->lockForUpdate()->first();
            if (! $product || ! $casing || (int) $casing->quantity + $delta['change'] < 0) {
                throw ValidationException::withMessages(['items' => 'Insufficient casing stock. Reduce the quantity or choose another casing.']);
            }
            $changes[] = $delta + ['before' => (int) $casing->quantity, 'after' => (int) $casing->quantity + $delta['change']];
            if ($apply) {
                app(CasingStock::class)->move($product, $delta['casing_id'], $delta['character_count'], $delta['change']);
            }
        }

        return $changes;
    }

    private function url(string $path): string
    {
        return filter_var($path, FILTER_VALIDATE_URL) ? $path : asset(ltrim($path, '/'));
    }
}
