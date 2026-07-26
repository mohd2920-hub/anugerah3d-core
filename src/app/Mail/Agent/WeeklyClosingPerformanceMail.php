<?php

namespace App\Mail\Agent;

use App\Models\WeeklyClosingAgentSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class WeeklyClosingPerformanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $summaryId,
        public string $weekKey,
    ) {}

    public function envelope(): Envelope
    {
        $summary = WeeklyClosingAgentSummary::query()->findOrFail($this->summaryId);
        $firstName = explode(' ', trim((string) $summary->agent_name))[0] ?: 'Agent';

        return new Envelope(
            subject: '['.$firstName."] Weekly closing performance summary ({$this->weekKey})",
        );
    }

    public function content(): Content
    {
        $summary = WeeklyClosingAgentSummary::query()
            ->with([
                'closing:id,week_key,period_start,period_end',
                'agent:id,agt_name,email,login_id,profile_picture,referrer_id',
                'agent.referrer:id,agt_name,email,phone_number,login_id,profile_picture',
            ])
            ->findOrFail($this->summaryId);

        $payload = [
            'brand' => [
                'logo_url' => asset('images/anugerah3d-logo.png'),
            ],
            'period_label' => $summary->closing->period_start->format('d M Y').' - '.$summary->closing->period_end->subSecond()->format('d M Y'),
            'week_key' => $summary->closing->week_key,
            'generated_at' => now('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
            'agent' => [
                'name' => (string) $summary->agent_name,
                'email' => (string) ($summary->agent?->email ?? $summary->agent_email),
                'login_id' => (string) ($summary->agent?->login_id ?? ''),
                'thumb_url' => $this->resolveImageUrl($summary->agent?->profile_picture),
            ],
            'bonus' => [
                'tier1_bonus' => (float) $summary->tier1_bonus,
                'tier2_bonus' => (float) $summary->tier2_bonus,
                'total_bonus' => (float) $summary->total_bonus,
                'payout_status' => str($summary->payout_status)->headline()->value(),
            ],
            'personal' => [
                'orders_count' => (int) $summary->personal_orders_count,
                'orders_amount' => (float) $summary->personal_order_amount,
            ],
            'team' => [
                'new_agents_registered' => (int) $summary->new_agents_registered,
                'tier1_agents_total' => (int) $summary->tier1_agents_total,
                'tier2_agents_total' => (int) $summary->tier2_agents_total,
                'tier1_orders_count' => (int) $summary->tier1_orders_count,
                'tier2_orders_count' => (int) $summary->tier2_orders_count,
                'tier1_orders_amount' => (float) $summary->tier1_orders_amount,
                'tier2_orders_amount' => (float) $summary->tier2_orders_amount,
            ],
            'pos' => [
                'sales_count' => (int) $summary->pos_sales_count,
                'sales_amount' => (float) $summary->pos_sales_amount,
            ],
            'referrer' => [
                'exists' => $summary->agent?->referrer !== null || $summary->referrer_name !== null || $summary->referrer_email !== null || $summary->referrer_phone !== null,
                'name' => (string) ($summary->agent?->referrer?->agt_name ?? $summary->referrer_name ?? ''),
                'email' => (string) ($summary->agent?->referrer?->email ?? $summary->referrer_email ?? ''),
                'phone' => (string) ($summary->agent?->referrer?->phone_number ?? $summary->referrer_phone ?? ''),
                'login_id' => (string) ($summary->agent?->referrer?->login_id ?? ''),
                'thumb_url' => $this->resolveImageUrl($summary->agent?->referrer?->profile_picture),
                'whatsapp_url' => $summary->agent?->referrer?->whatsappUrl('Hi '.$summary->agent?->referrer?->agt_name.', saya perlukan bantuan tentang weekly closing.'),
            ],
        ];

        return new Content(
            markdown: 'mail.agent.weekly-closing-performance',
            with: ['data' => $payload],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (File::exists(public_path($path))) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }
}
