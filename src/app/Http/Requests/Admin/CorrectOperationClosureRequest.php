<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CorrectOperationClosureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'closed_at' => ['required', 'date', 'before_or_equal:now'],
            'reason' => ['required', 'string', 'max:2000'],
            'next_opened_at' => ['nullable', 'date', 'after:closed_at', 'before_or_equal:now'],
            'next_report_date' => ['required_with:next_opened_at', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }
}
