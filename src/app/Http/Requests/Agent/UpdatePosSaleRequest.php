<?php

namespace App\Http\Requests\Agent;

use App\Models\PosSale;
use Illuminate\Validation\Validator;

class UpdatePosSaleRequest extends StorePosSaleRequest
{
    public function authorize(): bool
    {
        $posSale = $this->route('posSale');
        $session = $this->activePosSession();

        return $posSale instanceof PosSale
            && $session !== null
            && $posSale->business_site_id === $session->business_site_id;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['payment_proof'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'];

        return $rules;
    }

    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                $posSale = $this->route('posSale');
                $requiresProof = $this->string('payment_method')->toString() === PosSale::PaymentQr;
                $hasProof = $this->hasFile('payment_proof')
                    || ($posSale instanceof PosSale && $posSale->payment_proof_path !== null);

                if ($requiresProof && ! $hasProof) {
                    $validator->errors()->add('payment_proof', 'Payment proof is required for QR payments.');
                }
            },
        ];
    }
}
