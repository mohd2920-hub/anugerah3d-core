<?php

namespace App\Http\Requests\Admin;

use App\Models\AdminUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'accepted'],
        ];
    }

    /**
     * @return array{email: string, password: string, status: string}
     */
    public function credentials(): array
    {
        return [
            'email' => Str::lower((string) $this->validated('email')),
            'password' => (string) $this->validated('password'),
            'status' => AdminUser::StatusActive,
        ];
    }

    public function remember(): bool
    {
        return $this->boolean('remember');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }
}
