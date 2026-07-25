<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Agent $agent */
        $agent = $request->user('agent');

        $tier1Agents = $this->teamMembersByReferrerIds([$agent->getKey()]);
        $tier1Ids = $tier1Agents->pluck('id')->all();
        $tier2Agents = $this->teamMembersByReferrerIds($tier1Ids);

        $tier2ByReferrer = $tier2Agents
            ->groupBy('referrer_id')
            ->map(fn ($members) => $members->values());

        $allAgents = $tier1Agents->concat($tier2Agents);
        $tier1SalesTotal = (float) $tier1Agents->sum('completed_orders_total');
        $tier2SalesTotal = (float) $tier2Agents->sum('completed_orders_total');
        $tier1Rate = (float) ($agent->tier1_percentage ?? 7);
        $tier2Rate = (float) ($agent->tier2_percentage ?? 3);
        $teamTotalSales = (float) $allAgents->sum('completed_orders_total');
        $teamOrderCount = (int) $allAgents->sum('completed_orders_count');

        return view('agent.team.index', [
            'agent' => $agent,
            'tier1Agents' => $tier1Agents,
            'tier2ByReferrer' => $tier2ByReferrer,
            'teamAgentCount' => $allAgents->count(),
            'teamTotalSales' => $teamTotalSales,
            'teamOrderCount' => $teamOrderCount,
            'tier1SalesTotal' => $tier1SalesTotal,
            'tier2SalesTotal' => $tier2SalesTotal,
            'tier1Rate' => $tier1Rate,
            'tier2Rate' => $tier2Rate,
            'tier1BonusEstimate' => $tier1SalesTotal * ($tier1Rate / 100),
            'tier2BonusEstimate' => $tier2SalesTotal * ($tier2Rate / 100),
        ]);
    }

    public function show(Request $request, Agent $teamAgent): View
    {
        /** @var Agent $agent */
        $agent = $request->user('agent');

        if (! $this->isInFirstTwoTiers($agent, $teamAgent)) {
            abort(404);
        }

        $completedOrderCount = Order::query()
            ->whereBelongsTo($teamAgent)
            ->where('status', Order::StatusCompleted)
            ->count();

        $completedOrderTotal = (float) Order::query()
            ->whereBelongsTo($teamAgent)
            ->where('status', Order::StatusCompleted)
            ->sum('total_amount');

        $pendingOrderCount = Order::query()
            ->whereBelongsTo($teamAgent)
            ->whereIn('status', [Order::StatusPending, Order::StatusProcessing])
            ->count();

        return view('agent.team.show', [
            'agent' => $agent,
            'teamAgent' => $teamAgent,
            'completedOrderCount' => $completedOrderCount,
            'completedOrderTotal' => $completedOrderTotal,
            'pendingOrderCount' => $pendingOrderCount,
        ]);
    }

    private function isInFirstTwoTiers(Agent $rootAgent, Agent $teamAgent): bool
    {
        if ((int) $teamAgent->referrer_id === (int) $rootAgent->getKey()) {
            return true;
        }

        $tier1Ids = Agent::query()
            ->where('referrer_id', $rootAgent->getKey())
            ->pluck('id');

        if ($tier1Ids->isEmpty()) {
            return false;
        }

        return Agent::query()
            ->whereKey($teamAgent->getKey())
            ->whereIn('referrer_id', $tier1Ids)
            ->exists();
    }

    private function teamMembersByReferrerIds(array $referrerIds)
    {
        if ($referrerIds === []) {
            return collect();
        }

        return Agent::query()
            ->whereIn('referrer_id', $referrerIds)
            ->with('referrer:id,agt_name,login_id')
            ->withCount([
                'orders as completed_orders_count' => function (Builder $query): void {
                    $query->where('status', Order::StatusCompleted);
                },
            ])
            ->withSum([
                'orders as completed_orders_total' => function (Builder $query): void {
                    $query->where('status', Order::StatusCompleted);
                },
            ], 'total_amount')
            ->orderBy('agt_name')
            ->get();
    }
}