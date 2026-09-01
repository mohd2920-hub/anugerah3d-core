<?php

namespace App\Http\Requests\Admin;

use App\Models\PosSale;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSalesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'search' => ['nullable', 'string', 'max:255'],
            'business_site_id' => ['nullable', 'integer', 'exists:business_sites,id'],
            'payment_method' => ['nullable', 'string', Rule::in(array_keys(PosSale::paymentMethods()))],
            'period' => ['nullable', 'string', Rule::in(['today', 'yesterday', 'week', 'month', '30_days'])],
            'start_date' => ['nullable', 'required_with:end_date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'required_with:start_date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'show_discounts' => ['nullable', 'boolean'],
        ];
    }
}
