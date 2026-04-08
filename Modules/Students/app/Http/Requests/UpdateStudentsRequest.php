<?php

namespace Modules\Students\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateStudentsRequest extends FormRequest
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
        $id = $this->route('student');

        return [
            'name'                  => 'sometimes|string|max:255',
            'email'                 => "sometimes|email|max:255|unique:students,email,{$id}",
            'password'              => 'sometimes|string|min:8|confirmed',
            'password_confirmation' => 'required_with:password|string',
            'avatar'                => 'nullable|url|max:2048',
            'date_of_birth'         => 'nullable|date|before:today',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'                       => 'Email đã tồn tại.',
            'password.min'                       => 'Mật khẩu tối thiểu 8 ký tự.',
            'password.confirmed'                 => 'Xác nhận mật khẩu không khớp.',
            'password_confirmation.required_with' => 'Xác nhận mật khẩu là bắt buộc khi đổi mật khẩu.',
            'date_of_birth.before'               => 'Ngày sinh phải trước hôm nay.',
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
