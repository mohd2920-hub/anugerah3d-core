<?php

namespace App\Actions\WeeklyClosing;

use App\Models\Agent;
use App\Models\Order;
use App\Models\PosSale;
use App\Models\WeeklyClosing;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RunWeeklyClosing
{
    public function handle(?CarbonImmutable $runAt = null, bool $force = false): WeeklyClosing
    {
        $timezone = 'Asia/Kuala_Lumpur';
        $runAt = ($runAt ?? CarbonImmutable::now($timezone))->setTimezone($timezone);
        $periodEnd = $runAt->startOfWeek(CarbonImmutable::MONDAY);
        $periodStart = $periodEnd->subWeek();
        $weekKey = $periodStart->format('o').'-W'.$periodStart->format('W');

        return DB::transaction(function () use ($periodStart, $periodEnd, $weekKey, $runAt, $force): WeeklyClosing {
            $closing = WeeklyClosing::query()->firstOrNew(['week_key' => $weekKey]);

            if ($closing->exists && $closing->status === 'completed' && ! $force) {
                return $closing->load('agentSummaries');
            }

            $closing->fill([
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => 'processing',
                'closed_at' => $runAt,
                'email_dispatched_at' => null,
            ])->save();

            $hasTier1 = Schema::hasColumn('usr_agent', 'tier1_percentage');
            $hasTier2 = Schema::hasColumn('usr_agent', 'tier2_percentage');

            $agentSelect = ['id', 'agt_name', 'email', 'referrer_id', 'agt_status', 'created_at', 'phone_number'];
            if (Schema::hasColumn('usr_agent', 'bank_name')) {
                $agentSelect[] = 'bank_name';
            }
            if (Schema::hasColumn('usr_agent', 'bank_account_name')) {
                $agentSelect[] = 'bank_account_name';
            }
            if (Schema::hasColumn('usr_agent', 'bank_account_number')) {
                $agentSelect[] = 'bank_account_number';
            }
            if ($hasTier1) {
                $agentSelect[] = 'tier1_percentage';
            }
            if ($hasTier2) {
                $agentSelect[] = 'tier2_percentage';
            }

            $agents = Agent::query()
                ->with(['referrer:id,agt_name,email,phone_number'])
                ->where('agt_status', Agent::StatusActive)
                ->get($agentSelect);

            $allAgents = Agent::query()->get(['id', 'referrer_id', 'created_at']);
            $referralsByReferrer = $allAgents->groupBy('referrer_id');

            $orders = Order::query()
                ->where('placed_at', '>=', $periodStart)
                ->where('placed_at', '<', $periodEnd)
                ->where('status', '!=', Order::StatusCancelled)
                ->get(['id', 'agent_id', 'total_amount', 'total_units']);

            $posSales = PosSale::query()
                ->where('sold_at', '>=', $periodStart)
                ->where('sold_at', '<', $periodEnd)
                ->get(['id', 'sales_agent_id', 'total_amount']);

            $ordersByAgent = $orders->groupBy('agent_id');
            $posSalesByAgent = $posSales->groupBy('sales_agent_id');

            $summaryRows = [];
            $totalPayableBonus = 0.0;
            $totalNewAgents = 0;
            $totalTier1Orders = 0;
            $totalTier2Orders = 0;

            foreach ($agents as $agent) {
                $tier1Agents = collect($referralsByReferrer->get($agent->id, []))->values();
                $tier1AgentIds = $tier1Agents->pluck('id')->all();

                $tier2Agents = collect($tier1AgentIds)
                    ->flatMap(fn (int $tier1Id) => $referralsByReferrer->get($tier1Id, []))
                    ->values();
                $tier2AgentIds = $tier2Agents->pluck('id')->all();

                $personalOrders = collect($ordersByAgent->get($agent->id, []));
                $tier1Orders = $orders->whereIn('agent_id', $tier1AgentIds)->values();
                $tier2Orders = $orders->whereIn('agent_id', $tier2AgentIds)->values();

                $tier1OrderAmount = (float) $tier1Orders->sum('total_amount');
                $tier2OrderAmount = (float) $tier2Orders->sum('total_amount');
                $tier1OrdersCount = $tier1Orders->count();
                $tier2OrdersCount = $tier2Orders->count();

                $tier1Rate = (float) ($hasTier1 ? ($agent->tier1_percentage ?? 7) : 7);
                $tier2Rate = (float) ($hasTier2 ? ($agent->tier2_percentage ?? 3) : 3);
                $tier1Bonus = round($tier1OrderAmount * $tier1Rate / 100, 2);
                $tier2Bonus = round($tier2OrderAmount * $tier2Rate / 100, 2);
                $totalBonus = round($tier1Bonus + $tier2Bonus, 2);

                $newAgentsRegistered = $tier1Agents
                    ->filter(fn ($member) => $member->created_at >= $periodStart && $member->created_at < $periodEnd)
                    ->count();

                $agentPosSales = collect($posSalesByAgent->get($agent->id, []));
                $row = [
                    'weekly_closing_id' => $closing->id,
                    'agent_id' => $agent->id,
                    'agent_name' => (string) $agent->agt_name,
                    'agent_email' => (string) ($agent->email ?? ''),
                    'agent_bank_name' => (string) ($agent->bank_name ?? ''),
                    'agent_bank_account_name' => (string) ($agent->bank_account_name ?? ''),
                    'agent_bank_account_number' => (string) ($agent->bank_account_number ?? ''),
                    'referrer_name' => (string) ($agent->referrer?->agt_name ?? ''),
                    'referrer_email' => (string) ($agent->referrer?->email ?? ''),
                    'referrer_phone' => (string) ($agent->referrer?->phone_number ?? ''),
                    'tier1_rate' => $tier1Rate,
                    'tier2_rate' => $tier2Rate,
                    'personal_orders_count' => $personalOrders->count(),
                    'personal_order_amount' => (float) $personalOrders->sum('total_amount'),
                    'new_agents_registered' => $newAgentsRegistered,
                    'tier1_agents_total' => count($tier1AgentIds),
                    'tier2_agents_total' => count($tier2AgentIds),
                    'tier1_orders_count' => $tier1OrdersCount,
                    'tier2_orders_count' => $tier2OrdersCount,
                    'tier1_orders_amount' => $tier1OrderAmount,
                    'tier2_orders_amount' => $tier2OrderAmount,
                    'tier1_bonus' => $tier1Bonus,
                    'tier2_bonus' => $tier2Bonus,
                    'total_bonus' => $totalBonus,
                    'pos_sales_count' => $agentPosSales->count(),
                    'pos_sales_amount' => (float) $agentPosSales->sum('total_amount'),
                    'payout_status' => $totalBonus > 0 ? 'pending' : 'no_payout',
                    'paid_at' => null,
                    'notified_agent_at' => null,
                    'paid_by_admin_id' => null,
                    'payment_reference' => null,
                    'payment_receipt_datetime_text' => null,
                    'payment_attachment_path' => null,
                    'payment_notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $summaryRows[] = $row;
                $totalPayableBonus += $totalBonus;
                $totalNewAgents += $newAgentsRegistered;
                $totalTier1Orders += $tier1OrdersCount;
                $totalTier2Orders += $tier2OrdersCount;
            }

            DB::table('weekly_closing_agent_summaries')->where('weekly_closing_id', $closing->id)->delete();
            if ($summaryRows !== []) {
                DB::table('weekly_closing_agent_summaries')->insert($summaryRows);
            }

            $snapshotPayload = [
                'week_key' => $closing->week_key,
                'period_start' => $periodStart->format('Y-m-d H:i:s'),
                'period_end' => $periodEnd->format('Y-m-d H:i:s'),
                'closed_at' => $runAt->format('Y-m-d H:i:s'),
                'totals' => [
                    'total_agents' => $agents->count(),
                    'total_orders' => $orders->count(),
                    'total_order_amount' => round((float) $orders->sum('total_amount'), 2),
                    'total_order_units' => (int) $orders->sum('total_units'),
                    'total_pos_sales' => $posSales->count(),
                    'total_pos_amount' => round((float) $posSales->sum('total_amount'), 2),
                    'total_new_agents' => $totalNewAgents,
                    'total_tier1_orders' => $totalTier1Orders,
                    'total_tier2_orders' => $totalTier2Orders,
                    'total_payable_bonus' => round($totalPayableBonus, 2),
                ],
                'agents' => collect($summaryRows)->map(function (array $row): array {
                    unset($row['weekly_closing_id'], $row['created_at'], $row['updated_at']);

                    return $row;
                })->values()->all(),
            ];

            $backupPath = 'weekly-closing/snapshots/'.$closing->week_key.'.json';
            Storage::disk('local')->put($backupPath, json_encode($snapshotPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $closing->forceFill([
                'status' => 'completed',
                'closed_at' => $runAt,
                'backup_path' => 'storage/app/private/'.$backupPath,
                'total_agents' => $agents->count(),
                'total_orders' => $orders->count(),
                'total_order_amount' => round((float) $orders->sum('total_amount'), 2),
                'total_order_units' => (int) $orders->sum('total_units'),
                'total_pos_sales' => $posSales->count(),
                'total_pos_amount' => round((float) $posSales->sum('total_amount'), 2),
                'total_new_agents' => $totalNewAgents,
                'total_tier1_orders' => $totalTier1Orders,
                'total_tier2_orders' => $totalTier2Orders,
                'total_payable_bonus' => round($totalPayableBonus, 2),
            ])->save();

            return $closing->load('agentSummaries');
        });
    }
}
