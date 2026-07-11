<?php

namespace App\Http\Requests\Admin;

use App\Models\Agent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgentRequest extends FormRequest
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
        $agent = $this->route('agent');
        $agentId = $agent instanceof Agent ? $agent->getKey() : null;

        return [
            'login_id' => [
                'required',
                'string',
                'max:100',
                Rule::unique((new Agent)->getTable(), 'login_id')->ignore($agentId),
            ],
            'agt_name' => ['required', 'string', 'max:150'],
            'id_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique((new Agent)->getTable(), 'id_number')->ignore($agentId),
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique((new Agent)->getTable(), 'email')->ignore($agentId),
            ],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'agt_status' => ['required', Rule::in(array_keys(Agent::statuses()))],
            'address' => ['nullable', 'string', 'max:250'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100', Rule::exists('data_state', 'name')],
            'discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'profile_picture_file' => ['nullable', 'image', 'max:5120'],
            'total_sale' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}
