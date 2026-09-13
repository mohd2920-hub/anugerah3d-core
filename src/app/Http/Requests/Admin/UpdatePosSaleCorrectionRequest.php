<?php

namespace App\Http\Requests\Admin;

use App\Models\AdminUser;
use App\Models\PosSale;
use App\Support\PosClicker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePosSaleCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') instanceof AdminUser;
    }

    public function rules(): array
    {
        if ($this->routeIs('admin.sale-corrections.store')) {
            return ['token' => ['required', 'uuid']];
        }

        $rules = [
            'action' => ['required', Rule::in(['correct', 'missing', 'void'])],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'sale_number' => ['prohibited'],
            'total_amount' => ['prohibited'],
        ];
        if ($this->input('action') === 'void') {
            return $rules;
        }

        return $rules + [
            'sales_agent_id' => ['required', 'integer', 'exists:usr_agent,id'],
            'sold_at' => ['required', 'date', 'before_or_equal:now'],
            ...PosClicker::rules(),
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*' => ['array:product_id,quantity,discount_amount,clicker_character_count,clicker_characters,clicker_casing_image_id,clicker_huruf_image_id,sale_item_id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.sale_item_id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email:rfc', 'max:150'],
            'remark' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', Rule::in(array_keys(PosSale::paymentMethods()))],
            'payment_remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            PosClicker::validateStandardDuplicates(is_array($this->input('items')) ? $this->input('items') : [], $validator);
        }];
    }
}
