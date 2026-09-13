<?php

namespace App\Jobs;

use App\Mail\StaffSalarySlipMail;
use App\Models\SalaryPayment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendStaffSalarySlip implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $paymentId) {}

    public function backoff(): array
    {
        return [10, 60, 180];
    }

    public function handle(): void
    {
        DB::transaction(function (): void {
            $payment = SalaryPayment::whereKey($this->paymentId)->lockForUpdate()->firstOrFail();
            if ($payment->email_sent_at || ! $payment->staff_salary_draft_id || ! str_starts_with($payment->recipient_key, 'admin:')) {
                return;
            }
            Mail::to($payment->recipient_email)->send(new StaffSalarySlipMail($payment));
            $payment->update(['email_sent_at' => now(), 'email_error' => null]);
        });
    }

    public function failed(?Throwable $exception): void
    {
        SalaryPayment::whereKey($this->paymentId)->whereNull('email_sent_at')->update(['email_error' => 'E-mel gagal dihantar. Sila cuba semula.']);
    }
}
