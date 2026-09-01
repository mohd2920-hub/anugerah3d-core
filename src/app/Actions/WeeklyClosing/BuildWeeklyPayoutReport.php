<?php

namespace App\Actions\WeeklyClosing;

use App\Models\Agent;
use App\Models\Order;
use App\Models\WeeklyClosing;
use App\Models\WeeklyClosingAgentSummary;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;

class BuildWeeklyPayoutReport
{
    private const Timezone = 'Asia/Kuala_Lumpur';

    /**
     * @param  array{report_period: string, start_date: ?string, end_date: ?string}  $filters
     * @return array{
     *     filters: array{report_period: string, start_date: ?string, end_date: ?string},
     *     period_label: string,
     *     historical: array<string, float|int>,
     *     current_week: array<string, float|int|string>
     * }
     */
    public function handle(array $filters): array
    {
        [$periodStart, $periodEnd] = $this->selectedPeriod($filters);
        $currentWeekStart = CarbonImmutable::now(self::Timezone)->startOfWeek(CarbonImmutable::MONDAY);
        $currentWeekEnd = $currentWeekStart->addWeek();

        return [
            'filters' => $filters,
            'period_label' => $this->periodLabel($periodStart, $periodEnd),
            'historical' => $this->historicalSummary($periodStart, $periodEnd),
            'current_week' => $this->currentWeekProjection($currentWeekStart, $currentWeekEnd),
        ];
    }

    /**
     * @param  array{report_period: string, start_date: ?string, end_date: ?string}  $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function selectedPeriod(array $filters): array
    {
        $now = CarbonImmutable::now(self::Timezone);

        return match ($filters['report_period']) {
            'week' => [$now->startOfWeek(CarbonImmutable::MONDAY), $now->startOfWeek(CarbonImmutable::MONDAY)->addWeek()],
            'custom' => [
                CarbonImmutable::createFromFormat('Y-m-d', (string) $filters['start_date'], self::Timezone)->startOfDay(),
                CarbonImmutable::createFromFormat('Y-m-d', (string) $filters['end_date'], self::Timezone)->startOfDay()->addDay(),
            ],
            default => [$now->startOfMonth(), $now->startOfMonth()->addMonth()],
        };
    }

    private function periodLabel(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): string
    {
        return $periodStart->format('d M Y').' - '.$periodEnd->subDay()->format('d M Y');
    }

    /** @return array<string, float|int> */
    private function historicalSummary(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $summariesTable = (new WeeklyClosingAgentSummary)->getTable();
        $closingsTable = (new WeeklyClosing)->getTable();

        $totals = WeeklyClosingAgentSummary::query()
            ->join($closingsTable, "{$closingsTable}.id", '=', "{$summariesTable}.weekly_closing_id")
            ->where("{$closingsTable}.period_end", '>=', $periodStart)
            ->where("{$closingsTable}.period_end", '<', $periodEnd)
            ->toBase()
            ->selectRaw("COUNT(CASE WHEN {$summariesTable}.total_bonus > 0 THEN 1 END) as payout_records")
            ->selectRaw("SUM(CASE WHEN {$summariesTable}.payout_status = 'paid' AND {$summariesTable}.total_bonus > 0 THEN 1 ELSE 0 END) as paid_records")
            ->selectRaw("SUM(CASE WHEN {$summariesTable}.payout_status = 'pending' AND {$summariesTable}.total_bonus > 0 THEN 1 ELSE 0 END) as pending_records")
            ->selectRaw("COALESCE(SUM({$summariesTable}.tier1_bonus), 0) as tier1_total")
            ->selectRaw("COALESCE(SUM({$summariesTable}.tier2_bonus), 0) as tier2_total")
            ->selectRaw("COALESCE(SUM({$summariesTable}.total_bonus), 0) as payable_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN {$summariesTable}.payout_status = 'paid' THEN {$summariesTable}.tier1_bonus ELSE 0 END), 0) as tier1_paid")
            ->selectRaw("COALESCE(SUM(CASE WHEN {$summariesTable}.payout_status = 'pending' THEN {$summariesTable}.tier1_bonus ELSE 0 END), 0) as tier1_pending")
            ->selectRaw("COALESCE(SUM(CASE WHEN {$summariesTable}.payout_status = 'paid' THEN {$summariesTable}.tier2_bonus ELSE 0 END), 0) as tier2_paid")
            ->selectRaw("COALESCE(SUM(CASE WHEN {$summariesTable}.payout_status = 'pending' THEN {$summariesTable}.tier2_bonus ELSE 0 END), 0) as tier2_pending")
            ->selectRaw("COALESCE(SUM(CASE WHEN {$summariesTable}.payout_status = 'paid' THEN {$summariesTable}.total_bonus ELSE 0 END), 0) as paid_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN {$summariesTable}.payout_status = 'pending' THEN {$summariesTable}.total_bonus ELSE 0 END), 0) as pending_total")
            ->first();

        return [
            'payout_records' => (int) $totals->payout_records,
            'paid_records' => (int) $totals->paid_records,
            'pending_records' => (int) $totals->pending_records,
            'tier1_total' => round((float) $totals->tier1_total, 2),
            'tier2_total' => round((float) $totals->tier2_total, 2),
            'payable_total' => round((float) $totals->payable_total, 2),
            'tier1_paid' => round((float) $totals->tier1_paid, 2),
            'tier1_pending' => round((float) $totals->tier1_pending, 2),
            'tier2_paid' => round((float) $totals->tier2_paid, 2),
            'tier2_pending' => round((float) $totals->tier2_pending, 2),
            'paid_total' => round((float) $totals->paid_total, 2),
            'pending_total' => round((float) $totals->pending_total, 2),
        ];
    }

