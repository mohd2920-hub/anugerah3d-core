<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var Agent $agent */
        $agent = $request->user('agent');
        $monthlyTarget = 10000.0;
        $totalSales = (float) $agent->total_sale;
        $tier1Ids = Agent::query()
            ->where('referrer_id', $agent->getKey())
            ->pluck('id');
        $tier2Ids = $tier1Ids->isEmpty()
            ? collect()
            : Agent::query()->whereIn('referrer_id', $tier1Ids)->pluck('id');
        $teamAgentIds = $tier1Ids->concat($tier2Ids)->unique()->values();
        $teamOrderQuery = Order::query()
            ->whereIn('agent_id', $teamAgentIds)
            ->where('status', Order::StatusCompleted);

        return view('agent.progress', [
            'agent' => $agent,
            'monthlyTarget' => $monthlyTarget,
            'progressPercentage' => min(100, (int) round(($totalSales / $monthlyTarget) * 100)),
            'remainingTarget' => max(0, $monthlyTarget - $totalSales),
            'referralUrl' => $agent->referralUrl(),
            'referralMessage' => $agent->referralInviteMessage(),
            'pendingOrderItemCount' => Order::query()
                ->whereBelongsTo($agent)
                ->whereIn('status', [Order::StatusPending, Order::StatusProcessing])
                ->sum('total_units'),
            'teamAgentCount' => $teamAgentIds->count(),
            'teamSalesTotal' => (float) (clone $teamOrderQuery)->sum('total_amount'),
            'teamOrderCount' => (int) (clone $teamOrderQuery)->count(),
        ]);
    }
}
