<?php

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'admin';

        // ── Permissions ──
        $permissions = [
            // Users
            'users.view', 'users.create', 'users.edit', 'users.delete',
            // Courses
            'courses.view', 'courses.create', 'courses.edit', 'courses.delete',
            // Categories
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            // Lessons
            'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.delete',
            // Orders
            'orders.view', 'orders.edit',
            // Students
            'students.view', 'students.edit',
            // Dashboard
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
        }

        // ── Roles ──
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => $guard]);
        $admin      = Role::firstOrCreate(['name' => 'admin',       'guard_name' => $guard]);
        $teacher    = Role::firstOrCreate(['name' => 'teacher',     'guard_name' => $guard]);

        // super-admin có tất cả permissions
        $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());

        // admin có tất cả trừ users.delete
        $admin->syncPermissions(
            Permission::where('guard_name', $guard)
                ->where('name', '!=', 'users.delete')
                ->get()
        );

        // teacher chỉ quản lý courses & lessons của mình
        $teacher->syncPermissions([
            'courses.view', 'courses.create', 'courses.edit',
            'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.delete',
            'dashboard.view',
        ]);
    }
}
