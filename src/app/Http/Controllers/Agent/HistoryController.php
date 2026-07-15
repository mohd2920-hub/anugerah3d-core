<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class HistoryController extends Controller
{
    public function __invoke(): View
    {
        return view('agent.history', [
            'orders' => [
                [
                    'number' => 'A3D-250715-003', 'date' => '15 Jul 2026, 4:20 PM', 'items' => 35, 'amount' => 425.60, 'status' => 'Pending',
                    'fulfilment' => 'Delivery', 'payment' => 'Bank transfer · Awaiting payment', 'recipient' => 'Nur Aisyah', 'phone' => '012-345 6789', 'address' => 'No. 18, Jalan Anggerik, Shah Alam, Selangor', 'notes' => 'Please contact before delivery.',
                    'products' => [
                        ['code' => 'A3D-TP-010', 'name' => 'Flexible Cable Clip', 'quantity' => 15, 'price' => 7.10, 'preorder' => false],
                        ['code' => 'A3D-TP-020', 'name' => 'Flexible Cable Clip Batch 02', 'quantity' => 10, 'price' => 8.17, 'preorder' => true],
                        ['code' => 'A3D-CP-100', 'name' => 'Corporate Name Plaque', 'quantity' => 10, 'price' => 23.74, 'preorder' => false],
                    ],
                    'timeline' => [['label' => 'Order placed', 'complete' => true], ['label' => 'Payment confirmation', 'complete' => false], ['label' => 'Processing', 'complete' => false], ['label' => 'Completed', 'complete' => false]],
                ],
                [
                    'number' => 'A3D-250712-018', 'date' => '12 Jul 2026, 10:45 AM', 'items' => 12, 'amount' => 186.40, 'status' => 'Processing',
                    'fulfilment' => 'Self pickup', 'payment' => 'Bank transfer · Paid', 'recipient' => 'Nur Aisyah', 'phone' => '012-345 6789', 'address' => 'Anugerah3D pickup counter', 'notes' => null,
                    'products' => [['code' => 'A3D-KC-008', 'name' => 'Custom Keychain', 'quantity' => 8, 'price' => 13.80, 'preorder' => false], ['code' => 'A3D-DP-004', 'name' => 'Mini Display Stand', 'quantity' => 4, 'price' => 19.00, 'preorder' => false]],
                    'timeline' => [['label' => 'Order placed', 'complete' => true], ['label' => 'Payment confirmed', 'complete' => true], ['label' => 'Processing', 'complete' => true], ['label' => 'Ready for pickup', 'complete' => false]],
                ],
                [
                    'number' => 'A3D-250708-011', 'date' => '8 Jul 2026, 2:12 PM', 'items' => 8, 'amount' => 94.80, 'status' => 'Completed',
                    'fulfilment' => 'Delivery', 'payment' => 'Bank transfer · Paid', 'recipient' => 'Nur Aisyah', 'phone' => '012-345 6789', 'address' => 'No. 18, Jalan Anggerik, Shah Alam, Selangor', 'notes' => null,
                    'products' => [['code' => 'A3D-TG-008', 'name' => 'Personalised Bag Tag', 'quantity' => 8, 'price' => 11.85, 'preorder' => false]],
                    'timeline' => [['label' => 'Order placed', 'complete' => true], ['label' => 'Payment confirmed', 'complete' => true], ['label' => 'Shipped', 'complete' => true], ['label' => 'Delivered', 'complete' => true]],
                ],
                [
                    'number' => 'A3D-250701-026', 'date' => '1 Jul 2026, 9:30 AM', 'items' => 24, 'amount' => 318.20, 'status' => 'Completed',
                    'fulfilment' => 'Self pickup', 'payment' => 'Bank transfer · Paid', 'recipient' => 'Nur Aisyah', 'phone' => '012-345 6789', 'address' => 'Anugerah3D pickup counter', 'notes' => 'Packed in sets of 6.',
                    'products' => [['code' => 'A3D-KC-024', 'name' => 'Event Keychain Set', 'quantity' => 24, 'price' => 13.26, 'preorder' => true]],
                    'timeline' => [['label' => 'Order placed', 'complete' => true], ['label' => 'Payment confirmed', 'complete' => true], ['label' => 'Produced', 'complete' => true], ['label' => 'Collected', 'complete' => true]],
                ],
                [
                    'number' => 'A3D-250628-009', 'date' => '28 Jun 2026, 5:05 PM', 'items' => 6, 'amount' => 75.60, 'status' => 'Cancelled',
                    'fulfilment' => 'Delivery', 'payment' => 'No payment collected', 'recipient' => 'Nur Aisyah', 'phone' => '012-345 6789', 'address' => 'No. 18, Jalan Anggerik, Shah Alam, Selangor', 'notes' => 'Cancelled before payment confirmation.',
                    'products' => [['code' => 'A3D-MG-006', 'name' => 'Custom Fridge Magnet', 'quantity' => 6, 'price' => 12.60, 'preorder' => false]],
                    'timeline' => [['label' => 'Order placed', 'complete' => true], ['label' => 'Order cancelled', 'complete' => true]],
                ],
            ],
        ]);
    }
}
