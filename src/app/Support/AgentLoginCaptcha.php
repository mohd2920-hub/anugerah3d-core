<?php

namespace App\Support;

use Illuminate\Http\Request;

class AgentLoginCaptcha
{
    private const AnswerKey = 'agent_login_captcha_answer';

    private const ChallengeKey = 'agent_login_captcha_challenge';

    private const FailureKey = 'agent_login_failed_attempts';

    private const MaximumFailures = 3;

    public function challenge(Request $request): string
    {
        if (! $request->session()->has(self::AnswerKey) || ! $request->session()->has(self::ChallengeKey)) {
            $this->generate($request);
        }

        return (string) $request->session()->get(self::ChallengeKey);
    }

    public function registerFailure(Request $request): bool
    {
        $failureCount = (int) $request->session()->get(self::FailureKey, 0) + 1;

        if ($failureCount >= self::MaximumFailures) {
            $this->generate($request);
            $request->session()->put(self::FailureKey, 0);
            $request->session()->flash('captcha_regenerated', true);

            return true;
        }

        $request->session()->put(self::FailureKey, $failureCount);

        return false;
    }

    public function clear(Request $request): void
    {
        $request->session()->forget([
            self::AnswerKey,
            self::ChallengeKey,
            self::FailureKey,
        ]);
    }

    private function generate(Request $request): void
    {
        $firstNumber = random_int(2, 9);
        $secondNumber = random_int(1, 9);

        $request->session()->put([
            self::AnswerKey => $firstNumber + $secondNumber,
            self::ChallengeKey => "{$firstNumber} + {$secondNumber}",
        ]);
    }
}
