<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUsersRequest extends FormRequest
{
    /**
     * Xác định user có quyền thực hiện request này không.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules cho việc cập nhật Users.
     * TODO: Thêm rules cần thiết.
     */
    public function rules(): array
    {
        $id = $this->route('user');

        return [
            'name'     => 'sometimes|string|max:255',
            'email'    => "sometimes|email|max:255|unique:users,email,{$id}",
            'password' => 'sometimes|string|min:8|max:100',
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
            'email.email'   => 'Email không đúng định dạng.',
            'email.unique'  => 'Email đã được sử dụng.',
            'password.min'  => 'Mật khẩu tối thiểu 8 ký tự.',
            'role.exists'   => 'Role không hợp lệ.',
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
