<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Roles & Permissions (phải chạy trước)
            \Modules\Users\Database\Seeders\RolePermissionSeeder::class,

            // 2. Admin users
            \Modules\Users\Database\Seeders\AdminUserSeeder::class,

            // 3. Categories (cây danh mục)
            \Modules\Categories\Database\Seeders\CategoriesDatabaseSeeder::class,

            // 4. Teachers
            \Modules\Teachers\Database\Seeders\TeachersDatabaseSeeder::class,

            // 5. Media files (video + documents)
            \Modules\Upload\Database\Seeders\MediaFileSeeder::class,

            // 6. Courses (phụ thuộc teachers + categories)
            \Modules\Course\Database\Seeders\CourseDatabaseSeeder::class,

            // 7. Sections + Lessons (phụ thuộc courses + media)
            \Modules\Lessons\Database\Seeders\LessonDatabaseSeeder::class,

            // 8. Students + Enrollments
            \Modules\Students\Database\Seeders\StudentEnrollmentSeeder::class,
        ]);
    }
}
