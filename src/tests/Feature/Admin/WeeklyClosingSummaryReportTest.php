<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\Order;
use App\Models\WeeklyClosing;
use App\Models\WeeklyClosingAgentSummary;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WeeklyClosingSummaryReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_view_custom_tier_breakdown_without_changing_payment_status(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-02 10:00:00', 'Asia/Kuala_Lumpur'));
        $admin = AdminUser::factory()->create();
        $paid = $this->summary('2026-W31', '2026-08-03', 70, 30, 'paid');
        $pending = $this->summary('2026-W32', '2026-08-10', 14, 6, 'pending');
        $this->summary('2026-W36', '2026-09-07', 700, 300, 'pending');

        $response = $this->actingAs($admin, 'admin')->get(route('admin.weekly-closings.index', [
            'report_period' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ]));

        $response->assertOk()->assertSeeText('Tier 1 and Tier 2 payment summary')
            ->assertViewHas('payoutReport', fn (array $report): bool => $report['historical']['tier1_total'] === 84.0
                && $report['historical']['tier2_total'] === 36.0
                && $report['historical']['payable_total'] === 120.0
                && $report['historical']['paid_total'] === 100.0
                && $report['historical']['pending_total'] === 20.0
                && $report['historical']['payout_records'] === 2);

        $this->assertSame('paid', $paid->refresh()->payout_status);
        $this->assertSame('pending', $pending->refresh()->payout_status);
    }

    public function test_current_week_projection_matches_tier_rates_and_creates_no_closing(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-02 10:00:00', 'Asia/Kuala_Lumpur'));
        $admin = AdminUser::factory()->create();
        $tier2Payee = Agent::factory()->create(['tier2_percentage' => 3]);
        $tier1Payee = Agent::factory()->create(['referrer_id' => $tier2Payee->id, 'tier1_percentage' => 7]);
        $buyer = Agent::factory()->create(['referrer_id' => $tier1Payee->id]);

        $this->order($buyer, 'CURRENT', '2026-09-02 09:00:00', 100, Order::StatusProcessing);
        $this->order($buyer, 'CANCELLED', '2026-09-02 09:30:00', 500, Order::StatusCancelled);
        $this->order($buyer, 'OUTSIDE', '2026-08-30 09:00:00', 500, Order::StatusCompleted);

        $this->actingAs($admin, 'admin')->get(route('admin.weekly-closings.index', ['report_period' => 'week']))
            ->assertOk()
            ->assertViewHas('payoutReport', fn (array $report): bool => $report['current_week']['period_label'] === '31 Aug 2026 - 06 Sep 2026'
                && $report['current_week']['tier1_orders'] === 1
                && $report['current_week']['tier2_orders'] === 1
                && $report['current_week']['tier1_bonus'] === 7.0
                && $report['current_week']['tier2_bonus'] === 3.0
                && $report['current_week']['estimated_total'] === 10.0
                && $report['current_week']['estimated_payees'] === 2);

        $this->assertDatabaseCount('weekly_closings', 0);
        $this->assertDatabaseCount('weekly_closing_agent_summaries', 0);
    }

    public function test_custom_report_rejects_reversed_dates(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin')->get(route('admin.weekly-closings.index', [
            'report_period' => 'custom',
            'start_date' => '2026-08-31',
            'end_date' => '2026-08-01',
        ]))->assertSessionHasErrors('end_date');
    }

    private function summary(string $weekKey, string $periodEnd, float $tier1, float $tier2, string $status): WeeklyClosingAgentSummary
    {
        $agent = Agent::factory()->create();
        $end = CarbonImmutable::parse($periodEnd, 'Asia/Kuala_Lumpur');
        $closing = WeeklyClosing::query()->create([
            'week_key' => $weekKey,
            'period_start' => $end->subWeek(),
            'period_end' => $end,
            'status' => 'completed',
            'closed_at' => $end,
        ]);

        return WeeklyClosingAgentSummary::query()->create([
            'weekly_closing_id' => $closing->id,
            'agent_id' => $agent->id,
            'agent_name' => $agent->agt_name,
            'agent_email' => $agent->email,
            'tier1_bonus' => $tier1,
            'tier2_bonus' => $tier2,
            'total_bonus' => $tier1 + $tier2,
            'payout_status' => $status,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);
    }

    private function order(Agent $agent, string $number, string $placedAt, float $amount, string $status): void
    {
        Order::query()->create([
            'idempotency_key' => (string) str()->uuid(),
            'order_number' => $number,
            'agent_id' => $agent->id,
            'status' => $status,
            'fulfilment_method' => 'self_pickup',
            'recipient_name' => 'Projection Recipient',
            'phone_number' => '0123456789',
            'payment_method' => 'bank_transfer',
            'payment_status' => Order::PaymentStatusPaid,
            'subtotal' => $amount,
            'delivery_fee' => 0,
            'total_amount' => $amount,
            'total_units' => 1,
            'placed_at' => $placedAt,
        ]);
    }
}
