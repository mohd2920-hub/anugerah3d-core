<?php

namespace Tests\Feature;

use App\Jobs\SendStaffSalarySlip;
use App\Mail\StaffSalarySlipMail;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\SalaryPayment;
use App\Support\StaffSalaryCalculator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StaffSalaryConfirmationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private BusinessSiteOperation $operation;

    private PosSale $sale;

    private array $data;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $staff = AdminUser::factory()->count(3)->create();
        $agent = Agent::factory()->create();
        $site = BusinessSite::query()->create(['site_name' => 'Tapak ujian', 'city' => 'Klang', 'opened_at' => '2026-01-05 09:00:00']);
        $this->operation = BusinessSiteOperation::query()->create(['business_site_id' => $site->id, 'opened_at' => '2026-01-05 09:00:00', 'closed_at' => '2026-01-05 18:00:00']);
        $session = PosSession::query()->create(['agent_id' => $agent->id, 'business_site_id' => $site->id, 'signed_in_at' => '2026-01-05 09:00:00']);
        $this->sale = PosSale::query()->create(['sale_number' => 'POS-SALARY', 'pos_session_id' => $session->id, 'business_site_id' => $site->id, 'business_site_operation_id' => $this->operation->id, 'recorded_by_agent_id' => $agent->id, 'sales_agent_id' => $agent->id, 'payment_method' => 'cash', 'total_amount' => 1000, 'sold_at' => '2026-01-05 12:00:00']);
        $this->data = ['operation_id' => $this->operation->id, 'staff_ids' => $staff->modelKeys(), 'amounts' => [$staff[0]->id => 100, $staff[1]->id => 100, $staff[2]->id => 200], 'reason' => 'Kehadiran disemak admin', 'expected_version' => 0];
    }

    private function saveDraft(): void
    {
        $snapshot = $this->post(route('admin.salary-management.sessions.preview'), $this->data)->assertOk()->viewData('preview');
        $this->post(route('admin.salary-management.sessions.store'), $this->data + ['expected_hash' => $snapshot['hash']])->assertSessionHasNoErrors();
    }

    private function confirmation(): array
    {
        return ['expected_version' => 1, 'paid_date' => '2026-01-06', 'reference' => 'BANK-TEST', 'confirmed_paid' => 1];
    }

    public function test_confirmation_locks_draft_creates_slips_and_is_idempotent(): void
    {
        Queue::fake();
        $this->saveDraft();
        $url = route('admin.salary-management.confirm', $this->operation->id);
        $this->post($url, $this->confirmation())->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('salary_payments', 3);
        $this->assertSame(40000, (int) SalaryPayment::sum('amount_cents'));
        $this->assertNotNull(DB::table('staff_salary_drafts')->value('confirmed_at'));
        Queue::assertPushed(SendStaffSalarySlip::class, 3);
        $this->post($url, $this->confirmation())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('salary_payments', 3);
        Queue::assertPushed(SendStaffSalarySlip::class, 3);
        $this->get(route('admin.salary-management.sessions', ['operation_id' => $this->operation->id]))->assertOk()->assertSeeText('Draf Dikunci')->assertDontSeeText('Sahkan Bayaran & Hantar Slip');
        $this->post(route('admin.salary-management.sessions.store'), $this->data + ['expected_hash' => str_repeat('a', 64)])->assertSessionHasErrors();
        $payment = SalaryPayment::first();
        $this->get(route('admin.salary-management.show', $payment))->assertOk()->assertSeeText('Gaji Sesi')->assertSeeText('Menunggu penghantaran');
        $this->get(route('admin.salary-management.summary', ['recipient_key' => $payment->recipient_key, 'start' => '2026-01-01', 'end' => '2026-01-31']))->assertOk()->assertSeeText($payment->slipNumber());
        $this->get(route('admin.salary-management.index', ['month' => '2026-01']))->assertOk()->assertViewHas('total', 40000)->assertViewHas('pendingDraftCents', 0);
        $this->assertSame('1000.00', $this->sale->fresh()->total_amount);
    }

    public function test_legacy_weighted_draft_can_still_be_confirmed_without_changing_amounts(): void
    {
        Queue::fake();
        $legacy = app(StaffSalaryCalculator::class)->calculate([
            'operation_id' => $this->operation->id, 'staff_ids' => $this->data['staff_ids'],
            'weights' => array_combine($this->data['staff_ids'], [1, 1, 2]), 'rate' => 40,
        ]);
        DB::table('staff_salary_drafts')->insert([
            'business_site_operation_id' => $this->operation->id, 'snapshot' => json_encode($legacy, JSON_THROW_ON_ERROR),
            'reason' => 'Draf lama', 'version' => 1, 'updated_by' => auth('admin')->id(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->post(route('admin.salary-management.confirm', $this->operation->id), $this->confirmation())->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame([10000, 10000, 20000], SalaryPayment::orderBy('id')->pluck('amount_cents')->all());
        $this->assertSame($legacy, json_decode(DB::table('staff_salary_drafts')->value('snapshot'), true));
    }

    public function test_confirmation_rejects_stale_sales_version_dates_and_missing_acknowledgement(): void
    {
        Queue::fake();
        $this->saveDraft();
        $url = route('admin.salary-management.confirm', $this->operation->id);
        $this->post($url, array_replace($this->confirmation(), ['confirmed_paid' => 0]))->assertSessionHasErrors('confirmed_paid');
        $this->post($url, array_replace($this->confirmation(), ['expected_version' => 2]))->assertSessionHasErrors('expected_version');
        $this->post($url, array_replace($this->confirmation(), ['paid_date' => '2026-01-01']))->assertSessionHasErrors('paid_date');
        $this->sale->update(['total_amount' => 900]);
        $this->post($url, $this->confirmation())->assertSessionHasErrors('expected_version');
        $this->assertDatabaseCount('salary_payments', 0);
        $this->assertNull(DB::table('staff_salary_drafts')->value('confirmed_at'));
        Queue::assertNothingPushed();
    }

    public function test_staff_slip_email_contains_only_own_payments_and_job_does_not_send_twice(): void
    {
        Queue::fake();
        $this->saveDraft();
        $this->post(route('admin.salary-management.confirm', $this->operation->id), $this->confirmation())->assertSessionHasNoErrors();
        $payment = SalaryPayment::first();
        $job = new SendStaffSalarySlip($payment->id);
        $job->handle();
        $job->handle();
        Mail::assertSent(StaffSalarySlipMail::class, 1);
        Mail::assertSent(StaffSalarySlipMail::class, fn ($mail) => $mail->hasTo($payment->recipient_email));
        $mail = new StaffSalarySlipMail($payment);
        $html = $mail->render();
        $this->assertStringContainsString('Ringkasan Mingguan', $html);
        $this->assertStringContainsString('Ringkasan Bulanan', $html);
        $this->assertStringContainsString($payment->slipNumber(), $html);
        foreach (SalaryPayment::where('id', '!=', $payment->id)->get() as $other) {
            $this->assertStringNotContainsString($other->slipNumber(), $html);
            $this->assertStringNotContainsString($other->recipient_email, $html);
        }
        $this->assertCount(1, $mail->attachments());
        $this->assertNotNull($payment->fresh()->email_sent_at);
    }

    public function test_failed_email_can_retry_without_new_payment_and_unauthorised_staff_cannot_confirm(): void
    {
        Queue::fake();
        $this->saveDraft();
        $this->post(route('admin.salary-management.confirm', $this->operation->id), $this->confirmation())->assertSessionHasNoErrors();
        $payment = SalaryPayment::first();
        (new SendStaffSalarySlip($payment->id))->failed(new \RuntimeException('Test mail failure'));
        $this->get(route('admin.salary-management.show', $payment))->assertOk()->assertSeeText('E-mel gagal');
        $this->post(route('admin.salary-management.email', $payment))->assertRedirect();
        Queue::assertPushed(SendStaffSalarySlip::class, 4);
        $this->assertDatabaseCount('salary_payments', 3);
        $this->actingAs(AdminUser::factory()->create(), 'admin');
        $this->post(route('admin.salary-management.confirm', $this->operation->id), $this->confirmation())->assertForbidden();
        $this->post(route('admin.salary-management.email', $payment))->assertForbidden();
        $this->get(route('admin.salary-management.show', $payment))->assertForbidden();
    }

    public function test_draft_permission_does_not_grant_confirmation_and_stale_staff_email_is_rejected(): void
    {
        Queue::fake();
        $this->saveDraft();
        AdminUser::whereKey($this->data['staff_ids'][0])->update(['email' => 'updated@example.com']);
        $this->post(route('admin.salary-management.confirm', $this->operation->id), $this->confirmation())->assertSessionHasErrors('expected_version');
        $this->assertDatabaseCount('salary_payments', 0);
        $role = AdminRole::create(['name' => 'Salary drafter', 'permissions' => ['salary-management.view', 'salary-management.draft']]);
        $drafter = AdminUser::factory()->create();
        $drafter->accessRoles()->attach($role);
        $this->actingAs($drafter, 'admin');
        $this->get(route('admin.salary-management.sessions', ['operation_id' => $this->operation->id]))->assertOk()->assertDontSeeText('Sahkan Bayaran & Hantar Slip');
        $this->post(route('admin.salary-management.confirm', $this->operation->id), $this->confirmation())->assertForbidden();
        Queue::assertNothingPushed();
    }
}
