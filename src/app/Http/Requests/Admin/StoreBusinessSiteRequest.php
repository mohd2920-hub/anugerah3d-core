<?php

namespace App\Http\Requests\Admin;

use App\Models\AdminUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') instanceof AdminUser;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'agent_ids' => ['nullable', 'array'],
            'agent_ids.*' => ['integer', 'distinct', 'exists:usr_agent,id'],
        ];
    }
}
