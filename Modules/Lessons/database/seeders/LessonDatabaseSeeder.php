<?php

namespace Modules\Lessons\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Lessons\Models\Section;
use Modules\Lessons\Models\Lesson;
use Modules\Course\Models\Course;
use Modules\Upload\Models\MediaFile;

class LessonDatabaseSeeder extends Seeder
{
    private array $videoIds   = [];
    private array $documentIds = [];

    public function run(): void
    {
        $courses = Course::all();

        if ($courses->isEmpty()) {
            $this->command->warn('Không có khóa học. Seed Course trước.');
            return;
        }

        // Lấy IDs từ media_files đã seed
        $this->videoIds    = MediaFile::where('type', 'video')->pluck('id')->toArray();
        $this->documentIds = MediaFile::where('type', 'document')->pluck('id')->toArray();

        if (empty($this->videoIds)) {
            $this->command->warn('Không có video nào trong media_files. Seed MediaFile trước.');
            return;
        }

        $totalLessons = 0;

        foreach ($courses as $course) {
            $sectionTitles = $this->getSectionTitles($course->name);
            $numSections   = count($sectionTitles); // 3-5 sections

            foreach ($sectionTitles as $sIdx => $sectionTitle) {
                $section = Section::create([
                    'course_id'   => $course->id,
                    'title'       => $sectionTitle,
                    'description' => 'Nội dung của ' . $sectionTitle,
                    'order'       => $sIdx + 1,
                    'status'      => 1,
                ]);

                $lessonTitles = $this->getLessonTitles($sectionTitle);
                $numLessons   = count($lessonTitles); // 6-8 lessons

                foreach ($lessonTitles as $lIdx => $lessonTitle) {
                    $isFirstLesson = ($sIdx === 0 && $lIdx === 0);
                    $type = $this->pickType($lIdx);

                    $videoId    = null;
                    $documentId = null;

                    if ($type === 'video') {
                        $videoId = $this->videoIds[array_rand($this->videoIds)];
                    } elseif ($type === 'document') {
                        $documentId = !empty($this->documentIds)
                            ? $this->documentIds[array_rand($this->documentIds)]
                            : null;
                    }

                    $slug = Str::slug($lessonTitle) . '-' . Str::random(6);

                    Lesson::create([
                        'course_id'   => $course->id,
                        'section_id'  => $section->id,
                        'title'       => $lessonTitle,
                        'slug'        => $slug,
                        'description' => 'Mô tả chi tiết cho bài: ' . $lessonTitle,
                        'type'        => $type,
                        'content'     => $type === 'text'
                            ? '<p>Đây là nội dung text của bài <strong>' . $lessonTitle . '</strong>. Bạn sẽ học các khái niệm quan trọng và thực hành ngay trong bài học này.</p>'
                            : null,
                        'video_id'    => $videoId,
                        'document_id' => $documentId,
                        'order'       => $lIdx + 1,
                        'is_preview'  => $isFirstLesson, // Bài đầu tiên mỗi khóa xem thử miễn phí
                        'duration'    => $type === 'video' ? rand(300, 1200) : null,
                        'status'      => 1,
                    ]);

                    $totalLessons++;
                }

                // Cập nhật total_lessons cho course
                $course->update(['total_lessons' => $totalLessons]);
                $totalLessons = 0;
            }

            // Tính lại total_lessons chính xác
            $realTotal = Lesson::where('course_id', $course->id)->count();
            $course->update(['total_lessons' => $realTotal]);
        }

        $this->command->info('Đã seed sections và lessons thành công.');
    }

    // Mỗi lesson thứ lIdx: video nhiều hơn, xen kẽ document
    private function pickType(int $lIdx): string
    {
        // 0,1 → video | 2 → document | 3,4 → video | 5 → document | 6,7 → video
        return match($lIdx % 3) {
            2       => 'document',
            default => 'video',
        };
    }

