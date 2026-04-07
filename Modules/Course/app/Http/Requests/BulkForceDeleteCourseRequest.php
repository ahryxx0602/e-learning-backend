<?php

namespace Modules\Course\Http\Requests;

use Illuminate\Validation\Validator;

class BulkForceDeleteCourseRequest extends BaseBulkRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'required|integer',
        ];
    }

    /**
     * After validation: kiểm tra tất cả IDs tồn tại (bao gồm cả đã soft-delete).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $existingIds = \Modules\Course\Models\Course::withTrashed()
                ->whereIn('id', $this->ids)
                ->pluck('id')
                ->toArray();

            $notFound = array_diff($this->ids, $existingIds);

            if (!empty($notFound)) {
                $validator->errors()->add(
                    'ids',
                    'Các khóa học sau không tồn tại: ' . implode(', ', $notFound)
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
        ];
    }
}
