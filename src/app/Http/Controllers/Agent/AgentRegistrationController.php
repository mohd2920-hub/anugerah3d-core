<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\StoreAgentRegistrationRequest;
use App\Mail\AgentRegistrationAdminNotification;
use App\Mail\AgentRegistrationReceived;
use App\Models\Agent;
use App\Models\DataState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AgentRegistrationController extends Controller
{
    public function create(Agent $referrer): RedirectResponse
    {
        abort_unless($referrer->agt_status === Agent::StatusActive, 404);

        return redirect()->away($referrer->referralUrl());
    }

    public function store(StoreAgentRegistrationRequest $request, Agent $referrer): RedirectResponse
    {
        abort_unless($referrer->agt_status === Agent::StatusActive, 404);

        return $this->register($request, $referrer, $referrer->referralUrl());
    }

    public function redirectLegacyReferral(string $referralCode): RedirectResponse
    {
        return redirect()->to($this->activeReferrer($referralCode)->referralUrl(), 301);
    }

    public function createFromReferral(string $referralCode): View
    {
        $referrer = $this->activeReferrer($referralCode);

        return view('public.join-agent', [
            'referrer' => $referrer,
            'states' => DataState::query()->orderBy('name')->get(['name']),
            'submissionUrl' => route('public.join-agent.store', ['referralCode' => $referrer->referral_code]),
            'loginAvailabilityUrl' => route('public.join-agent.login-id-availability', ['referralCode' => $referrer->referral_code]),
        ]);
    }

    public function storeFromReferral(StoreAgentRegistrationRequest $request, string $referralCode): RedirectResponse
    {
        $referrer = $this->activeReferrer($referralCode);

        return $this->register(
            $request,
            $referrer,
            route('public.join-agent.create', ['referralCode' => $referrer->referral_code]),
        );
    }

    public function checkLoginIdAvailability(Request $request, string $referralCode): JsonResponse
    {
        $this->activeReferrer($referralCode);
        $loginId = $this->normalizeLoginId((string) $request->query('login_id', ''));

        $validator = Validator::make(
            ['login_id' => $loginId],
            ['login_id' => ['required', 'string', 'max:100', Rule::unique((new Agent)->getTable(), 'login_id')]],
            [
                'login_id.required' => 'Please enter a login ID.',
                'login_id.unique' => 'This login ID is already taken.',
            ],
        );

        if ($validator->fails()) {
            return response()->json([
                'available' => false,
                'login_id' => $loginId,
                'message' => $validator->errors()->first('login_id'),
            ], 422);
        }

        return response()->json([
            'available' => true,
            'login_id' => $loginId,
            'message' => 'This login ID is available.',
        ]);
    }

    private function activeReferrer(string $referralCode): Agent
    {
        return Agent::query()
            ->where('referral_code', strtoupper($referralCode))
            ->where('agt_status', Agent::StatusActive)
            ->firstOrFail();
    }

    private function register(StoreAgentRegistrationRequest $request, Agent $referrer, string $returnUrl): RedirectResponse
    {
        $validated = $request->validated();
        $profilePicture = $validated['profile_picture_file'];
        unset($validated['profile_picture_file']);
        $plainPassword = Str::password(8, true, true, false, false);

        $agent = DB::transaction(function () use ($validated, $plainPassword, $profilePicture, $referrer): Agent {
            $agent = Agent::query()->create($this->registrationAttributes(
                validated: $validated,
                plainPassword: $plainPassword,
                referrer: $referrer,
            ));

            $agent->forceFill([
                'profile_picture' => $this->storeProfilePicture($profilePicture, $agent),
            ])->save();

            return $agent;
        });

        $this->sendApplicantEmail($agent, $referrer, $plainPassword);
        $this->sendAdminNotification($agent, $referrer);

        return redirect($returnUrl)->with('registration_success', true);
    }

    private function registrationAttributes(array $validated, string $plainPassword, Agent $referrer): array
    {
        $attributes = [
                ...$validated,
                'referrer_id' => $referrer->getKey(),
                'login_id' => $validated['login_id'],
                'password' => $plainPassword,
                'agt_status' => Agent::StatusPending,
                'discount_percentage' => 0,
                'total_sale' => 0,
        ];

        if (Schema::hasColumn('usr_agent', 'commission_percentage')) {
            $attributes['commission_percentage'] = null;
        }

        if (Schema::hasColumn('usr_agent', 'tier1_percentage')) {
            $attributes['tier1_percentage'] = 7;
        }

        if (Schema::hasColumn('usr_agent', 'tier2_percentage')) {
            $attributes['tier2_percentage'] = 3;
        }

        return $attributes;
    }

    private function storeProfilePicture(UploadedFile $file, Agent $agent): string
    {
        $directory = public_path('profiles');
        File::ensureDirectoryExists($directory);

        if (! is_dir($directory) || ! is_writable($directory)) {
            throw ValidationException::withMessages([
                'profile_picture_file' => 'Unable to save the profile picture. Please contact support.',
            ]);
        }

        $source = match ($file->getMimeType()) {
            'image/png' => imagecreatefrompng($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            default => imagecreatefromjpeg($file->getRealPath()),
        };

        if (! $source) {
            throw ValidationException::withMessages([
                'profile_picture_file' => 'Unable to process the selected picture.',
            ]);
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $cropSize = min($sourceWidth, $sourceHeight);
        $thumb = imagecreatetruecolor(300, 300);
        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefilledrectangle($thumb, 0, 0, 300, 300, $white);
        imagecopyresampled(
            $thumb,
            $source,
            0,
            0,
            (int) floor(($sourceWidth - $cropSize) / 2),
            (int) floor(($sourceHeight - $cropSize) / 2),
            300,
            300,
            $cropSize,
            $cropSize,
        );

        $relativePath = 'profiles/agent-'.$agent->getKey().'-'.Str::uuid()->toString().'.jpg';

        if (! imagejpeg($thumb, public_path($relativePath), 85)) {
            imagedestroy($source);
            imagedestroy($thumb);

            throw ValidationException::withMessages([
                'profile_picture_file' => 'Unable to save the profile picture. Please contact support.',
            ]);
        }

        imagedestroy($source);
        imagedestroy($thumb);

        return $relativePath;
    }

    private function normalizeLoginId(string $value): string
    {
        return Str::lower(trim($value));
    }

    private function sendApplicantEmail(Agent $agent, Agent $referrer, string $plainPassword): void
    {
        try {
            Mail::to($agent->email)->send(new AgentRegistrationReceived(
                agent: $agent,
                referrer: $referrer,
                plainPassword: $plainPassword,
                loginUrl: route('agent.login'),
            ));
        } catch (Throwable $exception) {
            Log::warning('Pending agent registration email could not be sent.', [
                'agent_id' => $agent->getKey(),
                'email' => $agent->email,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendAdminNotification(Agent $agent, Agent $referrer): void
    {
        $adminEmail = (string) config('agent_registration.admin_email', 'anugerah3d@gmail.com');

        try {
            Mail::to($adminEmail)->send(new AgentRegistrationAdminNotification(
                agent: $agent,
                referrer: $referrer,
                reviewUrl: route('admin.agents.show', $agent),
            ));
        } catch (Throwable $exception) {
            Log::warning('Admin notification for pending agent registration could not be sent.', [
                'agent_id' => $agent->getKey(),
                'admin_email' => $adminEmail,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
