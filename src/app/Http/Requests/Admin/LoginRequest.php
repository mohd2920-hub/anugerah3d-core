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
            'email' => ['required', 'string', 'max:255'],
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
            'email' => $this->loginEmail(),
            'password' => (string) $this->validated('password'),
            'status' => AdminUser::StatusActive,
        ];
    }

    private function loginEmail(): string
    {
        $identifier = (string) $this->validated('email');
        if (str_contains($identifier, '@')) {
            return $identifier;
        }

        $emails = AdminUser::query()
            ->whereRaw('LOWER(SUBSTRING_INDEX(email, ?, 1)) = ?', ['@', $identifier])
            ->limit(2)
            ->pluck('email');

        return $emails->count() === 1 ? (string) $emails->first() : '';
    }

    public function remember(): bool
    {
        return $this->boolean('remember');
    }

    protected function prepareForValidation(): void
    {
        $identifier = $this->input('email');
        if (is_string($identifier)) {
            $this->merge(['email' => Str::lower(trim($identifier))]);
        }
    }
}
