<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware EnsureEmailVerified
 *
 * Chặn học viên chưa xác thực email truy cập vào các route yêu cầu kích hoạt.
 * Dùng sau middleware auth:api — bảo đảm $request->user('api') đã tồn tại.
 *
 * Response khi chưa xác thực:
 * HTTP 403
 * {
 *     "success": false,
 *     "message": "Tài khoản chưa được kích hoạt...",
 *     "data": null,
 *     "errors": { "email_not_verified": true, "email": "..." }
 * }
 */
class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $student = $request->user('api');

        if (!$student || !$student->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa được kích hoạt. Vui lòng kiểm tra email để xác thực tài khoản.',
                'data'    => null,
                'errors'  => [
                    'email_not_verified' => true,
                    'email'              => $student?->email,
                ],
            ], 403);
        }

        return $next($request);
    }
}
