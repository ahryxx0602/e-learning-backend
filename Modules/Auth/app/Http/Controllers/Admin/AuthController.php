<?php

namespace Modules\Auth\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Http\Requests\AdminLoginRequest;
use Modules\Users\Models\User;

/**
 * Admin AuthController
 *
 * Xử lý xác thực cho phía Admin (guard: admin).
 * Endpoints:
 *   POST /api/v1/admin/auth/login   → Đăng nhập
 *   POST /api/v1/admin/auth/logout  → Đăng xuất [auth:admin]
 *   GET  /api/v1/admin/auth/me      → Thông tin admin [auth:admin]
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Đăng nhập Admin.
     *
     * Validate email + password → kiểm tra credentials → trả token + user info.
     * Token name: 'admin-token' để phân biệt với student token.
     *
     * @param  AdminLoginRequest  $request
     * @return JsonResponse
     */
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        // Tìm user theo email
        $user = User::where('email', $credentials['email'])->first();

        // Kiểm tra user tồn tại và password đúng
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return $this->error('Email hoặc mật khẩu không đúng.', 401);
        }

        // Tạo Sanctum token
        $token = $user->createToken('admin-token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 'Đăng nhập thành công.');
    }

    /**
     * Đăng xuất Admin.
     *
     * Revoke token hiện tại (chỉ token đang dùng, không revoke tất cả).
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Xoá token hiện tại đang sử dụng
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Đăng xuất thành công.');
    }

    /**
     * Lấy thông tin Admin đang đăng nhập.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
        ]);
    }
}
