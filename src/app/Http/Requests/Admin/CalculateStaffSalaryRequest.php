<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CalculateStaffSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operation_id' => ['required', 'integer', 'exists:business_site_operations,id'],
            'rate' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:100'],
            'staff_ids' => ['required', 'array', 'min:1', 'max:100'],
            'staff_ids.*' => ['required', 'integer', 'distinct', 'exists:usr_admin,id'],
            'weights' => ['required', 'array', 'max:1000'],
            'weights.*' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:100'],
            'reason' => ['required', 'string', 'max:2000'],
            'expected_version' => ['required', 'integer', 'min:0'],
            'expected_hash' => ['nullable', 'string', 'size:64'],
        ];
    }
}
