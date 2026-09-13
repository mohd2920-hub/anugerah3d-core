<?php

namespace Tests\Feature;

use App\Actions\Orders\UpdateCustomerOrderProgress;
use App\Mail\Admin\CustomerOrderPlacedMail;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\CustomerOrder;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerCatalogueTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_discontinued_product_sells_last_units_and_rejects_customer_preorder(): void
    {
        $agent = Agent::factory()->create();
        $product = Product::factory()->create(['prd_balance' => 2, 'is_visible_to_agents' => true]);
        $product->forceFill(['discontinued_at' => now()])->save();
        Mail::fake();
        Storage::fake('local');
        $url = URL::signedRoute('agent.customer.store', ['referrer' => $agent->id]);
        $this->postJson($url, $this->payload($product))->assertCreated();
        $this->assertSame(0, $product->fresh()->prd_balance);
        $this->postJson($url, $this->payload($product))->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('customer_orders', 1);
    }

    public function test_customer_progress_receipt_and_shipped_commission(): void
    {
        $agent = Agent::factory()->create();
        $admin = AdminUser::factory()->superAdmin()->create();
        $order = $this->place($agent, Product::factory()->create(['price_selling' => 19]));
        $statusUrl = route('agent.customer.status', $order->tracking_token);
        $receiptUrl = route('agent.customer.receipt', $order->tracking_token);
        $this->get($statusUrl)->assertOk()->assertSee('Menunggu pengesahan bayaran')->assertSee('Salin Link Pesanan')->assertDontSee('110068155912')->assertDontSee('name="proof"', false);
        $this->get($receiptUrl)->assertNotFound();
        $this->actingAs($admin, 'admin');
        $url = route('admin.customer-orders.update', $order);
        $this->put($url, ['action' => 'request_proof', 'reason' => 'Resit kabur'])->assertSessionHasNoErrors();
        $this->get($statusUrl)->assertSee('name="proof"', false)->assertSee('Resit kabur');
        $this->put($url, ['action' => 'payment', 'payment_status' => 'paid', 'reason' => 'Bayaran disemak'])->assertSessionHasNoErrors();
        $order->refresh();
        $receiptNumber = $order->receipt_number;
        $snapshot = $order->receipt_snapshot;
        $this->get($receiptUrl)->assertOk()->assertSee($receiptNumber)->assertSee('41.00')->assertDontSee('commission')->assertDontSee($agent->agt_name);
        $this->get($statusUrl)->assertSee('Lihat Resit Pembelian')->assertDontSee('name="proof"', false);
        $this->put($url, ['action' => 'ship', 'courier' => 'Courier', 'tracking_number' => 'TRACK123', 'reason' => 'Parcel'])->assertSessionHasErrors('status');
        foreach (['process', 'ready'] as $action) {
            $this->put($url, ['action' => $action, 'reason' => 'Progress'])->assertSessionHasNoErrors();
        }
        $this->assertSame('Pending', $order->fresh()->commissionStatus());
        $this->put($url, ['action' => 'ship', 'courier' => 'Courier', 'tracking_number' => 'TRACK123', 'reason' => 'Parcel'])->assertSessionHasNoErrors();
        $this->assertSame('Eligible', $order->fresh()->commissionStatus());
        $this->get($statusUrl)->assertSee('TRACK123')->assertSee('Dihantar');
        $this->put($url, ['action' => 'complete', 'reason' => 'Delivered'])->assertSessionHasNoErrors();
        $this->put($url, ['action' => 'payment', 'payment_status' => 'paid', 'reason' => 'Rechecked'])->assertSessionHasNoErrors();
        $this->assertSame($receiptNumber, $order->fresh()->receipt_number);
        $this->assertSame($snapshot, $order->fresh()->receipt_snapshot);
        $this->assertSame('Eligible', $order->fresh()->commissionStatus());
        $this->put($url, ['action' => 'payment', 'payment_status' => 'refunded', 'reason' => 'Refund'])->assertSessionHasNoErrors();
        $this->get($receiptUrl)->assertNotFound();
        $this->assertSame('Cancelled', $order->fresh()->commissionStatus());
    }

    public function test_customer_confirmation_expires_private_links_without_changing_commission(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $agent = Agent::factory()->create();
        $product = Product::factory()->create(['prd_balance' => 10]);
        $order = $this->place($agent, $product);
        $url = route('agent.customer.confirm-receipt', $order->tracking_token);
        $statusUrl = route('agent.customer.status', $order->tracking_token);
        $this->get($statusUrl)->assertDontSee('Sahkan Barang Diterima');
        $this->post($url, ['confirmed' => 1])->assertStatus(422);
        $order->forceFill(['status' => 'completed'])->save();
        $this->post($url, ['confirmed' => 1])->assertStatus(422);
        $order->forceFill(['payment_status' => 'paid'])->save();
        app(UpdateCustomerOrderProgress::class)->issueReceipt($order);
        $commission = $order->earnedCommissionCents();
        $snapshot = $order->receipt_snapshot;
        $this->get($statusUrl)->assertSee('Sahkan Barang Diterima');
        $this->post($url, [])->assertSessionHasErrors('confirmed');
        $this->assertNull($order->fresh()->customer_received_at);
        $this->post($url, ['confirmed' => 1])->assertRedirect($statusUrl);
        $receivedAt = $order->fresh()->customer_received_at;
        $this->assertNotNull($receivedAt);
        $this->get($statusUrl)->assertStatus(410)->assertSee('Pautan telah tamat')->assertDontSee($order->order_number)->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('agent.customer.receipt', $order->tracking_token))->assertStatus(410)->assertDontSee($order->receipt_number);
        $this->post(route('agent.customer.proof', $order->tracking_token), ['proof' => UploadedFile::fake()->image('late.jpg')])->assertStatus(410);
        $this->post($url, ['confirmed' => 1])->assertRedirect($statusUrl);
        $this->assertTrue($receivedAt->equalTo($order->fresh()->customer_received_at));
        $this->assertSame($commission, $order->fresh()->earnedCommissionCents());
        $this->assertSame($snapshot, $order->fresh()->receipt_snapshot);
        $this->actingAs(AdminUser::factory()->superAdmin()->create(), 'admin')->get(route('admin.customer-orders.show', $order))->assertOk()->assertSee('Penerimaan disahkan pelanggan');
        $this->post(route('agent.customer.confirm-receipt', 'unknown-token'), ['confirmed' => 1])->assertNotFound();
    }

    public function test_customer_can_choose_pickup_without_delivery_address_or_charge(): void
    {
        Mail::fake();
        $agent = Agent::factory()->create();
        $product = Product::factory()->create(['prd_balance' => 10, 'price_selling' => 19]);
        $payload = $this->payload($product);
        $this->get(URL::signedRoute('agent.customer.catalogue', ['referrer' => $agent->id]))->assertOk()->assertSee('type="radio" name="fulfilment_method" value="pickup"', false)->assertSee('type="radio" name="fulfilment_method" value="delivery"', false)->assertDontSee('name="fulfilment_method" type="hidden"', false);
        $payload['fulfilment_method'] = 'pickup';
        $payload['delivery_address'] = '';
        $payload['expected_total'] = 38;
        $this->postJson(URL::signedRoute('agent.customer.store', ['referrer' => $agent->id]), $payload)->assertCreated();
        $order = CustomerOrder::latest('id')->firstOrFail();
        $this->assertSame('pickup', $order->fulfilment_method);
        $this->assertEquals(0, $order->deliveryFeeAmount());
        $this->assertEquals(38, $order->total_amount);
    }

    public function test_customer_whatsapp_uses_current_status_and_omits_expired_link(): void
    {
        $order = new CustomerOrder;
        $order->forceFill(['phone_number' => '013-360 3089', 'recipient_name' => 'TEST', 'order_number' => 'A3DC-TEST', 'status' => 'shipped', 'payment_status' => 'paid', 'total_amount' => 10, 'fulfilment_method' => 'delivery', 'courier' => 'Courier', 'tracking_number' => 'TRACK123', 'tracking_token' => 'private-token']);
        $url = $order->customerWhatsAppUrl();
        $this->assertStringStartsWith('https://wa.me/60133603089?text=', $url);
        $message = rawurldecode($url);
        $this->assertStringContainsString('Status: Dihantar', $message);
        $this->assertStringContainsString('Bayaran disahkan', $message);
        $this->assertStringContainsString('TRACK123', $message);
        $this->assertStringContainsString('private-token', $message);
        $this->assertStringNotContainsString('Komisen', $message);
        $order->customer_received_at = now();
        $this->assertStringNotContainsString('private-token', rawurldecode($order->customerWhatsAppUrl()));
        $order->phone_number = '';
        $this->assertNull($order->customerWhatsAppUrl());
    }

    public function test_status_refresh_fetches_latest_status_with_a_new_request(): void
    {
        $agent = Agent::factory()->create();
        $order = $this->place($agent, Product::factory()->create());
        $url = route('agent.customer.status', $order->tracking_token);
        $response = $this->get($url)->assertOk()->assertSee('name="refresh"', false)->assertSee('Status disemak:')->assertDontSee('action="'.$url.'#perkembangan-pesanan"', false);
        preg_match('/name="refresh" value="([^"]+)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches[1]);
        $order->forceFill(['status' => 'pickup_ready', 'fulfilment_method' => 'pickup'])->save();
        $this->get($url.'?refresh='.$matches[1])->assertOk()->assertSee('Sedia Diambil')->assertHeader('Cache-Control', 'no-store, private')->assertDontSee('value="'.$matches[1].'"', false);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_customer_checkout_requires_bank_transfer_and_receipt(): void
    {
        $agent = Agent::factory()->create();
        $product = Product::factory()->create(['prd_balance' => 10]);
        $url = URL::signedRoute('agent.customer.store', ['referrer' => $agent->id]);
        $data = $this->payload($product);
        unset($data['payment_proofs']);
        $this->postJson($url, $data)->assertUnprocessable()->assertJsonValidationErrors('payment_proofs');
        $data = $this->payload($product);
        $data['payment_method'] = 'pay_later';
        $this->postJson($url, $data)->assertUnprocessable()->assertJsonValidationErrors('payment_method');
        $data = $this->payload($product);
        $data['payment_proofs'] = [UploadedFile::fake()->create('invalid.txt', 1, 'text/plain')];
        $this->postJson($url, $data)->assertUnprocessable()->assertJsonValidationErrors('payment_proofs.0');
        $order = $this->place($agent, $product);
        $this->assertSame('bank_transfer', $order->payment_method);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertStringStartsWith('customer-payment-proofs/', $order->paymentProofPaths()[0]);
        Storage::disk('local')->assertExists($order->paymentProofPaths()[0]);
        $this->get(URL::signedRoute('agent.customer.catalogue', ['referrer' => $agent->id]))->assertOk()->assertSee('110068155912')->assertSee('Maybank')->assertDontSee('Pay later');
    }

    public function test_agent_can_view_only_own_commission_transactions_and_proof(): void
    {
        Storage::fake('local');
        $owner = Agent::factory()->create();
        $other = Agent::factory()->create();
        $product = Product::factory()->create(['prd_balance' => 10]);
        $order = $this->place($owner, $product);
        $otherOrder = $this->place($other, $product);
        $path = UploadedFile::fake()->image('receipt.jpg')->store('customer-commission-proofs', 'local');
        $entry = DB::table('customer_commission_entries')->insertGetId(['order_id' => $order->id, 'admin_id' => 1, 'type' => 'payment', 'amount' => 0.75, 'cash_amount' => 0.75, 'reference' => 'TRANSFER-COMMISSION-001', 'proof_path' => $path, 'reason' => 'Internal admin note', 'created_at' => now()]);
        $detailUrl = route('agent.customer.commissions.show', $order->id);
        $proofUrl = route('agent.customer.commissions.proof', [$order->id, $entry]);
        $this->get($detailUrl)->assertRedirect();
        $this->get($proofUrl)->assertRedirect();
        $this->actingAs($owner, 'agent')->get($detailUrl)->assertOk()->assertSee('TRANSFER-COMMISSION-001')->assertSee('0.75')->assertSee('Bukti bayaran komisen daripada Anugerah3D')->assertDontSee('Customer address')->assertDontSee('Internal admin note');
        $this->get($proofUrl)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('agent.customer.commissions.proof', [$order->id, $entry + 99]))->assertNotFound();
        $this->actingAs($other, 'agent')->get($detailUrl)->assertNotFound();
        $this->get($proofUrl)->assertNotFound();
        $this->get(route('agent.customer.commissions.proof', [$otherOrder->id, $entry]))->assertNotFound();
        $this->get(route('agent.customer.commissions.show', $otherOrder->id))->assertOk()->assertSee('Belum ada transaksi');
    }

    private function payload(Product $product): array
    {
        return ['expected_total' => round((float) $product->price_selling * 2 + 3, 2), 'idempotency_key' => (string) Str::uuid(), 'fulfilment_method' => 'delivery', 'recipient_name' => 'Customer Guest', 'phone_number' => '0123456789', 'delivery_address' => 'Customer address', 'payment_method' => 'bank_transfer', 'payment_proofs' => [UploadedFile::fake()->image('receipt.jpg')], 'items' => [['product_id' => $product->id, 'quantity' => 2]]];
    }

    private function place(Agent $agent, Product $product): CustomerOrder
    {
        Mail::fake();
        $this->postJson(URL::signedRoute('agent.customer.store', ['referrer' => $agent->id]), $this->payload($product))->assertCreated();

        return CustomerOrder::latest('id')->firstOrFail();
    }

    public function test_public_catalogue_uses_signed_active_referrer_and_hides_private_data(): void
    {
        $agent = Agent::factory()->create(['agt_name' => 'Private Referrer', 'discount_percentage' => 70]);
        $visible = Product::factory()->create();
        $hidden = Product::factory()->create(['is_visible_to_agents' => false]);
        $this->get(URL::signedRoute('agent.customer.catalogue', ['referrer' => $agent->id]))->assertOk()->assertSee($visible->prd_name)->assertDontSee($hidden->prd_name)->assertDontSee('Agent workspace')->assertDontSee('Private Referrer')->assertDontSee('commission_rate');
        $this->get(route('agent.customer.catalogue', $agent))->assertForbidden();
        $agent->update(['agt_status' => Agent::StatusInactive]);
        $this->get(URL::signedRoute('agent.customer.catalogue', ['referrer' => $agent->id]))->assertNotFound();
    }

    public function test_guest_order_full_price_commission_and_idempotency_preserve_agent_orders(): void
    {
        Mail::fake();
        $agent = Agent::factory()->create(['discount_percentage' => 70]);
        $product = Product::factory()->create(['price_selling' => 19, 'prd_balance' => 10]);
        $payload = $this->payload($product);
        $payload['discount_percentage'] = 99;
        $payload['agent_id'] = 999;
        $url = URL::signedRoute('agent.customer.store', ['referrer' => $agent->id]);
        $response = $this->postJson($url, $payload)->assertCreated()->assertJsonPath('order.total', '41.00');
        $this->postJson($url, $payload)->assertCreated();
        $order = CustomerOrder::firstOrFail();
        $this->assertSame($agent->id, $order->agent_id);
        $this->assertSame('38.00', $order->subtotal);
        $this->assertEquals(9.50, $order->commission_amount);
        $this->assertSame('Pending', $order->commissionStatus());
        $this->assertSame(8, $product->fresh()->prd_balance);
        $this->assertDatabaseCount('customer_orders', 1);
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('0.0', $order->items->first()->discount_percentage);
        $this->get($response->json('order.status_url'))->assertOk()->assertSee('41.00')->assertDontSee('commission')->assertDontSee($agent->agt_name);
        $this->get(route('agent.customer.status', (string) Str::uuid()))->assertNotFound();
        Mail::assertSent(CustomerOrderPlacedMail::class, 0);
    }

    public function test_hidden_products_and_invalid_clicker_cannot_be_ordered(): void
    {
        $agent = Agent::factory()->create();
        $hidden = Product::factory()->create(['is_visible_to_agents' => false]);
        $url = URL::signedRoute('agent.customer.store', ['referrer' => $agent->id]);
        $this->postJson($url, $this->payload($hidden))->assertUnprocessable()->assertJsonValidationErrors('items.0.product_id');
        $clicker = Product::factory()->create(['product_type' => 'clicker']);
        $this->postJson($url, $this->payload($clicker))->assertUnprocessable();
        $this->assertDatabaseCount('customer_orders', 0);
    }

    public function test_commission_requires_paid_completed_and_cannot_be_paid_twice(): void
    {
        Storage::fake('local');
        $admin = AdminUser::factory()->superAdmin()->create();
        $agent = Agent::factory()->create();
        $oldTotal = $agent->total_sale;
        $product = Product::factory()->create(['price_selling' => 19, 'prd_balance' => 10]);
        $order = $this->place($agent, $product);
        $this->actingAs($admin, 'admin');
        $payout = route('admin.customer-orders.payout', $order);
        $data = ['expected_cash' => 9.50, 'proof' => UploadedFile::fake()->image('payment.jpg'), 'expected_amount' => 9.50, 'reference' => 'BANK-001', 'reason' => 'Weekly payment'];
        $this->post($payout, $data)->assertSessionHasErrors('commission');
        foreach ([['action' => 'process'], ['action' => 'complete'], ['action' => 'payment', 'payment_status' => 'paid']] as $action) {
            $this->put(route('admin.customer-orders.update', $order), [...$action, 'reason' => 'Verified by admin'])->assertSessionHasNoErrors();
        }
        $this->assertEquals($oldTotal, $agent->fresh()->total_sale);
        $this->assertSame('Eligible', $order->fresh()->commissionStatus());
        $this->get(route('admin.customer-orders.show', $order))->assertOk();
        $this->post($payout, $data)->assertSessionHasNoErrors();
        $this->assertSame('Paid', $order->fresh()->commissionStatus());
        $this->post($payout, $data)->assertSessionHasErrors('commission');
        $this->assertSame(1, DB::table('customer_commission_entries')->where('type', 'payment')->count());
        $this->put(route('admin.customer-orders.update', $order), ['action' => 'refund', 'refunded_product_amount' => 19, 'reason' => 'One unit returned'])->assertSessionHasNoErrors();
        $this->assertSame('Adjustment due', $order->fresh()->commissionStatus());
        $this->assertSame(475, $order->fresh()->earnedCommissionCents());
        $next = $this->place($agent, $product);
        $next->forceFill(['status' => 'completed', 'payment_status' => 'paid'])->save();
        $this->post(route('admin.customer-orders.payout', $next), $data)->assertSessionHasErrors('commission');
        $data['expected_cash'] = 4.75;
        $this->post(route('admin.customer-orders.payout', $next), $data)->assertSessionHasNoErrors();
        $this->assertSame('Paid', $next->fresh()->commissionStatus());
        $this->assertSame('Paid', $order->fresh()->commissionStatus());
        $this->assertEquals(4.75, DB::table('customer_commission_entries')->where('order_id', $next->id)->where('type', 'payment')->value('cash_amount'));

    }

    public function test_customer_clicker_stock_preorder_and_price_change_rollback(): void
    {
        $agent = Agent::factory()->create();
        $product = Product::factory()->create(['product_type' => 'clicker', 'prd_balance' => 3]);
        $product->forceFill(['casing_stock_enabled' => true])->save();
        $casing = DB::table('product_clicker_images')->insertGetId(['product_id' => $product->id, 'image_type' => 'casing', 'position' => 1, 'image_path' => 'casing.jpg']);
        $huruf = DB::table('product_clicker_images')->insertGetId(['product_id' => $product->id, 'image_type' => 'huruf', 'position' => 1, 'image_path' => 'huruf.jpg']);
        DB::table('product_clicker_prices')->insert(['product_id' => $product->id, 'character_count' => 3, 'price_rm' => 10]);
        DB::table('product_clicker_stocks')->insert(['casing_image_id' => $casing, 'character_count' => 3, 'quantity' => 3]);
        $payload = $this->payload($product);
        $payload['expected_total'] = 23;
        $payload['items'][0] += ['clicker_character_count' => 3, 'clicker_characters' => ['A', 'L', 'I'], 'clicker_casing_image_id' => $casing, 'clicker_huruf_image_id' => $huruf];
        $url = URL::signedRoute('agent.customer.store', ['referrer' => $agent->id]);
        $this->postJson($url, $payload)->assertCreated();
        $this->assertEquals(1, DB::table('product_clicker_stocks')->where('casing_image_id', $casing)->value('quantity'));
        $payload['idempotency_key'] = (string) Str::uuid();
        $this->postJson($url, $payload)->assertUnprocessable();
        $this->assertDatabaseCount('customer_orders', 1);
        DB::table('product_clicker_stocks')->where('casing_image_id', $casing)->update(['quantity' => 0]);
        $product->forceFill(['prd_balance' => 0])->save();
        $this->postJson($url, $payload)->assertCreated();
        $this->assertTrue(CustomerOrder::latest('id')->first()->items->first()->is_preorder);
        $this->assertSame(0, $product->fresh()->prd_balance);
        $standard = Product::factory()->create(['price_selling' => 19, 'prd_balance' => 10]);
        $payload = $this->payload($standard);
        $standard->update(['price_selling' => 20]);
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('total');
        $this->assertSame(10, $standard->fresh()->prd_balance);
    }

    public function test_customer_payment_proof_is_private_and_does_not_mark_order_paid(): void
    {
        Storage::fake('local');
        $agent = Agent::factory()->create();
        $order = $this->place($agent, Product::factory()->create());
        $this->post(route('agent.customer.proof', $order->tracking_token), ['proof' => UploadedFile::fake()->image('proof.jpg')])->assertRedirect();
        $order->refresh();
        Storage::disk('local')->assertExists($order->paymentProofPaths()[0]);
        $this->assertSame('unpaid', $order->payment_status);
        $this->get(route('admin.customer-orders.proof', [$order, 0]))->assertRedirect();
        $admin = AdminUser::factory()->superAdmin()->create();
        $this->actingAs($admin, 'admin')->get(route('admin.customer-orders.proof', [$order, 0]))->assertOk();
        $staff = AdminUser::factory()->create();
        $this->actingAs($staff, 'admin')->get(route('admin.customer-orders.show', $order))->assertForbidden();
        $this->actingAs($staff, 'admin')->post(route('admin.customer-orders.payout', $order), [])->assertForbidden();
    }

    public function test_agent_share_card_and_qr_are_available_only_after_login(): void
    {
        $this->get(route('agent.customer.qr'))->assertRedirect();
        $agent = Agent::factory()->create();
        $this->actingAs($agent, 'agent')->get(route('agent.dashboard'))->assertOk()->assertSeeText('Kongsi Katalog & Jana Komisen');
        $this->get(route('agent.customer.qr'))->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->assertSee('<svg', false);
    }

    public function test_cancel_restores_stock_and_agent_only_sees_own_commission(): void
    {
        Storage::fake('local');
        $admin = AdminUser::factory()->superAdmin()->create();
        $agent = Agent::factory()->create();
        $other = Agent::factory()->create();
        $product = Product::factory()->create(['prd_balance' => 10]);
        $order = $this->place($agent, $product);
        $this->actingAs($other, 'agent')->get(route('agent.customer.commissions'))->assertOk()->assertDontSee($order->order_number);
        $this->actingAs($agent, 'agent')->get(route('agent.customer.commissions'))->assertOk()->assertSee($order->order_number)->assertDontSee('Customer address')->assertDontSee('0123456789');
        $this->actingAs($admin, 'admin')->put(route('admin.customer-orders.update', $order), ['action' => 'cancel', 'reason' => 'Customer cancelled'])->assertSessionHasNoErrors();
        $this->assertSame(10, $product->fresh()->prd_balance);
        $this->assertSame('Cancelled', $order->fresh()->commissionStatus());
    }
}
