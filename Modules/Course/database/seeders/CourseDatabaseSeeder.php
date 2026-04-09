<?php

namespace Modules\Course\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Course\Models\Course;
use Modules\Categories\Models\Category;
use Modules\Teachers\Models\Teachers;

class CourseDatabaseSeeder extends Seeder
{
    // Thumbnails xoay vòng từ file local
    private array $thumbs = [
        'seed/thumbnails/thumb-1.png',
        'seed/thumbnails/thumb-2.png',
        'seed/thumbnails/thumb-3.png',
        'seed/thumbnails/thumb-4.png',
        'seed/thumbnails/thumb-5.png',
    ];

    public function run(): void
    {
        $teachers   = Teachers::where('status', 1)->pluck('id')->toArray();
        $categories = Category::whereNotNull('parent_id')->pluck('id', 'slug');

        if (empty($teachers)) {
            $this->command->warn('Chưa có teacher. Seed Teachers trước.');
            return;
        }

        $courses = $this->courseData();
        $thumbIdx = 0;

        foreach ($courses as $data) {
            $course = Course::create([
                'teacher_id'  => $teachers[array_rand($teachers)],
                'name'        => $data['name'],
                'slug'        => Str::slug($data['name']),
                'description' => $data['description'],
                'price'       => $data['price'],
                'sale_price'  => $data['sale_price'] ?? null,
                'level'       => $data['level'],
                'status'      => $data['status'],
                'thumbnail'   => $this->thumbs[$thumbIdx % count($this->thumbs)],
                'rating'      => round(mt_rand(35, 50) / 10, 1), // 3.5 - 5.0
                'total_students' => rand(10, 500),
            ]);
            $thumbIdx++;

            // Gắn categories theo slug hint
            $catSlugs = $data['category_slugs'] ?? [];
            $catIds = [];
            foreach ($catSlugs as $slug) {
                if (isset($categories[$slug])) {
                    $catIds[] = $categories[$slug];
                }
            }
            if (!empty($catIds)) {
                $course->categories()->attach($catIds);
            }
        }

        $this->command->info('Đã seed ' . count($courses) . ' khóa học.');
    }

