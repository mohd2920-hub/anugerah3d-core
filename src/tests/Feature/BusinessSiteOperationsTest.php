<?php

namespace Tests\Feature;

use App\Actions\Pos\CreatePosSale;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\BusinessSite;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSession;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BusinessSiteOperationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_start_a_business_site(): void
    {
        $admin = AdminUser::factory()->create();
        $site = BusinessSite::query()->create(['site_name' => 'Test Site', 'city' => 'Shah Alam']);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.business-sites.start', $site))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($site->fresh()->opened_at);
        $this->get(route('admin.business-sites.index'))
            ->assertOk()
            ->assertSee('data-stop-business', false)
            ->assertSee('data-operation-timer', false)
            ->assertSee(route('admin.business-sites.stop', $site), false);
    }

    public function test_agent_cannot_check_in_when_site_is_closed(): void
    {
        $agent = Agent::factory()->create();
        $site = BusinessSite::query()->create(['site_name' => 'Closed Site', 'city' => 'Klang']);
        $site->agents()->attach($agent);

        $this->actingAs($agent, 'agent')
            ->from(route('agent.pos.index'))
            ->post(route('agent.pos.sign-in'), ['business_site_id' => $site->getKey()])
            ->assertRedirect(route('agent.pos.index'))
            ->assertSessionHasErrors(['business_site_id' => 'Please ask an admin to open the business site.']);

        $this->assertDatabaseCount('pos_sessions', 0);
    }

    public function test_agent_check_in_is_tracked_for_an_open_site(): void
    {
        $agent = Agent::factory()->create();
        $site = BusinessSite::query()->create(['site_name' => 'Open Site', 'city' => 'Klang', 'opened_at' => now()]);
        $site->agents()->attach($agent);

        $this->actingAs($agent, 'agent')
            ->post(route('agent.pos.sign-in'), ['business_site_id' => $site->getKey()])
            ->assertRedirect(route('agent.pos.index'));

        $session = PosSession::query()->sole();
        $this->assertSame($agent->getKey(), $session->agent_id);
        $this->assertSame($site->getKey(), $session->business_site_id);
        $this->assertNotNull($session->signed_in_at);
        $this->assertNull($session->signed_out_at);
    }

    public function test_stopping_business_checks_out_all_agents(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create(['agt_name' => 'Agent Closed']);
        $site = BusinessSite::query()->create(['site_name' => 'Running Site', 'city' => 'Klang', 'opened_at' => now()->subHour()]);
        $site->agents()->attach($agent);
        $session = PosSession::query()->create([
            'agent_id' => $agent->getKey(),
            'business_site_id' => $site->getKey(),
            'signed_in_at' => now()->subMinutes(30),
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.business-sites.stop', $site))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull($site->fresh()->opened_at);
        $this->assertNotNull($session->fresh()->signed_out_at);

        $this->get(route('admin.business-sites.index'))
            ->assertOk()
            ->assertSeeText('0 Active agent(s)')
            ->assertDontSeeText('Agent Closed')
            ->assertDontSee('data-attendance-timer', false);
    }

    public function test_business_site_cards_show_only_active_agent_count(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create(['agt_name' => 'Agent Hadir']);
        $site = BusinessSite::query()->create(['site_name' => 'Live Site', 'city' => 'Klang', 'opened_at' => now()->subMinutes(15)]);
        $site->agents()->attach($agent);
        PosSession::query()->create([
            'agent_id' => $agent->getKey(),
            'business_site_id' => $site->getKey(),
            'signed_in_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.business-sites.index'))
            ->assertOk()
            ->assertSee('data-business-site-card', false)
            ->assertSee('data-business-timer', false)
            ->assertSeeText('1 Active agent(s)')
            ->assertDontSeeText('Agent Hadir')
            ->assertDontSee('data-attendance-timer', false);
    }

    public function test_operation_summary_calculates_requested_totals(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create(['agt_name' => 'Agent Detail']);
        $site = BusinessSite::query()->create(['site_name' => 'Summary Site', 'city' => 'Klang']);
        $operation = BusinessSiteOperation::query()->create([
            'business_site_id' => $site->getKey(), 'opened_at' => now()->subMinutes(10), 'closed_at' => now(),
        ]);
        $session = PosSession::query()->create([
            'agent_id' => $agent->getKey(), 'business_site_id' => $site->getKey(),
            'signed_in_at' => $operation->opened_at->addMinutes(5), 'signed_out_at' => $operation->closed_at->subMinutes(5),
        ]);
        $product = Product::factory()->create(['cost_rm' => 30, 'price_selling' => 100]);
        $sale = PosSale::query()->create([
            'sale_number' => 'POS-SUMMARY-1', 'pos_session_id' => $session->getKey(), 'business_site_id' => $site->getKey(),
            'business_site_operation_id' => $operation->getKey(),
            'recorded_by_agent_id' => $agent->getKey(), 'sales_agent_id' => $agent->getKey(),
            'payment_method' => PosSale::PaymentCash, 'total_amount' => 180,
            'sold_at' => $operation->opened_at->addMinutes(10),
        ]);
        PosSaleItem::query()->create([
            'pos_sale_id' => $sale->getKey(), 'product_id' => $product->getKey(), 'product_code' => $product->prd_code,
            'product_name' => $product->prd_name, 'quantity' => 2, 'unit_price' => 100,
            'agent_discount_percentage' => 25, 'agent_discount_amount' => 50,
            'customer_discount_amount' => 20, 'line_total' => 180,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.business-sites.index'));
        $summary = $response->viewData('operationSummaries')->first();

        $response->assertOk()
            ->assertSeeText('Summary Site')
            ->assertSeeText('Business time')
            ->assertSeeText('10 minutes')
            ->assertDontSee('data-operation-timer', false)
            ->assertDontSeeText('Open time')
            ->assertDontSeeText('Closing time');
        $this->assertSame(1, (int) $summary->agents_count);
        $this->assertSame(1, (int) $summary->sales_count);
        $this->assertSame(2, (int) $summary->items_sold);
        $this->assertSame(180.0, (float) $summary->sales_total);
        $this->assertSame(130.0, (float) $summary->commission_total);
        $this->assertSame(60.0, (float) $summary->capital_total);

        $this->get(route('admin.business-site-operations.show', $operation))
            ->assertOk()
            ->assertSeeText('RM 180.00')
            ->assertSeeText('Agent Detail')
            ->assertSeeText('POS-SUMMARY-1')
            ->assertSee(route('admin.sales.show', $sale), false);

    }

    public function test_operation_summary_paginates_twenty_records(): void
    {
        $admin = AdminUser::factory()->create();
        $site = BusinessSite::query()->create(['site_name' => 'Pagination Site', 'city' => 'Klang']);

        foreach (range(1, 21) as $day) {
            BusinessSiteOperation::query()->create([
                'business_site_id' => $site->getKey(),
                'opened_at' => now()->subDays($day)->startOfDay(),
                'closed_at' => now()->subDays($day)->endOfDay(),
            ]);
        }

        $firstPage = $this->actingAs($admin, 'admin')->get(route('admin.business-sites.index'));
        $secondPage = $this->get(route('admin.business-sites.index', ['operations_page' => 2]));

        $this->assertCount(20, $firstPage->viewData('operationSummaries'));
        $this->assertCount(1, $secondPage->viewData('operationSummaries'));
    }

    public function test_business_site_action_links_directly_to_details_and_delete_is_moved(): void
    {
        $admin = AdminUser::factory()->create();
        $site = BusinessSite::query()->create(['site_name' => 'Action Site', 'city' => 'Klang']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.business-sites.index'))
            ->assertOk()
            ->assertSeeText('Add new site')
            ->assertSee('data-business-site-card', false)
            ->assertSeeText('...')
            ->assertSee('View details for Action Site', false)
            ->assertSee(route('admin.business-sites.show', $site), false)
            ->assertDontSee('data-site-action-toast', false)
            ->assertDontSeeText('Edit')
            ->assertDontSeeText('Delete');

        $this->get(route('admin.business-sites.show', $site))
            ->assertOk()
            ->assertSeeText('Edit site')
            ->assertSeeText('Delete site')
            ->assertSee('_method', false)
            ->assertSee('DELETE', false);
    }

    public function test_pos_sales_are_linked_and_scoped_to_the_current_site_operation(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create();
        $site = BusinessSite::query()->create([
            'site_name' => 'Operation Linked Site',
            'city' => 'Klang',
            'opened_at' => now()->subHour(),
        ]);
        $site->agents()->attach($agent);

        $oldOperation = BusinessSiteOperation::query()->create([
            'business_site_id' => $site->getKey(),
            'opened_at' => now()->subHours(2),
            'closed_at' => now()->addMinutes(30),
        ]);
        $currentOperation = BusinessSiteOperation::query()->create([
            'business_site_id' => $site->getKey(),
            'opened_at' => $site->opened_at,
            'closed_at' => null,
        ]);
        $session = PosSession::query()->create([
            'agent_id' => $agent->getKey(),
            'business_site_id' => $site->getKey(),
            'signed_in_at' => now()->subMinutes(20),
        ]);

        PosSale::query()->create([
            'sale_number' => 'POS-OLD-OPERATION',
            'pos_session_id' => $session->getKey(),
            'business_site_id' => $site->getKey(),
            'business_site_operation_id' => $oldOperation->getKey(),
            'recorded_by_agent_id' => $agent->getKey(),
            'sales_agent_id' => $agent->getKey(),
            'payment_method' => PosSale::PaymentCash,
            'total_amount' => 999,
            'sold_at' => now()->subMinutes(5),
        ]);

        $product = Product::factory()->create(['price_selling' => 75]);
        $currentSale = app(CreatePosSale::class)->handle($session, [
            'sales_agent_id' => $agent->getKey(),
            'items' => [['product_id' => $product->getKey(), 'quantity' => 1]],
            'payment_method' => PosSale::PaymentCash,
        ]);

        $this->assertSame($currentOperation->getKey(), $currentSale->business_site_operation_id);

        $this->actingAs($agent, 'agent')
            ->get(route('agent.pos.index', ['tab' => 'history']))
            ->assertOk()
            ->assertSee('data-operation-sales-summary', false)
            ->assertSeeText('RM 75.00')
            ->assertSeeText('1 sale(s) | Session #'.$currentOperation->getKey())
            ->assertSeeText($currentSale->sale_number)
            ->assertDontSeeText('POS-OLD-OPERATION');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.business-site-operations.show', $currentOperation))
            ->assertOk()
            ->assertSeeText($currentSale->sale_number)
            ->assertDontSeeText('POS-OLD-OPERATION');
    }

    public function test_admin_can_delete_a_closed_business_session_without_sales(): void
    {
        $admin = AdminUser::factory()->create();
        $site = BusinessSite::query()->create(['site_name' => 'Empty Session Site', 'city' => 'Klang']);
        $operation = BusinessSiteOperation::query()->create([
            'business_site_id' => $site->getKey(),
            'opened_at' => now()->subHours(2),
            'closed_at' => now()->subHour(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.business-site-operations.show', $operation))
            ->assertOk()
            ->assertSeeText('Delete session')
            ->assertSee('_method', false)
            ->assertSee('DELETE', false);

        $this->delete(route('admin.business-site-operations.destroy', $operation))
            ->assertRedirect(route('admin.business-sites.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($operation);
    }

    public function test_business_session_with_sales_cannot_be_deleted(): void
    {
        $admin = AdminUser::factory()->create();
        $agent = Agent::factory()->create();
        $site = BusinessSite::query()->create(['site_name' => 'Sales Session Site', 'city' => 'Klang']);
        $operation = BusinessSiteOperation::query()->create([
            'business_site_id' => $site->getKey(),
            'opened_at' => now()->subHours(2),
            'closed_at' => now()->subHour(),
        ]);
        $session = PosSession::query()->create([
            'agent_id' => $agent->getKey(),
            'business_site_id' => $site->getKey(),
            'signed_in_at' => $operation->opened_at->addMinutes(5),
            'signed_out_at' => $operation->closed_at->subMinutes(5),
        ]);
        PosSale::query()->create([
            'sale_number' => 'POS-DELETE-GUARD',
            'pos_session_id' => $session->getKey(),
            'business_site_id' => $site->getKey(),
            'business_site_operation_id' => $operation->getKey(),
            'recorded_by_agent_id' => $agent->getKey(),
            'sales_agent_id' => $agent->getKey(),
            'payment_method' => PosSale::PaymentCash,
            'total_amount' => 100,
            'sold_at' => $operation->closed_at->addDay(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.business-site-operations.show', $operation))
            ->assertOk()
            ->assertSeeText('POS-DELETE-GUARD')
            ->assertSeeText('This session cannot be deleted because it has sales.');

        $this->from(route('admin.business-site-operations.show', $operation))
            ->delete(route('admin.business-site-operations.destroy', $operation))
            ->assertRedirect(route('admin.business-site-operations.show', $operation))
            ->assertSessionHasErrors('business_site_operation');

        $this->assertModelExists($operation);
    }
}
