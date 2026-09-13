<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BusinessSiteReportRequest extends FormRequest
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
            'period' => ['sometimes', 'required', 'in:today,date,week,month,range,all'],
            'date' => ['nullable', 'required_if:period,date', 'date_format:Y-m-d'],
            'from' => ['nullable', 'required_if:period,range', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_if:period,range', 'date_format:Y-m-d', 'after_or_equal:from'],
            'mode' => ['sometimes', 'required', 'in:separate,compare,combined'],
            'site_ids' => ['required_if:mode,combined,compare', 'array', 'min:1'],
            'site_ids.*' => ['required', 'integer', 'distinct', 'exists:business_sites,id'],
        ];
    }
}
