<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'prd_code' => [
                'required',
                'string',
                'max:80',
                Rule::unique((new Product)->getTable(), 'prd_code')
                    ->ignore($product instanceof Product ? $product->getKey() : null),
            ],
            'prd_name' => ['required', 'string', 'max:255'],
            'weight_g' => ['required', 'numeric', 'min:0'],
            'width_mm' => ['required', 'numeric', 'min:0'],
            'height_mm' => ['required', 'numeric', 'min:0'],
            'length_mm' => ['nullable', 'numeric', 'min:0'],
            'color' => ['nullable', 'string', 'max:80'],
            'material_id' => ['nullable', 'exists:materials,id'],
            'prd_balance' => ['required', 'integer', 'min:0'],
            'cost_rm' => ['required', 'numeric', 'min:0'],
            'price_selling' => ['required', 'numeric', 'min:0'],
            'agent_discount_default' => ['required', 'numeric', 'min:0', 'max:100'],
            'prd_picture' => ['nullable', 'url', 'max:2048'],
            'product_images' => ['nullable', 'array', 'max:5'],
            'product_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => [
                'integer',
                Rule::exists((new ProductImage)->getTable(), 'id')
                    ->where(fn ($query) => $query->where('product_id', $product instanceof Product ? $product->getKey() : null)),
            ],
            'main_image' => ['nullable', 'string', 'regex:/^(existing|new)-\\d+$/'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $product = $this->route('product');

                if (! $product instanceof Product || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $removeIds = collect($this->input('remove_image_ids', []))
                    ->map(fn (mixed $id): int => (int) $id);

                $remainingIds = $product->images()
                    ->whereNotIn('id', $removeIds)
                    ->pluck('id');

                $uploads = array_values($this->file('product_images', []));

                if ($remainingIds->count() + count($uploads) > ProductImage::MAX_IMAGES_PER_PRODUCT) {
                    $validator->errors()->add('product_images', 'A product can have a maximum of 5 pictures.');
                }

                $mainImage = $this->string('main_image')->toString();

                if ($mainImage === '') {
                    return;
                }

                if (str($mainImage)->startsWith('existing-')) {
                    $imageId = (int) str($mainImage)->after('existing-')->toString();

                    if (! $remainingIds->contains($imageId)) {
                        $validator->errors()->add('main_image', 'Choose a main picture that is not removed.');
                    }

                    return;
                }

                $index = (int) str($mainImage)->after('new-')->toString();

                if (! isset($uploads[$index])) {
                    $validator->errors()->add('main_image', 'Choose a valid main picture.');
                }
            },
        ];
    }
}
