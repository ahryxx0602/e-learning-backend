<?php

namespace Modules\Students\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Students\Models\Student;
use Modules\Course\Models\Course;

class StudentEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo thêm students mẫu
        $students = [
            ['name' => 'Nguyễn Thị Mai',    'email' => 'mai.nguyen@gmail.com'],
            ['name' => 'Trần Văn Hùng',     'email' => 'hung.tran@gmail.com'],
            ['name' => 'Lê Thị Lan',        'email' => 'lan.le@gmail.com'],
            ['name' => 'Phạm Minh Tuấn',    'email' => 'tuan.pham@gmail.com'],
            ['name' => 'Hoàng Thị Thu',     'email' => 'thu.hoang@gmail.com'],
            ['name' => 'Vũ Quang Khải',     'email' => 'khai.vu@gmail.com'],
            ['name' => 'Đặng Thị Hoa',      'email' => 'hoa.dang@gmail.com'],
            ['name' => 'Bùi Văn Nam',       'email' => 'nam.bui@gmail.com'],
            ['name' => 'Ngô Thị Yến',       'email' => 'yen.ngo@gmail.com'],
            ['name' => 'Student Demo',      'email' => 'student@elearning.com'],
        ];

        $createdStudents = [];
        foreach ($students as $data) {
            $createdStudents[] = Student::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        // Enroll mỗi student vào 2-5 khóa học ngẫu nhiên
        $courseIds = Course::where('status', 1)->pluck('id')->toArray();

        if (empty($courseIds)) return;

        foreach ($createdStudents as $student) {
            $numCourses = rand(2, min(5, count($courseIds)));
            $shuffled   = $courseIds;
            shuffle($shuffled);
            $toEnroll   = array_slice($shuffled, 0, $numCourses);

            foreach ($toEnroll as $courseId) {
                $exists = DB::table('students_course')
                    ->where('student_id', $student->id)
                    ->where('course_id', $courseId)
                    ->exists();

                if (!$exists) {
                    DB::table('students_course')->insert([
                        'student_id'  => $student->id,
                        'course_id'   => $courseId,
                        'enrolled_at' => now()->subDays(rand(1, 90)),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }
        }

        $this->command->info('Đã seed ' . count($createdStudents) . ' students và enrollments.');
    }
}
