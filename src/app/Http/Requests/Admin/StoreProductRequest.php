<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use App\Support\CasingStock;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
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
        $rules = [
            'prd_code' => ['required', 'string', 'max:80', Rule::unique((new Product)->getTable(), 'prd_code')],
            'prd_name' => ['required', 'string', 'max:255'],
            'product_type' => ['required', 'string', Rule::in(['standard', 'clicker'])],
            'weight_g' => [Rule::requiredIf(fn (): bool => $this->input('product_type') === 'standard'), 'nullable', 'numeric', 'min:0'],
            'width_mm' => [Rule::requiredIf(fn (): bool => $this->input('product_type') === 'standard'), 'nullable', 'numeric', 'min:0'],
            'height_mm' => [Rule::requiredIf(fn (): bool => $this->input('product_type') === 'standard'), 'nullable', 'numeric', 'min:0'],
            'length_mm' => ['nullable', 'numeric', 'min:0'],
            'color' => ['nullable', 'string', 'max:80'],
            'material_id' => ['nullable', 'exists:materials,id'],
            'prd_balance' => [Rule::requiredIf(fn (): bool => $this->input('product_type') === 'standard'), 'nullable', 'integer', 'min:0'],
            'cost_rm' => [Rule::requiredIf(fn (): bool => $this->input('product_type') === 'standard'), 'nullable', 'numeric', 'min:0'],
            'price_selling' => [Rule::requiredIf(fn (): bool => $this->input('product_type') === 'standard'), 'nullable', 'numeric', 'min:0'],
            'agent_discount_default' => ['required', 'numeric', 'min:0', 'max:100'],
            'prd_picture' => ['nullable', 'url', 'max:2048'],
            'product_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'extensions:mp4,webm', 'max:20480'],
            'remove_product_video' => ['sometimes', 'boolean'],
            'product_images' => ['nullable', 'array', 'max:'.ProductImage::MAX_IMAGES_PER_PRODUCT],
            'product_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'main_image' => ['nullable', 'string', 'regex:/^new-[0-4]$/'],
            'clicker_character_prices' => ['nullable', 'array'],
            ...CasingStock::rules(),
            'clicker_images' => ['nullable', 'array:casing,huruf'],
            'clicker_images.casing' => ['nullable', 'array', 'max:25'],
            'clicker_images.casing.*' => ['array'],
            'clicker_images.casing.*.id' => ['prohibited'],
            'clicker_images.casing.*.name' => ['nullable', 'string', 'max:100', 'required_with:clicker_images.casing.*.image'],
            'clicker_images.casing.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_with:clicker_images.casing.*.name'],
            'clicker_images.huruf' => ['nullable', 'array', 'max:25'],
            'clicker_images.huruf.*' => ['array'],
            'clicker_images.huruf.*.id' => ['prohibited'],
            'clicker_images.huruf.*.name' => ['nullable', 'string', 'max:100', 'required_with:clicker_images.huruf.*.image'],
            'clicker_images.huruf.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_with:clicker_images.huruf.*.name'],
        ];

        foreach (range(1, 8) as $characterCount) {
            $rules["clicker_character_prices.$characterCount"] = [
                Rule::requiredIf(fn (): bool => $this->input('product_type') === 'clicker'),
                'array',
            ];

            foreach (['price_rm', 'cost_rm', 'weight_g', 'width_mm', 'height_mm', 'length_mm'] as $field) {
                $rules["clicker_character_prices.$characterCount.$field"] = [
                    Rule::requiredIf(fn (): bool => $this->input('product_type') === 'clicker'),
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
                $mainImage = $this->string('main_image')->toString();

                if ($mainImage !== '') {
                    $index = (int) str($mainImage)->after('new-')->toString();
                    $uploads = array_values($this->file('product_images', []));

                    if (! isset($uploads[$index])) {
                        $validator->errors()->add('main_image', 'Choose a valid main picture.');
                    }
                }

                if ($this->input('product_type') !== 'clicker') {
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