    /** @return array<string, float|int|string> */
    private function currentWeekProjection(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $hasTier1Column = Schema::hasColumn('usr_agent', 'tier1_percentage');
        $hasTier2Column = Schema::hasColumn('usr_agent', 'tier2_percentage');
        $agentColumns = ['id', 'referrer_id', 'agt_status'];

        if ($hasTier1Column) {
            $agentColumns[] = 'tier1_percentage';
        }

        if ($hasTier2Column) {
            $agentColumns[] = 'tier2_percentage';
        }

        $agents = Agent::query()->get($agentColumns)->keyBy('id');
        $activePayees = $agents->where('agt_status', Agent::StatusActive);
        $orders = Order::query()
            ->where('placed_at', '>=', $periodStart)
            ->where('placed_at', '<', $periodEnd)
            ->where('status', '!=', Order::StatusCancelled)
            ->get(['id', 'agent_id', 'total_amount']);

        $tier1Amounts = [];
        $tier2Amounts = [];
        $tier1Orders = 0;
        $tier2Orders = 0;

        foreach ($orders as $order) {
            $buyer = $agents->get($order->agent_id);
            $tier1PayeeId = $buyer?->referrer_id;

            if ($tier1PayeeId !== null && $activePayees->has($tier1PayeeId)) {
                $tier1Amounts[$tier1PayeeId] = ($tier1Amounts[$tier1PayeeId] ?? 0) + (float) $order->total_amount;
                $tier1Orders++;
            }

            $tier2PayeeId = $tier1PayeeId !== null ? $agents->get($tier1PayeeId)?->referrer_id : null;

            if ($tier2PayeeId !== null && $activePayees->has($tier2PayeeId)) {
                $tier2Amounts[$tier2PayeeId] = ($tier2Amounts[$tier2PayeeId] ?? 0) + (float) $order->total_amount;
                $tier2Orders++;
            }
        }

        $tier1Bonus = collect($tier1Amounts)->sum(function (float $amount, int $agentId) use ($activePayees, $hasTier1Column): float {
            $rate = $hasTier1Column ? (float) ($activePayees->get($agentId)?->tier1_percentage ?? 7) : 7.0;

            return round($amount * $rate / 100, 2);
        });
        $tier2Bonus = collect($tier2Amounts)->sum(function (float $amount, int $agentId) use ($activePayees, $hasTier2Column): float {
            $rate = $hasTier2Column ? (float) ($activePayees->get($agentId)?->tier2_percentage ?? 3) : 3.0;

            return round($amount * $rate / 100, 2);
        });

        return [
            'period_label' => $this->periodLabel($periodStart, $periodEnd),
            'tier1_orders' => $tier1Orders,
            'tier2_orders' => $tier2Orders,
            'tier1_sales' => round(array_sum($tier1Amounts), 2),
            'tier2_sales' => round(array_sum($tier2Amounts), 2),
            'tier1_bonus' => round((float) $tier1Bonus, 2),
            'tier2_bonus' => round((float) $tier2Bonus, 2),
            'estimated_total' => round((float) $tier1Bonus + (float) $tier2Bonus, 2),
            'estimated_payees' => count(array_unique([...array_keys($tier1Amounts), ...array_keys($tier2Amounts)])),
        ];
    }
}
