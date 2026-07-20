<?php

namespace App\Http\Requests\Agent;

use App\Models\Agent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignInPosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('agent') instanceof Agent;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $agent = $this->user('agent');

        return [
            'business_site_id' => [
                'required',
                'integer',
                Rule::exists('agent_business_site', 'business_site_id')
                    ->where(fn ($query) => $query->where('agent_id', $agent?->getKey())),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'business_site_id.exists' => 'Please choose a business site assigned to your account.',
        ];
    }
}
