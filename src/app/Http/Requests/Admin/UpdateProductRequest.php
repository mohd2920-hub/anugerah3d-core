<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use App\Support\CasingStock;
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
        $requiresClickerPricing = $this->input('product_type') === 'clicker'
            && (! $product instanceof Product || $product->product_type !== 'clicker' || $this->has('clicker_character_prices'));

        $rules = [
            'prd_code' => [
                'required',
                'string',
                'max:80',
                Rule::unique((new Product)->getTable(), 'prd_code')
                    ->ignore($product instanceof Product ? $product->getKey() : null),
            ],
            'prd_name' => ['required', 'string', 'max:255'],
            'product_type' => ['required', 'string', Rule::in(['standard', 'clicker'])],
            'weight_g' => [Rule::requiredIf(fn (): bool => $this->input('product_type') === 'standard'), 'nullable', 'numeric', 'min:0'],
            'width_mm' => [Rule::requiredIf(fn (): bool => $this->input('product_type') === 'standard'), 'nullable', 'numeric', 'min:0'],
            'height_mm' => [Rule::requiredIf(fn (): bool => $this->input('product_type') === 'standard'), 'nullable', 'numeric', 'min:0'],
            'length_mm' => ['nullable', 'numeric', 'min:0'],
            'color' => ['nullable', 'string', 'max:80'],
            'material_id' => ['nullable', 'exists:materials,id'],
            'prd_balance' => [Rule::requiredIf(fn (): bool => $this->input('product_type') === 'standard'), 'nullable', 'integer', 'min:0'],
            ...CasingStock::rules(),
            'cost_rm' => [Rule::requiredIf(fn (): bool => $this->input('product_type') === 'standard'), 'nullable', 'numeric', 'min:0'],
            'price_selling' => [Rule::requiredIf(fn (): bool => $this->input('product_type') === 'standard'), 'nullable', 'numeric', 'min:0'],
            'agent_discount_default' => ['required', 'numeric', 'min:0', 'max:100'],
            'prd_picture' => ['nullable', 'url', 'max:2048'],
            'product_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'extensions:mp4,webm', 'max:20480'],
            'remove_product_video' => ['sometimes', 'boolean'],
            'product_images' => ['nullable', 'array', 'max:'.ProductImage::MAX_IMAGES_PER_PRODUCT],
            'product_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => [
                'integer',
                Rule::exists((new ProductImage)->getTable(), 'id')
                    ->where(fn ($query) => $query->where('product_id', $product instanceof Product ? $product->getKey() : null)),
            ],
            'main_image' => ['nullable', 'string', "regex:/^(existing|new)-\d+$/"],
            'clicker_character_prices' => ['nullable', 'array'],
            'clicker_images' => ['nullable', 'array:casing,huruf'],
            'clicker_images.casing' => ['nullable', 'array', 'max:25'],
            'clicker_images.casing.*' => ['array'],
            'clicker_images.casing.*.name' => ['nullable', 'string', 'max:100', 'required_with:clicker_images.casing.*.id,clicker_images.casing.*.image'],
            'clicker_images.casing.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'clicker_images.huruf' => ['nullable', 'array', 'max:25'],
            'clicker_images.huruf.*' => ['array'],
            'clicker_images.huruf.*.name' => ['nullable', 'string', 'max:100', 'required_with:clicker_images.huruf.*.id,clicker_images.huruf.*.image'],
            'clicker_images.huruf.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'clicker_result' => ['nullable', 'array:casing_image_id,huruf_image_id,name,image'],
            'clicker_result.casing_image_id' => ['nullable', 'integer'],
            'clicker_result.huruf_image_id' => ['nullable', 'integer'],
            'clicker_result.name' => ['nullable', 'string', 'max:100'],
            'clicker_result.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        foreach (['casing', 'huruf'] as $imageType) {
            $rules["clicker_images.$imageType.*.remove"] = ['sometimes', 'boolean'];
            array_unshift($rules["clicker_images.$imageType.*.name"], "exclude_if:clicker_images.$imageType.*.remove,1");
            array_unshift($rules["clicker_images.$imageType.*.image"], "exclude_if:clicker_images.$imageType.*.remove,1");
            $rules["clicker_images.$imageType.*.id"] = [
                'nullable',
                'integer',
                Rule::exists('product_clicker_images', 'id')->where(
                    fn ($query) => $query
                        ->where('product_id', $product instanceof Product ? $product->getKey() : null)
                        ->where('image_type', $imageType),
                ),
            ];
        }

        foreach (['casing', 'huruf'] as $imageType) {
            $rules["clicker_result.{$imageType}_image_id"][] = Rule::exists('product_clicker_images', 'id')->where(
                fn ($query) => $query
                    ->where('product_id', $product instanceof Product ? $product->getKey() : null)
                    ->where('image_type', $imageType),
            );
        }

        foreach (range(1, 8) as $characterCount) {
            $rules["clicker_character_prices.$characterCount"] = [
                Rule::requiredIf(fn (): bool => $requiresClickerPricing),
                'array',
            ];

            foreach (['price_rm', 'cost_rm', 'weight_g', 'width_mm', 'height_mm', 'length_mm'] as $field) {
                $rules["clicker_character_prices.$characterCount.$field"] = [
                    Rule::requiredIf(fn (): bool => $requiresClickerPricing),
                    'numeric',
                    'min:0',
                ];
            }
        }

        return $rules;
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
                    $validator->errors()->add('product_images', 'A product can have a maximum of '.ProductImage::MAX_IMAGES_PER_PRODUCT.' pictures.');
                }

                $mainImage = $this->string('main_image')->toString();

                if ($mainImage !== '') {
                    if (str($mainImage)->startsWith('existing-')) {
                        $imageId = (int) str($mainImage)->after('existing-')->toString();

                        if (! $remainingIds->contains($imageId)) {
                            $validator->errors()->add('main_image', 'Choose a main picture that is not removed.');
                        }
                    } else {
                        $index = (int) str($mainImage)->after('new-')->toString();

                        if (! isset($uploads[$index])) {
                            $validator->errors()->add('main_image', 'Choose a valid main picture.');
                        }
                    }
                }

                $resultRequested = $this->hasFile('clicker_result.image')
                    || $this->filled('clicker_result.casing_image_id')
                    || $this->filled('clicker_result.huruf_image_id')
                    || $this->filled('clicker_result.name');

                if ($resultRequested) {
                    foreach (['casing_image_id', 'huruf_image_id'] as $field) {
                        if (! $this->filled("clicker_result.$field")) {
                            $validator->errors()->add("clicker_result.$field", 'Choose both casing and huruf for the result.');
                        }
                    }

                    if (! $this->hasFile('clicker_result.image')) {
                        $validator->errors()->add('clicker_result.image', 'Upload one result image.');
                    }

                }

                if ($this->input('product_type') !== 'clicker' || ! $this->has('clicker_character_prices')) {
                    return;
                }

                $prices = $this->input('clicker_character_prices', []);

                if (count($prices) < 8) {
                    $validator->errors()->add('clicker_character_prices', 'Enter a price for each character count from 1 to 8.');
                }
            },
        ];
    }
}
