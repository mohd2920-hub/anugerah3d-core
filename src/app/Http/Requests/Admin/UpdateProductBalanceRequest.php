<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminAccess;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AdminAccess::canRoute($this->user('admin'), 'admin.products.balance.update');
    }

    public function rules(): array
    {
        return [
            'expected_balance' => ['required', 'integer', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0', 'max:10000000'],
            'expected_quantity' => ['required', 'integer', 'min:0'],
            'casing_id' => ['nullable', 'integer', 'min:1'],
            'character_count' => ['required_with:casing_id', 'nullable', 'integer', 'between:1,8'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
