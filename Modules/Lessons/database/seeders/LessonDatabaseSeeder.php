<?php

namespace Modules\Lessons\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Lessons\Models\Section;
use Modules\Lessons\Models\Lesson;
use Modules\Course\Models\Course;

class LessonDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch all courses
        $courses = Course::all();

        if ($courses->isEmpty()) {
            $this->command->warn('Không có khóa học nào. Vui lòng seed khóa học trước.');
            return;
        }

        foreach ($courses as $course) {
            // Seed 2-4 sections per course
            $numSections = rand(2, 4);
            for ($i = 1; $i <= $numSections; $i++) {
                $section = Section::create([
                    'course_id'   => $course->id,
                    'title'       => 'Chương ' . $i . ': ' . $this->generateSectionTitle($i),
                    'description' => 'Nội dung chi tiết cho chương ' . $i . ' của khóa học.',
                    'order'       => $i,
                    'status'      => 1,
                ]);

                // Seed 3-5 lessons per section
                $numLessons = rand(3, 5);
                for ($j = 1; $j <= $numLessons; $j++) {
                    $lessonTitle = 'Bài ' . $j . ': ' . $this->generateLessonTitle($j);
                    Lesson::create([
                        'course_id'   => $course->id,
                        'section_id'  => $section->id,
                        'title'       => $lessonTitle,
                        'slug'        => Str::slug($lessonTitle) . '-' . uniqid(),
                        'description' => 'Mô tả ngắn gọn cho ' . $lessonTitle,
                        'type'        => rand(0, 1) ? 'video' : 'document', // Assuming string, or it could be an integer based on DB structure. We'll check the DB or use standard string.
                        'content'     => '<p>Đây là nội dung chi tiết của ' . $lessonTitle . '.</p>',
                        'video_id'    => null, // Can be null or an ID from Uploads if available
                        'document_id' => null,
                        'order'       => $j,
                        'is_preview'  => $i === 1 && $j === 1, // Only first lesson of first section is preview
                        'duration'    => rand(120, 900), // Duration in seconds
                        'status'      => 1,
                    ]);
                }
            }
        }

        $this->command->info('Đã seed sections và lessons thành công.');
    }

    private function generateSectionTitle($index)
    {
        $titles = [
            1 => 'Giới thiệu tổng quan',
            2 => 'Kiến thức cốt lõi',
            3 => 'Thực hành chuyên sâu',
            4 => 'Tổng kết và bài tập lớn',
            5 => 'Phụ lục bổ sung',
        ];

        return $titles[$index] ?? 'Chương mở rộng ' . $index;
    }

    private function generateLessonTitle($index)
    {
        $titles = [
            1 => 'Khái niệm cơ bản bạn cần biết',
            2 => 'Cấu trúc và cách hoạt động',
            3 => 'Cài đặt và thiết lập môi trường',
            4 => 'Ví dụ thực tế và demo',
            5 => 'Các lỗi thường gặp và cách khắc phục',
            6 => 'Bài tập thực hành',
        ];

        return $titles[$index] ?? 'Nội dung bổ sung ' . $index;
    }
}
