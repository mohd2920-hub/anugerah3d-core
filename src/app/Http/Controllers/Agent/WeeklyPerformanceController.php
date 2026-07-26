<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\WeeklyClosingAgentSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class WeeklyPerformanceController extends Controller
{
    public function index(Request $request): View
    {
        $agent = $request->user('agent');
        $status = $request->string('status')->trim()->toString();

        $rows = WeeklyClosingAgentSummary::query()
            ->with('closing:id,week_key,period_start,period_end,closed_at')
            ->where('agent_id', $agent->getKey())
            ->when(in_array($status, ['pending', 'paid', 'no_payout'], true), fn (Builder $query): Builder => $query->where('payout_status', $status))
            ->orderByDesc('weekly_closing_id')
            ->paginate(20)
            ->withQueryString();

        return view('agent.weekly-performance.index', [
            'rows' => $rows,
            'status' => $status,
        ]);
    }
}
