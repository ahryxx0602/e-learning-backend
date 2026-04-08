<?php

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Users\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@elearning.com'],
            [
                'name'     => 'Super Admin',
                'password' => bcrypt('password'),
            ]
        );
        $superAdmin->assignRole('super-admin');

        $admin = User::firstOrCreate(
            ['email' => 'admin@elearning.com'],
            [
                'name'     => 'Admin',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole('admin');
    }
}
