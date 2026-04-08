<?php

namespace Modules\Course\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Course\Models\Course;

class CourseDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            [
                'name'        => 'Laravel 12 Từ Cơ Bản Đến Nâng Cao',
                'description' => 'Khóa học Laravel toàn diện, từ cài đặt, routing, Eloquent ORM, đến API, Queue, Testing. Phù hợp cho người mới bắt đầu và muốn nâng cao kỹ năng PHP.',
                'price'       => 599000,
                'sale_price'  => 399000,
                'level'       => 'beginner',
                'status'      => 1,
            ],
            [
                'name'        => 'Vue.js 3 & Composition API Thực Chiến',
                'description' => 'Học Vue.js 3 với Composition API, Pinia, Vue Router. Xây dựng ứng dụng SPA hoàn chỉnh với Tailwind CSS.',
                'price'       => 499000,
                'sale_price'  => null,
                'level'       => 'intermediate',
                'status'      => 1,
            ],
            [
                'name'        => 'Thiết Kế Database & SQL Nâng Cao',
                'description' => 'Nắm vững thiết kế database, normalization, indexing, query optimization. Thực hành với MySQL 8.0.',
                'price'       => 349000,
                'sale_price'  => 249000,
                'level'       => 'advanced',
                'status'      => 1,
            ],
            [
                'name'        => 'React.js & Next.js Full-Stack Development',
                'description' => 'Xây dựng ứng dụng full-stack với React.js, Next.js, và Node.js. Từ frontend đến backend API.',
                'price'       => 799000,
                'sale_price'  => 599000,
                'level'       => 'intermediate',
                'status'      => 1,
            ],
            [
                'name'        => 'Docker & DevOps Cho Developer',
                'description' => 'Học Docker, Docker Compose, CI/CD với GitHub Actions. Deploy ứng dụng lên cloud server.',
                'price'       => 450000,
                'sale_price'  => null,
                'level'       => 'advanced',
                'status'      => 1,
            ],
            [
                'name'        => 'HTML, CSS & JavaScript Cho Người Mới',
                'description' => 'Khóa học nền tảng web development. Học HTML5, CSS3, Flexbox, Grid và JavaScript ES6+.',
                'price'       => 0,
                'sale_price'  => null,
                'level'       => 'beginner',
                'status'      => 1,
            ],
            [
                'name'        => 'Python & Machine Learning Cơ Bản',
                'description' => 'Nhập môn Python và Machine Learning. Sử dụng NumPy, Pandas, Scikit-learn để xây dựng model AI đầu tiên.',
                'price'       => 699000,
                'sale_price'  => 499000,
                'level'       => 'beginner',
                'status'      => 0, // Draft
            ],
            [
                'name'        => 'API Design & RESTful Best Practices',
                'description' => 'Thiết kế API chuẩn RESTful, authentication, versioning, rate limiting, documentation với Swagger/OpenAPI.',
                'price'       => 399000,
                'sale_price'  => null,
                'level'       => 'intermediate',
                'status'      => 1,
            ],
        ];

        // Lấy danh sách teacher_ids và category_ids đã seed
        $teacherIds = \Modules\Teachers\Models\Teachers::pluck('id')->toArray();
        $categoryIds = \Modules\Categories\Models\Category::pluck('id')->toArray();

        if (empty($teacherIds)) {
            $this->command->warn('Chưa có teacher nào. Hãy seed Module Teachers trước.');
            return;
        }

        foreach ($courses as $courseData) {
            $course = Course::create([
                'teacher_id'  => $teacherIds[array_rand($teacherIds)],
                'name'        => $courseData['name'],
                'slug'        => Str::slug($courseData['name']),
                'description' => $courseData['description'],
                'price'       => $courseData['price'],
                'sale_price'  => $courseData['sale_price'],
                'level'       => $courseData['level'],
                'status'      => $courseData['status'],
                'thumbnail'   => 'https://placehold.co/600x400?text=' . urlencode(Str::limit($courseData['name'], 20)),
            ]);

            // Gắn 1-3 categories ngẫu nhiên
            if (!empty($categoryIds)) {
                $randomCatIds = array_slice(
                    $categoryIds,
                    0,
                    min(rand(1, 3), count($categoryIds))
                );
                shuffle($categoryIds);
                $course->categories()->attach($randomCatIds);
            }
        }

        $this->command->info('Đã seed ' . count($courses) . ' khóa học mẫu.');
    }
}
