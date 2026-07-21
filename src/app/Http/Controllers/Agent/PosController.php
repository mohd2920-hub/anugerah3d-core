<?php

namespace App\Http\Controllers\Agent;

use App\Actions\Pos\CreatePosSale;
use App\Actions\Pos\UpdatePosSale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\SignInPosRequest;
use App\Http\Requests\Agent\StorePosSaleRequest;
use App\Http\Requests\Agent\UpdatePosSaleRequest;
use App\Models\Agent;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $agent = $request->user('agent');
        $activeSession = $this->activeSession($agent);
        $businessSites = $agent->businessSites()->orderBy('site_name')->get(['business_sites.id', 'site_name', 'city']);
        $siteIds = $businessSites->pluck('id');

        return view('agent.pos.index', [
            'activeSession' => $activeSession?->load('businessSite'),
            'businessSites' => $businessSites,
            'salesAgents' => $activeSession
                ? $activeSession->businessSite->agents()->where('agt_status', Agent::StatusActive)->orderBy('agt_name')->get(['usr_agent.id', 'agt_name', 'login_id', 'discount_percentage', 'profile_picture'])
                : collect(),
            'products' => $activeSession
                ? Product::query()->orderBy('prd_name')->get(['id', 'prd_code', 'prd_name', 'price_selling', 'agent_discount_default', 'prd_balance'])
                : collect(),
            'sales' => PosSale::query()
                ->when(
                    $activeSession,
                    fn ($query) => $query
                        ->where('business_site_id', $activeSession->business_site_id)
                        ->whereBetween('sold_at', [
                            $activeSession->signed_in_at,
                            $activeSession->signed_out_at ?? now(),
                        ]),
                    fn ($query) => $query->whereRaw('1 = 0'),
                )
                ->with(['businessSite:id,site_name,city', 'salesAgent:id,agt_name', 'recordedBy:id,agt_name', 'items'])
                ->latest('sold_at')
                ->paginate(15),
            'paymentMethods' => PosSale::paymentMethods(),
        ]);
    }

    public function signIn(SignInPosRequest $request): RedirectResponse
    {
        $agent = $request->user('agent');

        DB::transaction(function () use ($agent, $request): void {
            PosSession::query()
                ->whereBelongsTo($agent)
                ->whereNull('signed_out_at')
                ->update(['signed_out_at' => now()]);

            PosSession::query()->create([
                'agent_id' => $agent->getKey(),
                'business_site_id' => $request->integer('business_site_id'),
                'signed_in_at' => now(),
            ]);
        });

        return redirect()->route('agent.pos.index')->with('success', 'You have checked in to the business site.');
    }

    public function signOut(Request $request): RedirectResponse
    {
        $session = $this->activeSession($request->user('agent'));

        if ($session !== null) {
            $session->update(['signed_out_at' => now()]);
        }

        return redirect()->route('agent.pos.index')->with('success', 'You have checked out from the business site.');
    }

    public function store(StorePosSaleRequest $request, CreatePosSale $createPosSale): RedirectResponse
    {
        $createPosSale->handle($request->activePosSession(), $request->validated());

        return redirect()->route('agent.pos.index', ['tab' => 'history'])->with('success', 'Sale recorded successfully.');
    }

    public function edit(Request $request, PosSale $posSale): View
    {
        $session = $this->activeSession($request->user('agent'));
        abort_unless($session !== null && $session->business_site_id === $posSale->business_site_id, 403);

        return view('agent.pos.edit', [
            'activeSession' => $session->load('businessSite'),
            'posSale' => $posSale->load('items'),
            'salesAgents' => $session->businessSite->agents()->where('agt_status', Agent::StatusActive)->orderBy('agt_name')->get(['usr_agent.id', 'agt_name', 'login_id', 'discount_percentage', 'profile_picture']),
            'products' => Product::query()->orderBy('prd_name')->get(['id', 'prd_code', 'prd_name', 'price_selling', 'agent_discount_default', 'prd_balance']),
            'paymentMethods' => PosSale::paymentMethods(),
        ]);
    }

    public function update(UpdatePosSaleRequest $request, PosSale $posSale, UpdatePosSale $updatePosSale): RedirectResponse
    {
        $updatePosSale->handle($posSale, $request->activePosSession(), $request->validated());

        return redirect()->route('agent.pos.index', ['tab' => 'history'])->with('success', 'Sale updated successfully.');
    }

    private function activeSession(Agent $agent): ?PosSession
    {
        return PosSession::query()
            ->active()
            ->whereBelongsTo($agent)
            ->whereHas('businessSite.agents', fn ($query) => $query->whereKey($agent->getKey()))
            ->latest('signed_in_at')
            ->first();
    }
}
