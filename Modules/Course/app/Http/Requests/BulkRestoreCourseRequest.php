<?php

namespace Modules\Course\Http\Requests;

use Illuminate\Validation\Validator;

class BulkRestoreCourseRequest extends BaseBulkRequest
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
     * After validation: đảm bảo chỉ restore những record đã bị soft-delete.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $trashedIds = \Modules\Course\Models\Course::onlyTrashed()
                ->whereIn('id', $this->ids)
                ->pluck('id')
                ->toArray();

            $notTrashed = array_diff($this->ids, $trashedIds);

            if (!empty($notTrashed)) {
                $validator->errors()->add(
                    'ids',
                    'Các khóa học sau chưa bị xoá hoặc không tồn tại: ' . implode(', ', $notTrashed)
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
