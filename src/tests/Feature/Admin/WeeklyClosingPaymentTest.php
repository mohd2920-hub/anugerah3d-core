<?php

namespace Tests\Feature\Admin;

use App\Mail\Agent\WeeklyClosingPaymentMadeMail;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\WeeklyClosing;
use App\Models\WeeklyClosingAgentSummary;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WeeklyClosingPaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_weekly_closing_list_uses_current_agent_bank_details_and_payment_button_under_status(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create([
            'bank_name' => 'Maybank Current',
            'bank_account_name' => 'Aisyah Current Account',
            'bank_account_number' => '123456789012',
        ]);
        $closing = $this->createClosing();
        $summary = $this->createSummary($closing, $agent, [
            'agent_bank_name' => 'Old Snapshot Bank',
            'agent_bank_account_name' => 'Old Snapshot Name',
            'agent_bank_account_number' => '0000000000',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.weekly-closings.show', $closing))
            ->assertOk()
            ->assertSeeText('Maybank Current')
            ->assertSeeText('Aisyah Current Account')
            ->assertSeeText('123456789012')
            ->assertDontSeeText('Old Snapshot Bank')
            ->assertDontSee('>Payment update</th>', false)
            ->assertSeeText('Update payment')
            ->assertSee('data-summary-id="'.$summary->id.'"', false)
            ->assertSee('data-weekly-payment-modal', false)
            ->assertSeeText('Notify agent by email')
            ->assertSee('name="notify_agent" value="1" checked', false);
    }

    public function test_admin_can_complete_payment_and_queue_email_to_current_agent_email(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create([
            'email' => 'current-agent@example.com',
            'bank_name' => 'CIMB',
            'bank_account_name' => 'Current Agent',
            'bank_account_number' => '9988776655',
        ]);
        $closing = $this->createClosing();
        $summary = $this->createSummary($closing, $agent, [
            'agent_email' => 'old-agent@example.com',
        ]);

        Mail::fake();

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.weekly-closings.show', $closing))
            ->patch(route('admin.weekly-closings.payments.update', [$closing, $summary]), [
                'payout_status' => 'paid',
                'notify_agent' => 1,
                'payment_receipt_datetime_text' => '27 Jul 2026, 3:30 PM',
                'payment_reference' => 'BANK-REF-123',
                'payment_notes' => 'Weekly payout completed.',
                'modal_summary_id' => (string) $summary->id,
            ]);

        $response
            ->assertRedirect(route('admin.weekly-closings.show', $closing))
            ->assertSessionHas('success', 'Payment successful and email has been sent to current-agent@example.com.');

        $summary->refresh();

        $this->assertSame('paid', $summary->payout_status);
        $this->assertSame($admin->id, $summary->paid_by_admin_id);
        $this->assertSame('BANK-REF-123', $summary->payment_reference);
        $this->assertSame('27 Jul 2026, 3:30 PM', $summary->payment_receipt_datetime_text);
        $this->assertSame('Weekly payout completed.', $summary->payment_notes);
        $this->assertSame('CIMB', $summary->agent_bank_name);
        $this->assertSame('Current Agent', $summary->agent_bank_account_name);
        $this->assertSame('9988776655', $summary->agent_bank_account_number);
        $this->assertNotNull($summary->paid_at);
        $this->assertNotNull($summary->notified_agent_at);

        Mail::assertQueued(
            WeeklyClosingPaymentMadeMail::class,
            fn (WeeklyClosingPaymentMadeMail $mail): bool => $mail->summaryId === $summary->id
                && $mail->hasTo('current-agent@example.com'),
        );
    }

    public function test_admin_can_complete_payment_without_email_when_notify_is_unchecked(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create(['email' => 'invalid-email']);
        $closing = $this->createClosing();
        $summary = $this->createSummary($closing, $agent, ['agent_email' => null]);

        Mail::fake();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.weekly-closings.show', $closing))
            ->patch(route('admin.weekly-closings.payments.update', [$closing, $summary]), [
                'payout_status' => 'paid',
                'notify_agent' => 0,
                'payment_receipt_datetime_text' => '27 Jul 2026, 4:00 PM',
                'modal_summary_id' => (string) $summary->id,
            ])
            ->assertRedirect(route('admin.weekly-closings.show', $closing))
            ->assertSessionHas('success', 'Payment successful.');

        $summary->refresh();

        $this->assertSame('paid', $summary->payout_status);
        $this->assertNotNull($summary->paid_at);
        $this->assertNull($summary->notified_agent_at);
        Mail::assertNothingQueued();
    }

    public function test_payment_email_has_polished_recipient_content(): void
    {
        $agent = Agent::factory()->create();
        $closing = $this->createClosing();
        $summary = $this->createSummary($closing, $agent, [
            'agent_bank_name' => 'Public Bank',
            'agent_bank_account_name' => 'Aisyah Binti Ahmad',
            'agent_bank_account_number' => '1122334455',
            'tier1_bonus' => 30,
            'tier2_bonus' => 12.50,
            'payment_receipt_datetime_text' => '27 Jul 2026, 3:30 PM',
            'payment_reference' => 'PB-REF-88',
            'payment_notes' => 'Terima kasih atas prestasi anda.',
        ]);

        $mail = new WeeklyClosingPaymentMadeMail($summary->id);

        $mail->assertSeeInHtml('Weekly Closing anda telah dibayar');
        $mail->assertSeeInHtml('RM 42.50');
        $mail->assertSeeInHtml('Public Bank');
        $mail->assertSeeInHtml('1122334455');
        $mail->assertSeeInHtml('PB-REF-88');
        $mail->assertSeeInHtml('Terima kasih atas prestasi anda.');
    }

    public function test_payment_requires_receipt_date_time(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create();
        $closing = $this->createClosing();
        $summary = $this->createSummary($closing, $agent);

        Mail::fake();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.weekly-closings.show', $closing))
            ->patch(route('admin.weekly-closings.payments.update', [$closing, $summary]), [
                'payout_status' => 'paid',
                'notify_agent' => 1,
                'modal_summary_id' => (string) $summary->id,
            ])
            ->assertRedirect(route('admin.weekly-closings.show', $closing))
            ->assertSessionHasErrors('payment_receipt_datetime_text')
            ->assertSessionHasInput('modal_summary_id', (string) $summary->id);

        $this->assertSame('pending', $summary->refresh()->payout_status);
        Mail::assertNothingQueued();
    }

    public function test_payment_requires_a_valid_agent_email(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create(['email' => 'invalid-email']);
        $closing = $this->createClosing();
        $summary = $this->createSummary($closing, $agent, ['agent_email' => null]);

        Mail::fake();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.weekly-closings.show', $closing))
            ->patch(route('admin.weekly-closings.payments.update', [$closing, $summary]), [
                'payout_status' => 'paid',
                'notify_agent' => 1,
                'payment_receipt_datetime_text' => '27 Jul 2026, 3:30 PM',
                'modal_summary_id' => (string) $summary->id,
            ])
            ->assertRedirect(route('admin.weekly-closings.show', $closing))
            ->assertSessionHasErrors('payout_status');

        $this->assertSame('pending', $summary->refresh()->payout_status);
        Mail::assertNothingQueued();
    }

    public function test_summary_must_belong_to_the_weekly_closing_route(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create();
        $closing = $this->createClosing();
        $otherClosing = $this->createClosing('2026-W31');
        $summary = $this->createSummary($otherClosing, $agent);

        Mail::fake();

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.weekly-closings.payments.update', [$closing, $summary]), [
                'payout_status' => 'paid',
                'notify_agent' => 1,
                'payment_receipt_datetime_text' => '27 Jul 2026, 3:30 PM',
            ])
            ->assertNotFound();

        $this->assertSame('pending', $summary->refresh()->payout_status);
        Mail::assertNothingQueued();
    }

    public function test_payment_modal_javascript_source_contains_click_handler(): void
    {
        $script = file_get_contents(resource_path('js/weekly-closing-payment.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString("querySelector('[data-weekly-payment-modal]')", $script);
        $this->assertStringContainsString("addEventListener('click'", $script);
        $this->assertStringContainsString("classList.remove('hidden')", $script);
    }

    private function createClosing(string $weekKey = '2026-W30'): WeeklyClosing
    {
        return WeeklyClosing::query()->create([
            'week_key' => $weekKey,
            'period_start' => '2026-07-20 00:00:00',
            'period_end' => '2026-07-27 00:00:00',
            'status' => 'completed',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createSummary(WeeklyClosing $closing, Agent $agent, array $attributes = []): WeeklyClosingAgentSummary
    {
        return WeeklyClosingAgentSummary::query()->create(array_merge([
            'weekly_closing_id' => $closing->id,
            'agent_id' => $agent->id,
            'agent_name' => $agent->agt_name,
            'agent_email' => $agent->email,
            'total_bonus' => 42.50,
            'payout_status' => 'pending',
        ], $attributes));
    }
}
