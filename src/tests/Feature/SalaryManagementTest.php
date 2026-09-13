<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\SalaryPayment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalaryManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_historical_payment_uses_actual_dates_amount_and_prevents_duplicates(): void
    {
        Storage::fake('local');
        Mail::fake();
        $admin = AdminUser::factory()->superAdmin()->create();
        $agent = Agent::factory()->create();
        $this->actingAs($admin, 'admin');
        $data = ['submission_token' => (string) Str::uuid(), 'recipient_type' => 'agent', 'recipient_key' => 'agent:'.$agent->id, 'work_date' => '2026-01-05', 'paid_date' => '2026-01-06', 'site_name' => 'Tapak Januari', 'amount' => '123.45', 'reason' => 'Bayaran lama disemak', 'confirmed_paid' => 1];
        $this->post(route('admin.salary-management.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $payment = SalaryPayment::sole();
        $this->assertSame(12345, $payment->amount_cents);
        $this->assertSame('2026-01-06', $payment->paid_date->toDateString());
        $this->post(route('admin.salary-management.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame(1, SalaryPayment::count());
        $data['submission_token'] = (string) Str::uuid();
        $this->post(route('admin.salary-management.store'), $data)->assertSessionHasErrors('work_date');
        $data['work_date'] = '2025-12-31';
        $this->post(route('admin.salary-management.store'), $data)->assertSessionHasErrors('work_date');
        $this->get(route('admin.salary-management.index', ['month' => '2026-01', 'recipient_type' => 'agent']))->assertOk()->assertViewHas('total', 12345);
        $this->get(route('admin.salary-management.show', $payment))->assertOk()->assertSee('123.45')->assertSee('06/01/2026');
        $this->get(route('admin.salary-management.summary', ['recipient_type' => 'agent', 'recipient_key' => 'agent:'.$agent->id, 'start' => '2026-01-01', 'end' => '2026-01-31']))->assertOk()->assertSee('123.45');
        Mail::assertNothingSent();
    }

    public function test_weekly_closing_overlap_requires_review_and_proof_is_private(): void
    {
        Storage::fake('local');
        Mail::fake();
        $agent = Agent::factory()->create();
        $week = DB::table('weekly_closings')->insertGetId(['week_key' => '2026-W02', 'period_start' => '2026-01-05 00:00:00', 'period_end' => '2026-01-11 23:59:59']);
        DB::table('weekly_closing_agent_summaries')->insert(['weekly_closing_id' => $week, 'agent_id' => $agent->id, 'agent_name' => $agent->agt_name, 'payout_status' => 'paid']);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $data = ['submission_token' => (string) Str::uuid(), 'recipient_type' => 'agent', 'recipient_key' => 'agent:'.$agent->id, 'work_date' => '2026-01-05', 'paid_date' => '2026-01-06', 'site_name' => 'Tapak A', 'amount' => '50.00', 'reason' => 'Gaji berasingan daripada bonus upline', 'confirmed_paid' => 1];
        $this->post(route('admin.salary-management.store'), $data)->assertSessionHasErrors('separate_payment');
        $this->assertSame(0, SalaryPayment::count());
        $this->post(route('admin.salary-management.store'), $data + ['separate_payment' => 1, 'proof' => UploadedFile::fake()->image('proof.jpg')])->assertSessionHasNoErrors();
        $payment = SalaryPayment::sole();
        $this->assertSame(['2026-W02'], $payment->overlap_details);
        $this->get(route('admin.salary-management.proof', $payment))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs(AdminUser::factory()->create(), 'admin')->get(route('admin.salary-management.proof', $payment))->assertForbidden();
        $this->assertSame('paid', DB::table('weekly_closing_agent_summaries')->value('payout_status'));
        Mail::assertNothingSent();
    }

    public function test_staff_and_agent_lists_and_totals_are_separate(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create(['name' => 'STAFF UNIQUE']);
        $agent = Agent::factory()->create(['agt_name' => 'AGENT UNIQUE']);
        $this->actingAs($admin, 'admin');
        foreach (['admin:'.$admin->id => 100, 'agent:'.$agent->id => 200] as $key => $amount) {
            $data = ['submission_token' => (string) Str::uuid(), 'recipient_type' => explode(':', $key)[0], 'recipient_key' => $key, 'work_date' => '2026-01-05', 'paid_date' => '2026-01-06', 'site_name' => 'Site', 'amount' => $amount, 'reason' => 'Historical', 'confirmed_paid' => 1];
            $this->post(route('admin.salary-management.store'), $data)->assertSessionHasNoErrors();
        }
        $this->get(route('admin.salary-management.daily', ['month' => '2026-01', 'recipient_type' => 'admin']))->assertOk()->assertViewHas('total', 10000)->assertViewHas('recipients', fn ($rows) => ! $rows->has('agent:'.$agent->id))->assertDontSee('AGENT UNIQUE');
        $this->get(route('admin.salary-management.daily', ['month' => '2026-01', 'recipient_type' => 'agent']))->assertOk()->assertViewHas('total', 20000)->assertViewHas('recipients', fn ($rows) => ! $rows->has('admin:'.$admin->id));
        $data['recipient_type'] = 'admin';
        $this->post(route('admin.salary-management.store'), $data)->assertSessionHasErrors('recipient_key');
    }

    public function test_superadmin_can_view_all_preview_pages_without_sending_mail(): void
    {
        Mail::fake();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        foreach (['index', 'daily', 'settings', 'payslips'] as $tab) {
            $this->get(route('admin.salary-management.'.$tab))->assertOk()->assertSee('Salary Management');
        }
        Mail::assertNothingSent();
    }

    public function test_salary_access_requires_explicit_permission_and_has_no_write_endpoint(): void
    {
        $staff = AdminUser::factory()->create();
        $this->actingAs($staff, 'admin');
        foreach (['index', 'daily', 'settings', 'payslips'] as $tab) {
            $this->get(route('admin.salary-management.'.$tab))->assertForbidden();
        }
        $role = AdminRole::create(['name' => 'Salary viewer', 'permissions' => ['salary-management.view']]);
        $staff->accessRoles()->attach($role);
        $staff->unsetRelation('accessRoles');
        $this->get(route('admin.salary-management.index'))->assertOk()->assertSee('Belum ada data bayaran');
        $this->post(route('admin.salary-management.index'))->assertForbidden();
    }
}
