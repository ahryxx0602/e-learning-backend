<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUsersRequest extends FormRequest
{
    /**
     * Xác định user có quyền thực hiện request này không.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules cho việc tạo mới Users.
     * TODO: Thêm rules cần thiết.
     */
    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:100',
            'avatar'   => 'nullable|string|max:255',
            'role'     => 'nullable|string|exists:roles,name',
        ];
    }

    /**
     * Custom messages cho validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required'     => 'Tên không được để trống.',
            'email.required'    => 'Email không được để trống.',
            'email.email'       => 'Email không đúng định dạng.',
            'email.unique'      => 'Email đã được sử dụng.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.min'      => 'Mật khẩu tối thiểu 8 ký tự.',
            'role.exists'       => 'Role không hợp lệ.',
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