    private function courseData(): array
    {
        return [
            // ── Lập trình Web ──
            [
                'name'           => 'Laravel 12 Từ Cơ Bản Đến Nâng Cao',
                'description'    => 'Khóa học Laravel toàn diện cho lập trình viên PHP. Bạn sẽ học routing, Eloquent ORM, Sanctum API, Queue, Job, Event, Testing và deploy thực tế. Phù hợp từ người mới bắt đầu đến developer muốn nâng cao.',
                'price'          => 599000,
                'sale_price'     => 399000,
                'level'          => 'beginner',
                'status'         => 1,
                'category_slugs' => ['laravel', 'web-development'],
            ],
            [
                'name'           => 'Vue.js 3 & Pinia Thực Chiến',
                'description'    => 'Học Vue.js 3 với Composition API, Pinia state management, Vue Router và Tailwind CSS. Xây dựng SPA hoàn chỉnh tích hợp REST API. Thực hành qua dự án thực tế từ đầu đến cuối.',
                'price'          => 499000,
                'sale_price'     => null,
                'level'          => 'intermediate',
                'status'         => 1,
                'category_slugs' => ['vuejs', 'web-development'],
            ],
            [
                'name'           => 'HTML, CSS & JavaScript Cho Người Mới',
                'description'    => 'Khóa học nền tảng web development hoàn toàn miễn phí. Bạn sẽ nắm vững HTML5, CSS3 với Flexbox và Grid layout, JavaScript ES6+ và DOM manipulation. Điểm khởi đầu lý tưởng cho mọi lập trình viên web.',
                'price'          => 0,
                'sale_price'     => null,
                'level'          => 'beginner',
                'status'         => 1,
                'category_slugs' => ['html-css', 'javascript', 'web-development'],
            ],
            [
                'name'           => 'React.js & Next.js Full-Stack',
                'description'    => 'Xây dựng ứng dụng full-stack hiện đại với React.js, Next.js 14 App Router và Node.js. Học SSR, SSG, API Routes, authentication, và deploy lên Vercel. Dự án thực tế: E-Commerce Platform.',
                'price'          => 799000,
                'sale_price'     => 599000,
                'level'          => 'intermediate',
                'status'         => 1,
                'category_slugs' => ['react', 'web-development'],
            ],
            [
                'name'           => 'Node.js & Express REST API',
                'description'    => 'Xây dựng REST API production-ready với Node.js, Express, JWT authentication, MongoDB và Redis cache. Áp dụng clean architecture, middleware pattern và unit testing.',
                'price'          => 450000,
                'sale_price'     => null,
                'level'          => 'intermediate',
                'status'         => 1,
                'category_slugs' => ['nodejs', 'web-development'],
            ],

            // ── Database ──
            [
                'name'           => 'MySQL Nâng Cao & Tối Ưu Query',
                'description'    => 'Nắm vững thiết kế database chuẩn, normalization, indexing chiến lược, query optimization và stored procedures. Thực hành với MySQL 8.0 trên dữ liệu triệu record thực tế.',
                'price'          => 349000,
                'sale_price'     => 249000,
                'level'          => 'advanced',
                'status'         => 1,
                'category_slugs' => ['co-so-du-lieu'],
            ],

            // ── Mobile ──
            [
                'name'           => 'Flutter & Dart Từ Zero Đến Hero',
                'description'    => 'Học Flutter và Dart để xây dựng ứng dụng đa nền tảng iOS & Android. Bao gồm state management với Bloc/Provider, kết nối API, local storage và publish lên store.',
                'price'          => 699000,
                'sale_price'     => 499000,
                'level'          => 'beginner',
                'status'         => 1,
                'category_slugs' => ['flutter', 'mobile-development'],
            ],
            [
                'name'           => 'React Native Thực Chiến 2025',
                'description'    => 'Phát triển app mobile cross-platform với React Native và Expo. Học navigation, camera, push notification, payment integration và CI/CD cho mobile.',
                'price'          => 599000,
                'sale_price'     => null,
                'level'          => 'intermediate',
                'status'         => 1,
                'category_slugs' => ['react-native', 'mobile-development'],
            ],

            // ── DevOps ──
            [
                'name'           => 'Docker & DevOps Cho Developer',
                'description'    => 'Học Docker, Docker Compose, CI/CD với GitHub Actions và GitLab CI. Deploy ứng dụng lên VPS với Nginx reverse proxy, SSL và monitoring. Nền tảng DevOps thiết yếu cho mọi developer.',
                'price'          => 450000,
                'sale_price'     => 350000,
                'level'          => 'advanced',
                'status'         => 1,
                'category_slugs' => ['devops-cloud'],
            ],

            // ── AI ──
            [
                'name'           => 'Python & Machine Learning Cơ Bản',
                'description'    => 'Nhập môn Python và Machine Learning. Sử dụng NumPy, Pandas, Matplotlib để phân tích dữ liệu. Xây dựng model với Scikit-learn và deploy API dự đoán đầu tiên của bạn.',
                'price'          => 699000,
                'sale_price'     => 499000,
                'level'          => 'beginner',
                'status'         => 1,
                'category_slugs' => ['python', 'machine-learning', 'data-science'],
            ],

            // ── Tiếng Anh ──
            [
                'name'           => 'IELTS 7.0 Toàn Diện 4 Kỹ Năng',
                'description'    => 'Lộ trình học IELTS bài bản từ band 5.0 lên 7.0+. Bao gồm Listening, Reading, Writing Task 1 & 2, Speaking. Kèm 500+ đề thi thử và bộ từ vựng học thuật IELTS chuyên sâu.',
                'price'          => 899000,
                'sale_price'     => 699000,
                'level'          => 'intermediate',
                'status'         => 1,
                'category_slugs' => ['tieng-anh'],
            ],
            [
                'name'           => 'Tiếng Anh Giao Tiếp Văn Phòng',
                'description'    => 'Học tiếng Anh thực tế cho môi trường công sở: email chuyên nghiệp, meeting, thuyết trình và đàm phán. Tập trung phát âm chuẩn và phản xạ giao tiếp tự nhiên.',
                'price'          => 399000,
                'sale_price'     => null,
                'level'          => 'beginner',
                'status'         => 1,
                'category_slugs' => ['tieng-anh'],
            ],

            // ── Tiếng Nhật ──
            [
                'name'           => 'Tiếng Nhật N5-N4 Cho Người Mới Bắt Đầu',
                'description'    => 'Học tiếng Nhật từ zero: Hiragana, Katakana, Kanji N5, ngữ pháp và hội thoại cơ bản. Luyện thi JLPT N5 và N4 với bộ đề thi thử đầy đủ và giải thích chi tiết.',
                'price'          => 499000,
                'sale_price'     => 349000,
                'level'          => 'beginner',
                'status'         => 1,
                'category_slugs' => ['tieng-nhat'],
            ],

            // ── Tiếng Hàn ──
            [
                'name'           => 'Tiếng Hàn TOPIK I - Sơ Cấp',
                'description'    => 'Học tiếng Hàn từ bảng chữ cái Hangul đến TOPIK I level 1-2. Bao gồm phát âm chuẩn, từ vựng 1000 từ cơ bản, ngữ pháp và luyện đề thi TOPIK I thực tế.',
                'price'          => 449000,
                'sale_price'     => null,
                'level'          => 'beginner',
                'status'         => 1,
                'category_slugs' => ['tieng-han'],
            ],
            [
                'name'           => 'API Design & RESTful Best Practices',
                'description'    => 'Thiết kế REST API chuẩn: naming conventions, versioning, authentication (JWT/OAuth2), rate limiting, pagination và documentation với Swagger/OpenAPI. Kèm ví dụ thực tế với Laravel và Node.js.',
                'price'          => 399000,
                'sale_price'     => null,
                'level'          => 'intermediate',
                'status'         => 1,
                'category_slugs' => ['web-development'],
            ],
        ];
    }
}
