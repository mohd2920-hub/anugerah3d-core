<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\SalaryPayment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffSalaryDraftTest extends TestCase
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
        $this->data = ['operation_id' => $this->operation->id, 'staff_ids' => $staff->modelKeys(), 'weights' => [$staff[0]->id => 1, $staff[1]->id => 1, $staff[2]->id => 2], 'rate' => 40, 'reason' => 'Kehadiran disemak admin', 'expected_version' => 0];
    }

    public function test_preview_and_versioned_save_only_create_a_draft(): void
    {
        $void = $this->sale->replicate();
        $void->sale_number = 'POS-VOID';
        $void->voided_at = now();
        $void->save();
        $response = $this->post(route('admin.salary-management.sessions.preview'), $this->data)->assertOk();
        $snapshot = $response->viewData('preview');
        $this->assertSame(100000, $snapshot['net_cents']);
        $this->assertSame(40000, $snapshot['pool_cents']);
        $this->assertSame([10000, 10000, 20000], array_column($snapshot['staff'], 'amount_cents'));
        $this->assertSame($this->data['staff_ids'], array_column($snapshot['staff'], 'staff_id'));
        $this->assertDatabaseCount('staff_salary_drafts', 0);
        $payload = $this->data + ['expected_hash' => $snapshot['hash']];
        $this->post(route('admin.salary-management.sessions.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('staff_salary_drafts', 1);
        $this->get(route('admin.salary-management.sessions', ['operation_id' => $this->operation->id]))->assertOk()->assertViewHas('saved');
        $this->post(route('admin.salary-management.sessions.store'), $payload)->assertSessionHasErrors('operation_id');
        $payload['expected_version'] = 1;
        $this->post(route('admin.salary-management.sessions.store'), $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('staff_salary_drafts')->value('version'));
        $this->assertDatabaseCount('salary_payments', 0);
        $this->assertSame('1000.00', $this->sale->fresh()->total_amount);
        Mail::assertNothingSent();
    }

    public function test_rounding_allocates_every_cent_and_changed_sales_require_new_preview(): void
    {
        $this->sale->update(['total_amount' => '0.05']);
        $this->data['weights'] = array_fill_keys($this->data['staff_ids'], 1);
        $snapshot = $this->post(route('admin.salary-management.sessions.preview'), $this->data)->assertOk()->viewData('preview');
        $this->assertSame([1, 1, 0], array_column($snapshot['staff'], 'amount_cents'));
        $this->sale->update(['total_amount' => '10.00']);
        $this->post(route('admin.salary-management.sessions.store'), $this->data + ['expected_hash' => $snapshot['hash']])->assertSessionHasErrors('operation_id');
        $this->assertDatabaseCount('staff_salary_drafts', 0);
    }

    public function test_requires_selected_staff_valid_weights_closed_session_and_permission(): void
    {
        $data = $this->data;
        $data['staff_ids'] = [];
        $this->post(route('admin.salary-management.sessions.preview'), $data)->assertSessionHasErrors('staff_ids');
        $data = $this->data;
        unset($data['weights'][$data['staff_ids'][0]]);
        $this->post(route('admin.salary-management.sessions.preview'), $data)->assertSessionHasErrors('weights');
        $this->operation->update(['closed_at' => null]);
        $this->post(route('admin.salary-management.sessions.preview'), $this->data)->assertSessionHasErrors('operation_id');
        $this->actingAs(AdminUser::factory()->create(), 'admin');
        $this->get(route('admin.salary-management.sessions'))->assertForbidden();
        $this->post(route('admin.salary-management.sessions.preview'), $this->data)->assertForbidden();
        $this->post(route('admin.salary-management.sessions.store'), $this->data)->assertForbidden();
    }

    public function test_existing_staff_payment_blocks_duplicate_salary_draft(): void
    {
        SalaryPayment::query()->create(['submission_token' => (string) Str::uuid(), 'recipient_key' => 'admin:'.$this->data['staff_ids'][0], 'recipient_name' => 'Staf', 'work_date' => '2026-01-05', 'paid_date' => '2026-01-05', 'site_name' => 'Tapak ujian', 'duplicate_key' => hash('sha256', 'existing'), 'amount_cents' => 10000, 'reason' => 'Sudah dibayar', 'created_by' => auth('admin')->id()]);
        $snapshot = $this->post(route('admin.salary-management.sessions.preview'), $this->data)->assertOk()->viewData('preview');
        $this->assertCount(1, $snapshot['overlaps']);
        $this->post(route('admin.salary-management.sessions.store'), $this->data + ['expected_hash' => $snapshot['hash']])->assertSessionHasErrors('staff_ids');
        $this->assertDatabaseCount('staff_salary_drafts', 0);
    }
}
