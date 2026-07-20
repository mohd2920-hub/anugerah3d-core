<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBusinessSiteRequest;
use App\Http\Requests\Admin\UpdateBusinessSiteRequest;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessSiteController extends Controller
{
    public function index(): View
    {
        return view('admin.business-sites.index', [
            'businessSites' => BusinessSite::query()->withCount(['agents', 'posSales'])->latest()->paginate(20),
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
        if ($businessSite->posSales()->exists()) {
            return back()->withErrors(['business_site' => 'This business site already has POS sales and cannot be deleted.']);
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
