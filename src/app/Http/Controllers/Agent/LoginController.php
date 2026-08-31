<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\LoginRequest;
use App\Models\Agent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('agent.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $authenticated = false;

        foreach ($this->loginCandidates($request) as $agent) {
            $authenticated = Auth::guard('agent')->attempt([
                $agent->getAuthIdentifierName() => $agent->getAuthIdentifier(),
                'password' => $request->password(),
                'agt_status' => Agent::StatusActive,
            ], $request->remember());

            if ($authenticated) {
                break;
            }
        }

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'login_id' => 'The login ID / phone number or password is incorrect, or this account is not active.',
            ])->redirectTo(route('agent.login'));
        }

        $request->session()->regenerate();

        /** @var Agent|null $agent */
        $agent = Auth::guard('agent')->user();
        $agent?->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

        return redirect()->intended(route('agent.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('agent')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('agent.login');
    }

    /** @return Collection<int, Agent> */
    private function loginCandidates(LoginRequest $request): Collection
    {
        $agents = Agent::query()
            ->where('agt_status', Agent::StatusActive)
            ->where('login_id', $request->identifier())
            ->get();

        $phoneCandidates = $request->phoneCandidates();

        if ($phoneCandidates !== []) {
            $normalizedPhone = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone_number, ''), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '')";

            $agents = $agents->merge(
                Agent::query()
                    ->where('agt_status', Agent::StatusActive)
                    ->whereIn(DB::raw($normalizedPhone), $phoneCandidates)
                    ->get(),
            );
        }

        return $agents->unique(fn (Agent $agent): mixed => $agent->getKey())->values();
    }
}
