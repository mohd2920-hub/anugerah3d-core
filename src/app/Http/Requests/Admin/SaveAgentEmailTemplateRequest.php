<?php

namespace App\Http\Requests\Admin;

use App\Models\AgentEmailTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

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
            'name' => ['required', 'string', 'max:150'],
            'recipient_scope' => ['required', 'string', Rule::in(array_keys(AgentEmailTemplate::recipientScopes()))],
            'agent_ids' => ['nullable', 'array', 'required_if:recipient_scope,'.AgentEmailTemplate::RecipientSelectedAgents],
            'agent_ids.*' => ['integer', 'distinct', 'exists:usr_agent,id'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'image_position' => ['required', 'string', Rule::in(array_keys(AgentEmailTemplate::imagePositions()))],
            'template_images' => ['nullable', 'array', 'max:'.AgentEmailTemplate::MAX_IMAGES],
            'template_images.*' => [File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max('5mb')],
            'removed_image_paths' => ['nullable', 'array'],
            'removed_image_paths.*' => ['string', 'distinct', Rule::in($this->existingImagePaths())],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('template_images') || $validator->errors()->has('removed_image_paths')) {
                    return;
                }

                $remainingImageCount = collect($this->existingImagePaths())
                    ->diff(is_array($this->input('removed_image_paths', [])) ? $this->input('removed_image_paths', []) : [])
                    ->count();

                if ($remainingImageCount + count($this->imageUploads()) > AgentEmailTemplate::MAX_IMAGES) {
                    $validator->errors()->add('template_images', 'An email template can have a maximum of 4 images.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $agentIds = $this->input('agent_ids', []);

        $this->merge([
            'agent_ids' => is_array($agentIds)
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
        $selectedAgentIds = $validated['recipient_scope'] === AgentEmailTemplate::RecipientSelectedAgents
            ? array_values($validated['agent_ids'] ?? [])
            : [];

        return [
            'name' => $validated['name'],
            'recipient_scope' => $validated['recipient_scope'],
            'selected_agent_ids' => $selectedAgentIds,
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'image_position' => $validated['image_position'],
        ];
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function imageUploads(): array
    {
        $uploads = $this->file('template_images', []);

        return is_array($uploads) ? array_values($uploads) : [];
    }

    /**
     * @return array<int, string>
     */
    public function removedImagePaths(): array
    {
        $paths = $this->validated('removed_image_paths', []);

        return is_array($paths) ? array_values($paths) : [];
    }

    /**
     * @return array<int, string>
     */
    private function existingImagePaths(): array
    {
        $template = $this->route('agentEmailTemplate');

        return $template instanceof AgentEmailTemplate ? $template->imagePaths() : [];
    }
}
