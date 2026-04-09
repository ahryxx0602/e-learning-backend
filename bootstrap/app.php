<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API-only project: không redirect về trang login
        // Để AuthenticationException được throw → exception handler trả về 401 JSON
        $middleware->redirectGuestsTo(fn () => null);

        // Alias middleware kiểm tra tài khoản đã xác thực email
        $middleware->alias([
            'email.verified' => \Modules\Auth\Http\Middleware\EnsureEmailVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // --- API JSON Exception Handler ---
        // Đảm bảo tất cả lỗi trong /api/ routes trả về JSON thay vì HTML

        // ModelNotFoundException → 404 JSON
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'message' => "{$model} không tìm thấy.",
                    'data'    => null,
                ], 404);
            }
        });

        // MethodNotAllowedHttpException → 405 JSON
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phương thức HTTP không được phép cho endpoint này.',
                    'data'    => null,
                ], 405);
            }
        });

        // NotFoundHttpException (route không tồn tại) → 404 JSON
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Endpoint không tồn tại.',
                    'data'    => null,
                ], 404);
            }
        });

        // AuthenticationException → 401 JSON
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa đăng nhập hoặc token không hợp lệ.',
                    'data'    => null,
                ], 401);
            }
        });

        // AccessDeniedHttpException → 403 JSON
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thực hiện hành động này.',
                    'data'    => null,
                ], 403);
            }
        });

        // ValidationException → 422 JSON (chuẩn hoá format giống ApiResponse)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ.',
                    'data'    => null,
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('media:prune-orphans')->dailyAt('03:00');
    })
    ->create();
