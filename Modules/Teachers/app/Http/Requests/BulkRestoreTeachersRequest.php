<?php

namespace Modules\Teachers\Http\Requests;

use Illuminate\Validation\Validator;

class BulkRestoreTeachersRequest extends BaseBulkRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'required|integer|exists:teachers,id',
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

            $trashedIds = \Modules\Teachers\Models\Teachers::onlyTrashed()
                ->whereIn('id', $this->ids)
                ->pluck('id')
                ->toArray();

            $notTrashed = array_diff($this->ids, $trashedIds);

            if (!empty($notTrashed)) {
                $validator->errors()->add(
                    'ids',
                    'Các giảng viên sau chưa bị xoá hoặc không tồn tại: ' . implode(', ', $notTrashed)
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
            'ids.*.exists'  => 'Một hoặc nhiều giảng viên không tồn tại.',
        ];
    }
}
