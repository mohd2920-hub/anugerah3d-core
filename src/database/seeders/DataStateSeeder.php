<?php

namespace Database\Seeders;

use App\Models\DataState;
use Illuminate\Database\Seeder;

class DataStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            ['code' => 'JHR', 'name' => 'Johor'],
            ['code' => 'KDH', 'name' => 'Kedah'],
            ['code' => 'KTN', 'name' => 'Kelantan'],
            ['code' => 'MLK', 'name' => 'Melaka'],
            ['code' => 'NSN', 'name' => 'Negeri Sembilan'],
            ['code' => 'PHG', 'name' => 'Pahang'],
            ['code' => 'PNG', 'name' => 'Pulau Pinang'],
            ['code' => 'PRK', 'name' => 'Perak'],
            ['code' => 'PLS', 'name' => 'Perlis'],
            ['code' => 'SBH', 'name' => 'Sabah'],
            ['code' => 'SWK', 'name' => 'Sarawak'],
            ['code' => 'SGR', 'name' => 'Selangor'],
            ['code' => 'TRG', 'name' => 'Terengganu'],
            ['code' => 'KUL', 'name' => 'Kuala Lumpur'],
            ['code' => 'LBN', 'name' => 'Labuan'],
            ['code' => 'PJY', 'name' => 'Putrajaya'],
        ];

        foreach ($states as $state) {
            DataState::query()->updateOrCreate(
                ['code' => $state['code']],
                $state,
            );
        }
    }
}
