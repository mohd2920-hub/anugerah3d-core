<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'metrics' => [
                ['label' => 'Orders Today', 'value' => '24', 'trend' => '+8%'],
                ['label' => 'Pending Quotes', 'value' => '12', 'trend' => '+3'],
                ['label' => 'Active Customers', 'value' => '148', 'trend' => '+11'],
                ['label' => 'Print Queue', 'value' => '7', 'trend' => '2 urgent'],
            ],
            'activities' => [
                'New custom keychain order received',
                'Quotation prepared for corporate gift request',
                'Miniature print marked ready for pickup',
                'Customer profile updated by support desk',
            ],
            'productionStages' => [
                ['label' => 'Design approval', 'percent' => 82, 'caption' => '12 jobs ready'],
                ['label' => 'Printing queue', 'percent' => 64, 'caption' => '7 jobs active'],
                ['label' => 'Finishing work', 'percent' => 48, 'caption' => '5 jobs pending'],
                ['label' => 'Ready to deliver', 'percent' => 76, 'caption' => '9 parcels packed'],
            ],
            'customers' => [
                ['name' => 'Nur Aisyah', 'initials' => 'NA', 'segment' => 'Corporate buyer', 'lastOrder' => 'Gift box set', 'value' => 'RM 1,280', 'status' => 'Active'],
                ['name' => 'Farid Studio', 'initials' => 'FS', 'segment' => 'Event partner', 'lastOrder' => 'Name keychains', 'value' => 'RM 860', 'status' => 'Quotation'],
                ['name' => 'Mira Craft', 'initials' => 'MC', 'segment' => 'Repeat customer', 'lastOrder' => 'Miniature display', 'value' => 'RM 540', 'status' => 'Printing'],
                ['name' => 'Khalid Enterprise', 'initials' => 'KE', 'segment' => 'Bulk order', 'lastOrder' => 'Corporate souvenir', 'value' => 'RM 2,450', 'status' => 'Priority'],
            ],
            'channelMix' => [
                ['label' => 'WhatsApp orders', 'percent' => 58],
                ['label' => 'Website leads', 'percent' => 24],
                ['label' => 'Agent referrals', 'percent' => 18],
            ],
        ]);
    }
}
