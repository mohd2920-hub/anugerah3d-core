<?php

namespace App\Support;

use App\Models\AdminUser;
use App\Models\BusinessSiteOperation;
use App\Models\SalaryPayment;
use Illuminate\Validation\ValidationException;

class StaffSalaryCalculator
{
    public function calculate(array $data): array
    {
        $operation = BusinessSiteOperation::with('businessSite')->findOrFail($data['operation_id']);
        if (! $operation->closed_at || $operation->opened_at->toDateString() < '2026-01-01') {
            throw ValidationException::withMessages(['operation_id' => 'Pilih sesi yang telah ditutup, bermula Januari 2026.']);
        }
        $staff = AdminUser::whereIn('id', $data['staff_ids'])->orderBy('id')->get();
        if ($staff->count() !== count($data['staff_ids'])) {
            throw ValidationException::withMessages(['staff_ids' => 'Senarai staf telah berubah.']);
        }
        $amountMode = array_key_exists('amounts', $data);
        $totalWeight = 0;
        $rows = [];
        foreach ($staff as $person) {
            if ($amountMode) {
                if (! isset($data['amounts'][$person->id])) {
                    throw ValidationException::withMessages(['amounts' => 'Masukkan gaji RM bagi setiap staf yang hadir.']);
                }
                $rows[] = ['staff_id' => $person->id, 'name' => $person->name, 'email' => $person->email, 'amount_cents' => (int) round((float) $data['amounts'][$person->id] * 100)];

                continue;
            }
            if (! isset($data['weights'][$person->id])) {
                throw ValidationException::withMessages(['weights' => 'Masukkan weightage bagi setiap staf yang hadir.']);
            }
            $weight = (int) round((float) $data['weights'][$person->id] * 100);
            $totalWeight += $weight;
            $rows[] = ['staff_id' => $person->id, 'name' => $person->name, 'email' => $person->email, 'weight' => $weight];
        }
        $sales = $operation->sales()->orderBy('id')->get(['id', 'total_amount', 'voided_at', 'correction_version']);
        $net = $sales->whereNull('voided_at')->sum(fn ($sale) => (int) round((float) $sale->total_amount * 100));
        if ($net < 0) {
            throw ValidationException::withMessages(['operation_id' => 'Jumlah jualan negatif perlu disemak sebelum pengiraan gaji.']);
        }
        if ($amountMode) {
            $pool = array_sum(array_column($rows, 'amount_cents'));
            $rate = $net > 0 ? (int) round($pool * 10000 / $net) : null;
        } else {
            $rate = (int) round((float) $data['rate'] * 100);
            $pool = intdiv($net * $rate + 5000, 10000);
            $allocated = 0;
            foreach ($rows as &$row) {
                $row['amount_cents'] = intdiv($pool * $row['weight'], $totalWeight);
                $row['remainder'] = ($pool * $row['weight']) % $totalWeight;
                $allocated += $row['amount_cents'];
            }
            unset($row);
            $priority = array_keys($rows);
            usort($priority, fn ($a, $b) => ($rows[$b]['remainder'] <=> $rows[$a]['remainder']) ?: ($rows[$a]['staff_id'] <=> $rows[$b]['staff_id']));
            for ($i = 0; $i < $pool - $allocated; $i++) {
                $rows[$priority[$i]]['amount_cents']++;
            }
            foreach ($rows as &$row) {
                unset($row['remainder']);
            }
            unset($row);
        }
        $workDate = $operation->opened_at->timezone('Asia/Kuala_Lumpur')->toDateString();
        $overlaps = SalaryPayment::whereIn('recipient_key', $staff->map(fn ($person) => 'admin:'.$person->id))->whereDate('work_date', $workDate)->get(['id', 'recipient_name', 'site_name']);
        $snapshot = [
            'operation_id' => $operation->id, 'site_name' => $operation->businessSite->site_name, 'work_date' => $workDate,
            'net_cents' => $net, 'rate' => $rate, 'pool_cents' => $pool, 'staff' => $rows,
            'source_hash' => hash('sha256', $sales->toJson()), 'overlaps' => $overlaps->toArray(),
        ];
        if ($amountMode) {
            $snapshot['mode'] = 'amount';
        }
        $snapshot['hash'] = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));

        return $snapshot;
    }
}
