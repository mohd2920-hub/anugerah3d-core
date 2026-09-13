<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BusinessSites\StartBusinessSite;
use App\Actions\BusinessSites\StopBusinessSite;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBusinessSiteRequest;
use App\Http\Requests\Admin\UpdateBusinessSiteRequest;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessSiteController extends Controller
{
    public function index(): View
    {
        return view('admin.business-sites.index', [
            'businessSites' => BusinessSite::query()
                ->withCount([
                    'currentOperationPosSessions as active_pos_sessions_count' => fn (Builder $query): Builder => $query->active(),
                ])
                ->latest()
                ->paginate(20, ['*'], 'sites_page'),
        ]);
    }

    public function start(Request $request, BusinessSite $businessSite, StartBusinessSite $startBusinessSite): RedirectResponse
    {
        $businessSite = $startBusinessSite->handle($businessSite);
        $this->recordActivity($request, $businessSite, 'started');

        return back()->with('success', "{$businessSite->site_name} is now open for business.");
    }

    public function stop(Request $request, BusinessSite $businessSite, StopBusinessSite $stopBusinessSite): RedirectResponse
    {
        $checkedOutAgents = $stopBusinessSite->handle($businessSite);
        $this->recordActivity($request, $businessSite, 'stopped');

        return back()->with('success', "{$businessSite->site_name} has stopped. {$checkedOutAgents} checked-in agent(s) were checked out.");
    }

    public function show(BusinessSite $businessSite): View
    {
        $businessSite->load(['agents:id,agt_name,login_id,email,phone_number'])
            ->loadCount(['agents', 'posSales', 'operations', 'activePosSessions']);

        return view('admin.business-sites.show', [
            'businessSite' => $businessSite,
            'recentOperations' => $businessSite->operations()->latest('opened_at')->limit(10)->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.business-sites.create', [
            'businessSite' => null,
            'agents' => $this->agents(),
        ]);
    }

    public function store(StoreBusinessSiteRequest $request): RedirectResponse
    {
        $businessSite = DB::transaction(function () use ($request): BusinessSite {
            $businessSite = BusinessSite::query()->create($request->safe()->only(['site_name', 'city']));
            $businessSite->agents()->sync($request->validated('agent_ids', []));

            return $businessSite;
        });

        $this->recordActivity($request, $businessSite, 'created');

        return redirect()->route('admin.business-sites.index')->with('success', 'Business site created successfully.');
    }

    public function edit(BusinessSite $businessSite): View
    {
        $businessSite->load('agents:id');

        return view('admin.business-sites.edit', [
            'businessSite' => $businessSite,
            'agents' => $this->agents(),
        ]);
    }

    public function update(UpdateBusinessSiteRequest $request, BusinessSite $businessSite): RedirectResponse
    {
        DB::transaction(function () use ($request, $businessSite): void {
            $businessSite->update($request->safe()->only(['site_name', 'city']));
            $businessSite->agents()->sync($request->validated('agent_ids', []));
        });

        $this->recordActivity($request, $businessSite, 'updated');

        return redirect()->route('admin.business-sites.index')->with('success', 'Business site updated successfully.');
    }

    public function destroy(Request $request, BusinessSite $businessSite): RedirectResponse
    {
        if ($businessSite->posSales()->exists() || $businessSite->operations()->exists()) {
            return back()->withErrors(['business_site' => 'This business site has sales or operation history and cannot be deleted.']);
        }

        $siteName = $businessSite->site_name;
        $siteId = $businessSite->getKey();
        $businessSite->delete();

        AdminActivity::record(
            request: $request,
            event: 'admin.business_site.deleted',
            description: "Business site {$siteName} deleted.",
            adminUser: $request->user('admin'),
            properties: ['page' => 'Business Sites', 'business_site_id' => $siteId],
        );

        return redirect()->route('admin.business-sites.index')->with('success', 'Business site deleted successfully.');
    }

    private function agents(): Collection
    {
        return Agent::query()
            ->where('agt_status', Agent::StatusActive)
            ->orderBy('agt_name')
            ->get(['id', 'agt_name', 'login_id']);
    }

    private function recordActivity(Request $request, BusinessSite $businessSite, string $action): void
    {
        AdminActivity::record(
            request: $request,
            event: "admin.business_site.{$action}",
            description: "Business site {$businessSite->site_name} {$action}.",
            adminUser: $request->user('admin'),
            properties: ['page' => 'Business Sites', 'business_site_id' => $businessSite->getKey()],
        );
    }
}
