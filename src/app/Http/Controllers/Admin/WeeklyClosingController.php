<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateWeeklyClosingPaymentRequest;
use App\Mail\Agent\WeeklyClosingPaymentMadeMail;
use App\Models\WeeklyClosing;
use App\Models\WeeklyClosingAgentSummary;
use App\Support\AdminActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WeeklyClosingController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $closings = WeeklyClosing::query()
            ->when($search !== '', fn (Builder $query): Builder => $query->where('week_key', 'like', "%{$search}%"))
            ->withCount([
                'agentSummaries',
                'agentSummaries as pending_payout_count' => fn (Builder $query): Builder => $query->where('payout_status', 'pending'),
                'agentSummaries as paid_payout_count' => fn (Builder $query): Builder => $query->where('payout_status', 'paid'),
            ])
            ->latest('period_end')
            ->paginate(20)
            ->withQueryString();

        return view('admin.weekly-closings.index', [
            'closings' => $closings,
            'search' => $search,
        ]);
    }

    public function show(WeeklyClosing $weeklyClosing, Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'payout_status' => $request->string('payout_status')->trim()->toString(),
        ];

        $rows = $weeklyClosing->agentSummaries()
            ->with(['agent:id,agt_name,email', 'paidByAdmin:id,name'])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): Builder {
                return $query->where(function (Builder $query) use ($filters): void {
                    $query->where('agent_name', 'like', "%{$filters['search']}%")
                        ->orWhere('agent_email', 'like', "%{$filters['search']}%");
                });
            })
            ->when(in_array($filters['payout_status'], ['pending', 'paid', 'no_payout'], true), fn (Builder $query): Builder => $query->where('payout_status', $filters['payout_status']))
            ->orderByDesc('total_bonus')
            ->orderBy('agent_name')
            ->paginate(25)
            ->withQueryString();

        $summaryBaseQuery = $weeklyClosing->agentSummaries();

        return view('admin.weekly-closings.show', [
            'weeklyClosing' => $weeklyClosing,
            'rows' => $rows,
            'filters' => $filters,
            'summary' => [
                'total_rows' => (clone $summaryBaseQuery)->count(),
                'pending_rows' => (clone $summaryBaseQuery)->where('payout_status', 'pending')->count(),
                'paid_rows' => (clone $summaryBaseQuery)->where('payout_status', 'paid')->count(),
                'pending_bonus' => (float) (clone $summaryBaseQuery)->where('payout_status', 'pending')->sum('total_bonus'),
                'paid_bonus' => (float) (clone $summaryBaseQuery)->where('payout_status', 'paid')->sum('total_bonus'),
            ],
        ]);
    }

    public function updatePayment(UpdateWeeklyClosingPaymentRequest $request, WeeklyClosing $weeklyClosing, WeeklyClosingAgentSummary $agentSummary): RedirectResponse
    {
        abort_unless($agentSummary->weekly_closing_id === $weeklyClosing->id, 404);

        $previousStatus = (string) $agentSummary->payout_status;
        $status = $request->validated('payout_status');
        $attachmentPath = $agentSummary->payment_attachment_path;

        if ($request->hasFile('payment_attachment')) {
            $attachmentPath = $this->storePaymentAttachment($request->file('payment_attachment'));
            $this->deleteAttachment($agentSummary->payment_attachment_path);
        }

        $notifyAgent = (bool) $request->validated('notify_agent');
        $didNotifyAgent = false;

        if ($status === 'paid') {
            $agentSummary->forceFill([
                'payout_status' => 'paid',
                'paid_at' => now(),
                'paid_by_admin_id' => $request->user('admin')?->getKey(),
                'payment_reference' => $request->validated('payment_reference'),
                'payment_receipt_datetime_text' => $request->validated('payment_receipt_datetime_text'),
                'payment_attachment_path' => $attachmentPath,
                'payment_notes' => $request->validated('payment_notes'),
            ])->save();

            if ($notifyAgent && ($agentSummary->agent_email || $agentSummary->agent?->email)) {
                Mail::to((string) ($agentSummary->agent?->email ?? $agentSummary->agent_email))
                    ->send(new WeeklyClosingPaymentMadeMail($agentSummary->id));

                $agentSummary->forceFill([
                    'notified_agent_at' => now(),
                ])->save();

                $didNotifyAgent = true;

                AdminActivity::record(
                    request: $request,
                    event: 'weekly_closing.payment.agent_notified',
                    description: 'Admin notified agent about weekly closing payment.',
                    adminUser: $request->user('admin'),
                    properties: [
                        'page' => 'Weekly Closings',
                        'weekly_closing_id' => $weeklyClosing->id,
                        'week_key' => $weeklyClosing->week_key,
                        'agent_summary_id' => $agentSummary->id,
                        'agent_id' => $agentSummary->agent_id,
                        'agent_name' => $agentSummary->agent_name,
                        'agent_email' => $agentSummary->agent_email,
                    ],
                );
            }

            AdminActivity::record(
                request: $request,
                event: 'weekly_closing.payment.updated',
                description: 'Admin updated weekly closing payment to paid.',
                adminUser: $request->user('admin'),
                properties: [
                    'page' => 'Weekly Closings',
                    'weekly_closing_id' => $weeklyClosing->id,
                    'week_key' => $weeklyClosing->week_key,
                    'agent_summary_id' => $agentSummary->id,
                    'agent_id' => $agentSummary->agent_id,
                    'agent_name' => $agentSummary->agent_name,
                    'from_status' => $previousStatus,
                    'to_status' => 'paid',
                    'notify_agent' => $didNotifyAgent,
                    'has_attachment' => $attachmentPath !== null,
                ],
            );

            return back()->with('success', 'Payment updated to paid for '.$agentSummary->agent_name.($didNotifyAgent ? ' Agent has been notified by email.' : '.'));
        }

        $agentSummary->forceFill([
            'payout_status' => 'pending',
            'paid_at' => null,
            'notified_agent_at' => null,
            'paid_by_admin_id' => null,
            'payment_reference' => $request->validated('payment_reference'),
            'payment_receipt_datetime_text' => $request->validated('payment_receipt_datetime_text'),
            'payment_attachment_path' => $attachmentPath,
            'payment_notes' => $request->validated('payment_notes'),
        ])->save();

        AdminActivity::record(
            request: $request,
            event: 'weekly_closing.payment.updated',
            description: 'Admin updated weekly closing payment to pending.',
            adminUser: $request->user('admin'),
            properties: [
                'page' => 'Weekly Closings',
                'weekly_closing_id' => $weeklyClosing->id,
                'week_key' => $weeklyClosing->week_key,
                'agent_summary_id' => $agentSummary->id,
                'agent_id' => $agentSummary->agent_id,
                'agent_name' => $agentSummary->agent_name,
                'from_status' => $previousStatus,
                'to_status' => 'pending',
                'has_attachment' => $attachmentPath !== null,
            ],
        );

        return back()->with('success', 'Payment updated to pending for '.$agentSummary->agent_name.'.');
    }

    private function storePaymentAttachment(UploadedFile $file): string
    {
        $extension = $file->guessExtension() ?: 'jpg';
        $filename = 'weekly-payment-'.Str::uuid().'.'.$extension;
        $relativePath = 'weekly-closing-payments/'.$filename;
        $directory = public_path('weekly-closing-payments');

        File::ensureDirectoryExists($directory);
        $file->move($directory, $filename);

        return $relativePath;
    }

    private function deleteAttachment(?string $path): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        if (File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
    }
}
