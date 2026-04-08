<?php

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Users\Models\User;

/**
 * Seed dữ liệu test cho module Users.
 *
 * Tạo đủ loại user để test tất cả endpoints trên Postman:
 *   - Active users (có role)
 *   - User không có role
 *   - Soft-deleted users (để test trashed / restore / forceDelete)
 *
 * Chạy: php artisan db:seed --class="Modules\Users\Database\Seeders\TestUsersSeeder"
 */
class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Users active có role ──────────────────────────────────────
        $activeUsers = [
            [
                'name'     => 'Nguyễn Văn Teacher',
                'email'    => 'teacher1@elearning.com',
                'password' => bcrypt('password'),
                'avatar'   => null,
                'role'     => 'teacher',
            ],
            [
                'name'     => 'Trần Thị Teacher',
                'email'    => 'teacher2@elearning.com',
                'password' => bcrypt('password'),
                'avatar'   => null,
                'role'     => 'teacher',
            ],
            [
                'name'     => 'Lê Văn Admin',
                'email'    => 'admin2@elearning.com',
                'password' => bcrypt('password'),
                'avatar'   => null,
                'role'     => 'admin',
            ],
        ];

        foreach ($activeUsers as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::firstOrCreate(['email' => $data['email']], $data);
            $user->syncRoles([$role]);
        }

        // ── 2. Users active KHÔNG có role (để test assign-role) ──────────
        $noRoleUsers = [
            [
                'name'     => 'Phạm Thị No Role A',
                'email'    => 'norole1@elearning.com',
                'password' => bcrypt('password'),
            ],
            [
                'name'     => 'Hoàng Văn No Role B',
                'email'    => 'norole2@elearning.com',
                'password' => bcrypt('password'),
            ],
            [
                'name'     => 'Đỗ Thị No Role C',
                'email'    => 'norole3@elearning.com',
                'password' => bcrypt('password'),
            ],
        ];

        foreach ($noRoleUsers as $data) {
            User::firstOrCreate(['email' => $data['email']], $data);
        }

        // ── 3. Users sẽ bị soft-delete (để test trashed / restore) ───────
        $trashedUsers = [
            [
                'name'     => 'Vũ Văn Deleted A',
                'email'    => 'deleted1@elearning.com',
                'password' => bcrypt('password'),
                'role'     => 'teacher',
            ],
            [
                'name'     => 'Bùi Thị Deleted B',
                'email'    => 'deleted2@elearning.com',
                'password' => bcrypt('password'),
                'role'     => 'admin',
            ],
            [
                'name'     => 'Đinh Văn Deleted C',
                'email'    => 'deleted3@elearning.com',
                'password' => bcrypt('password'),
                'role'     => null,
            ],
        ];

        foreach ($trashedUsers as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::withTrashed()->firstOrCreate(['email' => $data['email']], $data);

            if ($role) {
                $user->syncRoles([$role]);
            }

            // Soft-delete nếu chưa bị xóa
            if (! $user->trashed()) {
                $user->delete();
            }
        }

        $this->command->info('TestUsersSeeder: Đã tạo đủ dữ liệu test.');
        $this->command->table(
            ['Loại', 'Email', 'Role', 'Trạng thái'],
            [
                ['Active + role',    'teacher1@elearning.com', 'teacher',      'active'],
                ['Active + role',    'teacher2@elearning.com', 'teacher',      'active'],
                ['Active + role',    'admin2@elearning.com',   'admin',        'active'],
                ['Active no role',   'norole1@elearning.com',  '—',            'active'],
                ['Active no role',   'norole2@elearning.com',  '—',            'active'],
                ['Active no role',   'norole3@elearning.com',  '—',            'active'],
                ['Soft-deleted',     'deleted1@elearning.com', 'teacher',      'trashed'],
                ['Soft-deleted',     'deleted2@elearning.com', 'admin',        'trashed'],
                ['Soft-deleted',     'deleted3@elearning.com', '—',            'trashed'],
            ]
        );
    }
}
