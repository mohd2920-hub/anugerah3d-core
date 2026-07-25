<?php

namespace App\Http\Requests\Admin;

use App\Models\Agent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgentRequest extends FormRequest
{
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
            'login_id' => ['required', 'string', 'max:100', Rule::unique((new Agent)->getTable(), 'login_id')],
            'agt_name' => ['required', 'string', 'max:150'],
            'id_number' => ['nullable', 'string', 'max:50', Rule::unique((new Agent)->getTable(), 'id_number')],
            'email' => ['required', 'email', 'max:100', Rule::unique((new Agent)->getTable(), 'email')],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'agt_status' => ['required', Rule::in(array_keys(Agent::statuses()))],
            'referrer_id' => ['nullable', 'integer', Rule::exists((new Agent)->getTable(), 'id')->where('agt_status', Agent::StatusActive)],
            'address' => ['nullable', 'string', 'max:250'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100', Rule::exists('data_state', 'name')],
            'discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'commission_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'profile_picture' => ['sometimes', 'nullable', 'string', 'max:250'],
            'total_sale' => ['sometimes', 'numeric', 'min:0', 'max:999999.99'],
            'business_site_ids' => ['nullable', 'array'],
            'business_site_ids.*' => ['integer', 'distinct', 'exists:business_sites,id'],
        ];
    }
}
