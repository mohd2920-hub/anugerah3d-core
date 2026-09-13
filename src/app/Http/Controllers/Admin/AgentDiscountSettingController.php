<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAgentDiscountSettingRequest;
use App\Models\AgentDiscountSetting;
use App\Support\AdminActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgentDiscountSettingController extends Controller
{
    public function update(UpdateAgentDiscountSettingRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $setting = AgentDiscountSetting::query()->lockForUpdate()->findOrFail(1);
            if ($setting->version !== (int) $request->validated('version')) {
                throw ValidationException::withMessages(['version' => 'Kadar telah berubah. Muat semula halaman sebelum menyimpan.']);
            }
            $before = $setting->only(['below_rm20', 'below_rm100', 'at_least_rm100']);
            $setting->fill($request->safe()->only(array_keys($before)));
            if (! $setting->isDirty()) {
                return;
            }
            $setting->version++;
            $setting->save();
            AdminActivity::record($request, 'admin.orders.discount.updated', 'Superadmin mengubah kadar diskaun Orders.', $request->user('admin'), [
                'before' => $before,
                'after' => $setting->only(array_keys($before)),
                'version' => $setting->version,
                'changed_by' => $request->user('admin')->name,
            ]);
        });

        return redirect()->route('admin.orders.index')->with('success', 'Tetapan diskaun disimpan. Kadar semasa digunakan untuk pesanan baharu.');
    }
}
