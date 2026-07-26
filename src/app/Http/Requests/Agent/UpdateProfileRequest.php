<?php

namespace App\Http\Requests\Agent;

use App\Models\Agent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    protected $errorBag = 'profileUpdate';

    public function authorize(): bool
    {
        return $this->user('agent') instanceof Agent;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        /** @var Agent|null $agent */
        $agent = $this->user('agent');

        return [
            'agt_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:100', Rule::unique((new Agent)->getTable(), 'email')->ignore($agent?->getKey())],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'id_number' => ['nullable', 'string', 'max:50', Rule::unique((new Agent)->getTable(), 'id_number')->ignore($agent?->getKey())],
            'address' => ['nullable', 'string', 'max:250'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100', Rule::exists('data_state', 'name')],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'bank_account_number' => ['nullable', 'string', 'max:80'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullableString = static fn (mixed $value): ?string => trim((string) $value) !== '' ? trim((string) $value) : null;

        $this->merge([
            'agt_name' => trim((string) $this->input('agt_name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'phone_number' => $nullableString($this->input('phone_number')),
            'id_number' => $nullableString($this->input('id_number')),
            'address' => $nullableString($this->input('address')),
            'city' => $nullableString($this->input('city')),
            'state' => $nullableString($this->input('state')),
            'bank_name' => $nullableString($this->input('bank_name')),
            'bank_account_name' => $nullableString($this->input('bank_account_name')),
            'bank_account_number' => $nullableString($this->input('bank_account_number')),
        ]);
    }
}
