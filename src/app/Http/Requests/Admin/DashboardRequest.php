<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminAccess;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AdminAccess::allows($this->user('admin'), 'dashboard.view');
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'required_with:end_date', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:today'],
            'end_date' => ['nullable', 'required_with:start_date', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:today'],
            'year' => ['sometimes', 'integer', 'between:2000,'.now()->year],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'day' => ['nullable', 'integer', 'between:1,31'],
            'channel' => ['sometimes', Rule::in(['all', 'pos', 'orders', 'customer'])],
            'site' => ['nullable', 'integer', Rule::exists('business_sites', 'id')],
            'comparison' => ['sometimes', Rule::in(['previous', 'year'])],
            'cost_quality' => ['nullable', Rule::in(['all', 'estimated', 'missing'])],
            'page' => ['sometimes', 'integer', 'between:1,100000'],
            'stock_quality' => ['nullable', Rule::in(['all', 'missing', 'negative', 'allocation'])],
            'stock_reservations' => ['sometimes', 'boolean'],
            'stock_include_discontinued' => ['sometimes', 'boolean'],
            'stock_page' => ['sometimes', 'integer', 'between:1,100000'],
            'stock_search' => ['nullable', 'string', 'max:100'],
            'stock_type' => ['nullable', Rule::in(['all', 'normal', 'clicker'])],
            'stock_status' => ['nullable', Rule::in(['all', 'low', 'out', 'healthy'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $data = $validator->validated();
            if (isset($data['start_date'])) {
                if (isset($data['month']) || isset($data['day'])) {
                    $validator->errors()->add('start_date', 'Gunakan julat tarikh atau pilihan bulan / hari, bukan kedua-duanya.');
                }

                return;
            }
            if (isset($data['day']) && (! isset($data['month']) || ! checkdate((int) $data['month'], (int) $data['day'], (int) ($data['year'] ?? now()->year)))) {
                $validator->errors()->add('day', 'Pilih tarikh yang sah.');
            }
            if (isset($data['month']) && CarbonImmutable::create((int) ($data['year'] ?? now()->year), (int) $data['month'], 1)->startOfDay()->isFuture()) {
                $validator->errors()->add('month', 'Bulan akan datang belum mempunyai prestasi.');
            }
        }];
    }
}
