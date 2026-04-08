<?php

namespace Modules\Students\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreStudentsRequest extends FormRequest
{
    /**
     * Xác định user có quyền thực hiện request này không.
     */
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|max:255|unique:students,email',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
            'avatar'                => 'nullable|url|max:2048',
            'date_of_birth'         => 'nullable|date|before:today',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                  => 'Tên là bắt buộc.',
            'email.required'                 => 'Email là bắt buộc.',
            'email.unique'                   => 'Email đã tồn tại.',
            'password.required'              => 'Mật khẩu là bắt buộc.',
            'password.min'                   => 'Mật khẩu tối thiểu 8 ký tự.',
            'password.confirmed'             => 'Xác nhận mật khẩu không khớp.',
            'password_confirmation.required' => 'Xác nhận mật khẩu là bắt buộc.',
            'date_of_birth.before'           => 'Ngày sinh phải trước hôm nay.',
        ];
    }

    /**
     * Override: trả về JSON thay vì redirect khi validation fail (API-only).
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
