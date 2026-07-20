<?php

namespace App\Actions\Pos;

use App\Models\Agent;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class CreatePosSale
{
    public function handle(PosSession $session, array $data): PosSale
    {
        $paths = $this->storePictures($data);

        try {
            return DB::transaction(function () use ($session, $data, $paths): PosSale {
                $items = $this->items($data['items'], $data['sales_agent_id']);
                $sale = PosSale::query()->create([
                    'sale_number' => 'POS-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                    'pos_session_id' => $session->getKey(),
                    'business_site_id' => $session->business_site_id,
                    'recorded_by_agent_id' => $session->agent_id,
                    'sales_agent_id' => $data['sales_agent_id'],
                    'customer_name' => $data['customer_name'] ?? null,
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'remark' => $data['remark'] ?? null,
                    'payment_method' => $data['payment_method'],
                    'payment_remark' => $data['payment_remark'] ?? null,
                    'sale_picture_path' => $paths['sale_picture_path'],
                    'payment_proof_path' => $paths['payment_proof_path'],
                    'total_amount' => collect($items)->sum('line_total'),
                    'sold_at' => now(),
                ]);

                $sale->items()->createMany($items);

                return $sale->load(['items', 'businessSite', 'salesAgent']);
            });
        } catch (Throwable $exception) {
            File::delete(array_filter([
                $paths['sale_picture_path'] ? public_path($paths['sale_picture_path']) : null,
                $paths['payment_proof_path'] ? public_path($paths['payment_proof_path']) : null,
            ]));

            throw $exception;
        }
    }

    /** @param array<int, array{product_id: int, quantity: int}> $items */
    private function productsFor(array $items): Collection
    {
        return Product::query()
            ->whereKey(collect($items)->pluck('product_id'))
            ->get(['id', 'prd_code', 'prd_name', 'price_selling', 'agent_discount_default'])
            ->keyBy('id');
    }

    /** @param array<int, array{product_id: int, quantity: int, discount_percentage?: float|int|string|null}> $items */
    public function items(array $items, int $salesAgentId): array
    {
        $agentDiscount = (float) Agent::query()
            ->whereKey($salesAgentId)
            ->valueOrFail('discount_percentage');

        return $this->itemRows($items, $this->productsFor($items), $agentDiscount);
    }

    /** @param array<int, array{product_id: int, quantity: int, discount_percentage?: float|int|string|null}> $items */
    private function itemRows(array $items, Collection $products, float $agentDiscount): array
    {
        return collect($items)->map(function (array $item) use ($products, $agentDiscount): array {
            $product = $products->get($item['product_id']);
            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $product->price_selling;
            $unitPriceCents = (int) round($unitPrice * 100);
            $grossTotalCents = $unitPriceCents * $quantity;
            $baselineDiscount = $agentDiscount > 0
                ? $agentDiscount
                : (float) $product->agent_discount_default;
            $discountPercentage = isset($item['discount_percentage'])
                ? min(100, max(0, (float) $item['discount_percentage']))
                : $baselineDiscount;
            $discountAmountCents = (int) round($grossTotalCents * ($discountPercentage / 100));

            return [
                'product_id' => $product->getKey(),
                'product_code' => $product->prd_code,
                'product_name' => $product->prd_name,
                'quantity' => $quantity,
                'unit_price' => $unitPriceCents / 100,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmountCents / 100,
                'line_total' => ($grossTotalCents - $discountAmountCents) / 100,
            ];
        })->all();
    }

    /** @return array{sale_picture_path: ?string, payment_proof_path: ?string} */
    private function storePictures(array $data): array
    {
        return [
            'sale_picture_path' => $this->storePicture($data['sale_picture'] ?? null, 'sale'),
            'payment_proof_path' => $this->storePicture($data['payment_proof'] ?? null, 'payment'),
        ];
    }

    public function storePicture(?UploadedFile $file, string $type): ?string
    {
        if ($file === null) {
            return null;
        }

        $directory = public_path('pos-sales');
        File::ensureDirectoryExists($directory);
        $filename = $type.'-'.Str::uuid().'.'.$file->guessExtension();
        $file->move($directory, $filename);

        return 'pos-sales/'.$filename;
    }
}
