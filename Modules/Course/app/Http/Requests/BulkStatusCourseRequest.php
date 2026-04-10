<?php

namespace Modules\Course\Http\Requests;

class BulkStatusCourseRequest extends BaseBulkRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:courses,id',
            'status' => 'required|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required'    => 'Vui lòng chọn ít nhất một khóa học.',
            'ids.array'       => 'Danh sách ID không hợp lệ.',
            'ids.*.exists'    => 'Một số khóa học không tồn tại.',
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.in'       => 'Trạng thái không hợp lệ.',
        ];
    }
}
