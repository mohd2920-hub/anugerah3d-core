<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PayCustomerCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return ['reference' => ['required', 'string', 'max:255'], 'reason' => ['required', 'string', 'max:1000'], 'expected_amount' => ['required', 'numeric', 'min:0'], 'expected_cash' => ['required', 'numeric', 'min:0'], 'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120']];
    }
}
