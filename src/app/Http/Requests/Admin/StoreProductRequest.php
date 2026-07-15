<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
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
        return [
            'prd_code' => ['required', 'string', 'max:80', Rule::unique((new Product)->getTable(), 'prd_code')],
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
            'main_image' => ['nullable', 'string', 'regex:/^new-[0-4]$/'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $mainImage = $this->string('main_image')->toString();

                if ($mainImage === '') {
                    return;
                }

                $index = (int) str($mainImage)->after('new-')->toString();
                $uploads = array_values($this->file('product_images', []));

                if (! isset($uploads[$index])) {
                    $validator->errors()->add('main_image', 'Choose a valid main picture.');
                }
            },
        ];
    }
}
