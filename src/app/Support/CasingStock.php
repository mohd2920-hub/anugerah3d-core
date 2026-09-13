<?php

namespace App\Support;

use App\Models\CustomerOrderItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CasingStock
{
    public static function enabled(Product $product): bool
    {
        return (bool) $product->getRawOriginal('casing_stock_enabled');
    }

    public static function rules(): array
    {
        return [
            'enable_casing_stock' => ['sometimes', 'boolean'],
            'casing_stock_reason' => ['nullable', 'string', 'max:500'],
            'clicker_images.casing.*.stock' => ['sometimes', 'array:1,2,3,4,5,6,7,8', 'size:8'],
            'clicker_images.casing.*.stock.*' => ['required', 'integer', 'min:0', 'max:10000000'],
            'clicker_images.casing.*.stock_expected' => ['sometimes', 'array:1,2,3,4,5,6,7,8', 'size:8'],
            'clicker_images.casing.*.stock_expected.*' => ['required', 'integer', 'min:0'],
        ];
    }

    public function quantities(array $casingIds): array
    {
        if (! Schema::hasTable('product_clicker_stocks')) {
            return [];
        }

        return DB::table('product_clicker_stocks')->whereIn('casing_image_id', $casingIds)->get()
            ->groupBy('casing_image_id')->map(fn ($rows): array => $rows->pluck('quantity', 'character_count')->all())->all();
    }

    /** @param array<string, mixed> $data */
    public function save(Product $product, array $data, Request $request, bool $validateOnly = false): void
    {
        $enabled = self::enabled($product);
        $previousBalance = (int) $product->prd_balance;
        if (! $enabled && $data['product_type'] !== 'clicker') {
            return;
        }
        if (! $enabled && ! ($data['enable_casing_stock'] ?? false)) {
            return;
        }
        if ($data['product_type'] !== 'clicker') {
            throw ValidationException::withMessages(['product_type' => 'A product using casing stock must remain a Clicker product.']);
        }
        if (! $enabled && ($product->orderItems()->whereHas('order', fn ($query) => $query->whereIn('status', [Order::StatusPending, Order::StatusProcessing]))->exists() || (Schema::hasTable('customer_order_items') && CustomerOrderItem::where('product_id', $product->id)->whereHas('order', fn ($query) => $query->whereIn('status', [Order::StatusPending, Order::StatusProcessing, 'ready', 'pickup_ready', 'shipped']))->exists()))) {
            throw ValidationException::withMessages(['casing_stock' => 'Complete or cancel existing open orders before allocating casing stock.']);
        }
        $casings = DB::table('product_clicker_images')->where('product_id', $product->id)->where('image_type', 'casing')->orderBy('id')->lockForUpdate()->get();
        $current = $this->quantities($casings->pluck('id')->all());
        if ($validateOnly) {
            foreach ($data['clicker_images']['casing'] ?? [] as $position => $row) {
                if (empty($row['id']) && $request->hasFile("clicker_images.casing.{$position}.image")) {
                    $casings->push((object) ['id' => -((int) $position), 'position' => (int) $position]);
                }
            }
        }

        $before = [];
        $after = [];
        foreach ($casings as $casing) {
            $row = data_get($data, 'clicker_images.casing.'.$casing->position, []);
            if (count($row['stock'] ?? []) !== 8 || count($row['stock_expected'] ?? []) !== 8) {
                throw ValidationException::withMessages(['casing_stock' => 'Reload the page and enter all eight stock sizes for each casing.']);
            }
            foreach (range(1, 8) as $count) {
                $quantity = (int) ($current[$casing->id][$count] ?? 0);
                if ((int) $row['stock_expected'][$count] !== $quantity) {
                    throw ValidationException::withMessages(['casing_stock' => 'Casing stock changed while this page was open. Reload before saving.']);
                }
                $before[$casing->id][$count] = $quantity;
                $after[$casing->id][$count] = (int) $row['stock'][$count];
            }
        }
        foreach ($data['clicker_images']['casing'] ?? [] as $position => $row) {
            if (array_sum($row['stock'] ?? []) > 0 && ! $casings->contains('position', (int) $position)) {
                throw ValidationException::withMessages(['casing_stock' => 'Upload a casing image and name before assigning its stock.']);
            }
        }
        $total = array_sum(array_map('array_sum', $after));
        if (($before !== $after || ! $enabled) && blank($data['casing_stock_reason'] ?? null)) {
            throw ValidationException::withMessages(['casing_stock_reason' => 'Enter a reason for the stock allocation or adjustment.']);
        }
        if ($validateOnly) {
            return;
        }
        foreach ($after as $id => $sizes) {
            foreach ($sizes as $count => $quantity) {
                DB::table('product_clicker_stocks')->updateOrInsert(['casing_image_id' => $id, 'character_count' => $count], ['quantity' => $quantity]);
            }
        }
        $product->forceFill(['casing_stock_enabled' => true, 'prd_balance' => $total])->save();
        if ($before !== $after || ! $enabled) {
            AdminActivity::record(request: $request, event: 'admin.product.casing-stock.updated', description: "Casing stock updated for {$product->prd_code}.", adminUser: $request->user('admin'),
                properties: ['page' => 'Products', 'product_id' => $product->id, 'reason' => $data['casing_stock_reason'], 'before' => $before, 'after' => $after, 'activated' => ! $enabled, 'balance_before' => $previousBalance, 'balance_after' => $total]);
        }
    }

    public function orderShortages(Collection $items): Collection
    {
        $quantities = $this->quantities($items->map(fn ($item) => $item->getRawOriginal('clicker_casing_image_id'))->filter()->all());

        return $items->groupBy(fn ($item): string => $item->product_id.':'.($item->getRawOriginal('clicker_casing_image_id') ?? 'legacy').':'.($item->getRawOriginal('clicker_casing_image_id') ? $item->clicker_character_count : 0))
            ->map(function ($rows) use ($quantities): ?array {
                $item = $rows->first();
                $id = $item->getRawOriginal('clicker_casing_image_id');
                $required = $rows->sum(fn ($row): int => $row->missingReservationQuantity());
                $available = $id ? (int) ($quantities[$id][$item->clicker_character_count] ?? 0) : max(0, (int) $item->product?->prd_balance);

                return $required > $available ? ['product_name' => $item->product_name.($id ? ' ('.$item->clicker_character_count.' huruf)' : ''), 'required' => $required, 'available' => $available] : null;
            })->filter()->values();
    }

    public function move(Product $product, int $casingId, int $count, int $quantity): void
    {
        $valid = DB::table('product_clicker_images')->where('product_id', $product->id)->where('image_type', 'casing')->where('id', $casingId)->exists();
        $stock = DB::table('product_clicker_stocks')->where('casing_image_id', $casingId)->where('character_count', $count)->lockForUpdate()->first();
        if (! $valid || ! $stock || (int) $stock->quantity + $quantity < 0) {
            throw ValidationException::withMessages(['stock' => "Insufficient {$count}-character casing stock for {$product->prd_name}."]);
        }
        DB::table('product_clicker_stocks')->where('id', $stock->id)->update(['quantity' => (int) $stock->quantity + $quantity]);
        $product->increment('prd_balance', $quantity);
    }
}
