<?php

namespace App\Http\Requests\Agent;

use App\Models\Agent;
use App\Models\BusinessSite;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $agent = $this->user('agent');

                if (! $agent instanceof Agent || $validator->errors()->has('business_site_id')) {
                    return;
                }

                $isClosed = BusinessSite::query()
                    ->whereKey($this->integer('business_site_id'))
                    ->whereHas('agents', fn ($query) => $query->whereKey($agent->getKey()))
                    ->whereNull('opened_at')
                    ->exists();

                if ($isClosed) {
                    $validator->errors()->add('business_site_id', 'Please ask an admin to open the business site.');
                }
            },
        ];
    }
}
