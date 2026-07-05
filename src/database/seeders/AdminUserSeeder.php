<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AdminUser::query()->updateOrCreate(
            ['email' => 'anugerah3d@gmail.com'],
            [
                'name' => 'Mohamad',
                'password' => Hash::make('012345678*'),
                'role' => AdminUser::RoleSuperAdmin,
                'status' => AdminUser::StatusActive,
                'email_verified_at' => now(),
            ],
        );
    }
}
