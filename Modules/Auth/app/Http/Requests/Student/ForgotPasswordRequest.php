<?php

namespace Modules\Auth\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'exists:students,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email không được để trống.',
            'email.email'    => 'Email không đúng định dạng.',
            'email.exists'   => 'Email không tồn tại trong hệ thống.',
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