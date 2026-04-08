<?php

namespace Modules\Users\Http\Requests;

class BulkAssignRoleRequest extends BaseBulkRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'required|integer|exists:users,id',
            'role'  => 'required|string|exists:roles,name',
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required'  => 'Danh sách ID không được để trống.',
            'ids.array'     => 'ids phải là mảng.',
            'ids.min'       => 'Phải chọn ít nhất 1 user.',
            'ids.max'       => 'Không thể xử lý quá 100 user cùng lúc.',
            'ids.*.integer' => 'ID phải là số nguyên.',
            'ids.*.exists'  => 'Một hoặc nhiều user không tồn tại.',
            'role.required' => 'role không được để trống.',
            'role.exists'   => 'Role không hợp lệ.',
        ];
    }
}
