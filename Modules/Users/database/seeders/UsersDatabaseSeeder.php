<?php

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;

class UsersDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            TestUsersSeeder::class,
        ]);
    }
}
