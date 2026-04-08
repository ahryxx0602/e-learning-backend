# E-Learning Marketplace Platform — Backend

> **Đồ án tốt nghiệp** — Trường Khoa học Máy tính, Khoa Công nghệ Thông tin
> Ngành: Công nghệ Phần mềm | Đại học Duy Tân

---

## Thông tin dự án

| Thông tin | Chi tiết |
|-----------|----------|
| **Tên đề tài** | Xây dựng hệ thống nền tảng học tập trực tuyến (E-learning Marketplace) tích hợp thanh toán trực tuyến |
| **Sinh viên** | Phan Văn Thành — MSSV: 28211102974 |
| **GVHD** | Trịnh Sử Trường Thi |
| **Thời gian** | 12/03/2026 – 15/05/2026 |

---

## Giới thiệu dự án

**E-Learning Marketplace** là một nền tảng giáo dục số toàn diện, đóng vai trò như một khu chợ trung gian kết nối **giảng viên** và **người học**. Hệ thống cho phép giảng viên đóng gói và phân phối khóa học dạng video (VOD), đồng thời giúp học viên học tập mọi lúc mọi nơi.

> **Lưu ý:** Repository này là phần **Backend**. Frontend đang được phát triển riêng.

### Tính năng dự kiến

- **Quản lý khóa học Video (VOD)** — upload, tổ chức và phân phối bài giảng video
- **Hệ thống giỏ hàng & thanh toán** — tích hợp VNPAY/MoMo, xử lý giao dịch an toàn
- **AI Auto-Quiz** — tự động sinh câu hỏi trắc nghiệm từ tài liệu PDF/TXT (Google Gemini / OpenAI GPT-4o-mini)
- **Dashboard thống kê** — theo dõi doanh thu, tiến độ học tập
- **Phân quyền đa vai trò** — Admin / Giảng viên / Học viên
- **Mã giảm giá (Coupon)** và thông báo real-time

---

## Công nghệ sử dụng

### Backend
| Công nghệ | Mô tả |
|-----------|-------|
| **PHP 8.1+ / Laravel 11** | Framework chính — kiến trúc Modular (Nwidart Modules) |
| **MySQL 8.0** | Cơ sở dữ liệu quan hệ |
| **Laravel Sanctum** | Xác thực API token |
| **Spatie Laravel Permission** | Quản lý phân quyền theo vai trò (RBAC) |

### Tích hợp & Dịch vụ
| Công nghệ | Mô tả |
|-----------|-------|
| **VNPAY / MoMo API** | Cổng thanh toán trực tuyến |
| **AWS S3 / Cloudinary** | Cloud Storage lưu trữ video bài giảng |
| **OpenAI GPT-4o-mini** | Tính năng AI Auto-Quiz tự động tạo câu hỏi |
| **Spatie PDF-to-Text** | Trích xuất văn bản từ tài liệu PDF |

### Quy trình phát triển
- Phương pháp **Agile/Scrum** — chia 3 Sprint

---

## Yêu cầu hệ thống

- PHP >= 8.1
- Composer >= 2.x
- Node.js >= 18.x & NPM
- MySQL >= 8.0
- Git

---

## Cài đặt

**1. Clone repository**
```bash
git clone git@github.com:ahryxx0602/e-learning-pj.git
cd e-learning-backend
```

**2. Cài đặt dependencies PHP**
```bash
composer install
```

**3. Cài đặt dependencies JavaScript**
```bash
npm install
npm run build
```

**4. Cấu hình môi trường**
```bash
cp .env.example .env
php artisan key:generate
```

**5. Cấu hình file `.env`**
```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=elearning_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

**6. Migrate & Seed database**
```bash
php artisan migrate --seed
```

**7. Tạo symbolic link cho storage**
```bash
php artisan storage:link
```

**8. Khởi chạy ứng dụng**
```bash
php artisan serve
```

Truy cập: `http://localhost:8000`

---

## Cấu trúc dự án

```
e-learning-backend/
├── app/                            # Core application (base classes)
│   ├── Http/Controllers/           # Base Controller
│   ├── Models/                     # User model
│   └── Providers/                  # AppServiceProvider
│
├── Modules/                        # Feature modules (Nwidart Laravel Modules)
│   └── User/                       # Module quản lý người dùng
│   │   ├── app/
│   │   │   ├── Http/Controllers/   # Controllers của module
│   │   │   └── Providers/          # Service providers
│   │   ├── config/                 # Cấu hình module
│   │   ├── database/
│   │   │   ├── migrations/
│   │   │   └── seeders/            # RoleAndAdminSeeder, UserDatabaseSeeder
│   │   ├── resources/
│   │   │   ├── assets/             # JS, SCSS
│   │   │   └── views/              # Blade templates
│   │   ├── routes/
│   │   │   ├── api.php
│   │   │   └── web.php
│   │   └── module.json
│   └── .../
│   
├── config/                         # Cấu hình ứng dụng
│   ├── app.php
│   ├── auth.php
│   ├── permission.php              # Spatie permission config
│   ├── sanctum.php                 # API token auth config
│   └── ...
│
├── database/
│   ├── migrations/                 # Schema: users, cache, jobs, tokens, permissions
│   ├── factories/
│   └── seeders/
│
├── routes/
│   ├── api.php                     # API routes
│   ├── web.php                     # Web routes
│   └── console.php
│
├── resources/
│   ├── css/
│   ├── js/
│   └── views/                      # Blade templates chung
│
├── public/                         # Entry point (index.php)
├── storage/                        # Logs, cache, uploads
├── tests/
│   ├── Feature/
│   └── Unit/
│
├── modules_statuses.json           # Trạng thái các module
├── composer.json
├── package.json
├── vite.config.js
└── .env.example
```

---

## Bảo mật

Hệ thống sử dụng các cơ chế bảo mật mặc định của Laravel:

- **CSRF Protection** — chống tấn công Cross-Site Request Forgery
- **SQL Injection Prevention** — thông qua Eloquent ORM & Query Builder
- **XSS Protection** — Blade Template tự động escape output
- **Authentication & Authorization** — Laravel Sanctum + Spatie Permission (RBAC)

---

## Phạm vi & Giới hạn

**Bao gồm:**
- Quản lý khóa học VOD, phân quyền đa vai trò
- Giỏ hàng, thanh toán trực tuyến (VNPAY/MoMo)
- AI Auto-Quiz từ tài liệu văn bản

**Không bao gồm:**
- Livestream / gọi video trực tiếp
- Hệ thống thi trắc nghiệm thời gian thực quy mô lớn
- AI phân tích dữ liệu học tập nâng cao

---

## Tác giả

**Phan Văn Thành**
- Email: phvanthanh06@gmail.com
- Phone: 0327461459
- Sinh viên năm 4 — Khoa học Máy tính, Đại học Duy Tân

---

## License

Dự án phát hành theo giấy phép [MIT](LICENSE).

Được thực hiện với mục đích học thuật — Đồ án tốt nghiệp Đại học Duy Tân, 2026.
