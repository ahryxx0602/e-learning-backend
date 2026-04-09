<?php

namespace Modules\Teachers\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Teachers\Models\Teachers;

class TeachersDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.-
     */
    public function run(): void
    {
        $teachers = [
            [
                'name' => 'Nguyễn Văn An',
                'slug' => 'nguyen-van-an',
                'description' => 'Giảng viên lập trình Web với hơn 10 năm kinh nghiệm trong phát triển phần mềm. Chuyên gia về PHP, Laravel, và các công nghệ web hiện đại.',
                'exp' => 10,
                'date_of_birth' => '1985-03-15',
                'image' => 'seed/images/teacher-1.png',
                'status' => 1,
            ],
            [
                'name' => 'Trần Thị Bình',
                'slug' => 'tran-thi-binh',
                'description' => 'Chuyên gia thiết kế UI/UX với 8 năm kinh nghiệm. Đã tham gia thiết kế giao diện cho nhiều dự án lớn tại Việt Nam và quốc tế.',
                'exp' => 8,
                'date_of_birth' => '1990-07-22',
                'image' => 'seed/images/teacher-2.png',
                'status' => 1,
            ],
            [
                'name' => 'Lê Minh Cường',
                'slug' => 'le-minh-cuong',
                'description' => 'Giảng viên Digital Marketing với 6 năm kinh nghiệm. Chuyên về SEO, Google Ads, và Facebook Marketing.',
                'exp' => 6,
                'date_of_birth' => '1992-11-05',
                'image' => 'seed/images/teacher-3.png',
                'status' => 1,
            ],
            [
                'name' => 'Phạm Hồng Đức',
                'slug' => 'pham-hong-duc',
                'description' => 'Lập trình viên Mobile với 7 năm kinh nghiệm. Chuyên về Flutter, React Native và phát triển ứng dụng đa nền tảng.',
                'exp' => 7,
                'date_of_birth' => '1988-01-30',
                'image' => 'seed/images/teacher-4.png',
                'status' => 1,
            ],
            [
                'name' => 'Hoàng Thị Em',
                'slug' => 'hoang-thi-em',
                'description' => 'Giảng viên tiếng Anh với chứng chỉ IELTS 8.5, 5 năm kinh nghiệm giảng dạy trực tuyến.',
                'exp' => 5,
                'date_of_birth' => '1995-06-18',
                'image' => 'seed/images/teacher-1.png',
                'status' => 1,
            ],
            [
                'name' => 'Vũ Đình Phú',
                'slug' => 'vu-dinh-phu',
                'description' => 'Chuyên gia DevOps và Cloud Computing với 9 năm kinh nghiệm. AWS Certified Solutions Architect, Docker, Kubernetes.',
                'exp' => 9,
                'date_of_birth' => '1986-09-12',
                'image' => 'seed/images/teacher-2.png',
                'status' => 1,
            ],
            [
                'name' => 'Đặng Thị Giang',
                'slug' => 'dang-thi-giang',
                'description' => 'Giảng viên Data Science & Machine Learning. Tiến sĩ AI từ Đại học Bách Khoa, 4 năm kinh nghiệm giảng dạy.',
                'exp' => 4,
                'date_of_birth' => '1993-12-25',
                'image' => 'seed/images/teacher-3.png',
                'status' => 1,
            ],
            [
                'name' => 'Bùi Quang Huy',
                'slug' => 'bui-quang-huy',
                'description' => 'Chuyên gia bảo mật mạng (Cybersecurity) với 12 năm kinh nghiệm. CEH, OSCP certified.',
                'exp' => 12,
                'date_of_birth' => '1983-04-08',
                'image' => 'seed/images/teacher-4.png',
                'status' => 1,
            ],
            [
                'name' => 'Ngô Thanh Vy',
                'slug' => 'ngo-thanh-vy',
                'description' => 'Giảng viên Graphic Design với 6 năm kinh nghiệm. Thành thạo Adobe Creative Suite, Figma, Sketch.',
                'exp' => 6,
                'date_of_birth' => '1991-08-14',
                'image' => 'seed/images/teacher-1.png',
                'status' => 0,
            ],
            [
                'name' => 'Trịnh Văn Khoa',
                'slug' => 'trinh-van-khoa',
                'description' => 'Giảng viên Blockchain & Web3 với 3 năm kinh nghiệm. Solidity developer, DeFi specialist.',
                'exp' => 3,
                'date_of_birth' => '1996-02-28',
                'image' => 'seed/images/teacher-2.png',
                'status' => 0,
            ],
        ];

        foreach ($teachers as $teacher) {
            Teachers::updateOrCreate(
            ['slug' => $teacher['slug']],
                $teacher
            );
        }
    }
}
