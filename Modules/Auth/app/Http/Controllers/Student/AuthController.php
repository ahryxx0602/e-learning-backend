<?php

namespace Modules\Auth\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Auth\Http\Requests\Student\ForgotPasswordRequest;
use Modules\Auth\Http\Requests\Student\LoginRequest;
use Modules\Auth\Http\Requests\Student\RegisterRequest;
use Modules\Auth\Http\Requests\Student\ResendVerificationRequest;
use Modules\Auth\Http\Requests\Student\ResetPasswordRequest;
use Modules\Students\Models\Student;

/**
 * Student AuthController
 *
 * Xử lý xác thực cho phía học viên (guard: api).
 * Endpoints:
 *   POST /api/v1/auth/register             → Đăng ký
 *   POST /api/v1/auth/login                → Đăng nhập
 *   POST /api/v1/auth/logout               → Đăng xuất [auth:api]
 *   GET  /api/v1/auth/me                   → Thông tin học viên [auth:api]
 *   GET  /api/v1/auth/verify-email/{token} → Xác thực email
 *   POST /api/v1/auth/resend-verification  → Gửi lại email xác thực
 *   POST /api/v1/auth/forgot-password      → Gửi link reset mật khẩu
 *   POST /api/v1/auth/reset-password       → Đặt lại mật khẩu
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Đăng ký học viên mới.
     *
     * Tạo tài khoản → gửi email xác thực → trả token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $verifyToken = null;

        // Wrap trong transaction để đảm bảo atomicity
        $student = DB::transaction(function () use ($data, &$verifyToken) {
            $student = Student::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password'], // model cast 'hashed'
            ]);

            // Tạo token xác thực email (cryptographically secure)
            $verifyToken = bin2hex(random_bytes(32));
            DB::table('student_email_verifications')->insert([
                'email'      => $student->email,
                'token'      => $verifyToken,
                'expires_at' => now()->addHours(24),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $student;
        });

        // Gửi email xác thực (fire & forget — nếu mail chưa cấu hình sẽ log lỗi nhưng không crash)
        try {
            $verifyUrl = config('app.url') . '/api/v1/auth/verify-email/' . $verifyToken;

            Mail::send('auth::emails.verify-email', [
                'studentName' => $student->name,
                'verifyUrl'   => $verifyUrl,
            ], function ($message) use ($student) {
                $message->to($student->email)
                    ->subject('Xác thực email — E-Learning');
            });
        } catch (\Exception) {
            // Không block đăng ký nếu mail lỗi
        }

        $token = $student->createToken('student-token')->plainTextToken;

        return $this->success([
            'token'   => $token,
            'student' => [
                'id'                => $student->id,
                'name'              => $student->name,
                'email'             => $student->email,
                'email_verified_at' => $student->email_verified_at,
            ],
        ], 'Đăng ký thành công. Vui lòng kiểm tra email để xác thực tài khoản.', 201);
    }

    /**
     * Đăng nhập học viên.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $student = Student::where('email', $credentials['email'])->first();

        if (!$student || !Hash::check($credentials['password'], $student->password)) {
            return $this->error('Email hoặc mật khẩu không đúng.', 401);
        }

        // Kiểm tra tài khoản đã xác thực email chưa
        if (!$student->email_verified_at) {
            return $this->error(
                'Tài khoản chưa được kích hoạt. Vui lòng kiểm tra email để xác thực tài khoản.',
                403,
                ['email_not_verified' => true, 'email' => $student->email]
            );
        }

        $token = $student->createToken('student-token')->plainTextToken;

        return $this->success([
            'token'   => $token,
            'student' => [
                'id'                => $student->id,
                'name'              => $student->name,
                'email'             => $student->email,
                'email_verified_at' => $student->email_verified_at,
            ],
        ], 'Đăng nhập thành công.');
    }

    /**
     * Đăng xuất học viên (revoke token hiện tại).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user('api')->currentAccessToken()->delete();

        return $this->success(null, 'Đăng xuất thành công.');
    }

    /**
     * Lấy thông tin học viên đang đăng nhập.
     */
    public function me(Request $request): JsonResponse
    {
        $student = $request->user('api');

        return $this->success([
            'id'                => $student->id,
            'name'              => $student->name,
            'email'             => $student->email,
            'avatar'            => $student->avatar,
            'date_of_birth'     => $student->date_of_birth,
            'email_verified_at' => $student->email_verified_at,
            'created_at'        => $student->created_at,
        ]);
    }

    /**
     * Xác thực email qua token.
     *
     * GET /api/v1/auth/verify-email/{token}
     */
    public function verifyEmail(string $token): JsonResponse
    {
        $record = DB::table('student_email_verifications')
            ->where('token', $token)
            ->first();

        if (!$record) {
            return $this->error('Token xác thực không hợp lệ.', 400);
        }

        if (now()->isAfter($record->expires_at)) {
            DB::table('student_email_verifications')->where('token', $token)->delete();
            return $this->error('Token xác thực đã hết hạn. Vui lòng đăng ký lại.', 400);
        }

        $student = Student::where('email', $record->email)->first();

        if (!$student) {
            return $this->error('Tài khoản không tồn tại.', 404);
        }

        if ($student->email_verified_at) {
            return $this->error('Email đã được xác thực trước đó.', 400);
        }

        $student->update(['email_verified_at' => now()]);
        DB::table('student_email_verifications')->where('token', $token)->delete();

        return $this->success(null, 'Xác thực email thành công.');
    }

    /**
     * Gửi lại email xác thực.
     *
     * POST /api/v1/auth/resend-verification
     * Xoá token cũ → tạo token mới → gửi lại email.
     */
    public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        $email = $request->validated('email');

        $student = Student::where('email', $email)->first();

        if ($student->email_verified_at) {
            return $this->error('Email đã được xác thực trước đó.', 400);
        }

        // Xoá token cũ (nếu có) và tạo token mới
        DB::transaction(function () use ($email, &$verifyToken) {
            DB::table('student_email_verifications')->where('email', $email)->delete();

            $verifyToken = bin2hex(random_bytes(32));
            DB::table('student_email_verifications')->insert([
                'email'      => $email,
                'token'      => $verifyToken,
                'expires_at' => now()->addHours(24),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // Gửi email xác thực
        try {
            $verifyUrl = config('app.url') . '/api/v1/auth/verify-email/' . $verifyToken;

            Mail::send('auth::emails.verify-email', [
                'studentName' => $student->name,
                'verifyUrl'   => $verifyUrl,
            ], function ($message) use ($student) {
                $message->to($student->email)
                    ->subject('Xác thực email — E-Learning');
            });
        } catch (\Exception) {
            return $this->error('Không thể gửi email. Vui lòng thử lại sau.', 500);
        }

        return $this->success(null, 'Email xác thực đã được gửi lại. Vui lòng kiểm tra hộp thư.');
    }

    /**
     * Gửi link đặt lại mật khẩu về email.
     *
     * Dùng Laravel Password Broker (broker: students).
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('students')->sendResetLink(
            $request->only('email')
        );

        // Trả 200 chung dù email có tồn tại hay không — tránh user enumeration
        if ($status === Password::RESET_LINK_SENT || $status === Password::INVALID_USER) {
            return $this->success(null, 'Nếu email tồn tại trong hệ thống, link đặt lại mật khẩu sẽ được gửi về email.');
        }

        if ($status === Password::RESET_THROTTLED) {
            return $this->error('Vui lòng chờ trước khi gửi lại.', 429);
        }

        return $this->error('Không thể gửi email. Vui lòng thử lại sau.', 500);
    }

    /**
     * Đặt lại mật khẩu.
     *
     * Dùng Laravel Password Broker (broker: students).
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('students')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Student $student, string $password) {
                $student->update([
                    'password'       => $password, // model cast 'hashed'
                    'remember_token' => Str::random(60),
                ]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(null, 'Đặt lại mật khẩu thành công.');
        }

        $message = match ($status) {
            Password::INVALID_TOKEN => 'Token không hợp lệ hoặc đã hết hạn.',
            Password::INVALID_USER  => 'Email không tồn tại trong hệ thống.',
            default                 => 'Không thể đặt lại mật khẩu. Vui lòng thử lại sau.',
        };

        return $this->error($message, 400);
    }
}
