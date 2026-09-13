<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreHistoricalSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_token' => ['required', 'uuid'],
            'recipient_type' => ['required', 'in:admin,agent'],
            'recipient_key' => ['required', 'regex:/^'.($this->input('recipient_type') === 'agent' ? 'agent' : 'admin').':[1-9][0-9]*$/'],
            'work_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:2026-01-01', 'before_or_equal:today'],
            'paid_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:work_date', 'before_or_equal:today'],
            'site_name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999.99'],
            'reference' => ['nullable', 'string', 'max:150'],
            'reason' => ['required', 'string', 'max:2000'],
            'confirmed_paid' => ['accepted'],
            'separate_payment' => ['sometimes', 'accepted'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }
}
