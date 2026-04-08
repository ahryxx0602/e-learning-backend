<?php

namespace Modules\Categories\Http\Requests;

class BulkDeleteCategoriesRequest extends BaseBulkRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'required|integer|exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required'  => 'Danh sách ID không được để trống.',
            'ids.array'     => 'ids phải là mảng.',
            'ids.min'       => 'Phải chọn ít nhất 1 danh mục.',
            'ids.max'       => 'Không thể xử lý quá 100 danh mục cùng lúc.',
            'ids.*.integer' => 'ID phải là số nguyên.',
            'ids.*.exists'  => 'Một hoặc nhiều danh mục không tồn tại.',
        ];
    }
}
