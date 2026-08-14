<?php

namespace App\Http\Requests\Admin;

use App\Models\AgentEmailTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAgentEmailTemplateRequest extends FormRequest
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
        return [
            "name" => ["required", "string", "max:150"],
            "recipient_scope" => ["required", "string", Rule::in(array_keys(AgentEmailTemplate::recipientScopes()))],
            "agent_ids" => ["nullable", "array", "required_if:recipient_scope,".AgentEmailTemplate::RecipientSelectedAgents],
            "agent_ids.*" => ["integer", "distinct", "exists:usr_agent,id"],
            "subject" => ["required", "string", "max:200"],
            "body" => ["required", "string"],
        ];
    }

    protected function prepareForValidation(): void
    {
        $agentIds = $this->input("agent_ids", []);

        $this->merge([
            "agent_ids" => is_array($agentIds)
                ? array_values(array_filter(array_map(static fn ($id): int => (int) $id, $agentIds)))
                : [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function templateData(): array
    {
        $validated = $this->validated();
        $selectedAgentIds = $validated["recipient_scope"] === AgentEmailTemplate::RecipientSelectedAgents
            ? array_values($validated["agent_ids"] ?? [])
            : [];

        return [
            "name" => $validated["name"],
            "recipient_scope" => $validated["recipient_scope"],
            "selected_agent_ids" => $selectedAgentIds,
            "subject" => $validated["subject"],
            "body" => $validated["body"],
        ];
    }
}
