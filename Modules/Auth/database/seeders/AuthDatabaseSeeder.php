<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;

/**
 * Seeder tạo tài khoản admin mặc định để test.
 *
 * Admin: admin@elearning.com / password
 *
 * Lưu ý: Ở Task 1.2 (Module Users) sẽ tạo seeder đầy đủ hơn
 * với Spatie Roles/Permissions.
 */
class AuthDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo admin mặc định (nếu chưa có)
        User::firstOrCreate(
            ['email' => 'admin@elearning.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );
    }
}
