<?php

namespace App\Actions\Pos;

use App\Models\PosSale;
use App\Models\PosSession;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdatePosSale
{
    public function __construct(private CreatePosSale $createPosSale) {}

    public function handle(PosSale $sale, PosSession $session, array $data): PosSale
    {
        $newSalePictures = $this->createPosSale->storePictureSet($data['sale_pictures'] ?? [], 'sale');
        $newPaymentProofs = $this->createPosSale->storePictureSet($data['payment_proofs'] ?? [], 'payment');
        $newPictures = [...$newSalePictures, ...$newPaymentProofs];

        try {
            $currentSalePictures = $sale->salePicturePaths();
            $currentPaymentProofs = $sale->paymentProofPaths();

            $updatedSale = DB::transaction(function () use ($sale, $session, $data, $newSalePictures, $newPaymentProofs, $currentSalePictures, $currentPaymentProofs): PosSale {
                $items = $this->createPosSale->items($data['items'], $data['sales_agent_id']);
                $salePicturePaths = array_values(array_slice(array_merge($currentSalePictures, $newSalePictures), 0, 5));
                $paymentProofPaths = array_values(array_slice(array_merge($currentPaymentProofs, $newPaymentProofs), 0, 5));

                $sale->update([
                    'pos_session_id' => $session->getKey(),
                    'recorded_by_agent_id' => $session->agent_id,
                    'sales_agent_id' => $data['sales_agent_id'],
                    'customer_name' => $data['customer_name'] ?? null,
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'customer_email' => $data['customer_email'] ?? null,
                    'remark' => $data['remark'] ?? null,
                    'payment_method' => $data['payment_method'],
                    'payment_remark' => $data['payment_remark'] ?? null,
                    'sale_picture_path' => $salePicturePaths[0] ?? null,
                    'sale_picture_paths' => $salePicturePaths,
                    'payment_proof_path' => $paymentProofPaths[0] ?? null,
                    'payment_proof_paths' => $paymentProofPaths,
                    'total_amount' => collect($items)->sum('line_total'),
                ]);

                $sale->items()->delete();
                $sale->items()->createMany($items);

                return $sale->load(['items', 'businessSite', 'salesAgent', 'recordedBy']);
            });

            return $updatedSale;
        } catch (Throwable $exception) {
            $this->createPosSale->deleteStoredPictures($newPictures);

            throw $exception;
        }
    }
}
