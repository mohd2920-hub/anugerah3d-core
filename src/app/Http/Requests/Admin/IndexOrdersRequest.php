<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOrdersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in([Order::StatusPending, Order::StatusProcessing, Order::StatusCompleted, Order::StatusCancelled])],
            'payment_status' => ['nullable', 'string', Rule::in([Order::PaymentStatusUnpaid, Order::PaymentStatusPaid, Order::PaymentStatusRefunded])],
            'fulfilment_method' => ['nullable', 'string', Rule::in(['delivery', 'pickup'])],
            'start_date' => ['nullable', 'required_with:end_date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'required_with:start_date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }
}
