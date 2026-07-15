<?php

namespace App\Http\Requests\Agent;

use App\Models\Agent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    protected $errorBag = 'passwordUpdate';

    public function authorize(): bool
    {
        return $this->user('agent') instanceof Agent;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:agent'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
