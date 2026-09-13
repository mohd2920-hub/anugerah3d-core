<?php

namespace App\Http\Controllers;

use App\Actions\Orders\PlaceAgentOrder;
use App\Http\Controllers\Agent\OrderController;
use App\Http\Requests\ConfirmCustomerReceiptRequest;
use App\Http\Requests\StoreCustomerOrderRequest;
use App\Http\Requests\StoreCustomerPaymentProofRequest;
use App\Models\Agent;
use App\Models\CustomerOrder;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class CustomerCatalogueController extends Controller
{
    public function show(Agent $referrer, OrderController $catalogue): View
    {
        abort_unless($referrer->agt_status === Agent::StatusActive, 404);

        return view('customer.catalogue', [
            ...$catalogue->catalogueData(), 'customerCatalogue' => true,
            'agent' => new Agent, 'referrer' => $referrer,
            'checkoutAction' => URL::signedRoute('agent.customer.store', ['referrer' => $referrer->id]),
        ]);
    }

    public function store(StoreCustomerOrderRequest $request, Agent $referrer, PlaceAgentOrder $place): JsonResponse
    {
        abort_unless($referrer->agt_status === Agent::StatusActive, 404);
        $order = $place->handleCustomer($referrer, $request->validated(), $request);

        return response()->json(['order' => ['number' => $order->order_number, 'total' => $order->total_amount, 'status_url' => route('agent.customer.status', $order->tracking_token)]], 201);
    }

    public function status(string $token): Response
    {
        $order = CustomerOrder::query()->where('tracking_token', $token)->with('items')->firstOrFail();

        if ($order->customer_received_at) {
            return response()->view('customer.expired', [], 410)->header('Cache-Control', 'private, no-store');
        }

        return response()->view('customer.status', compact('order'))->header('Cache-Control', 'private, no-store');
    }

    public function proof(StoreCustomerPaymentProofRequest $request, string $token): Response
    {
        $data = $request->validated();
        $path = $data['proof']->store('customer-payment-proofs', 'local');
        try {
            DB::transaction(function () use ($token, $path): void {
                $order = CustomerOrder::query()->where('tracking_token', $token)->lockForUpdate()->firstOrFail();
                abort_if($order->customer_received_at, 410);
                abort_unless($order->payment_status === 'unpaid' && $order->status !== 'cancelled', 422);
                $paths = $order->paymentProofPaths();
                abort_if(count($paths) >= 5 && ! $order->payment_proof_requested, 422, 'Maximum 5 payment proofs.');
                if (array_key_exists('payment_proof_requested', $order->getAttributes())) {
                    $order->payment_proof_requested = false;
                }
                $order->forceFill(['payment_method' => 'bank_transfer', 'payment_proof_paths' => [...$paths, $path]])->save();
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return back()->with('success', 'Bukti pembayaran dihantar. Menunggu semakan admin.');
    }

    public function receipt(string $token): Response
    {
        $order = CustomerOrder::query()->where('tracking_token', $token)->firstOrFail();
        if ($order->customer_received_at) {
            return response()->view('customer.expired', [], 410)->header('Cache-Control', 'private, no-store');
        }
        abort_unless($order->payment_status === 'paid' && $order->receipt_number && $order->receipt_snapshot, 404);

        return response()->view('customer.receipt', ['order' => $order, 'receipt' => $order->receipt_snapshot])->header('Cache-Control', 'private, no-store');
    }

    public function confirmReceipt(ConfirmCustomerReceiptRequest $request, string $token): Response
    {
        DB::transaction(function () use ($token): void {
            $order = CustomerOrder::query()->where('tracking_token', $token)->lockForUpdate()->firstOrFail();
            if ($order->customer_received_at) {
                return;
            }
            abort_unless($order->canConfirmReceipt(), 422, 'Penerimaan belum boleh disahkan.');
            $order->forceFill(['customer_received_at' => now()])->save();
        });

        return redirect()->route('agent.customer.status', $token);
    }

    public function commissions(Request $request): View
    {
        $orders = CustomerOrder::query()->where('agent_id', $request->user('agent')->id)->latest()->paginate(20);

        return view('agent.customer-commissions', ['agent' => $request->user('agent'), 'orders' => $orders]);
    }

    public function commissionDetail(Request $request, int $order): View
    {
        $ownedOrder = CustomerOrder::query()->where('agent_id', $request->user('agent')->id)->findOrFail($order);
        $entries = DB::table('customer_commission_entries')->where('order_id', $ownedOrder->id)->where('type', 'payment')->orderByDesc('id')->get(['id', 'amount', 'cash_amount', 'reference', 'proof_path', 'created_at']);

        return view('agent.customer-commission-detail', ['agent' => $request->user('agent'), 'order' => $ownedOrder, 'entries' => $entries]);
    }

    public function commissionProof(Request $request, int $order, int $entry): Response
    {
        $ownedOrder = CustomerOrder::query()->where('agent_id', $request->user('agent')->id)->findOrFail($order);
        $path = DB::table('customer_commission_entries')->where('order_id', $ownedOrder->id)->where('id', $entry)->where('type', 'payment')->value('proof_path');
        abort_unless($path && str_starts_with($path, 'customer-commission-proofs/') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function qr(Request $request): Response
    {
        $url = URL::signedRoute('agent.customer.catalogue', ['referrer' => $request->user('agent')->id]);
        $svg = (new QRCode(new QROptions(['outputBase64' => false])))->render($url);

        return response($svg, 200, ['Content-Type' => 'image/svg+xml', 'Content-Disposition' => 'attachment; filename="katalog-pelanggan.svg"']);
    }
}
