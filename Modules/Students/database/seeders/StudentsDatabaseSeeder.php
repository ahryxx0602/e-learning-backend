<?php

namespace Modules\Students\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Students\Models\Student;

class StudentsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Đã xác thực email — dùng test luồng bình thường
        Student::firstOrCreate(
            ['email' => 'student@elearning.com'],
            [
                'name'              => 'Student Demo',
                'password'          => 'password',
                'email_verified_at' => now(),
            ]
        );

        // Chưa xác thực email — dùng test luồng "chưa kích hoạt tài khoản"
        Student::firstOrCreate(
            ['email' => 'student-unverified@elearning.com'],
            [
                'name'              => 'Student Unverified',
                'password'          => 'password',
                'email_verified_at' => null,
            ]
        );
    }
}
