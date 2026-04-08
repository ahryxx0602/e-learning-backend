<?php

namespace Modules\Course\Http\Requests;

use Illuminate\Validation\Validator;

class BulkDeleteCourseRequest extends BaseBulkRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'required|integer|exists:courses,id',
        ];
    }

    /**
     * After validation: đảm bảo chỉ soft-delete những record chưa bị soft-delete.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $alreadyTrashed = \Modules\Course\Models\Course::onlyTrashed()
                ->whereIn('id', $this->ids)
                ->pluck('id')
                ->toArray();

            if (!empty($alreadyTrashed)) {
                $validator->errors()->add(
                    'ids',
                    'Các khóa học sau đã bị xoá: ' . implode(', ', $alreadyTrashed)
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'ids.required'  => 'Danh sách ID không được để trống.',
            'ids.array'     => 'ids phải là mảng.',
            'ids.min'       => 'Phải chọn ít nhất 1 khóa học.',
            'ids.max'       => 'Không thể xử lý quá 100 khóa học cùng lúc.',
            'ids.*.integer' => 'ID phải là số nguyên.',
            'ids.*.exists'  => 'Một hoặc nhiều khóa học không tồn tại.',
        ];
    }
}
