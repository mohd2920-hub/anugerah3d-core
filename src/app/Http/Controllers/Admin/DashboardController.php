<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardRequest;
use App\Models\BusinessSite;
use App\Support\AdminAccess;
use App\Support\DashboardInventory;
use App\Support\DashboardReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __construct(private DashboardReport $report, private DashboardInventory $inventory) {}

    public function __invoke(DashboardRequest $request): View
    {
        $user = $request->user('admin');

        return view('admin.dashboard', [
            'report' => $this->report->data($user, $request->validated()),
            'inventory' => AdminAccess::allows($user, 'products.view') ? $this->inventory->data($request->validated(), $user) : null,
            'channels' => $this->report->channels($user),
            'businessSites' => AdminAccess::allows($user, 'sales.view') ? BusinessSite::query()->orderBy('site_name')->get(['id', 'site_name']) : collect(),
        ]);
    }

    public function data(DashboardRequest $request): JsonResponse
    {
        return response()->json($this->report->data($request->user('admin'), $request->validated()))->header('Cache-Control', 'private, no-store');
    }

    public function inventory(DashboardRequest $request): JsonResponse
    {
        abort_unless(AdminAccess::allows($request->user('admin'), 'products.view'), 403);

        if ($request->boolean('stock_reservations')) {
            return response()->json($this->inventory->reservations($request->user('admin'), $request->validated()))->header('Cache-Control', 'private, no-store');
        }

        return response()->json($this->inventory->data($request->validated(), $request->user('admin')))->header('Cache-Control', 'private, no-store');
    }

    public function export(DashboardRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        [$start, $end] = $this->report->ranges($filters);
        $query = $this->report->entries($request->user('admin'), $filters, $start, $end)
            ->when(isset($filters['day']), fn ($q) => $q->whereDate('date', $start->day((int) $filters['day'])->toDateString()))
            ->orderBy('date')->orderBy('channel')->orderBy('kind')->orderBy('id');

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Rujukan', 'Tarikh', 'Saluran', 'Jenis', 'Lokasi', 'Jualan RM', 'Kos direkodkan / anggaran RM', 'Anggaran untung RM'], escape: '');
            foreach ($query->cursor() as $entry) {
                $row = $this->report->transaction($entry);
                $cells = array_map(function (mixed $value): string {
                    $text = (string) $value;

                    return preg_match('/^[\s]*[=+@\-]/u', $text) ? "'".$text : $text;
                }, [$row['reference'], $row['date'], $row['channel'], $row['kind'], $row['site'], $row['sales'], $row['cost'], $row['profit']]);
                fputcsv($handle, $cells, escape: '');
            }
            fclose($handle);
        }, 'prestasi-'.$start->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
