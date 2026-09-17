<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BusinessSites\CorrectOperationClosure;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CorrectOperationClosureRequest;
use App\Models\BusinessSiteOperation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OperationClosureCorrectionController extends Controller
{
    public function preview(CorrectOperationClosureRequest $request, BusinessSiteOperation $businessSiteOperation, CorrectOperationClosure $correct): View
    {
        abort_unless(Schema::hasTable('operation_closure_corrections'), 503, 'Migration sejarah pembetulan sesi belum dijalankan.');
        $data = $request->validated();
        $review = $correct->review($businessSiteOperation, $data);
        $token = (string) Str::uuid();
        $request->session()->put('operation_closure.'.$token, ['operation_id' => $businessSiteOperation->id, 'admin_id' => $request->user('admin')->id, 'data' => $data, 'fingerprint' => $review['fingerprint'], 'expires' => now()->addMinutes(15)->timestamp]);

        return view('admin.business-site-operations.closure-preview', ['operation' => $businessSiteOperation, 'data' => $data, 'review' => $review, 'token' => $token]);
    }

    public function store(Request $request, BusinessSiteOperation $businessSiteOperation, CorrectOperationClosure $correct): RedirectResponse
    {
        abort_unless($request->user('admin')?->isSuperAdmin(), 403);
        $preview = $request->session()->get('operation_closure.'.(string) $request->input('token'));
        abort_unless($preview && $preview['operation_id'] === $businessSiteOperation->id && $preview['admin_id'] === $request->user('admin')->id && $preview['expires'] >= now()->timestamp, 419);
        $correct->apply($businessSiteOperation, $request->user('admin'), $preview['data'], $preview['fingerprint']);
        $request->session()->forget('operation_closure.'.(string) $request->input('token'));

        return redirect()->route('admin.business-site-operations.show', $businessSiteOperation)->with('success', 'Pembetulan penutupan sesi disimpan.');
    }
}
