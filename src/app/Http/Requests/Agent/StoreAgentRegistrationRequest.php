<?php

namespace App\Http\Requests\Agent;

use App\Models\Agent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreAgentRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $agentTable = (new Agent)->getTable();

        return [
            'agt_name' => ['required', 'string', 'max:150'],
            'profile_picture_file' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'email' => [
                'required',
                'email:rfc',
                'max:100',
                Rule::unique($agentTable, 'email'),
            ],
            'login_id' => [
                'required',
                'string',
                'max:100',
                Rule::unique($agentTable, 'login_id'),
            ],
            'phone_number' => ['required', 'string', 'max:50', 'regex:/^[0-9+() .-]{8,50}$/', Rule::unique($agentTable, 'phone_number')],
            'address' => ['required', 'string', 'max:250'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100', Rule::exists('data_state', 'name')],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_picture_file.required' => 'Please add or take a profile picture.',
            'profile_picture_file.max' => 'The profile picture must not be larger than 5 MB.',
            'email.unique' => 'This email is already registered.',
            'login_id.required' => 'Please enter a login ID.',
            'login_id.unique' => 'This login ID is already taken.',
            'phone_number.regex' => 'Enter a valid WhatsApp number.',
            'phone_number.unique' => 'This WhatsApp number has already been registered.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => trim((string) $this->input('email')),
            'login_id' => Str::lower(trim((string) $this->input('login_id'))),
        ]);
    }
}
