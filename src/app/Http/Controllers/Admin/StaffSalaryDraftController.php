<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CalculateStaffSalaryRequest;
use App\Models\AdminUser;
use App\Models\BusinessSiteOperation;
use App\Models\SalaryPayment;
use App\Support\AdminActivity;
use App\Support\StaffSalaryCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StaffSalaryDraftController extends Controller
{
    public function index(Request $request): View
    {
        $draft = Schema::hasTable('staff_salary_drafts') && $request->filled('operation_id')
            ? DB::table('staff_salary_drafts')->where('business_site_operation_id', $request->integer('operation_id'))->first() : null;
        $snapshot = $draft ? json_decode($draft->snapshot, true, flags: JSON_THROW_ON_ERROR) : null;
        $input = $snapshot ? ['operation_id' => $snapshot['operation_id'], 'rate' => $snapshot['rate'] / 100, 'staff_ids' => array_column($snapshot['staff'], 'staff_id'), 'weights' => collect($snapshot['staff'])->mapWithKeys(fn ($row) => [$row['staff_id'] => $row['weight'] / 100])->all(), 'reason' => $draft->reason, 'expected_version' => $draft->version] : [];

        return $this->view($input, null, $snapshot, $draft);
    }

    public function preview(CalculateStaffSalaryRequest $request, StaffSalaryCalculator $calculator): View
    {
        return $this->view($request->validated(), $calculator->calculate($request->validated()));
    }

    public function store(CalculateStaffSalaryRequest $request, StaffSalaryCalculator $calculator): RedirectResponse
    {
        abort_unless(Schema::hasTable('staff_salary_drafts'), 503, 'Pengaktifan draf gaji belum selesai.');
        $data = $request->validated();
        DB::transaction(function () use ($data, $calculator, $request): void {
            BusinessSiteOperation::whereKey($data['operation_id'])->lockForUpdate()->firstOrFail();
            $snapshot = $calculator->calculate($data);
            if (! hash_equals($snapshot['hash'], $data['expected_hash'] ?? '')) {
                throw ValidationException::withMessages(['operation_id' => 'Jualan, staf atau pilihan berubah. Kira semula dan semak pratonton.']);
            }
            if ($snapshot['overlaps']) {
                throw ValidationException::withMessages(['staff_ids' => 'Terdapat bayaran gaji staf pada tarikh ini. Semak rekod asal sebelum menyimpan draf bagi mengelakkan pertindihan.']);
            }
            $draft = DB::table('staff_salary_drafts')->where('business_site_operation_id', $data['operation_id'])->lockForUpdate()->first();
            if ($draft->confirmed_at ?? null) {
                throw ValidationException::withMessages(['operation_id' => 'Bayaran telah disahkan. Draf dikunci.']);
            }
            if ((int) ($draft->version ?? 0) !== (int) $data['expected_version']) {
                throw ValidationException::withMessages(['operation_id' => 'Draf sudah berubah. Buka semula draf terkini.']);
            }
            $attributes = ['snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR), 'reason' => $data['reason'], 'updated_by' => $request->user('admin')->id, 'updated_at' => now(), 'version' => ($draft->version ?? 0) + 1];
            if ($draft) {
                DB::table('staff_salary_drafts')->where('id', $draft->id)->update($attributes);
            } else {
                DB::table('staff_salary_drafts')->insert($attributes + ['business_site_operation_id' => $data['operation_id'], 'created_at' => now()]);
            }
            AdminActivity::record($request, 'admin.salary.draft.saved', 'Draf gaji sesi '.$data['operation_id'], $request->user('admin'), ['operation_id' => $data['operation_id'], 'before' => $draft ? json_decode($draft->snapshot, true) : null, 'after' => $snapshot, 'reason' => $data['reason']]);
        });

        return redirect()->route('admin.salary-management.sessions', ['operation_id' => $data['operation_id']])->with('success', 'Draf dan kehadiran staf disimpan. Belum disahkan atau dibayar.');
    }

    private function view(array $input = [], ?array $preview = null, ?array $saved = null, ?object $draft = null): View
    {
        return view('admin.salary-management.sessions', [
            'draft' => $draft,
            'confirmationReady' => Schema::hasColumn('staff_salary_drafts', 'confirmed_at'),
            'draftPayments' => $draft && Schema::hasColumn('salary_payments', 'staff_salary_draft_id') ? SalaryPayment::where('staff_salary_draft_id', $draft->id)->get() : collect(),
            'input' => $input, 'preview' => $preview, 'saved' => $saved,
            'staff' => AdminUser::orderBy('name')->get(['id', 'name', 'status']),
            'operations' => BusinessSiteOperation::with('businessSite')->whereNotNull('closed_at')->where('opened_at', '>=', '2026-01-01')->latest('opened_at')->get(),
            'drafts' => Schema::hasTable('staff_salary_drafts') ? DB::table('staff_salary_drafts')->orderByDesc('updated_at')->paginate(15) : null,
            'ready' => Schema::hasTable('staff_salary_drafts'),
        ]);
    }
}
