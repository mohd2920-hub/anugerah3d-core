<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProductBalanceRequest;
use App\Models\Product;
use App\Support\AdminActivity;
use App\Support\CasingStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductBalanceController extends Controller
{
    public function show(Product $product): JsonResponse
    {
        return DB::transaction(function () use ($product): JsonResponse {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $variations = [];
            if (CasingStock::enabled($product)) {
                $casings = DB::table('product_clicker_images')->where('product_id', $product->id)->where('image_type', 'casing')->orderBy('id')->get();
                $quantities = app(CasingStock::class)->quantities($casings->pluck('id')->all());
                foreach ($casings as $casing) {
                    foreach (range(1, 8) as $count) {
                        $variations[] = ['casing_id' => $casing->id, 'character_count' => $count, 'label' => ($casing->alt_text ?: 'Casing '.$casing->position).' · '.$count.' huruf', 'quantity' => (int) ($quantities[$casing->id][$count] ?? 0)];
                    }
                }
            }

            return response()->json(['name' => $product->prd_name, 'code' => $product->prd_code, 'balance' => (int) $product->prd_balance, 'casing_stock' => CasingStock::enabled($product), 'variations' => $variations])->header('Cache-Control', 'no-store');
        });
    }

    public function update(UpdateProductBalanceRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $product, $data): void {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $balanceBefore = (int) $product->prd_balance;
            if ($balanceBefore !== (int) $data['expected_balance']) {
                throw ValidationException::withMessages(['quantity' => 'Stok telah berubah. Tutup dan buka semula dialog untuk menyemak baki terkini.']);
            }
            $before = $balanceBefore;
            if (CasingStock::enabled($product)) {
                $casing = DB::table('product_clicker_images')->where('product_id', $product->id)->where('image_type', 'casing')->where('id', $data['casing_id'] ?? 0)->lockForUpdate()->first();
                if (! $casing || empty($data['character_count'])) {
                    throw ValidationException::withMessages(['quantity' => 'Pilih casing dan saiz yang sah. Jumlah produk dikira automatik.']);
                }
                $stockQuery = DB::table('product_clicker_stocks')->where('casing_image_id', $casing->id)->where('character_count', $data['character_count']);
                $before = (int) ($stockQuery->lockForUpdate()->first()?->quantity ?? 0);
            } elseif (! empty($data['casing_id']) || $product->product_type === 'clicker') {
                throw ValidationException::withMessages(['quantity' => 'Urus stok Clicker melalui tetapan casing produk terlebih dahulu.']);
            }
            if ($before !== (int) $data['expected_quantity']) {
                throw ValidationException::withMessages(['quantity' => 'Stok variasi telah berubah. Tutup dan buka semula dialog sebelum menyimpan.']);
            }
            if ($before === (int) $data['quantity']) {
                return;
            }
            if (CasingStock::enabled($product)) {
                DB::table('product_clicker_stocks')->updateOrInsert(['casing_image_id' => $casing->id, 'character_count' => $data['character_count']], ['quantity' => (int) $data['quantity']]);
                $total = DB::table('product_clicker_stocks as stock')->join('product_clicker_images as casing', 'casing.id', '=', 'stock.casing_image_id')->where('casing.product_id', $product->id)->where('casing.image_type', 'casing')->sum('stock.quantity');
            } else {
                $total = (int) $data['quantity'];
            }
            $product->forceFill(['prd_balance' => $total])->save();
            AdminActivity::record(request: $request, event: 'admin.product.balance.updated', description: "Stock adjusted for {$product->prd_code}.", adminUser: $request->user('admin'), properties: ['page' => 'Products', 'product_id' => $product->id, 'reason' => $data['reason'], 'before' => $before, 'after' => (int) $data['quantity'], 'balance_before' => $balanceBefore, 'balance_after' => (int) $total, 'casing_id' => $data['casing_id'] ?? null, 'character_count' => $data['character_count'] ?? null]);
        });

        return response()->json(['message' => 'Baki stok berjaya dikemas kini.']);
    }
}
