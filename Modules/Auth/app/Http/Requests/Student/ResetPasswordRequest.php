<?php

namespace Modules\Auth\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'                 => ['required', 'string'],
            'email'                 => ['required', 'email', 'max:255', 'exists:students,email'],
            'password'              => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'                 => 'Token không được để trống.',
            'email.required'                 => 'Email không được để trống.',
            'email.email'                    => 'Email không đúng định dạng.',
            'email.exists'                   => 'Email không tồn tại trong hệ thống.',
            'password.required'              => 'Mật khẩu không được để trống.',
            'password.min'                   => 'Mật khẩu tối thiểu 8 ký tự.',
            'password.confirmed'             => 'Xác nhận mật khẩu không khớp.',
            'password_confirmation.required' => 'Xác nhận mật khẩu không được để trống.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}