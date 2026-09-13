<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class IndexAgentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ranking_period' => ['nullable', 'in:all,month,custom'],
            'ranking_month' => ['nullable', 'required_if:ranking_period,month', 'date_format:Y-m'],
            'ranking_start' => ['nullable', 'required_if:ranking_period,custom', 'date_format:Y-m-d'],
            'ranking_end' => ['nullable', 'required_if:ranking_period,custom', 'date_format:Y-m-d', 'after_or_equal:ranking_start'],
        ];
    }
}
