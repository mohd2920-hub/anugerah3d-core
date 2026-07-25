<?php

namespace App\Http\Controllers\Agent;

use App\Actions\Pos\CreatePosSale;
use App\Actions\Pos\UpdatePosSale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\SignInPosRequest;
use App\Http\Requests\Agent\StorePosSaleRequest;
use App\Http\Requests\Agent\UpdatePosSaleRequest;
use App\Mail\PosSaleReceipt;
use App\Models\Agent;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSession;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $agent = $request->user('agent');
        $activeSession = $this->activeSession($agent);
        $businessSites = $agent->businessSites()->orderBy('site_name')->get(['business_sites.id', 'site_name', 'city']);
        $products = $activeSession ? $this->posProducts() : collect();

        return view('agent.pos.index', [
            'activeSession' => $activeSession?->load('businessSite'),
            'businessSites' => $businessSites,
            'salesAgents' => $activeSession
                ? $activeSession->businessSite->agents()->where('agt_status', Agent::StatusActive)->orderBy('agt_name')->get(['usr_agent.id', 'agt_name', 'login_id', 'discount_percentage', 'profile_picture'])
                : collect(),
            'products' => $products,
            'topProducts' => $activeSession
                ? $this->topSellingProducts($products, $activeSession->business_site_id)
                : collect(),
            'sales' => PosSale::query()
                ->when(
                    $activeSession,
                    fn ($query) => $query
                        ->where('business_site_id', $activeSession->business_site_id)
                        ->whereBetween('sold_at', [
                            $activeSession->signed_in_at,
                            $activeSession->signed_out_at ?? now(),
                        ]),
                    fn ($query) => $query->whereRaw('1 = 0'),
                )
                ->with(['businessSite:id,site_name,city', 'salesAgent:id,agt_name', 'recordedBy:id,agt_name', 'items'])
                ->latest('sold_at')
                ->paginate(15),
            'paymentMethods' => PosSale::paymentMethods(),
        ]);
    }

    public function signIn(SignInPosRequest $request): RedirectResponse
    {
        $agent = $request->user('agent');

        DB::transaction(function () use ($agent, $request): void {
            PosSession::query()
                ->whereBelongsTo($agent)
                ->whereNull('signed_out_at')
                ->update(['signed_out_at' => now()]);

            PosSession::query()->create([
                'agent_id' => $agent->getKey(),
                'business_site_id' => $request->integer('business_site_id'),
                'signed_in_at' => now(),
            ]);
        });

        return redirect()->route('agent.pos.index')->with('success', 'You have checked in to the business site.');
    }

    public function signOut(Request $request): RedirectResponse
    {
        $session = $this->activeSession($request->user('agent'));

        if ($session !== null) {
            $session->update(['signed_out_at' => now()]);
        }

        return redirect()->route('agent.pos.index')->with('success', 'You have checked out from the business site.');
    }

    public function store(StorePosSaleRequest $request, CreatePosSale $createPosSale): RedirectResponse
    {
        $sale = $createPosSale->handle($request->activePosSession(), $request->validated());
        $message = 'Sale recorded successfully.';

        if ($sale->customer_email !== null && $sale->customer_email !== '') {
            $message = $this->sendCustomerReceipt($sale)
                ? 'Sale recorded successfully. The receipt was emailed to the customer.'
                : 'Sale recorded successfully, but the receipt email could not be sent.';
        }

        return redirect()->route('agent.pos.index', ['tab' => 'history'])->with('success', $message);
    }

    public function edit(Request $request, PosSale $posSale): View
    {
        $session = $this->activeSession($request->user('agent'));
        abort_unless($session !== null && $session->business_site_id === $posSale->business_site_id, 403);
        $products = $this->posProducts();

        return view('agent.pos.edit', [
            'activeSession' => $session->load('businessSite'),
            'posSale' => $posSale->load('items'),
            'salesAgents' => $session->businessSite->agents()->where('agt_status', Agent::StatusActive)->orderBy('agt_name')->get(['usr_agent.id', 'agt_name', 'login_id', 'discount_percentage', 'profile_picture']),
            'products' => $products,
            'topProducts' => $this->topSellingProducts($products, $session->business_site_id),
            'paymentMethods' => PosSale::paymentMethods(),
        ]);
    }

    public function update(UpdatePosSaleRequest $request, PosSale $posSale, UpdatePosSale $updatePosSale): RedirectResponse
    {
        $updatePosSale->handle($posSale, $request->activePosSession(), $request->validated());

        return redirect()->route('agent.pos.index', ['tab' => 'history'])->with('success', 'Sale updated successfully.');
    }

    public function sendReceipt(Request $request, PosSale $posSale): RedirectResponse
    {
        $session = $this->activeSession($request->user('agent'));
        abort_unless($session !== null && $session->business_site_id === $posSale->business_site_id, 403);

        if ($posSale->customer_email === null || $posSale->customer_email === '') {
            $validated = $request->validateWithBag('receipt', [
                'customer_name' => ['required', 'string', 'max:150'],
                'customer_email' => ['required', 'email:rfc', 'max:150'],
            ]);

            $posSale->update([
                'customer_name' => trim($validated['customer_name']),
                'customer_email' => trim($validated['customer_email']),
            ]);
        }

        if (! $this->sendCustomerReceipt($posSale)) {
            return redirect()
                ->route('agent.pos.index', ['tab' => 'history'])
                ->with('error', 'The receipt email could not be sent. Please try again later.');
        }

        return redirect()
            ->route('agent.pos.index', ['tab' => 'history'])
            ->with('success', 'Receipt emailed to '.$posSale->customer_email.'.');
    }

    public function destroy(Request $request, PosSale $posSale, CreatePosSale $createPosSale): RedirectResponse
    {
        $session = $this->activeSession($request->user('agent'));
        abort_unless($session !== null && $session->business_site_id === $posSale->business_site_id, 403);

        $request->validateWithBag('deleteSale', [
            'delete_password' => ['required', 'string'],
        ]);

        $agent = $request->user('agent');

        if (! Hash::check((string) $request->input('delete_password'), $agent->password)) {
            return back()
                ->withInput($request->except('delete_password'))
                ->withErrors(['delete_password' => 'The provided password is incorrect.'], 'deleteSale');
        }

        $picturePaths = [
            ...$posSale->salePicturePaths(),
            ...$posSale->paymentProofPaths(),
        ];

        $posSale->delete();
        $createPosSale->deleteStoredPictures($picturePaths);

        return redirect()
            ->route('agent.pos.index', ['tab' => 'history'])
            ->with('success', 'Sale deleted successfully.');
    }

    private function activeSession(Agent $agent): ?PosSession
    {
        return PosSession::query()
            ->active()
            ->whereBelongsTo($agent)
            ->whereHas('businessSite.agents', fn ($query) => $query->whereKey($agent->getKey()))
            ->latest('signed_in_at')
            ->first();
    }

    private function sendCustomerReceipt(PosSale $sale): bool
    {
        try {
            $sale->load([
                'businessSite:id,site_name,city',
                'salesAgent:id,agt_name,phone_number,profile_picture',
                'items.product:id,prd_name,prd_picture',
                'items.product.images:id,product_id,image_path,alt_text,position',
            ]);

            Mail::to($sale->customer_email)->send(new PosSaleReceipt($sale));

            return true;
        } catch (Throwable $exception) {
            Log::warning('Customer POS receipt email could not be sent.', [
                'pos_sale_id' => $sale->getKey(),
                'sale_number' => $sale->sale_number,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function posProducts(): Collection
    {
        return Product::query()
            ->with('images:id,product_id,image_path,alt_text,position')
            ->orderBy('prd_name')
            ->get([
                'id',
                'prd_code',
                'prd_name',
                'price_selling',
                'agent_discount_default',
                'prd_balance',
                'prd_picture',
            ]);
    }

    private function topSellingProducts(Collection $products, int $businessSiteId): Collection
    {
        $rankedProductIds = PosSaleItem::query()
            ->join('pos_sales', 'pos_sales.id', '=', 'pos_sale_items.pos_sale_id')
            ->where('pos_sales.business_site_id', $businessSiteId)
            ->select('pos_sale_items.product_id')
            ->selectRaw('SUM(pos_sale_items.quantity) as units_sold')
            ->groupBy('pos_sale_items.product_id')
            ->orderByDesc('units_sold')
            ->orderBy('pos_sale_items.product_id')
            ->limit(14)
            ->pluck('pos_sale_items.product_id')
            ->map(fn ($productId): int => (int) $productId);

        $rankedProducts = $rankedProductIds
            ->map(fn (int $productId) => $products->firstWhere('id', $productId))
            ->filter();

        return $rankedProducts
            ->concat(
                $products
                    ->whereNotIn('id', $rankedProductIds)
                    ->take(14 - $rankedProducts->count()),
            )
            ->values();
    }
}
