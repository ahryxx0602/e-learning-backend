<?php

namespace Modules\Users\Http\Requests;

class BulkActionUsersRequest extends BaseBulkRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'    => 'required|array|min:1|max:100',
            'ids.*'  => 'required|integer|exists:users,id',
            'action' => 'required|string|in:activate,deactivate',
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required'     => 'Danh sách ID không được để trống.',
            'ids.array'        => 'ids phải là mảng.',
            'ids.min'          => 'Phải chọn ít nhất 1 user.',
            'ids.max'          => 'Không thể xử lý quá 100 user cùng lúc.',
            'ids.*.integer'    => 'ID phải là số nguyên.',
            'ids.*.exists'     => 'Một hoặc nhiều user không tồn tại.',
            'action.required'  => 'action không được để trống.',
            'action.in'        => 'action phải là: activate hoặc deactivate.',
        ];
    }
}
