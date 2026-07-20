<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetAgentPasswordRequest;
use App\Http\Requests\Admin\StoreAgentRequest;
use App\Http\Requests\Admin\UpdateAgentRequest;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Models\DataState;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AgentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $agents = Agent::query()
            ->when($search !== '', fn (Builder $query): Builder => $query->search($search))
            ->when($status !== '' && array_key_exists($status, Agent::statuses()), fn (Builder $query): Builder => $query->where('agt_status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.agents.index', [
            'agents' => $agents,
            'search' => $search,
            'selectedStatus' => $status,
            'statusOptions' => Agent::statuses(),
            'businessSites' => BusinessSite::query()->orderBy('site_name')->get(['id', 'site_name', 'city']),
            'loginInfo' => session('agent_login_info'),
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

        return redirect()
            ->route('admin.agents.index')
            ->with('success', 'Agent created successfully.')
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
        $totalSale = (float) $agent->total_sale;
        $pendingOrders = max(1, (int) round($totalSale / 500));
        $completedOrders = max(3, (int) round($totalSale / 320));
        $commissionRate = 5.0;
        $commissionAmount = $totalSale * ($commissionRate / 100);
        $averageOrderValue = $completedOrders > 0 ? ($totalSale / $completedOrders) : 0;

        return view('admin.agents.show', [
            'agent' => $agent,
            'statusOptions' => Agent::statuses(),
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
