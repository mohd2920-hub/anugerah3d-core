<?php

namespace App\Actions\Pos;

use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSaleCorrection;
use App\Models\Product;
use App\Support\CasingStock;
use App\Support\PosClicker;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CorrectPosSale
{
    public function snapshot(PosSale $sale): array
    {
        $sale->load(['items.product', 'salesAgent']);
        $items = $sale->items->map(fn ($item): array => [
            'sale_item_id' => $item->id,
            'clicker_configuration' => $item->clicker_configuration ?? null,
            'stock_casing_image_id' => $item->stock_casing_image_id ?? null,
            'uses_product_stock' => (bool) ($item->uses_product_stock ?? false),
            'product_id' => $item->product_id,
            'product_code' => $item->product_code,
            'product_name' => $item->product_name,
            'quantity' => $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'unit_cost' => (float) ($item->unit_cost ?? $item->product?->cost_rm ?? 0),
            'agent_discount_percentage' => (float) $item->agent_discount_percentage,
            'agent_discount_amount' => (float) $item->agent_discount_amount,
            'customer_discount_amount' => (float) $item->customer_discount_amount,
            'line_total' => (float) $item->line_total,
        ])->all();

        return $this->summarize([
            'sale_number' => $sale->sale_number,
            'sales_agent_id' => $sale->sales_agent_id,
            'sales_person' => $sale->salesAgent?->agt_name,
            'sold_at' => $sale->sold_at->format('Y-m-d H:i:s'),
            'customer_name' => $sale->customer_name,
            'customer_phone' => $sale->customer_phone,
            'customer_email' => $sale->customer_email,
            'remark' => $sale->remark,
            'payment_method' => $sale->payment_method,
            'payment_remark' => $sale->payment_remark,
            'status' => $sale->voided_at ? 'Void' : 'Active',
            'version' => $sale->correction_version ?? 0,
            'items' => $items,
        ], (float) $sale->total_amount);
    }

    public function preview(BusinessSiteOperation $operation, ?PosSale $sale, array $data): array
    {
        if ($sale?->voided_at) {
            throw ValidationException::withMessages(['sale' => 'A void sale cannot be changed.']);
        }
        $before = $sale ? $this->snapshot($sale) : null;
        if ($data['action'] === 'void') {
            if (! $sale) {
                throw ValidationException::withMessages(['sale' => 'Select an existing sale to void.']);
            }
            $after = $before;
            $after['status'] = 'Void';
            $after['version']++;

            return ['before' => $before, 'after' => $after, 'stock_changes' => app(PosClicker::class)->inventory($before['items'], [])];
        }
        $soldAt = Carbon::parse($data['sold_at']);
        if ($soldAt->lt($operation->opened_at) || $soldAt->gt($operation->closed_at ?? now())) {
            throw ValidationException::withMessages(['sold_at' => 'Sale time must fall within this business session.']);
        }
        $agent = Agent::query()->findOrFail($data['sales_agent_id']);
        if ($sale?->sales_agent_id !== $agent->id && ! $agent->businessSites()->whereKey($operation->business_site_id)->exists()) {
            throw ValidationException::withMessages(['sales_agent_id' => 'The sales person must be assigned to this business site.']);
        }
        $products = Product::query()->whereKey(array_column($data['items'], 'product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $oldItems = collect($before['items'] ?? []);
        $clickerService = app(PosClicker::class);
        $items = [];
        foreach ($data['items'] as $index => $input) {
            $product = $products->get($input['product_id']);
            if (! $product) {
                throw ValidationException::withMessages(['items' => 'A selected product is no longer available.']);
            }
            $old = ! empty($input['sale_item_id'])
                ? $oldItems->firstWhere('sale_item_id', (int) $input['sale_item_id'])
                : $oldItems->first(fn (array $row): bool => $row['product_id'] === $product->id && $clickerService->matches($row['clicker_configuration'], $input));
            if (! empty($input['sale_item_id']) && ! $old) {
                throw ValidationException::withMessages(['items' => 'The selected sale item does not belong to this sale.']);
            }
            if ($old && ($old['product_id'] !== $product->id || ! $clickerService->matches($old['clicker_configuration'], $input))) {
                $old = null;
            }
            $clicker = $old ? ($old['clicker_configuration'] ? collect($old)->only(['clicker_configuration', 'stock_casing_image_id', 'unit_price', 'unit_cost'])->all() : null)
                : $clickerService->resolve($product, $input, "items.{$index}");
            $price = (int) round((float) ($old['unit_price'] ?? $clicker['unit_price'] ?? $product->price_selling) * 100);
            $quantity = (int) $input['quantity'];
            if ($old && ! $old['uses_product_stock'] && ! ($old['stock_casing_image_id'] ?? null) && $quantity > $old['quantity']) {
                throw ValidationException::withMessages(['items' => 'Rekod jualan lama ini tidak menolak stok pusat. Rekodkan kuantiti tambahan sebagai jualan baharu untuk menolak stok pusat.']);
            }
            $gross = $price * $quantity;
            $discount = (int) round((float) ($input['discount_amount'] ?? 0) * 100);
            if ($discount > $gross) {
                throw ValidationException::withMessages(["items.$index.discount_amount" => 'Customer discount cannot exceed the product subtotal.']);
            }
            $percentage = (float) ($agent->discount_percentage > 0 ? $agent->discount_percentage : $product->agent_discount_default);
            $items[] = [
                'product_id' => $product->id,
                'uses_product_stock' => $old ? (bool) $old['uses_product_stock'] : ! CasingStock::enabled($product),
                'product_code' => $old['product_code'] ?? $product->prd_code,
                'product_name' => $old['product_name'] ?? $product->prd_name,
                'quantity' => $quantity,
                'unit_price' => $price / 100,
                'unit_cost' => (float) ($old['unit_cost'] ?? $clicker['unit_cost'] ?? $product->cost_rm ?? 0),
                ...($clicker ? collect($clicker)->only(['clicker_configuration', 'stock_casing_image_id'])->all() : []),
                'agent_discount_percentage' => $percentage,
                'agent_discount_amount' => round($gross * $percentage / 100) / 100,
                'customer_discount_amount' => $discount / 100,
                'line_total' => ($gross - $discount) / 100,
            ];
        }
        $after = [
            'sale_number' => $sale?->sale_number ?? 'Assigned on save',
            'sales_agent_id' => $agent->id,
            'sales_person' => $agent->agt_name,
            'sold_at' => $soldAt->format('Y-m-d H:i:s'),
            'status' => 'Active',
            'version' => ($sale?->correction_version ?? 0) + 1,
            'items' => $items,
        ];
        foreach (['customer_name', 'customer_phone', 'customer_email', 'remark', 'payment_method', 'payment_remark'] as $field) {
            $after[$field] = $data[$field] ?? null;
        }

        return ['before' => $before, 'after' => $this->summarize($after), 'stock_changes' => $clickerService->inventory($before['items'] ?? [], $items)];
    }

    public function handle(AdminUser $admin, string $token, array $preview): PosSale
    {
        return DB::transaction(function () use ($admin, $token, $preview): PosSale {
            $operation = BusinessSiteOperation::query()->lockForUpdate()->findOrFail($preview['operation_id']);
            $existing = PosSaleCorrection::query()->where('request_token', $token)->first();
            if ($existing) {
                return PosSale::query()->findOrFail($existing->pos_sale_id);
            }
            $sale = $preview['sale_id'] ? PosSale::query()->lockForUpdate()->findOrFail($preview['sale_id']) : null;
            $current = $this->preview($operation, $sale, $preview['data']);
            if ($current != $preview['comparison']) {
                throw ValidationException::withMessages(['sale' => 'The sale, product pricing or session changed. Preview the correction again before saving.']);
            }
            $after = $current['after'];
            app(PosClicker::class)->inventory($current['before']['items'] ?? [], $preview['data']['action'] === 'void' ? [] : $after['items'], true);
            if ($preview['data']['action'] === 'void') {
                $sale->update(['voided_at' => now(), 'correction_version' => $after['version']]);
            } else {
                $attributes = collect($after)->only(['sales_agent_id', 'sold_at', 'customer_name', 'customer_phone', 'customer_email', 'remark', 'payment_method', 'payment_remark'])->all();
                $attributes['total_amount'] = $after['net_sales'];
                $attributes['correction_version'] = $after['version'];
                if ($sale) {
                    $sale->update($attributes);
                } else {
                    $sale = PosSale::query()->create($attributes + [
                        'sale_number' => 'POS-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                        'business_site_id' => $operation->business_site_id,
                        'business_site_operation_id' => $operation->id,
                    ]);
                }
                $sale->items()->delete();
                $sale->items()->createMany($after['items']);
            }
            $sale->corrections()->create([
                'admin_user_id' => $admin->id,
                'request_token' => $token,
                'action' => $preview['data']['action'],
                'reason' => $preview['data']['reason'],
                'before' => $current['before'],
                'after' => $this->snapshot($sale->fresh()),
            ]);

            return $sale;
        });
    }

    private function summarize(array $snapshot, ?float $netSales = null): array
    {
        $items = collect($snapshot['items']);
        $net = $netSales ?? round($items->sum('line_total'), 2);
        $capital = round($items->sum(fn (array $item): float => $item['unit_cost'] * $item['quantity']), 2);

        return $snapshot + ['net_sales' => $net, 'net_company' => $net, 'capital' => $capital, 'gross_profit' => round($net - $capital, 2)];
    }
}
