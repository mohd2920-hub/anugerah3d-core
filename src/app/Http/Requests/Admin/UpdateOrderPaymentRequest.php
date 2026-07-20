<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'payment_status' => [
                'required',
                Rule::in([
                    Order::PaymentStatusUnpaid,
                    Order::PaymentStatusPaid,
                    Order::PaymentStatusRefunded,
                ]),
            ],
        ];
    }
}
