<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BusinessSiteReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_control_and_directory_have_separate_pages_and_new_sites_have_summary_links(): void
    {
        $site = $this->site('New Location');
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $this->get(route('admin.business-sites.index'))->assertOk()->assertDontSeeText('Business time')
            ->assertSee(route('admin.business-sites.summary', $site), false);
        $this->get(route('admin.business-sites.summaries'))->assertOk()->assertSeeText('New Location')
            ->assertSee(route('admin.business-sites.summary', $site), false);
        $this->get(route('admin.business-sites.summary', $site))->assertOk()->assertViewHas('summary', fn ($summary) => (float) $summary->sales_total === 0.0);
    }

    public function test_single_day_keeps_whole_overnight_session_and_excludes_next_opening_day(): void
    {
        $site = $this->site('Overnight Site');
        $operation = $this->operation($site, '2026-09-05 20:00:00', '2026-09-06 03:00:00');
        $this->sale($operation, '2026-09-05 23:59:00', 100);
        $this->sale($operation, '2026-09-06 02:00:00', 60);
        $next = $this->operation($site, '2026-09-06 20:00:00');
        $this->sale($next, '2026-09-06 21:00:00', 30);
        $other = $this->site('Other Private Summary');
        $this->sale($this->operation($other, '2026-09-05 20:00:00'), '2026-09-06 01:00:00', 900);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $response = $this->get(route('admin.business-sites.summary', ['businessSite' => $site, 'period' => 'date', 'date' => '2026-09-05', 'site_ids' => [$other->id]]));
        $response->assertOk()->assertDontSeeText('Other Private Summary');
        $this->assertSame(160.0, (float) $response->viewData('summary')->sales_total);
        $this->assertSame(2, (int) $response->viewData('summary')->sales_count);
        $this->assertCount(1, $response->viewData('operationSummaries'));
        $response = $this->get(route('admin.business-sites.summary', ['businessSite' => $site, 'period' => 'date', 'date' => '2026-09-06']));
        $response->assertOk();
        $this->assertSame(30.0, (float) $response->viewData('summary')->sales_total);
    }

    public function test_statistics_are_separate_unless_combined_is_explicitly_requested(): void
    {
        $first = $this->site('Alpha Site');
        $second = $this->site('Beta Site');
        $third = $this->site('Unselected Site');
        foreach ([$first, $second, $third] as $index => $site) {
            $this->sale($this->operation($site, '2026-09-05 20:00:00'), '2026-09-06 01:00:00', ($index + 1) * 100);
        }
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $filters = ['period' => 'date', 'date' => '2026-09-05'];
        $this->get(route('admin.business-sites.statistics', $filters))->assertOk()->assertViewHas('mode', 'separate')->assertDontSee('data-combined-summary', false);
        $filters['site_ids'] = [$first->id, $second->id];
        $this->get(route('admin.business-sites.statistics', $filters))->assertOk()->assertDontSee('data-combined-summary', false);
        $response = $this->get(route('admin.business-sites.statistics', $filters + ['mode' => 'combined']));
        $response->assertOk()->assertSee('data-combined-summary', false)->assertSeeText('Alpha Site + Beta Site');
        $this->assertCount(2, $response->viewData('summaries'));
        $this->assertSame(300.0, (float) $response->viewData('combinedSummary')->sales_total);
        $this->get(route('admin.business-sites.statistics', $filters + ['mode' => 'compare']))->assertOk()->assertSeeText('Perbandingan Lokasi')->assertDontSee('data-combined-summary', false);
    }

    public function test_range_includes_multiple_sessions_and_zero_sales_sites_without_voided_or_deleted_items(): void
    {
        $site = $this->site('Multiple Sessions');
        $empty = $this->site('No Sales');
        $first = $this->operation($site, '2026-09-05 00:00:00', '2026-09-05 12:00:00');
        $second = $this->operation($site, '2026-09-05 20:00:00');
        $sale = $this->sale($first, '2026-09-05 01:00:00', 70);
        $this->sale($second, '2026-09-07 01:00:00', 90);
        $voided = $this->sale($second, '2026-09-06 01:00:00', 1000);
        $voided->update(['voided_at' => now()]);
        $this->sale($this->operation($site, '2026-09-06 00:00:00'), '2026-09-06 01:00:00', 800);
        $product = Product::factory()->create(['cost_rm' => 999]);
        $item = PosSaleItem::query()->create(['pos_sale_id' => $sale->id, 'product_id' => $product->id, 'product_code' => $product->prd_code, 'product_name' => $product->prd_name, 'quantity' => 2, 'unit_price' => 35, 'unit_cost' => 10, 'line_total' => 70]);
        $deleted = $item->replicate();
        $deleted->save();
        $deleted->delete();
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $response = $this->get(route('admin.business-sites.statistics', ['period' => 'range', 'from' => '2026-09-05', 'to' => '2026-09-05']));
        $response->assertOk();
        $summary = $response->viewData('summaries')->firstWhere('id', $site->id);
        $this->assertSame(160.0, (float) $summary->sales_total);
        $this->assertSame(20.0, (float) $summary->capital_total);
        $this->assertSame(2, (int) $summary->items_sold);
        $this->assertSame(2, (int) $summary->operations_count);
        $this->assertSame(0.0, (float) $response->viewData('summaries')->firstWhere('id', $empty->id)->sales_total);
    }

    public function test_invalid_dates_and_unselected_combination_are_rejected(): void
    {
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        foreach ([['period' => 'date', 'date' => '2026-02-30'], ['period' => 'date'], ['period' => 'range', 'from' => '2026-09-06', 'to' => '2026-09-05'], ['mode' => 'combined'], ['mode' => 'compare'], ['site_ids' => [999999]], ['mode' => 'invalid']] as $filters) {
            $this->get(route('admin.business-sites.statistics', $filters))->assertRedirect()->assertSessionHasErrors();
        }
    }

    public function test_view_permission_covers_reports_but_does_not_allow_operations(): void
    {
        $site = $this->site('Permission Site');
        $staff = AdminUser::factory()->create(['role' => AdminUser::RoleAdmin]);
        $role = AdminRole::query()->create(['name' => 'Site Viewer', 'permissions' => ['business-sites.view']]);
        $staff->accessRoles()->attach($role);
        $this->actingAs($staff, 'admin');
        foreach (['statistics', 'summaries', 'summary'] as $route) {
            $this->get(route('admin.business-sites.'.$route, $route === 'summary' ? $site : []))->assertOk();
        }
        $this->patch(route('admin.business-sites.start', $site))->assertForbidden();
        $role->update(['permissions' => []]);
        foreach (['statistics', 'summaries', 'summary'] as $route) {
            $this->get(route('admin.business-sites.'.$route, $route === 'summary' ? $site : []))->assertForbidden();
        }
    }

    public function test_operation_days_are_unique_across_locations_and_sessions_include_zero_sales(): void
    {
        $first = $this->site('Day Count Alpha');
        $second = $this->site('Day Count Beta');
        $empty = $this->site('No Operations');
        $excluded = $this->site('Excluded Location');
        $this->operation($first, '2026-09-05 08:00:00', '2026-09-05 12:00:00');
        $this->operation($first, '2026-09-05 20:00:00', '2026-09-06 03:00:00');
        $this->operation($second, '2026-09-05 21:00:00', '2026-09-06 02:00:00');
        $this->operation($second, '2026-09-07 20:00:00');
        $this->operation($excluded, '2026-09-08 20:00:00');
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        $filters = ['period' => 'range', 'from' => '2026-09-01', 'to' => '2026-09-10', 'mode' => 'combined', 'site_ids' => [$first->id, $second->id, $empty->id]];
        $response = $this->get(route('admin.business-sites.statistics', $filters));
        $response->assertOk()->assertSeeText('Hari Operasi')->assertSeeText('Sesi Perniagaan');
        $this->assertSame(2, $response->viewData('combinedSummary')->operation_days);
        $this->assertEquals(4, $response->viewData('combinedSummary')->operations_count);
        $summaries = $response->viewData('summaries');
        $this->assertSame(1, $summaries->firstWhere('id', $first->id)->operation_days);
        $this->assertSame(2, $summaries->firstWhere('id', $second->id)->operation_days);
        $this->assertSame(0, $summaries->firstWhere('id', $empty->id)->operation_days);
        $this->get(route('admin.business-sites.statistics', array_replace($filters, ['mode' => 'compare'])))
            ->assertOk()->assertSeeText('Hari Operasi')->assertSeeText('Sesi Perniagaan');
        $response = $this->get(route('admin.business-sites.summary', ['businessSite' => $first, 'period' => 'date', 'date' => '2026-09-05']));
        $response->assertOk()->assertSeeText('1 hari')->assertSeeText('2 sesi');
        $this->assertSame(1, $response->viewData('summary')->operation_days);
        $response = $this->get(route('admin.business-sites.summary', ['businessSite' => $first, 'period' => 'date', 'date' => '2026-09-06']));
        $response->assertOk()->assertSeeText('0 hari');
        $this->assertSame(0, $response->viewData('summary')->operation_days);
        $response = $this->get(route('admin.business-sites.statistics', array_replace($filters, ['period' => 'date', 'date' => '2026-09-06'])));
        $response->assertOk();
        $this->assertSame(0, $response->viewData('combinedSummary')->operation_days);
        $this->assertEquals(0, $response->viewData('combinedSummary')->operations_count);
    }

    private function site(string $name): BusinessSite
    {
        return BusinessSite::query()->create(['site_name' => $name, 'city' => 'Klang']);
    }

    private function operation(BusinessSite $site, string $opened, ?string $closed = null): BusinessSiteOperation
    {
        return BusinessSiteOperation::query()->create(['business_site_id' => $site->id, 'opened_at' => $opened, 'closed_at' => $closed]);
    }

    private function sale(BusinessSiteOperation $operation, string $soldAt, float $total): PosSale
    {
        $agent = Agent::factory()->create();

        return PosSale::query()->create(['sale_number' => 'POS-'.fake()->unique()->numerify('########'), 'business_site_id' => $operation->business_site_id, 'business_site_operation_id' => $operation->id, 'recorded_by_agent_id' => $agent->id, 'sales_agent_id' => $agent->id, 'payment_method' => PosSale::PaymentCash, 'total_amount' => $total, 'sold_at' => $soldAt]);
    }
}
