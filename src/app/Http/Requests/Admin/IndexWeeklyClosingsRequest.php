<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexWeeklyClosingsRequest extends FormRequest
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
            'search' => ['nullable', 'string'],
            'report_period' => ['nullable', 'string', Rule::in(['week', 'month', 'custom'])],
            'start_date' => ['nullable', 'required_if:report_period,custom', 'required_with:end_date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'required_if:report_period,custom', 'required_with:start_date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }
}
