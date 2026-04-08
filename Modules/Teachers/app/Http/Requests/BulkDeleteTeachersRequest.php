<?php

namespace Modules\Teachers\Http\Requests;

use Illuminate\Validation\Validator;

class BulkDeleteTeachersRequest extends BaseBulkRequest
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
     * After validation: đảm bảo chỉ soft-delete những record chưa bị soft-delete.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $alreadyTrashed = \Modules\Teachers\Models\Teachers::onlyTrashed()
                ->whereIn('id', $this->ids)
                ->pluck('id')
                ->toArray();

            if (!empty($alreadyTrashed)) {
                $validator->errors()->add(
                    'ids',
                    'Các giảng viên sau đã bị xoá: ' . implode(', ', $alreadyTrashed)
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
