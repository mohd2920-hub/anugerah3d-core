<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\AgentDiscountSetting;
use App\Models\Order;
use App\Models\Product;
use App\Support\AgentOrderDiscount;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentDiscountSettingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_superadmin_can_view_save_and_audit_rates(): void
    {
        $admin = AdminUser::factory()->superAdmin()->create();
        $this->actingAs($admin, 'admin')->get(route('admin.orders.index'))->assertOk()->assertSeeText('Ubah Diskaun');
        $this->put(route('admin.orders.discount-settings.update'), $this->rates())->assertSessionHasNoErrors()->assertRedirect(route('admin.orders.index'));
        $setting = AgentDiscountSetting::current();
        $this->assertSame(12.5, $setting->below_rm20);
        $this->assertSame(1, $setting->version);
        $log = ActivityLog::query()->where('event', 'admin.orders.discount.updated')->sole();
        $this->assertEquals(10, $log->properties['before']['below_rm20']);
        $this->assertEquals(12.5, $log->properties['after']['below_rm20']);
        $this->assertSame($admin->id, $log->admin_user_id);
        $this->get(route('admin.orders.index'))->assertOk()->assertSeeText('12.5%')->assertSeeText('Terakhir diubah oleh');
    }

    public function test_orders_staff_can_view_but_cannot_change_rates(): void
    {
        $staff = AdminUser::factory()->create();
        $role = AdminRole::factory()->create(['permissions' => ['orders.view', 'orders.edit']]);
        $staff->accessRoles()->attach($role);
        $this->actingAs($staff, 'admin')->get(route('admin.orders.index'))->assertOk()->assertSeeText('Diskaun Ejen')->assertDontSeeText('Ubah Diskaun');
        $this->put(route('admin.orders.discount-settings.update'), $this->rates())->assertForbidden();
        $this->assertSame(0, AgentDiscountSetting::current()->version);
    }

    public function test_invalid_and_stale_changes_are_rejected(): void
    {
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin');
        foreach ([-1, 101, 12.55, 'abc', null] as $invalid) {
            $this->put(route('admin.orders.discount-settings.update'), array_replace($this->rates(), ['below_rm20' => $invalid]))->assertSessionHasErrors('below_rm20');
        }
        $this->put(route('admin.orders.discount-settings.update'), $this->rates())->assertSessionHasNoErrors();
        $this->put(route('admin.orders.discount-settings.update'), $this->rates())->assertSessionHasErrors('version');
        $this->assertSame(1, ActivityLog::query()->where('event', 'admin.orders.discount.updated')->count());
    }

    public function test_boundaries_profile_override_and_frontend_use_current_rates(): void
    {
        $this->assertSame(10.0, AgentOrderDiscount::resolvePercentage(1999));
        $this->assertSame(25.0, AgentOrderDiscount::resolvePercentage(2000));
        AgentDiscountSetting::current()->update($this->rates());
        foreach ([[1999, 12.5], [2000, 20.0], [9999, 20.0], [10000, 30.0]] as [$amount, $rate]) {
            $this->assertSame($rate, AgentOrderDiscount::resolvePercentage($amount));
        }
        $this->assertSame(40.0, AgentOrderDiscount::resolvePercentage(10000, 40));
        $this->assertSame(20.0, AgentOrderDiscount::resolvePercentage(9999, 40));
        $config = AgentOrderDiscount::frontendConfig(40);
        $this->assertSame(12.5, $config['belowRm20Percentage']);
        $this->assertSame(20.0, $config['belowRm100Percentage']);
        $this->assertSame(40.0, $config['aboveRm100Percentage']);
        AgentDiscountSetting::current()->update(['below_rm20' => 0, 'below_rm100' => 100]);
        $this->assertSame(0.0, AgentOrderDiscount::resolvePercentage(1999));
        $this->assertSame(100.0, AgentOrderDiscount::resolvePercentage(2000));
    }

    public function test_checkout_uses_new_rate_preserves_old_orders_and_rejects_stale_total(): void
    {
        Mail::fake();
        $agent = Agent::factory()->create(['discount_percentage' => 0]);
        $product = Product::factory()->create(['product_type' => 'standard', 'is_visible_to_agents' => true, 'price_selling' => 40, 'prd_balance' => 10]);
        $payload = ['idempotency_key' => (string) Str::uuid(), 'fulfilment_method' => 'pickup', 'recipient_name' => 'Agent', 'phone_number' => '0123456789', 'payment_method' => 'pay_later', 'expected_total' => 30, 'items' => [['product_id' => $product->id, 'quantity' => 1]]];
        $this->actingAs($agent, 'agent')->postJson(route('agent.orders.store'), $payload)->assertCreated();
        $old = Order::query()->sole();
        AgentDiscountSetting::current()->update(['below_rm100' => 20]);
        $payload['idempotency_key'] = (string) Str::uuid();
        $this->postJson(route('agent.orders.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('total');
        $this->assertSame(9, $product->fresh()->prd_balance);
        $this->assertDatabaseCount('orders', 1);
        $payload['expected_total'] = 32;
        $this->postJson(route('agent.orders.store'), $payload)->assertCreated();
        $this->assertSame('30.00', $old->fresh()->subtotal);
        $this->assertSame('25.0', $old->items()->sole()->discount_percentage);
        $new = Order::query()->latest('id')->first();
        $this->assertSame('32.00', $new->subtotal);
        $this->assertSame('20.0', $new->items()->sole()->discount_percentage);
    }

    private function rates(): array
    {
        return ['below_rm20' => 12.5, 'below_rm100' => 20, 'at_least_rm100' => 30, 'version' => 0];
    }
}
