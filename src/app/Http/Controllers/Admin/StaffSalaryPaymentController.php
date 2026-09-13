<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Salary\ConfirmStaffSalary;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmStaffSalaryRequest;
use App\Jobs\SendStaffSalarySlip;
use App\Models\SalaryPayment;
use App\Support\AdminActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StaffSalaryPaymentController extends Controller
{
    public function store(ConfirmStaffSalaryRequest $request, int $operation, ConfirmStaffSalary $action): RedirectResponse
    {
        abort_unless(Schema::hasColumn('staff_salary_drafts', 'confirmed_at'), 503, 'Pengesahan gaji belum diaktifkan.');
        $ids = $action->handle($request, $operation, $request->validated());
        foreach ($ids as $id) {
            $this->dispatchSlip(SalaryPayment::findOrFail($id));
        }

        return redirect()->route('admin.salary-management.sessions', ['operation_id' => $operation])->with('success', 'Bayaran disahkan dan slip tersedia. Semak status penghantaran e-mel pada slip staf.');
    }

    public function retry(Request $request, SalaryPayment $salaryPayment): RedirectResponse
    {
        abort_unless($salaryPayment->staff_salary_draft_id && str_starts_with($salaryPayment->recipient_key, 'admin:'), 404);
        if (! $salaryPayment->email_sent_at) {
            $this->dispatchSlip($salaryPayment);
            AdminActivity::record($request, 'admin.salary.email-retried', 'Cuba semula e-mel '.$salaryPayment->slipNumber(), $request->user('admin'), ['payment_id' => $salaryPayment->id]);
        }

        return back()->with('success', 'Permintaan e-mel diproses. Semak status penghantaran.');
    }

    private function dispatchSlip(SalaryPayment $payment): void
    {
        try {
            SendStaffSalarySlip::dispatch($payment->id)->onConnection('database')->onQueue('staff-salary')->afterCommit();
        } catch (\Throwable $exception) {
            report($exception);
            $payment->update(['email_error' => 'Penghantaran tidak berjaya. Cuba semula.']);
        }
    }
}
