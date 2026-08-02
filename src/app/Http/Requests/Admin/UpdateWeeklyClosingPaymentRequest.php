<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateWeeklyClosingPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'payout_status' => ['required', 'in:paid'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'payment_receipt_datetime_text' => ['nullable', 'string', 'max:200'],
            'payment_attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf', 'max:5120'],
            'notify_agent' => ['required', 'boolean'],
            'payment_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $trimmed = static fn (mixed $value): ?string => trim((string) $value) !== '' ? trim((string) $value) : null;

        $this->merge([
            'payment_reference' => $trimmed($this->input('payment_reference')),
            'payment_receipt_datetime_text' => $trimmed($this->input('payment_receipt_datetime_text')),
            'payment_notes' => $trimmed($this->input('payment_notes')),
            'notify_agent' => $this->boolean('notify_agent'),
        ]);
    }

    /**
     * @return array<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $agentSummary = $this->route('agentSummary');
                $recipientEmail = $agentSummary?->agent?->email ?? $agentSummary?->agent_email;

                if ($this->boolean('notify_agent') && (! is_string($recipientEmail) || filter_var($recipientEmail, FILTER_VALIDATE_EMAIL) === false)) {
                    $validator->errors()->add('payout_status', 'The agent must have a valid email address before the notification can be sent.');
                }

                if ($this->input('payout_status') === 'paid' && $this->input('payment_receipt_datetime_text') === null) {
                    $validator->errors()->add('payment_receipt_datetime_text', 'Please enter the payment receipt date/time text when completing payment.');
                }
            },
        ];
    }
}
