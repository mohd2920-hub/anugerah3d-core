<?php

namespace App\Actions\Pos;

use App\Models\PosSale;
use App\Models\PosSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class UpdatePosSale
{
    public function __construct(private CreatePosSale $createPosSale) {}

    public function handle(PosSale $sale, PosSession $session, array $data): PosSale
    {
        $newSalePicture = $this->createPosSale->storePicture($data['sale_picture'] ?? null, 'sale');
        $newPaymentProof = $this->createPosSale->storePicture($data['payment_proof'] ?? null, 'payment');
        $newPictures = array_filter([$newSalePicture, $newPaymentProof]);

        try {
            $oldPictures = array_filter([$sale->sale_picture_path, $sale->payment_proof_path]);

            $updatedSale = DB::transaction(function () use ($sale, $session, $data, $newSalePicture, $newPaymentProof): PosSale {
                $items = $this->createPosSale->items($data['items'], $data['sales_agent_id']);

                $sale->update([
                    'pos_session_id' => $session->getKey(),
                    'recorded_by_agent_id' => $session->agent_id,
                    'sales_agent_id' => $data['sales_agent_id'],
                    'customer_name' => $data['customer_name'] ?? null,
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'remark' => $data['remark'] ?? null,
                    'payment_method' => $data['payment_method'],
                    'payment_remark' => $data['payment_remark'] ?? null,
                    'sale_picture_path' => $newSalePicture ?: $sale->sale_picture_path,
                    'payment_proof_path' => $newPaymentProof ?: $sale->payment_proof_path,
                    'total_amount' => collect($items)->sum('line_total'),
                ]);

                $sale->items()->delete();
                $sale->items()->createMany($items);

                return $sale->load(['items', 'businessSite', 'salesAgent', 'recordedBy']);
            });

            foreach ($oldPictures as $oldPicture) {
                if (! in_array($oldPicture, [$updatedSale->sale_picture_path, $updatedSale->payment_proof_path], true)) {
                    File::delete(public_path($oldPicture));
                }
            }

            return $updatedSale;
        } catch (Throwable $exception) {
            foreach ($newPictures as $newPicture) {
                File::delete(public_path($newPicture));
            }

            throw $exception;
        }
    }
}
