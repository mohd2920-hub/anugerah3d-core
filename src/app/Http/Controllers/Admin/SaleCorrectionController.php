<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Pos\CorrectPosSale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePosSaleCorrectionRequest;
use App\Models\Agent;
use App\Models\BusinessSiteOperation;
use App\Models\PosSale;
use App\Models\Product;
use App\Support\PosClicker;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleCorrectionController extends Controller
{
    public function edit(PosSale $sale): View
    {
        abort_if($sale->voided_at, 409, 'A void sale cannot be changed.');

        return $this->form($sale->businessSiteOperation, $sale->load('items'));
    }

    public function create(BusinessSiteOperation $businessSiteOperation): View
    {
        return $this->form($businessSiteOperation);
    }

    public function preview(UpdatePosSaleCorrectionRequest $request, CorrectPosSale $correct, PosSale $sale): View
    {
        abort_unless(in_array($request->validated('action'), ['correct', 'void'], true), 422);

        return $this->comparison($request, $correct, $sale->businessSiteOperation, $sale);
    }

    public function previewMissing(UpdatePosSaleCorrectionRequest $request, CorrectPosSale $correct, BusinessSiteOperation $businessSiteOperation): View
    {
        abort_unless($request->validated('action') === 'missing', 422);

        return $this->comparison($request, $correct, $businessSiteOperation);
    }

    public function store(UpdatePosSaleCorrectionRequest $request, CorrectPosSale $correct): RedirectResponse
    {
        $token = $request->validated('token');
        $preview = $request->session()->get('sale_corrections.'.$token);
        abort_unless($preview && $preview['admin_id'] === $request->user('admin')->id && $preview['expires_at'] >= now()->timestamp, 419, 'Preview expired. Please preview again.');
        try {
            $sale = $correct->handle($request->user('admin'), $token, $preview);
        } catch (ValidationException $exception) {
            return redirect()->route($preview['sale_id'] ? 'admin.sales.edit' : 'admin.sales.create', $preview['sale_id'] ?? $preview['operation_id'])
                ->withInput($preview['data'])->withErrors($exception->errors());
        }
        $request->session()->forget('sale_corrections.'.$token);

        return redirect()->route('admin.sales.show', $sale)->with('success', 'Sale correction saved with its audit history.');
    }

    private function form(BusinessSiteOperation $operation, ?PosSale $sale = null): View
    {
        $products = Product::query()->orderBy('prd_name')->get();

        return view('admin.sales.correct', [
            'operation' => $operation->load('businessSite'),
            'sale' => $sale,
            'products' => $products,
            'posClickerCatalog' => app(PosClicker::class)->catalog($products),
            'agents' => Agent::query()->where(function ($query) use ($operation, $sale): void {
                $query->whereHas('businessSites', fn ($sites) => $sites->whereKey($operation->business_site_id));
                if ($sale) {
                    $query->orWhere('id', $sale->sales_agent_id);
                }
            })->orderBy('agt_name')->get(['id', 'agt_name']),
            'paymentMethods' => PosSale::paymentMethods(),
        ]);
    }

    private function comparison(UpdatePosSaleCorrectionRequest $request, CorrectPosSale $correct, BusinessSiteOperation $operation, ?PosSale $sale = null): View
    {
        $data = $request->validated();
        $comparison = $correct->preview($operation, $sale, $data);
        $request->session()->flashInput($data);
        $token = (string) Str::uuid();
        $request->session()->put('sale_corrections.'.$token, [
            'admin_id' => $request->user('admin')->id,
            'expires_at' => now()->addMinutes(20)->timestamp,
            'operation_id' => $operation->id,
            'sale_id' => $sale?->id,
            'data' => $data,
            'comparison' => $comparison,
        ]);

        return view('admin.sales.preview', compact('comparison', 'token', 'data', 'sale', 'operation'));
    }
}
