<?php

namespace App\Http\Requests\Agent;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'login_id' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'accepted'],
        ];
    }

    public function identifier(): string
    {
        return (string) $this->validated('login_id');
    }

    public function password(): string
    {
        return (string) $this->validated('password');
    }

    /** @return array<int, string> */
    public function phoneCandidates(): array
    {
        $digits = preg_replace('/\D+/', '', $this->identifier()) ?? '';

        if (! preg_match('/^(?:(?:60|0)?1\d{8,9})$/', $digits)) {
            return [];
        }

        if (str_starts_with($digits, '60')) {
            $international = $digits;
            $local = '0'.substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $local = $digits;
            $international = '60'.substr($digits, 1);
        } else {
            $local = '0'.$digits;
            $international = '60'.$digits;
        }

        return array_values(array_unique([$local, $international]));
    }

    public function remember(): bool
    {
        return $this->boolean('remember');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['login_id' => Str::lower(trim((string) $this->input('login_id')))]);
    }
}
