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
        Category::query()->forceDelete();

        // Insert thẳng vào DB với parent_id, sau đó fixTree() sẽ tính lại lft/rgt
        $nodes = [
            // [name, slug, description, icon, parent_slug]
            ['Lập trình',          'lap-trinh',        'Các khóa học lập trình',                'fa-code',       null],
            ['Ngoại ngữ',          'ngoai-ngu',        'Các khóa học ngoại ngữ',                'fa-language',   null],

            ['Web Development',    'web-development',  'Frontend & Backend',                    'fa-globe',      'lap-trinh'],
            ['Mobile Development', 'mobile-development','Android, iOS, cross-platform',         'fa-mobile-alt', 'lap-trinh'],
            ['Data Science & AI',  'data-science',     'Machine Learning và Data Science',      'fa-chart-bar',  'lap-trinh'],
            ['DevOps & Cloud',     'devops-cloud',     'Docker, CI/CD, AWS',                    'fa-cloud',      'lap-trinh'],
            ['Cơ sở dữ liệu',      'co-so-du-lieu',    'MySQL, MongoDB, Redis',                 'fa-database',   'lap-trinh'],

            ['HTML & CSS',         'html-css',         'Nền tảng HTML5 và CSS3',                'fa-html5',      'web-development'],
            ['JavaScript',         'javascript',       'JavaScript ES6+',                       'fa-js',         'web-development'],
            ['React.js',           'react',            'Thư viện React.js',                     'fa-react',      'web-development'],
            ['Vue.js',             'vuejs',            'Framework Vue.js 3',                    'fa-vuejs',      'web-development'],
            ['Laravel',            'laravel',          'Framework PHP Laravel',                 'fa-php',        'web-development'],
            ['Node.js',            'nodejs',           'Runtime JavaScript Node.js',            'fa-node',       'web-development'],

            ['Flutter',            'flutter',          'Flutter & Dart',                        'fa-mobile',     'mobile-development'],
            ['React Native',       'react-native',     'React Native & Expo',                   'fa-mobile',     'mobile-development'],

            ['Python',             'python',           'Ngôn ngữ Python',                       'fa-python',     'data-science'],
            ['Machine Learning',   'machine-learning', 'ML với Scikit-learn',                   'fa-brain',      'data-science'],

            ['Tiếng Anh',          'tieng-anh',        'IELTS, TOEIC, giao tiếp',               'fa-flag',       'ngoai-ngu'],
            ['Tiếng Nhật',         'tieng-nhat',       'JLPT N5-N1',                            'fa-flag',       'ngoai-ngu'],
            ['Tiếng Hàn',          'tieng-han',        'TOPIK I-II',                            'fa-flag',       'ngoai-ngu'],
        ];

        // Map slug -> id để resolve parent_id
        $slugToId = [];
        $now = now();

        foreach ($nodes as $node) {
            [$name, $slug, $desc, $icon, $parentSlug] = $node;

            $parentId = $parentSlug ? ($slugToId[$parentSlug] ?? null) : null;

            $id = \Illuminate\Support\Facades\DB::table('categories')->insertGetId([
                'name'        => $name,
                'slug'        => $slug,
                'description' => $desc,
                'icon'        => $icon,
                'status'      => 1,
                'order'       => 0,
                'parent_id'   => $parentId,
                '_lft'        => 0,
                '_rgt'        => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $slugToId[$slug] = $id;
        }

        // Rebuild lft/rgt cho toàn bộ cây
        Category::fixTree();

        $this->command->info('Đã seed ' . Category::count() . ' categories.');
    }
}
