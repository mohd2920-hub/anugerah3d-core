<?php

namespace Tests\Feature\Admin;

use App\Actions\BusinessSites\CorrectOperationClosure;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OperationClosureCorrectionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function operation(bool $open = false): BusinessSiteOperation
    {
        $site = BusinessSite::create(['site_name' => 'Correction Site', 'city' => 'Klang', 'opened_at' => $open ? '2026-09-11 18:00:00' : null]);

        return BusinessSiteOperation::create(['business_site_id' => $site->id, 'opened_at' => '2026-09-11 18:00:00', 'closed_at' => $open ? null : '2026-09-13 00:00:00']);
    }

    private function data(): array
    {
        return ['closed_at' => '2026-09-11 23:00:00', 'reason' => 'Forgot to close session'];
    }

    public function test_staff_with_operate_permission_cannot_correct_sessions(): void
    {
        $staff = AdminUser::factory()->create();
        $role = AdminRole::factory()->create(['permissions' => ['business-sites.view', 'business-sites.operate']]);
        $staff->accessRoles()->attach($role);
        $operation = $this->operation();
        $this->actingAs($staff, 'admin')->get(route('admin.business-site-operations.show', $operation))->assertOk()->assertDontSee('Semak Pembetulan');
        $this->post(route('admin.business-site-operations.closure-preview', $operation), $this->data())->assertForbidden();
        $this->post(route('admin.business-site-operations.closure-store', $operation), ['token' => 'fake'])->assertForbidden();
        $this->assertDatabaseCount('operation_closure_corrections', 0);
    }

    public function test_superadmin_can_review_and_close_an_open_session_once(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $operation = $this->operation(true);
        $response = $this->actingAs($admin, 'admin')->post(route('admin.business-site-operations.closure-preview', $operation), $this->data())->assertOk();
        $this->assertNull($operation->fresh()->closed_at);
        $token = $response->viewData('token');
        $this->post(route('admin.business-site-operations.closure-store', $operation), ['token' => $token])->assertRedirect(route('admin.business-site-operations.show', $operation));
        $this->assertSame('2026-09-11 23:00:00', $operation->fresh()->closed_at->format('Y-m-d H:i:s'));
        $this->assertNull($operation->businessSite->fresh()->opened_at);
        $this->assertDatabaseHas('operation_closure_corrections', ['admin_id' => $admin->id, 'reason' => $this->data()['reason']]);
        $this->post(route('admin.business-site-operations.closure-store', $operation), ['token' => $token])->assertStatus(419);
    }

    public function test_late_sales_move_to_replacement_without_changing_stock_or_amounts(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $operation = $this->operation();
        $agent = Agent::factory()->create();
        $session = PosSession::create(['business_site_id' => $operation->business_site_id, 'agent_id' => $agent->id, 'signed_in_at' => '2026-09-12 18:00:00', 'signed_out_at' => '2026-09-12 23:00:00']);
        $sale = PosSale::create(['business_site_operation_id' => $operation->id, 'business_site_id' => $operation->business_site_id, 'pos_session_id' => $session->id, 'recorded_by_agent_id' => $agent->id, 'sales_agent_id' => $agent->id, 'sale_number' => 'POS-CLOSURE', 'sold_at' => '2026-09-12 20:00:00', 'payment_method' => 'cash', 'total_amount' => 22]);
        PosSession::create(['business_site_id' => $operation->business_site_id, 'agent_id' => $agent->id, 'signed_in_at' => '2026-09-12 19:00:00', 'signed_out_at' => '2026-09-12 23:00:00']);
        $before = $sale->getAttributes();
        $attendanceBefore = $session->fresh()->getAttributes();
        $this->actingAs($admin, 'admin')->post(route('admin.business-site-operations.closure-preview', $operation), $this->data())->assertSessionHasErrors('next_opened_at');
        $data = $this->data() + ['next_opened_at' => '2026-09-12 18:00:00', 'next_report_date' => '2026-09-12'];
        $this->from(route('admin.business-site-operations.show', $operation))->post(route('admin.business-site-operations.closure-preview', $operation), array_replace($data, ['next_opened_at' => '2026-09-12 21:00:00']))->assertSessionHasErrors(['next_opened_at' => 'Ada jualan antara masa tutup sebenar dan masa buka sesi pengganti. Rekod berikut belum termasuk dalam mana-mana sesi:']);
        $notes = session('errors');
        $page = $this->get(route('admin.business-site-operations.show', $operation))->assertOk();
        $html = $page->original->with('errors', $notes)->render();
        $this->assertStringContainsString('POS-CLOSURE', $html);
        $this->assertStringContainsString('12/09/2026 20:00:00', $html);
        $this->assertStringContainsString('selewat-lewatnya', $html);
        $response = $this->post(route('admin.business-site-operations.closure-preview', $operation), $data)->assertOk();
        $response->assertSee($agent->agt_name);
        foreach ($response->viewData('review')['attendances'] as $attendance) {
            $this->assertTrue($attendance->relationLoaded('agent'));
        }
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $this->post(route('admin.business-site-operations.closure-store', $operation), ['token' => $response->viewData('token')])->assertRedirect();
        $sale->refresh();
        $this->assertNotSame($operation->id, $sale->business_site_operation_id);
        $this->assertSame('2026-09-12', $sale->report_date->toDateString());
        foreach (['total_amount', 'sold_at', 'stock_deducted', 'voided_at', 'pos_session_id'] as $field) {
            $this->assertEquals($before[$field] ?? null, $sale->getAttributes()[$field] ?? null);
        }
        $this->assertEquals($attendanceBefore, $session->fresh()->getAttributes());
        foreach ($queries as $sql) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(update|insert|delete).*\b(products|product_clicker_stocks|pos_sale_items)\b/i', $sql);
        }
        $originalScopes = Agent::getAllGlobalScopes();
        Agent::addGlobalScope('unavailable-agent', fn (Builder $query): Builder => $query->where('usr_agent.id', '!=', $agent->id));
        try {
            $this->get(route('admin.business-site-operations.show', $sale->business_site_operation_id))->assertOk()->assertSee('Ejen tidak tersedia')->assertSee('Admin / rekod asal tidak tersedia')->assertSee('Semak Pembetulan');
        } finally {
            Agent::setAllGlobalScopes($originalScopes);
        }
        $this->assertDatabaseCount('operation_closure_corrections', 1);
    }

    public function test_replacement_for_open_session_preserves_one_active_operation(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $operation = $this->operation(true);
        $data = $this->data() + ['next_opened_at' => '2026-09-12 18:00:00', 'next_report_date' => '2026-09-12'];
        $response = $this->actingAs($admin, 'admin')->post(route('admin.business-site-operations.closure-preview', $operation), $data)->assertOk();
        $this->post(route('admin.business-site-operations.closure-store', $operation), ['token' => $response->viewData('token')])->assertRedirect();
        $this->assertSame(1, BusinessSiteOperation::whereNull('closed_at')->count());
        $this->assertSame('2026-09-12 18:00:00', $operation->businessSite->fresh()->opened_at->format('Y-m-d H:i:s'));
    }

    public function test_replacement_cannot_overlap_another_operation(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $operation = $this->operation();
        BusinessSiteOperation::create(['business_site_id' => $operation->business_site_id, 'opened_at' => '2026-09-12 19:00:00', 'closed_at' => '2026-09-12 23:00:00']);
        $data = $this->data() + ['next_opened_at' => '2026-09-12 18:00:00', 'next_report_date' => '2026-09-12'];
        $this->actingAs($admin, 'admin')->post(route('admin.business-site-operations.closure-preview', $operation), $data)->assertSessionHasErrors('next_opened_at');
        $this->assertDatabaseCount('operation_closure_corrections', 0);
    }

    public function test_stale_review_and_invalid_times_are_rejected(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $operation = $this->operation();
        $this->actingAs($admin, 'admin')->post(route('admin.business-site-operations.closure-preview', $operation), ['closed_at' => '2026-09-11 17:00:00', 'reason' => 'Wrong'])->assertSessionHasErrors('closed_at');
        $correct = app(CorrectOperationClosure::class);
        $review = $correct->review($operation, $this->data());
        $operation->update(['closed_at' => '2026-09-12 23:00:00']);
        try {
            $correct->apply($operation, $admin, $this->data(), $review['fingerprint']);
            $this->fail('Expected stale review rejection.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('closed_at', $exception->errors());
        }
        $this->assertDatabaseCount('operation_closure_corrections', 0);
    }
}
