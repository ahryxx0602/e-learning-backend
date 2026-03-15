<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * AdminLoginRequest — Validation cho endpoint đăng nhập Admin.
 *
 * Rules:
 *   - email: bắt buộc, đúng format email, tối đa 255 ký tự
 *   - password: bắt buộc, tối thiểu 6, tối đa 100 ký tự
 *
 * Khi validation fail → Exception Handler tự trả JSON 422
 * (đã config trong bootstrap/app.php).
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
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
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
            'email.max'         => 'Email không được vượt quá :max ký tự.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.min'      => 'Mật khẩu phải có ít nhất :min ký tự.',
            'password.max'      => 'Mật khẩu không được vượt quá :max ký tự.',
        ];
    }
}

