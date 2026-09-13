<?php

namespace App\Actions\Salary;

use App\Models\AdminUser;
use App\Models\BusinessSiteOperation;
use App\Models\SalaryPayment;
use App\Support\AdminActivity;
use App\Support\StaffSalaryCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConfirmStaffSalary
{
    public function __construct(private StaffSalaryCalculator $calculator) {}

    public function handle(Request $request, int $operationId, array $data): array
    {
        return DB::transaction(function () use ($request, $operationId, $data): array {
            BusinessSiteOperation::whereKey($operationId)->lockForUpdate()->firstOrFail();
            $draft = DB::table('staff_salary_drafts')->where('business_site_operation_id', $operationId)->lockForUpdate()->first();
            abort_unless($draft, 404);
            if ($draft->confirmed_at) {
                return [];
            }
            if ($draft->version !== (int) $data['expected_version']) {
                throw ValidationException::withMessages(['expected_version' => 'Draf berubah. Buka dan semak draf terkini.']);
            }
            $snapshot = json_decode($draft->snapshot, true, flags: JSON_THROW_ON_ERROR);
            $ids = array_column($snapshot['staff'], 'staff_id');
            AdminUser::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            $fresh = $this->calculator->calculate(['operation_id' => $operationId, 'staff_ids' => $ids, 'weights' => collect($snapshot['staff'])->mapWithKeys(fn ($row) => [$row['staff_id'] => $row['weight'] / 100])->all(), 'rate' => $snapshot['rate'] / 100]);
            if ($fresh['overlaps'] || ! hash_equals($snapshot['hash'], $fresh['hash'])) {
                throw ValidationException::withMessages(['expected_version' => 'Jualan, staf atau bayaran berubah. Semak dan simpan semula draf dahulu.']);
            }
            if ($data['paid_date'] < $snapshot['work_date'] || $snapshot['pool_cents'] <= 0) {
                throw ValidationException::withMessages(['paid_date' => 'Tarikh bayaran mesti pada atau selepas tarikh kerja dan tabung gaji mestilah positif.']);
            }
            foreach ($snapshot['staff'] as $row) {
                if ($row['amount_cents'] > 0 && ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    throw ValidationException::withMessages(['expected_version' => 'Lengkapkan e-mel staf '.$row['name'].' dan simpan semula draf.']);
                }
            }
            $paymentIds = [];
            foreach ($snapshot['staff'] as $row) {
                if ($row['amount_cents'] === 0) {
                    continue;
                }
                $key = 'admin:'.$row['staff_id'];
                $site = preg_replace('/\s+/u', ' ', trim($snapshot['site_name']));
                $payment = SalaryPayment::create([
                    'staff_salary_draft_id' => $draft->id, 'submission_token' => (string) Str::uuid(),
                    'recipient_key' => $key, 'recipient_name' => $row['name'], 'recipient_email' => $row['email'],
                    'work_date' => $snapshot['work_date'], 'paid_date' => $data['paid_date'], 'site_name' => $site,
                    'duplicate_key' => hash('sha256', $key.'|'.$snapshot['work_date'].'|'.mb_strtolower($site)),
                    'amount_cents' => $row['amount_cents'], 'reference' => $data['reference'], 'reason' => $draft->reason,
                    'created_by' => $request->user('admin')->id,
                ]);
                $paymentIds[] = $payment->id;
            }
            DB::table('staff_salary_drafts')->where('id', $draft->id)->update(['confirmed_at' => now(), 'updated_by' => $request->user('admin')->id, 'updated_at' => now()]);
            AdminActivity::record($request, 'admin.salary.confirmed', 'Bayaran gaji sesi '.$operationId.' disahkan', $request->user('admin'), ['draft_id' => $draft->id, 'payment_ids' => $paymentIds, 'paid_date' => $data['paid_date'], 'reference' => $data['reference']]);

            return $paymentIds;
        });
    }
}
