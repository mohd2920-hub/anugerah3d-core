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
        $rules['payment_proofs'] = ['nullable', 'array', 'max:5'];
        $rules['payment_proofs.*'] = ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'];

        return $rules;
    }

    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                $posSale = $this->route('posSale');
                $requiresProof = $this->string('payment_method')->toString() === PosSale::PaymentQr;
                $hasProof = $this->hasFile('payment_proofs')
                    || ($posSale instanceof PosSale && count($posSale->paymentProofPaths()) > 0);

                if ($requiresProof && ! $hasProof) {
                    $validator->errors()->add('payment_proofs', 'Payment proof is required for QR payments.');
                }
            },
        ];
    }
}
