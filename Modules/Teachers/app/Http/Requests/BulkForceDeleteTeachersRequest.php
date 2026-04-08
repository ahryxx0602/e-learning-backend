<?php

namespace Modules\Teachers\Http\Requests;

use Illuminate\Validation\Validator;

class BulkForceDeleteTeachersRequest extends BaseBulkRequest
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

            $existingIds = \Modules\Teachers\Models\Teachers::withTrashed()
                ->whereIn('id', $this->ids)
                ->pluck('id')
                ->toArray();

            $notFound = array_diff($this->ids, $existingIds);

            if (!empty($notFound)) {
                $validator->errors()->add(
                    'ids',
                    'Các giảng viên sau không tồn tại: ' . implode(', ', $notFound)
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'ids.required'  => 'Danh sách ID không được để trống.',
            'ids.array'     => 'ids phải là mảng.',
            'ids.min'       => 'Phải chọn ít nhất 1 giảng viên.',
            'ids.max'       => 'Không thể xử lý quá 100 giảng viên cùng lúc.',
            'ids.*.integer' => 'ID phải là số nguyên.',
        ];
    }
}
