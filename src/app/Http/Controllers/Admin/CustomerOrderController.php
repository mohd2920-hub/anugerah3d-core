<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\ManageAdminOrder;
use App\Actions\Orders\UpdateCustomerOrderProgress;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PayCustomerCommissionRequest;
use App\Http\Requests\Admin\UpdateCustomerOrderRequest;
use App\Models\Agent;
use App\Models\CustomerOrder;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CustomerOrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $orders = CustomerOrder::query()->with('agent')->when($search !== '', fn ($q) => $q->search($search))->latest()->paginate(20)->withQueryString();

        return view('admin.customer-orders.index', compact('orders', 'search'));
    }

    public function show(CustomerOrder $customerOrder): View
    {
        $order = $customerOrder->load(['agent', 'items.product']);
        $entries = DB::table('customer_commission_entries')->where('order_id', $order->id)->orderByDesc('id')->get();
        $due = max(0, $order->earnedCommissionCents() - $order->paidCommissionCents());
        $debt = CustomerOrder::where('agent_id', $order->agent_id)->get()->sum(fn ($other) => max(0, $other->paidCommissionCents() - $other->earnedCommissionCents()));

        return view('admin.customer-orders.show', compact('order', 'entries', 'due', 'debt'));
    }

    public function update(UpdateCustomerOrderRequest $request, CustomerOrder $customerOrder, ManageAdminOrder $manage): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $customerOrder, $manage, $data): void {
            Agent::query()->whereKey($customerOrder->agent_id)->lockForUpdate()->firstOrFail();
            $order = CustomerOrder::query()->whereKey($customerOrder->id)->lockForUpdate()->firstOrFail();
            $progressAvailable = array_key_exists('receipt_snapshot', $order->getAttributes());
            if (! $progressAvailable && in_array($data['action'], ['ready', 'ship', 'pickup_ready', 'request_proof'], true)) {
                throw ValidationException::withMessages(['status' => 'Fungsi perkembangan pesanan sedang menunggu pengaktifan pangkalan data.']);
            }
            $before = $order->only(['status', 'payment_status', 'payment_instructions', 'refunded_product_amount', 'courier', 'tracking_number', 'payment_proof_requested', 'receipt_number']);
            match ($data['action']) {
                'process' => $manage->process($order, $request),
                'ready', 'ship', 'pickup_ready' => app(UpdateCustomerOrderProgress::class)->handle($order, $data['action'], $data),
                'complete' => $progressAvailable ? app(UpdateCustomerOrderProgress::class)->handle($order, 'complete', $data) : $manage->complete($order, $request),
                'request_proof' => $this->requestProof($order, $data),
                'cancel' => $manage->cancel($order, $request),
                'payment' => $manage->updatePayment($order, $data['payment_status'], $request),
                'instructions' => $order->forceFill(['payment_instructions' => $data['payment_instructions']])->save(),
                'refund' => $this->refund($order, $data, $request),
            };
            if ($progressAvailable && $data['action'] === 'payment' && $data['payment_status'] === 'paid') {
                $order->refresh()->forceFill(['payment_proof_requested' => false])->save();
                app(UpdateCustomerOrderProgress::class)->issueReceipt($order);
            }
            AdminActivity::record(request: $request, event: 'admin.customer-order.updated', description: 'Customer order '.$order->order_number.' updated.', adminUser: $request->user('admin'), properties: ['page' => 'Customer Orders', 'order_id' => $order->id, 'reason' => $data['reason'], 'before' => $before, 'after' => $order->fresh()->only(array_keys($before))]);
        });

        return back()->with('success', 'Pesanan pelanggan dikemas kini.');
    }

    private function requestProof(CustomerOrder $order, array $data): void
    {
        if ($order->payment_status !== 'unpaid' || $order->status === 'cancelled') {
            throw ValidationException::withMessages(['payment_status' => 'Permintaan resit hanya untuk bayaran yang belum disahkan.']);
        }
        $order->forceFill(['payment_proof_requested' => true, 'payment_instructions' => $data['reason']])->save();
    }

    private function refund(CustomerOrder $order, array $data, Request $request): void
    {
        $amount = round((float) $data['refunded_product_amount'], 2);
        if ($amount > (float) $order->subtotal) {
            throw ValidationException::withMessages(['refunded_product_amount' => 'Nilai pulangan tidak boleh melebihi jumlah produk.']);
        }
        DB::table('customer_commission_entries')->insert(['order_id' => $order->id, 'admin_id' => $request->user('admin')->id, 'type' => 'refund', 'amount' => $amount, 'previous_amount' => $order->refunded_product_amount, 'reason' => $data['reason'], 'created_at' => now()]);
        $order->forceFill(['refunded_product_amount' => $amount])->save();
    }

    public function payout(PayCustomerCommissionRequest $request, CustomerOrder $customerOrder): RedirectResponse
    {
        $data = $request->validated();
        $proofPath = isset($data['proof']) ? $data['proof']->store('customer-commission-proofs', 'local') : null;
        try {
            DB::transaction(function () use ($request, $customerOrder, $data, $proofPath): void {
                Agent::query()->whereKey($customerOrder->agent_id)->lockForUpdate()->firstOrFail();
                $orders = CustomerOrder::query()->where('agent_id', $customerOrder->agent_id)->orderBy('id')->lockForUpdate()->get();
                $order = $orders->firstWhere('id', $customerOrder->id);
                $due = $order->earnedCommissionCents() - $order->paidCommissionCents();
                if ($order->commissionStatus() !== 'Eligible' || $due <= 0 || $due !== (int) round((float) $data['expected_amount'] * 100)) {
                    throw ValidationException::withMessages(['commission' => 'Komisen belum layak, telah dibayar atau nilainya berubah. Muat semula halaman.']);
                }
                $totalDebt = $orders->sum(fn ($previous) => max(0, $previous->paidCommissionCents() - $previous->earnedCommissionCents()));
                $netCash = max(0, $due - $totalDebt);
                if ($netCash !== (int) round((float) $data['expected_cash'] * 100)) {
                    throw ValidationException::withMessages(['commission' => 'Jumlah bayaran selepas pelarasan berubah. Muat semula halaman.']);
                }
                if ($netCash > 0 && ! $proofPath) {
                    throw ValidationException::withMessages(['proof' => 'Bukti bayaran komisen wajib dimuat naik.']);
                }
                $cash = $due;
                foreach ($orders as $previous) {
                    $debt = max(0, $previous->paidCommissionCents() - $previous->earnedCommissionCents());
                    $recovery = min($cash, $debt);
                    if ($recovery > 0) {
                        DB::table('customer_commission_entries')->insert(['order_id' => $previous->id, 'admin_id' => $request->user('admin')->id, 'type' => 'payment', 'amount' => -$recovery / 100, 'cash_amount' => 0, 'reference' => $data['reference'], 'reason' => 'Pelarasan melalui '.$order->order_number.': '.$data['reason'], 'created_at' => now()]);
                        $cash -= $recovery;
                    }
                }
                DB::table('customer_commission_entries')->insert(['order_id' => $order->id, 'admin_id' => $request->user('admin')->id, 'type' => 'payment', 'amount' => $due / 100, 'cash_amount' => $cash / 100, 'proof_path' => $proofPath, 'reference' => $data['reference'], 'reason' => $data['reason'], 'created_at' => now()]);
                AdminActivity::record(request: $request, event: 'admin.customer-commission.paid', description: 'Commission settled for '.$order->order_number, adminUser: $request->user('admin'), properties: ['page' => 'Customer Orders', 'order_id' => $order->id, 'agent_id' => $order->agent_id, 'commission' => $due / 100, 'cash_paid' => $cash / 100, 'reference' => $data['reference'], 'reason' => $data['reason']]);
            });

        } catch (\Throwable $exception) {
            if ($proofPath) {
                Storage::disk('local')->delete($proofPath);
            }
            throw $exception;
        }

        return back()->with('success', 'Bayaran komisen direkodkan.');
    }

    public function commissionProof(CustomerOrder $customerOrder, int $entry): Response
    {
        $path = DB::table('customer_commission_entries')->where('order_id', $customerOrder->id)->where('id', $entry)->value('proof_path');
        abort_unless($path && str_starts_with($path, 'customer-commission-proofs/'), 404);

        return Storage::disk('local')->response($path);
    }

    public function proof(CustomerOrder $customerOrder, int $index): Response
    {
        $path = $customerOrder->paymentProofPaths()[$index] ?? null;
        abort_unless($path && str_starts_with($path, 'customer-payment-proofs/'), 404);

        return Storage::disk('local')->response($path);
    }
}
