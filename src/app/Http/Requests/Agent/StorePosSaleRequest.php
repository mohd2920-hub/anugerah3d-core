<?php

namespace App\Http\Requests\Agent;

use App\Models\Agent;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Support\PosClicker;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->activePosSession() !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'sales_agent_id' => ['required', 'integer', 'exists:usr_agent,id'],
            ...PosClicker::rules(),
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*' => ['array:product_id,quantity,discount_amount,clicker_character_count,clicker_characters,clicker_casing_image_id,clicker_huruf_image_id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email:rfc', 'max:150'],
            'remark' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', Rule::in(array_keys(PosSale::paymentMethods()))],
            'payment_remark' => ['nullable', 'string', 'max:500'],
            'sale_pictures' => ['nullable', 'array', 'max:5'],
            'sale_pictures.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'payment_proofs' => ['nullable', 'required_if:payment_method,qr', 'array', 'max:5'],
            'payment_proofs.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                PosClicker::validateStandardDuplicates(is_array($this->input('items')) ? $this->input('items') : [], $validator);
                $session = $this->activePosSession();

                if ($session === null) {
                    return;
                }

                $salesAgentExists = Agent::query()
                    ->whereKey($this->integer('sales_agent_id'))
                    ->whereHas('businessSites', fn ($query) => $query->whereKey($session->business_site_id))
                    ->exists();

                if (! $salesAgentExists) {
                    $validator->errors()->add('sales_agent_id', 'The sales person must be assigned to this business site.');
                }
            },
        ];
    }

    public function activePosSession(): ?PosSession
    {
        $agent = $this->user('agent');

        if (! $agent instanceof Agent) {
            return null;
        }

        return PosSession::query()
            ->active()
            ->whereBelongsTo($agent)
            ->whereHas('businessSite', fn ($query) => $query
                ->open()
                ->whereHas('operations', fn ($query) => $query->whereNull('closed_at')))
            ->latest('signed_in_at')
            ->first();
    }
}
