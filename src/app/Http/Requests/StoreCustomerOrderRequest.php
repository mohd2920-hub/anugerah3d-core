<?php

namespace App\Http\Requests;

use App\Http\Requests\Agent\StoreOrderRequest;

class StoreCustomerOrderRequest extends StoreOrderRequest
{
    public function rules(): array
    {
        return array_replace(parent::rules(), ['expected_total' => ['required', 'numeric', 'min:0'], 'payment_method' => ['required', 'in:bank_transfer'], 'payment_proofs' => ['required', 'array', 'min:1', 'max:5']]);
    }

    public function authorize(): bool
    {
        return $this->hasValidSignature();
    }
}
