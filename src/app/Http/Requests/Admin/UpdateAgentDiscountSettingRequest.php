<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentDiscountSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'below_rm20' => ['required', 'numeric', 'between:0,100', 'decimal:0,1'],
            'below_rm100' => ['required', 'numeric', 'between:0,100', 'decimal:0,1'],
            'at_least_rm100' => ['required', 'numeric', 'between:0,100', 'decimal:0,1'],
            'version' => ['required', 'integer', 'min:0'],
        ];
    }
}
