<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ];
    }
}
