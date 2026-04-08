<?php

namespace Modules\Students\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Students\Models\Student;

class StudentsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo student mẫu để test
        Student::firstOrCreate(
            ['email' => 'student@elearning.com'],
            [
                'name'              => 'Student Demo',
                'password'          => 'password',
                'email_verified_at' => now(),
            ]
        );
    }
}
