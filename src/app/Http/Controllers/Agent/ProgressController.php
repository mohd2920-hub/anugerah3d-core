<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
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

        return view('agent.progress', [
            'agent' => $agent,
            'monthlyTarget' => $monthlyTarget,
            'progressPercentage' => min(100, (int) round(($totalSales / $monthlyTarget) * 100)),
            'remainingTarget' => max(0, $monthlyTarget - $totalSales),
        ]);
    }
}
