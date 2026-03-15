<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * AdminLoginRequest — Validation cho endpoint đăng nhập Admin.
 *
 * Rules:
 *   - email: bắt buộc, đúng format email
 *   - password: bắt buộc, tối thiểu 6 ký tự
 *
 * Khi validation fail → trả JSON 422 (không redirect HTML).
 */
class AdminLoginRequest extends FormRequest
{
    /**
     * Xác định user có quyền gửi request này không.
     * Login không cần auth → luôn true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    /**
     * Custom messages tiếng Việt.
     */
    public function messages(): array
    {
        return [
            'email.required'    => 'Email không được để trống.',
            'email.email'       => 'Email không đúng định dạng.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.min'      => 'Mật khẩu phải có ít nhất :min ký tự.',
        ];
    }

    /**
     * Override failedValidation để trả JSON thay vì redirect.
     * Đảm bảo tương thích với API-only (không dùng Blade).
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
