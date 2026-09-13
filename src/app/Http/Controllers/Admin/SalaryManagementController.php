<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHistoricalSalaryRequest;
use App\Models\AdminUser;
use App\Models\Agent;
use App\Models\SalaryPayment;
use App\Support\AdminActivity;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SalaryManagementController extends Controller
{
    public function index(Request $request): View
    {
        $tab = match ($request->route()->getName()) {
            'admin.salary-management.daily' => 'daily',
            'admin.salary-management.payslips' => 'payslips',
            default => 'overview',
        };
        if (! Schema::hasTable('salary_payments')) {
            return view('admin.salary-management.index', compact('tab'));
        }
        $month = (string) $request->query('month', now()->format('Y-m'));
        if (! preg_match('/^202[6-9]-(0[1-9]|1[0-2])$/', $month)) {
            $month = now()->format('Y-m');
        }
        $recipientType = $request->query('recipient_type') === 'agent' ? 'agent' : 'admin';
        $categoryLabel = $recipientType === 'agent' ? 'Ejen' : 'Staf';
        $base = SalaryPayment::query()->where('recipient_key', 'like', $recipientType.':%');
        $start = CarbonImmutable::parse($month.'-01');
        $query = (clone $base)->whereBetween('paid_date', [$start->toDateString(), $start->endOfMonth()->toDateString()]);
        $total = (int) (clone $query)->sum('amount_cents');
        $breakdown = (clone $query)->selectRaw('recipient_key, MAX(recipient_name) as recipient_name, SUM(amount_cents) as amount_cents')->groupBy('recipient_key')->orderByDesc('amount_cents')->get();
        $recipients = $recipientType === 'agent'
            ? Agent::query()->orderBy('agt_name')->get(['id', 'agt_name'])->toBase()->mapWithKeys(fn ($agent) => ['agent:'.$agent->id => $agent->agt_name])
            : AdminUser::query()->orderBy('name')->get(['id', 'name'])->toBase()->mapWithKeys(fn ($admin) => ['admin:'.$admin->id => $admin->name]);

        return view('admin.salary-management.history', [
            'pendingDraftCents' => $recipientType === 'admin' && Schema::hasColumn('staff_salary_drafts', 'confirmed_at') ? DB::table('staff_salary_drafts')->whereNull('confirmed_at')->get(['snapshot'])->sum(fn ($draft) => (int) (json_decode($draft->snapshot, true)['pool_cents'] ?? 0)) : 0,
            'recipientType' => $recipientType, 'categoryLabel' => $categoryLabel,
            'tab' => $tab, 'month' => $month, 'total' => $total, 'breakdown' => $breakdown, 'recipients' => $recipients,
            'paidToday' => (int) (clone $base)->whereDate('paid_date', now()->toDateString())->sum('amount_cents'),
            'recipientCount' => (clone $base)->distinct()->count('recipient_key'),
            'payments' => (clone $query)->orderByDesc('paid_date')->orderByDesc('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function store(StoreHistoricalSalaryRequest $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('salary_payments'), 503, 'Modul rekod gaji belum diaktifkan.');
        $data = $request->validated();
        $path = null;
        try {
            $payment = DB::transaction(function () use ($request, $data, &$path): SalaryPayment {
                [$type, $id] = explode(':', $data['recipient_key']);
                $recipient = ($type === 'agent' ? Agent::query() : AdminUser::query())->whereKey($id)->lockForUpdate()->first();
                if (! $recipient) {
                    throw ValidationException::withMessages(['recipient_key' => 'Staf / ejen tidak ditemui.']);
                }
                $existing = SalaryPayment::where('submission_token', $data['submission_token'])->first();
                if ($existing) {
                    if ($existing->created_by !== $request->user('admin')->id) {
                        abort(409);
                    }

                    return $existing;
                }
                $site = preg_replace('/\s+/u', ' ', trim($data['site_name']));
                $duplicateKey = hash('sha256', $data['recipient_key'].'|'.$data['work_date'].'|'.mb_strtolower($site));
                if (SalaryPayment::where('duplicate_key', $duplicateKey)->exists()) {
                    throw ValidationException::withMessages(['work_date' => 'Gaji staf untuk tarikh dan tapak ini sudah direkodkan. Semak rekod asal.']);
                }
                $overlaps = $type === 'agent' ? DB::table('weekly_closing_agent_summaries as s')->join('weekly_closings as w', 'w.id', '=', 's.weekly_closing_id')
                    ->where('s.agent_id', $id)->where('s.payout_status', 'paid')->whereDate('w.period_start', '<=', $data['work_date'])->whereDate('w.period_end', '>=', $data['work_date'])->pluck('w.week_key')->all() : [];
                if ($overlaps && empty($data['separate_payment'])) {
                    throw ValidationException::withMessages(['separate_payment' => 'Terdapat Weekly Closing dibayar: '.implode(', ', $overlaps).'. Semak dan sahkan bahawa gaji ini bayaran berasingan; jika bayaran sama, jangan rekod lagi.']);
                }
                if ($request->hasFile('proof')) {
                    $path = $request->file('proof')->store('salary-payment-proofs', 'local');
                }
                $payment = SalaryPayment::create([
                    'submission_token' => $data['submission_token'], 'recipient_key' => $data['recipient_key'],
                    'recipient_name' => $type === 'agent' ? $recipient->agt_name : $recipient->name, 'recipient_email' => $recipient->email,
                    'work_date' => $data['work_date'], 'paid_date' => $data['paid_date'], 'site_name' => $site,
                    'duplicate_key' => $duplicateKey, 'amount_cents' => (int) round((float) $data['amount'] * 100),
                    'reference' => $data['reference'] ?? null, 'reason' => $data['reason'], 'proof_path' => $path,
                    'overlap_details' => $overlaps, 'created_by' => $request->user('admin')->id,
                ]);
                AdminActivity::record($request, 'admin.salary.historical-recorded', 'Rekod bayaran terdahulu '.$payment->slipNumber(), $request->user('admin'), ['payment_id' => $payment->id, 'amount_cents' => $payment->amount_cents, 'paid_date' => $data['paid_date'], 'work_date' => $data['work_date'], 'reason' => $data['reason'], 'weekly_closing_reviewed' => $overlaps]);

                return $payment;
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return redirect()->route('admin.salary-management.show', $payment)->with('success', 'Bayaran terdahulu direkodkan. Tiada pembayaran atau e-mel dihantar.');
    }

    public function show(SalaryPayment $salaryPayment): View
    {
        return view('admin.salary-management.slip', ['payments' => collect([$salaryPayment]), 'title' => $salaryPayment->slipNumber()]);
    }

    public function summary(Request $request): View
    {
        $data = $request->validate(['recipient_key' => ['required', 'string'], 'start' => ['required', 'date_format:Y-m-d', 'after_or_equal:2026-01-01'], 'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start']]);
        $payments = SalaryPayment::where('recipient_key', $data['recipient_key'])->whereBetween('paid_date', [$data['start'], $data['end']])->orderBy('paid_date')->orderBy('id')->get();

        return view('admin.salary-management.slip', ['payments' => $payments, 'title' => 'Ringkasan Bayaran '.$data['start'].' hingga '.$data['end']]);
    }

    public function proof(SalaryPayment $salaryPayment): Response
    {
        abort_unless($salaryPayment->proof_path && str_starts_with($salaryPayment->proof_path, 'salary-payment-proofs/'), 404);

        return Storage::disk('local')->response($salaryPayment->proof_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
