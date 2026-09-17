<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CalculateStaffSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $amounts = $this->input('amounts');
        $staffIds = $this->input('staff_ids');
        if (is_array($amounts) && is_array($staffIds)) {
            $selectedIds = array_filter($staffIds, fn ($id) => is_int($id) || is_string($id));
            $this->merge(['amounts' => array_intersect_key($amounts, array_flip($selectedIds))]);
        }
    }

    public function rules(): array
    {
        return [
            'operation_id' => ['required', 'integer', 'exists:business_site_operations,id'],
            'staff_ids' => ['required', 'array', 'min:1', 'max:100'],
            'staff_ids.*' => ['required', 'integer', 'distinct', 'exists:usr_admin,id'],
            'amounts' => ['required', 'array', 'max:100'],
            'amounts.*' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
            'reason' => ['required', 'string', 'max:2000'],
            'expected_version' => ['required', 'integer', 'min:0'],
            'expected_hash' => ['nullable', 'string', 'size:64'],
        ];
    }
}
