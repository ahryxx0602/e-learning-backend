<?php

namespace Modules\Categories\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Categories\Models\Category;

class CategoriesDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Tạo cây danh mục mẫu cho E-Learning.
     * Sử dụng nested set model (kalnoy/nestedset).
     */
    public function run(): void
    {
        // Xoá hết dữ liệu cũ (reset nested set)
        Category::query()->forceDelete();

        $categories = [
            [
                'name'        => 'Lập trình',
                'slug'        => 'lap-trinh',
                'description' => 'Các khóa học về lập trình và phát triển phần mềm',
                'icon'        => 'fa-code',
                'children'    => [
                    [
                        'name'        => 'Web Development',
                        'slug'        => 'web-development',
                        'description' => 'Phát triển web frontend và backend',
                        'icon'        => 'fa-globe',
                        'children'    => [
                            ['name' => 'HTML & CSS',    'slug' => 'html-css',    'description' => 'Nền tảng HTML và CSS'],
                            ['name' => 'JavaScript',    'slug' => 'javascript',  'description' => 'Ngôn ngữ JavaScript'],
                            ['name' => 'React',         'slug' => 'react',       'description' => 'Thư viện React.js'],
                            ['name' => 'Vue.js',        'slug' => 'vuejs',       'description' => 'Framework Vue.js'],
                            ['name' => 'Laravel',       'slug' => 'laravel',     'description' => 'Framework PHP Laravel'],
                            ['name' => 'Node.js',       'slug' => 'nodejs',      'description' => 'Runtime Node.js'],
                        ],
                    ],
                    [
                        'name'        => 'Mobile Development',
                        'slug'        => 'mobile-development',
                        'description' => 'Phát triển ứng dụng di động',
                        'icon'        => 'fa-mobile-alt',
                        'children'    => [
                            ['name' => 'Flutter',        'slug' => 'flutter',       'description' => 'Framework Flutter'],
                            ['name' => 'React Native',   'slug' => 'react-native',  'description' => 'Framework React Native'],
                            ['name' => 'Swift (iOS)',     'slug' => 'swift-ios',     'description' => 'Lập trình iOS với Swift'],
                            ['name' => 'Kotlin (Android)', 'slug' => 'kotlin-android', 'description' => 'Lập trình Android với Kotlin'],
                        ],
                    ],
                    [
                        'name'        => 'Data Science',
                        'slug'        => 'data-science',
                        'description' => 'Khoa học dữ liệu và Machine Learning',
                        'icon'        => 'fa-chart-bar',
                        'children'    => [
                            ['name' => 'Python',         'slug' => 'python',        'description' => 'Ngôn ngữ Python'],
                            ['name' => 'Machine Learning', 'slug' => 'machine-learning', 'description' => 'Học máy'],
                            ['name' => 'Deep Learning',  'slug' => 'deep-learning', 'description' => 'Học sâu'],
                        ],
                    ],
                ],
            ],
            [
                'name'        => 'Thiết kế',
                'slug'        => 'thiet-ke',
                'description' => 'Các khóa học về thiết kế đồ họa và UI/UX',
                'icon'        => 'fa-paint-brush',
                'children'    => [
                    ['name' => 'UI/UX Design',    'slug' => 'ui-ux-design',    'description' => 'Thiết kế giao diện và trải nghiệm người dùng'],
                    ['name' => 'Graphic Design',  'slug' => 'graphic-design',  'description' => 'Thiết kế đồ họa'],
                    ['name' => 'Figma',           'slug' => 'figma',           'description' => 'Công cụ thiết kế Figma'],
                    ['name' => 'Adobe Photoshop', 'slug' => 'adobe-photoshop', 'description' => 'Phần mềm Adobe Photoshop'],
                ],
            ],
            [
                'name'        => 'Kinh doanh',
                'slug'        => 'kinh-doanh',
                'description' => 'Các khóa học về kinh doanh và quản lý',
                'icon'        => 'fa-briefcase',
                'children'    => [
                    ['name' => 'Marketing',       'slug' => 'marketing',       'description' => 'Digital Marketing'],
                    ['name' => 'Quản lý dự án',   'slug' => 'quan-ly-du-an',   'description' => 'Project Management'],
                    ['name' => 'Khởi nghiệp',     'slug' => 'khoi-nghiep',     'description' => 'Startup và khởi nghiệp'],
                ],
            ],
            [
                'name'        => 'Ngoại ngữ',
                'slug'        => 'ngoai-ngu',
                'description' => 'Các khóa học ngoại ngữ',
                'icon'        => 'fa-language',
                'children'    => [
                    ['name' => 'Tiếng Anh',       'slug' => 'tieng-anh',       'description' => 'Học tiếng Anh'],
                    ['name' => 'Tiếng Nhật',      'slug' => 'tieng-nhat',      'description' => 'Học tiếng Nhật'],
                    ['name' => 'Tiếng Hàn',       'slug' => 'tieng-han',       'description' => 'Học tiếng Hàn'],
                    ['name' => 'Tiếng Trung',     'slug' => 'tieng-trung',     'description' => 'Học tiếng Trung'],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $this->createCategory($categoryData);
        }
    }

    /**
     * Tạo category đệ quy (nested set).
     */
    private function createCategory(array $data, ?Category $parent = null): void
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        // Thêm status mặc định nếu chưa có
        $data['status'] = $data['status'] ?? 1;

        if ($parent) {
            $category = $parent->children()->create($data);
        } else {
            $category = Category::create($data);
        }

        foreach ($children as $childData) {
            $this->createCategory($childData, $category);
        }
    }
}