    private function getSectionTitles(string $courseName): array
    {
        // Mỗi khóa có 3-5 sections, tên section khớp với chủ đề
        if (str_contains($courseName, 'Laravel')) {
            return [
                'Chương 1: Giới thiệu & Cài đặt môi trường',
                'Chương 2: Routing, Controller & Middleware',
                'Chương 3: Eloquent ORM & Database',
                'Chương 4: Authentication & API với Sanctum',
                'Chương 5: Queue, Job & Deploy thực tế',
            ];
        }
        if (str_contains($courseName, 'Vue.js')) {
            return [
                'Chương 1: Vue 3 Cơ bản & Composition API',
                'Chương 2: Component, Props & Emit',
                'Chương 3: Vue Router & Navigation',
                'Chương 4: Pinia State Management',
                'Chương 5: Gọi API & Dự án thực tế',
            ];
        }
        if (str_contains($courseName, 'HTML')) {
            return [
                'Chương 1: HTML5 Cơ bản',
                'Chương 2: CSS3 & Flexbox',
                'Chương 3: CSS Grid & Responsive',
                'Chương 4: JavaScript Cơ bản',
            ];
        }
        if (str_contains($courseName, 'React') && str_contains($courseName, 'Next')) {
            return [
                'Chương 1: React.js Cơ bản',
                'Chương 2: Hooks & State Management',
                'Chương 3: Next.js App Router',
                'Chương 4: Database & API Routes',
                'Chương 5: Authentication & Deploy',
            ];
        }
        if (str_contains($courseName, 'Node.js')) {
            return [
                'Chương 1: Node.js & Express Cơ bản',
                'Chương 2: REST API Design',
                'Chương 3: JWT Authentication',
                'Chương 4: MongoDB & Redis',
            ];
        }
        if (str_contains($courseName, 'MySQL')) {
            return [
                'Chương 1: Cơ bản về Database',
                'Chương 2: SQL nâng cao',
                'Chương 3: Index & Optimization',
                'Chương 4: Stored Procedures & Triggers',
            ];
        }
        if (str_contains($courseName, 'Flutter')) {
            return [
                'Chương 1: Dart & Flutter Cơ bản',
                'Chương 2: Widget & Layout',
                'Chương 3: State Management với Bloc',
                'Chương 4: Kết nối API & Local Storage',
                'Chương 5: Publish lên App Store',
            ];
        }
        if (str_contains($courseName, 'React Native')) {
            return [
                'Chương 1: React Native & Expo',
                'Chương 2: Navigation & Screens',
                'Chương 3: Camera & Device Features',
                'Chương 4: Push Notification & Payment',
            ];
        }
        if (str_contains($courseName, 'Docker')) {
            return [
                'Chương 1: Docker Cơ bản',
                'Chương 2: Docker Compose & Networks',
                'Chương 3: CI/CD với GitHub Actions',
                'Chương 4: Deploy lên VPS & Monitoring',
            ];
        }
        if (str_contains($courseName, 'Python')) {
            return [
                'Chương 1: Python Cơ bản',
                'Chương 2: NumPy & Pandas',
                'Chương 3: Trực quan hóa dữ liệu',
                'Chương 4: Machine Learning với Scikit-learn',
                'Chương 5: Deploy Model API',
            ];
        }
        if (str_contains($courseName, 'IELTS')) {
            return [
                'Chương 1: Listening & Chiến lược làm bài',
                'Chương 2: Reading - Đọc hiểu nâng cao',
                'Chương 3: Writing Task 1',
                'Chương 4: Writing Task 2',
                'Chương 5: Speaking - Phát âm & Fluency',
            ];
        }
        if (str_contains($courseName, 'Tiếng Anh Giao Tiếp')) {
            return [
                'Chương 1: Phát âm chuẩn',
                'Chương 2: Email & Văn phòng',
                'Chương 3: Meeting & Thuyết trình',
                'Chương 4: Đàm phán & Phản xạ',
            ];
        }
        if (str_contains($courseName, 'Tiếng Nhật')) {
            return [
                'Chương 1: Hiragana & Katakana',
                'Chương 2: Kanji N5 cơ bản',
                'Chương 3: Ngữ pháp N5',
                'Chương 4: Ngữ pháp N4',
                'Chương 5: Luyện đề JLPT',
            ];
        }
        if (str_contains($courseName, 'Tiếng Hàn')) {
            return [
                'Chương 1: Hangul - Bảng chữ cái',
                'Chương 2: Phát âm & Từ vựng cơ bản',
                'Chương 3: Ngữ pháp sơ cấp',
                'Chương 4: Luyện đề TOPIK I',
            ];
        }

        // Fallback chung
        return [
            'Chương 1: Giới thiệu tổng quan',
            'Chương 2: Kiến thức cốt lõi',
            'Chương 3: Thực hành chuyên sâu',
            'Chương 4: Tổng kết & Bài tập lớn',
        ];
    }

    private function getLessonTitles(string $sectionTitle): array
    {
        // Mỗi section có 6-8 lessons
        $baseNum = rand(6, 8);

        $pools = [
            'Giới thiệu' => [
                'Tổng quan khóa học và lộ trình học tập',
                'Cài đặt môi trường phát triển',
                'Cấu trúc dự án và quy ước đặt tên',
                'Công cụ hỗ trợ: VS Code, extensions và tips',
                'Giới thiệu tài liệu tham khảo',
                'Bài kiểm tra đầu vào',
                'Hỏi đáp và cộng đồng học tập',
                'Cài đặt nhanh bằng script tự động',
            ],
            'Cơ bản' => [
                'Khái niệm và nguyên lý nền tảng',
                'Cú pháp và cấu trúc cơ bản',
                'Kiểu dữ liệu và biến',
                'Cấu trúc điều kiện và vòng lặp',
                'Hàm và phạm vi biến',
                'Module và import/export',
                'Debug và xử lý lỗi cơ bản',
                'Bài tập thực hành tổng hợp',
            ],
            'Nâng cao' => [
                'Kiến trúc và design pattern',
                'Tối ưu hiệu năng',
                'Xử lý bất đồng bộ',
                'Testing và viết test case',
                'Security và best practices',
                'Tích hợp với third-party services',
                'Monitoring và logging',
                'Case study dự án thực tế',
            ],
            'Thực hành' => [
                'Phân tích yêu cầu dự án',
                'Thiết kế database và API',
                'Xây dựng tính năng core',
                'Xây dựng giao diện người dùng',
                'Tích hợp authentication',
                'Testing và debug',
                'Deploy và CI/CD',
                'Review code và tối ưu',
            ],
            'Tổng kết' => [
                'Ôn tập toàn bộ kiến thức',
                'Giải đề bài tập lớn - Phần 1',
                'Giải đề bài tập lớn - Phần 2',
                'Code review và nhận xét',
                'Q&A phần câu hỏi thường gặp',
                'Hướng dẫn làm dự án portfolio',
                'Bước tiếp theo trong lộ trình học',
                'Chứng chỉ hoàn thành khóa học',
            ],
        ];

        // Chọn pool phù hợp với section title
        $pool = $pools['Cơ bản']; // default
        foreach ($pools as $keyword => $titles) {
            if (str_contains($sectionTitle, $keyword)) {
                $pool = $titles;
                break;
            }
        }

        return array_slice($pool, 0, $baseNum);
    }
}
