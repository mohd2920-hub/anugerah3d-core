<?php

namespace App\Http\Requests\Agent;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('agent') !== null;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'fulfilment_method' => ['required', Rule::in(['delivery', 'pickup'])],
            'recipient_name' => ['required', 'string', 'max:150'],
            'phone_number' => ['required', 'string', 'max:50'],
            'delivery_address' => ['nullable', 'required_if:fulfilment_method,delivery', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'pay_later'])],
            'payment_proofs' => ['nullable', 'required_if:payment_method,bank_transfer', 'array', 'max:5'],
            'payment_proofs.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.clicker_character_count' => ['nullable', 'integer', 'min:1', 'max:8'],
            'items.*.clicker_characters' => ['nullable', 'array', 'max:8'],
            'items.*.clicker_casing_image_id' => ['nullable', 'integer', 'min:1'],
            'items.*.clicker_huruf_image_id' => ['nullable', 'integer', 'min:1'],
            'items.*.clicker_characters.*' => ['nullable', 'string', 'max:1'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $items = collect($this->input('items', []));
                $productIds = $items
                    ->pluck('product_id')
                    ->filter(fn (mixed $id): bool => is_numeric($id))
                    ->map(fn (mixed $id): int => (int) $id)
                    ->values();

                if ($productIds->isEmpty()) {
                    return;
                }

                $products = Product::query()
                    ->whereKey($productIds)
                    ->get(['id', 'product_type'])
                    ->keyBy('id');

                $clickerImageIds = $items
                    ->flatMap(fn (mixed $item): array => [
                        data_get($item, 'clicker_casing_image_id'),
                        data_get($item, 'clicker_huruf_image_id'),
                    ])
                    ->filter(fn (mixed $id): bool => is_numeric($id) && (int) $id > 0)
                    ->map(fn (mixed $id): int => (int) $id);
                $clickerImages = DB::table('product_clicker_images')
                    ->whereIn('id', $clickerImageIds)
                    ->get(['id', 'product_id', 'image_type'])
                    ->keyBy('id');

                foreach ($items as $index => $item) {
                    $product = $products->get((int) ($item['product_id'] ?? 0));

                    if (! $product instanceof Product) {
                        continue;
                    }

                    if ($product->product_type !== 'clicker') {
                        if (array_key_exists('clicker_character_count', $item) || array_key_exists('clicker_characters', $item)) {
                            $validator->errors()->add("items.{$index}.clicker_character_count", 'Character selection is only available for clicker products.');
                        }

                        continue;
                    }

                    foreach (['casing', 'huruf'] as $imageType) {
                        $field = "clicker_{$imageType}_image_id";
                        $imageId = (int) ($item[$field] ?? 0);
                        $image = $clickerImages->get($imageId);

                        if (
                            ! $image
                            || (int) $image->product_id !== $product->getKey()
                            || $image->image_type !== $imageType
                        ) {
                            $validator->errors()->add(
                                "items.{$index}.{$field}",
                                "Choose a valid {$imageType} image for this clicker.",
                            );
                        }
                    }

                    $characterCount = (int) ($item['clicker_character_count'] ?? 0);

                    if ($characterCount < 1 || $characterCount > 8) {
                        $validator->errors()->add("items.{$index}.clicker_character_count", 'Choose between 1 and 8 characters for this clicker.');

                        continue;
                    }

                    $characters = collect($item['clicker_characters'] ?? [])
                        ->map(fn (mixed $character): string => trim((string) $character))
                        ->filter()
                        ->values();

                    if ($characters->count() !== $characterCount) {
                        $validator->errors()->add("items.{$index}.clicker_characters", "Enter exactly {$characterCount} characters for this clicker.");

                        continue;
                    }

                    $invalidCharacter = $characters->first(
                        fn (string $character): bool => mb_strlen($character) !== 1,
                    );

                    if ($invalidCharacter !== null) {
                        $validator->errors()->add("items.{$index}.clicker_characters", 'Each clicker field accepts one character only.');
                    }
                }
            },
        ];
    }
}
