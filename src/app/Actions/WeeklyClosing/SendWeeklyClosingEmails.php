<?php

namespace App\Actions\WeeklyClosing;

use App\Mail\Admin\WeeklyClosingSummaryMail;
use App\Mail\Agent\WeeklyClosingPerformanceMail;
use App\Models\WeeklyClosing;
use Illuminate\Support\Facades\Mail;

class SendWeeklyClosingEmails
{
    /**
     * @return array{agent_emails_sent: int, admin_emails_sent: int}
     */
    public function handle(WeeklyClosing $closing): array
    {
        $closing->load([
            'agentSummaries.agent:id,agt_name,email,referrer_id',
            'agentSummaries.agent.referrer:id,agt_name,email,phone_number,login_id,profile_picture',
        ]);

        $agentEmailsSent = 0;

        foreach ($closing->agentSummaries as $summary) {
            $recipient = (string) ($summary->agent?->email ?? $summary->agent_email ?? '');
            if ($recipient === '') {
                continue;
            }

            Mail::to($recipient)->send(new WeeklyClosingPerformanceMail(
                summaryId: $summary->id,
                weekKey: $closing->week_key,
            ));
            $agentEmailsSent++;
        }

        Mail::to('anugerah3d@gmail.com')->send(new WeeklyClosingSummaryMail(
            closingId: $closing->id,
        ));

        $closing->forceFill(['email_dispatched_at' => now()])->save();

        return [
            'agent_emails_sent' => $agentEmailsSent,
            'admin_emails_sent' => 1,
        ];
    }
}
