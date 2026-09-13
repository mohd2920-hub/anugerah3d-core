<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:process,ready,ship,pickup_ready,complete,cancel,payment,instructions,refund,request_proof'],
            'payment_status' => ['nullable', 'required_if:action,payment', 'in:unpaid,paid,refunded'],
            'payment_instructions' => ['nullable', 'required_if:action,instructions', 'string', 'max:2000'],
            'refunded_product_amount' => ['nullable', 'required_if:action,refund', 'numeric', 'min:0'],
            'courier' => ['nullable', 'required_if:action,ship', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'required_if:action,ship', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
