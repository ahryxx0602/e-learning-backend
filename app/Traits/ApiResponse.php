<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Trait ApiResponse
 *
 * Cung cấp format response chuẩn cho toàn bộ API.
 * Mọi Controller chỉ cần `use ApiResponse;` để có 3 method:
 * success(), error(), paginated().
 *
 * Response format chuẩn:
 * {
 *     "success": true|false,
 *     "message": "string",
 *     "data": any,
 *     "pagination": { ... }  // chỉ có khi dùng paginated()
 * }
 */
trait ApiResponse
{
    /**
     * Trả về response thành công.
     *
     * @param  mixed   $data     Dữ liệu trả về
     * @param  string  $message  Thông báo
     * @param  int     $code     HTTP status code
     * @return JsonResponse
     */
    protected function success(mixed $data = null, string $message = 'Thành công', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Trả về response lỗi.
     *
     * @param  string      $message  Thông báo lỗi
     * @param  int         $code     HTTP status code
     * @param  array|null  $errors   Chi tiết lỗi (validation errors, etc.)
     * @return JsonResponse
     */
    protected function error(string $message = 'Có lỗi xảy ra', int $code = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'data'    => null,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Trả về response có phân trang.
     *
     * @param  LengthAwarePaginator  $paginator  Kết quả phân trang từ Eloquent
     * @param  string                $message    Thông báo
     * @return JsonResponse
     */
    protected function paginated(LengthAwarePaginator $paginator, string $message = 'Thành công'): JsonResponse
    {
        return response()->json([
            'success'    => true,
            'message'    => $message,
            'data'       => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ], 200);
    }
}
