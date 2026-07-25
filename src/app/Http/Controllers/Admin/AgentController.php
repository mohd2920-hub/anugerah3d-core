<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetAgentPasswordRequest;
use App\Http\Requests\Admin\StoreAgentRequest;
use App\Http\Requests\Admin\UpdateAgentRequest;
use App\Mail\AgentActivated;
use App\Mail\AgentApproved;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Models\DataState;
use App\Models\Order;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AgentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $agents = Agent::query()
            ->with('referrer:id,referrer_id,agt_name,login_id')
            ->orderByRaw("CASE WHEN agt_status IN ('pending', 'new') THEN 0 ELSE 1 END")
            ->when($search !== '', fn (Builder $query): Builder => $query->search($search))
            ->when($status !== '' && array_key_exists($status, Agent::statuses()), fn (Builder $query): Builder => $query->where('agt_status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $listedAgents = $agents->getCollection();
        $rootAgentIds = $listedAgents->pluck('id')->all();

        $tier1Agents = Agent::query()
            ->whereIn('referrer_id', $rootAgentIds)
            ->get(['id', 'referrer_id']);

        $tier1CountByRootId = $tier1Agents
            ->groupBy('referrer_id')
            ->map(fn ($members): int => $members->count());

        $tier1Ids = $tier1Agents->pluck('id')->all();

        $tier1ToRoot = $tier1Agents
            ->mapWithKeys(fn (Agent $member): array => [(int) $member->id => (int) $member->referrer_id]);

        $tier2Agents = $tier1Ids === []
            ? collect()
            : Agent::query()
                ->whereIn('referrer_id', $tier1Ids)
                ->get(['id', 'referrer_id']);

        $tier2CountByRootId = $tier2Agents
            ->groupBy(fn (Agent $member): int => (int) ($tier1ToRoot[(int) $member->referrer_id] ?? 0))
            ->map(fn ($members): int => $members->count());

        $tier1SalesByAgentId = $tier1Ids === []
            ? collect()
            : Order::query()
                ->whereIn('agent_id', $tier1Ids)
                ->where('status', Order::StatusCompleted)
                ->selectRaw('agent_id, SUM(total_amount) as total_sales')
                ->groupBy('agent_id')
                ->pluck('total_sales', 'agent_id')
                ->map(fn ($value): float => (float) $value);

        $tier2Ids = $tier2Agents->pluck('id')->all();

        $tier2SalesByAgentId = $tier2Ids === []
            ? collect()
            : Order::query()
                ->whereIn('agent_id', $tier2Ids)
                ->where('status', Order::StatusCompleted)
                ->selectRaw('agent_id, SUM(total_amount) as total_sales')
                ->groupBy('agent_id')
                ->pluck('total_sales', 'agent_id')
                ->map(fn ($value): float => (float) $value);

        $tier2ByTier1 = $tier2Agents
            ->groupBy('referrer_id')
            ->map(fn ($members) => $members->pluck('id')->all());

        $listedAgents->transform(function (Agent $agent) use ($tier1Agents, $tier1CountByRootId, $tier2CountByRootId, $tier1SalesByAgentId, $tier2SalesByAgentId, $tier2ByTier1): Agent {
            $rootId = (int) $agent->id;
            $rootTier1Ids = $tier1Agents
                ->where('referrer_id', $rootId)
                ->pluck('id')
                ->all();

            $tier1SalesTotal = collect($rootTier1Ids)
                ->sum(fn ($tier1Id): float => (float) ($tier1SalesByAgentId[(int) $tier1Id] ?? 0));

            $rootTier2Ids = collect($rootTier1Ids)
                ->flatMap(fn ($tier1Id): array => $tier2ByTier1[(int) $tier1Id] ?? [])
                ->all();

            $tier2SalesTotal = collect($rootTier2Ids)
                ->sum(fn ($tier2Id): float => (float) ($tier2SalesByAgentId[(int) $tier2Id] ?? 0));

            $tier1Rate = (float) ($agent->tier1_percentage ?? 7);
            $tier2Rate = (float) ($agent->tier2_percentage ?? 3);
            $bonusEstimate = ($tier1SalesTotal * ($tier1Rate / 100)) + ($tier2SalesTotal * ($tier2Rate / 100));

            $agent->setAttribute('team_tier1_count', (int) ($tier1CountByRootId[$rootId] ?? 0));
            $agent->setAttribute('team_tier2_count', (int) ($tier2CountByRootId[$rootId] ?? 0));
            $agent->setAttribute('team_bonus_estimate', $bonusEstimate);

            return $agent;
        });

        $agents->setCollection($listedAgents);

        return view('admin.agents.index', [
            'agents' => $agents,
            'search' => $search,
            'selectedStatus' => $status,
            'statusOptions' => Agent::statuses(),
            'referrerOptions' => Agent::query()
                ->where('agt_status', Agent::StatusActive)
                ->orderBy('agt_name')
                ->get(['id', 'agt_name', 'login_id', 'phone_number', 'email', 'profile_picture']),
            'businessSites' => BusinessSite::query()->orderBy('site_name')->get(['id', 'site_name', 'city']),
            'loginInfo' => session('agent_login_info'),
            'newRegistrationCount' => Agent::query()->where('agt_status', Agent::StatusPending)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.agents.create', $this->formData());
    }

    public function store(StoreAgentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $plainPassword = $validated['password'];

        unset($validated['password_confirmation']);
        $businessSiteIds = $validated['business_site_ids'] ?? [];
        unset($validated['business_site_ids']);

        $agent = Agent::query()->create($validated);
        $agent->businessSites()->sync($businessSiteIds);

        AdminActivity::record(
            request: $request,
            event: 'admin.agent.created',
            description: "Agent {$agent->login_id} created.",
            adminUser: $request->user('admin'),
            properties: ['page' => 'Agents', 'agent_id' => $agent->getKey(), 'login_id' => $agent->login_id],
        );

        $creationMessage = 'Agent created successfully.';

        if ($agent->agt_status === Agent::StatusActive) {
            $agent->load('referrer');
            $creationMessage = 'Agent created and login details emailed successfully.';

            try {
                Mail::to($agent->email)->send(new AgentApproved(
                    agent: $agent,
                    referrer: $agent->referrer,
                    plainPassword: $plainPassword,
                    loginUrl: route('agent.login'),
                ));
            } catch (Throwable $exception) {
                $creationMessage = 'Agent created, but the login email could not be sent.';
                Log::warning('New agent login email could not be sent.', [
                    'agent_id' => $agent->getKey(),
                    'email' => $agent->email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('admin.agents.index')
            ->with('success', $creationMessage)
            ->with('agent_login_info', $this->loginInfoPayload($agent, $plainPassword));
    }

    public function edit(Agent $agent): View
    {
        $agent->load('businessSites:id');

        return view('admin.agents.edit', array_merge($this->formData(), [
            'agent' => $agent,
            'loginInfo' => session('agent_login_info'),
        ]));
    }

    public function show(Agent $agent): View
    {
        $agent->load('referrer:id,referrer_id,agt_name,login_id,profile_picture');

        $totalSale = (float) $agent->total_sale;
        $pendingOrders = max(1, (int) round($totalSale / 500));
        $completedOrders = max(3, (int) round($totalSale / 320));
        $commissionRate = (float) ($agent->commission_percentage ?? 0);
        $commissionAmount = $totalSale * ($commissionRate / 100);
        $averageOrderValue = $completedOrders > 0 ? ($totalSale / $completedOrders) : 0;

        $tier1Agents = Agent::query()
            ->where('referrer_id', $agent->getKey())
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
            ->get(['id', 'referrer_id', 'agt_name', 'login_id', 'agt_status', 'profile_picture']);

        $tier1Ids = $tier1Agents->pluck('id')->all();

        $tier2Agents = $tier1Ids === []
            ? collect()
            : Agent::query()
                ->whereIn('referrer_id', $tier1Ids)
                ->with('referrer:id,agt_name')
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
                ->get(['id', 'referrer_id', 'agt_name', 'login_id', 'agt_status', 'profile_picture']);

        $tier2ByReferrer = $tier2Agents
            ->groupBy('referrer_id')
            ->map(fn ($members) => $members->values());

        $tier1OrderCount = (int) $tier1Agents->sum('completed_orders_count');
        $tier2OrderCount = (int) $tier2Agents->sum('completed_orders_count');
        $tier1SalesTotal = (float) $tier1Agents->sum('completed_orders_total');
        $tier2SalesTotal = (float) $tier2Agents->sum('completed_orders_total');
        $tier1Rate = (float) ($agent->tier1_percentage ?? 7);
        $tier2Rate = (float) ($agent->tier2_percentage ?? 3);
        $tier1BonusPayable = $tier1SalesTotal * ($tier1Rate / 100);
        $tier2BonusPayable = $tier2SalesTotal * ($tier2Rate / 100);
        $totalBonusPayable = $tier1BonusPayable + $tier2BonusPayable;

        return view('admin.agents.show', [
            'agent' => $agent,
            'statusOptions' => Agent::statuses(),
            'tier1Agents' => $tier1Agents,
            'tier2ByReferrer' => $tier2ByReferrer,
            'teamSummary' => [
                'tier1_count' => $tier1Agents->count(),
                'tier2_count' => $tier2Agents->count(),
                'tier1_order_count' => $tier1OrderCount,
                'tier2_order_count' => $tier2OrderCount,
                'tier1_sales_total' => $tier1SalesTotal,
                'tier2_sales_total' => $tier2SalesTotal,
                'tier1_rate' => $tier1Rate,
                'tier2_rate' => $tier2Rate,
                'tier1_bonus_payable' => $tier1BonusPayable,
                'tier2_bonus_payable' => $tier2BonusPayable,
                'total_bonus_payable' => $totalBonusPayable,
            ],
            'summary' => [
                'pending_orders' => $pendingOrders,
                'completed_orders' => $completedOrders,
                'total_sales' => $totalSale,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'ranking' => '#'.(($agent->getKey() % 25) + 1),
                'active_customers' => max(5, (int) round($completedOrders * 1.4)),
                'average_order_value' => $averageOrderValue,
            ],
        ]);
    }

    public function approve(Request $request, Agent $agent): RedirectResponse
    {
        if (! in_array($agent->agt_status, [Agent::StatusPending, Agent::StatusNew], true)) {
            return back()->withErrors(['approval' => 'Only pending registrations can be approved.']);
        }

        if ($agent->agt_status === Agent::StatusPending) {
            $validated = $request->validate([
                'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            ]);

            $agent->forceFill([
                'agt_status' => Agent::StatusActive,
                'commission_percentage' => $validated['commission_percentage'],
                'remember_token' => null,
            ])->save();

            AdminActivity::record(
                request: $request,
                event: 'admin.agent.approved',
                description: "Agent {$agent->login_id} registration approved.",
                adminUser: $request->user('admin'),
                properties: [
                    'page' => 'Agents',
                    'agent_id' => $agent->getKey(),
                    'referrer_id' => $agent->referrer_id,
                    'commission_percentage' => $agent->commission_percentage,
                ],
            );

            $agent->load('referrer');
            $approvalMessage = 'Agent registration approved and activation email sent successfully.';

            try {
                Mail::to($agent->email)->send(new AgentActivated(
                    agent: $agent,
                    referrer: $agent->referrer,
                    loginUrl: route('agent.login'),
                ));
            } catch (Throwable $exception) {
                $approvalMessage = 'Agent registration approved, but the activation email could not be sent.';
                Log::warning('Approved pending agent activation email could not be sent.', [
                    'agent_id' => $agent->getKey(),
                    'email' => $agent->email,
                    'error' => $exception->getMessage(),
                ]);
            }

            return redirect()->route('admin.agents.index')->with('success', $approvalMessage);
        }

        $validated = $request->validate([
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $plainPassword = $validated['password'];
        $loginId = $agent->email;

        if (Agent::query()->where('login_id', $loginId)->whereKeyNot($agent->getKey())->exists()) {
            $loginId = 'AGT'.str_pad((string) $agent->getKey(), 5, '0', STR_PAD_LEFT);
        }

        $agent->forceFill([
            'login_id' => $loginId,
            'password' => $plainPassword,
            'agt_status' => Agent::StatusActive,
            'commission_percentage' => $validated['commission_percentage'],
            'remember_token' => null,
        ])->save();

        AdminActivity::record(
            request: $request,
            event: 'admin.agent.approved',
            description: "Agent {$agent->login_id} registration approved.",
            adminUser: $request->user('admin'),
            properties: [
                'page' => 'Agents',
                'agent_id' => $agent->getKey(),
                'referrer_id' => $agent->referrer_id,
                'commission_percentage' => $agent->commission_percentage,
            ],
        );

        $agent->load('referrer');
        $approvalMessage = 'Agent registration approved and login details emailed successfully.';

        try {
            Mail::to($agent->email)->send(new AgentApproved(
                agent: $agent,
                referrer: $agent->referrer,
                plainPassword: $plainPassword,
                loginUrl: route('agent.login'),
            ));
        } catch (Throwable $exception) {
            $approvalMessage = 'Agent registration approved, but the login email could not be sent.';
            Log::warning('Approved legacy agent login email could not be sent.', [
                'agent_id' => $agent->getKey(),
                'email' => $agent->email,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.agents.index')
            ->with('success', $approvalMessage)
            ->with('agent_login_info', $this->loginInfoPayload($agent, $plainPassword));
    }

    public function update(UpdateAgentRequest $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('profile_picture_file')) {
            $validated['profile_picture'] = $this->storeProfilePicture($request->file('profile_picture_file'), $agent);
        }

        unset($validated['profile_picture_file']);
        $businessSiteIds = $validated['business_site_ids'] ?? [];
        unset($validated['business_site_ids']);

        $agent->update($validated);
        $agent->businessSites()->sync($businessSiteIds);

        AdminActivity::record(
            request: $request,
            event: 'admin.agent.updated',
            description: "Agent {$agent->login_id} updated.",
            adminUser: $request->user('admin'),
            properties: ['page' => 'Agents', 'agent_id' => $agent->getKey(), 'login_id' => $agent->login_id],
        );

        return redirect()
            ->route('admin.agents.index')
            ->with('success', 'Agent updated successfully.');
    }

    public function resetPassword(ResetAgentPasswordRequest $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validated();
        $plainPassword = $validated['password'];

        $agent->forceFill([
            'password' => $plainPassword,
            'remember_token' => null,
        ])->save();

        AdminActivity::record(
            request: $request,
            event: 'admin.agent.password_reset',
            description: "Agent {$agent->login_id} password reset.",
            adminUser: $request->user('admin'),
            properties: ['page' => 'Agents', 'agent_id' => $agent->getKey(), 'login_id' => $agent->login_id],
        );

        return redirect()
            ->route('admin.agents.edit', $agent)
            ->with('success', 'Agent password reset successfully.')
            ->with('agent_login_info', $this->loginInfoPayload($agent, $plainPassword));
    }

    public function resendRegistrationInfo(Request $request, Agent $agent): RedirectResponse
    {
        if ($agent->agt_status !== Agent::StatusActive) {
            return back()->withErrors([
                'resend_registration_info' => 'Registration info can only be sent to an active agent.',
            ]);
        }

        $plainPassword = Str::password(8, true, true, false, false);
        $agent->forceFill([
            'password' => $plainPassword,
            'remember_token' => null,
        ])->save();
        $agent->load('referrer');

        $emailSent = true;
        $message = 'A new 8-character password was created and registration info was emailed successfully.';

        try {
            Mail::to($agent->email)->send(new AgentApproved(
                agent: $agent,
                referrer: $agent->referrer,
                plainPassword: $plainPassword,
                loginUrl: route('agent.login'),
            ));
        } catch (Throwable $exception) {
            $emailSent = false;
            $message = 'A new 8-character password was created, but the registration email could not be sent. Copy the login info below and share it manually.';
            Log::warning('Agent registration info could not be resent.', [
                'agent_id' => $agent->getKey(),
                'email' => $agent->email,
                'error' => $exception->getMessage(),
            ]);
        }

        AdminActivity::record(
            request: $request,
            event: 'admin.agent.registration_info_resent',
            description: "Registration info for agent {$agent->login_id} was resent with a new password.",
            adminUser: $request->user('admin'),
            properties: [
                'page' => 'Agents',
                'agent_id' => $agent->getKey(),
                'login_id' => $agent->login_id,
                'email_sent' => $emailSent,
            ],
        );

        return redirect()
            ->route('admin.agents.edit', $agent)
            ->with('success', $message)
            ->with('agent_login_info', $this->loginInfoPayload($agent, $plainPassword));
    }

    public function updateProfilePicture(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'profile_picture_file' => ['required', 'image', 'max:5120'],
        ]);

        $agent->forceFill([
            'profile_picture' => $this->storeProfilePicture($validated['profile_picture_file'], $agent),
        ])->save();

        AdminActivity::record(
            request: $request,
            event: 'admin.agent.profile_picture_updated',
            description: "Agent {$agent->login_id} profile picture updated.",
            adminUser: $request->user('admin'),
            properties: ['page' => 'Agents', 'agent_id' => $agent->getKey(), 'login_id' => $agent->login_id],
        );

        return redirect()
            ->route('admin.agents.edit', $agent)
            ->with('success', 'Profile picture updated successfully.');
    }

    public function destroy(Request $request, Agent $agent): RedirectResponse
    {
        $request->validate([
            'delete_password' => ['required', 'string'],
        ]);

        $adminUser = $request->user('admin');

        if (! $adminUser || ! Hash::check($request->input('delete_password'), $adminUser->password)) {
            return back()
                ->withInput([
                    'delete_action' => route('admin.agents.destroy', $agent),
                    'delete_agent_name' => $agent->agt_name,
                ])
                ->withErrors(['delete_password' => 'The provided password is incorrect.']);
        }

        $agentLoginId = $agent->login_id;
        $agentId = $agent->getKey();

        $agent->delete();

        AdminActivity::record(
            request: $request,
            event: 'admin.agent.deleted',
            description: "Agent {$agentLoginId} deleted.",
            adminUser: $adminUser,
            properties: ['page' => 'Agents', 'agent_id' => $agentId, 'login_id' => $agentLoginId],
        );

        return redirect()
            ->route('admin.agents.index')
            ->with('success', 'Agent deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function storeProfilePicture(UploadedFile $file, Agent $agent): string
    {
        $directory = public_path('profiles');

        File::ensureDirectoryExists($directory);

        if (! is_dir($directory) || ! is_writable($directory)) {
            throw ValidationException::withMessages([
                'profile_picture_file' => 'Unable to write to public/profiles. Please check folder permission.',
            ]);
        }

        $source = $this->imageResourceFromUpload($file);

        if (! $source) {
            throw ValidationException::withMessages([
                'profile_picture_file' => 'Unable to process the selected image.',
            ]);
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $cropSize = min($sourceWidth, $sourceHeight);
        $sourceX = (int) floor(($sourceWidth - $cropSize) / 2);
        $sourceY = (int) floor(($sourceHeight - $cropSize) / 2);

        $thumb = imagecreatetruecolor(300, 300);
        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefilledrectangle($thumb, 0, 0, 300, 300, $white);

        imagecopyresampled(
            $thumb,
            $source,
            0,
            0,
            $sourceX,
            $sourceY,
            300,
            300,
            $cropSize,
            $cropSize,
        );

        $filename = 'agent-'.$agent->getKey().'-'.Str::uuid()->toString().'.jpg';
        $relativePath = 'profiles/'.$filename;
        $absolutePath = public_path($relativePath);

        if (! imagejpeg($thumb, $absolutePath, 85)) {
            throw ValidationException::withMessages([
                'profile_picture_file' => 'Unable to save the profile picture. Please check folder permission.',
            ]);
        }

        return $relativePath;
    }

    private function imageResourceFromUpload(UploadedFile $file)
    {
        return match ($file->getMimeType()) {
            'image/png' => imagecreatefrompng($file->getRealPath()),
            'image/gif' => imagecreatefromgif($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            default => imagecreatefromjpeg($file->getRealPath()),
        };
    }

    private function formData(): array
    {
        return [
            'states' => DataState::query()->orderBy('name')->get(),
            'statusOptions' => Agent::statuses(),
            'referrerOptions' => Agent::query()
                ->where('agt_status', Agent::StatusActive)
                ->orderBy('agt_name')
                ->get(['id', 'agt_name', 'login_id', 'phone_number', 'email', 'profile_picture']),
            'businessSites' => BusinessSite::query()->orderBy('site_name')->get(['id', 'site_name', 'city']),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function loginInfoPayload(Agent $agent, string $plainPassword): array
    {
        $message = $agent->loginInfoMessage($plainPassword);

        return [
            'agent_name' => $agent->agt_name,
            'login_id' => $agent->login_id,
            'message' => $message,
            'whatsapp_url' => $agent->whatsappUrl($message),
        ];
    }
}
