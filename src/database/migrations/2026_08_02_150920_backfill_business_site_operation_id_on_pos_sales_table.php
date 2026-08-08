<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pos_sales')
            ->whereNull('business_site_operation_id')
            ->orderBy('id')
            ->chunkById(200, function ($sales): void {
                foreach ($sales as $sale) {
                    $operationId = $this->matchingOperationId($sale);

                    if ($operationId === null) {
                        $operationId = $this->createLegacyOperation($sale);
                    }

                    DB::table('pos_sales')
                        ->where('id', $sale->id)
                        ->update(['business_site_operation_id' => $operationId]);
                }
            });
    }

    public function down(): void
    {
        DB::table('pos_sales')->update(['business_site_operation_id' => null]);
    }

    private function matchingOperationId(object $sale): ?int
    {
        $operationId = DB::table('business_site_operations')
            ->where('business_site_id', $sale->business_site_id)
            ->where('opened_at', '<=', $sale->sold_at)
            ->where(function ($query) use ($sale): void {
                $query->whereNull('closed_at')
                    ->orWhere('closed_at', '>=', $sale->sold_at);
            })
            ->latest('opened_at')
            ->value('id');

        return $operationId === null ? null : (int) $operationId;
    }

    private function createLegacyOperation(object $sale): int
    {
        $session = DB::table('pos_sessions')->where('id', $sale->pos_session_id)->first();
        $saleRange = DB::table('pos_sales')
            ->where('pos_session_id', $sale->pos_session_id)
            ->selectRaw('MIN(sold_at) as first_sold_at, MAX(sold_at) as last_sold_at')
            ->first();

        $firstSoldAt = $saleRange?->first_sold_at ?? $sale->sold_at;
        $lastSoldAt = $saleRange?->last_sold_at ?? $sale->sold_at;
        $openedAt = $session?->signed_in_at ?? $firstSoldAt;
        $closedAt = $session?->signed_out_at ?? $lastSoldAt;

        if ($openedAt > $firstSoldAt) {
            $openedAt = $firstSoldAt;
        }

        if ($closedAt < $lastSoldAt) {
            $closedAt = $lastSoldAt;
        }

        return DB::table('business_site_operations')->insertGetId([
            'business_site_id' => $sale->business_site_id,
            'opened_at' => $openedAt,
            'closed_at' => $closedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
