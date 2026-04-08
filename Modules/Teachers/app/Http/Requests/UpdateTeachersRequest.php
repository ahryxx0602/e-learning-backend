<?php

namespace Modules\Teachers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTeachersRequest extends FormRequest
{
    /**
     * Xác định user có quyền thực hiện request này không.
     * Kiểm tra đã đăng nhập qua guard admin.
     */
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * Validation rules cho việc cập nhật Teacher.
     * Tất cả fields đều sometimes (chỉ validate khi gửi lên).
     */
    public function rules(): array
    {
        $teacherId = $this->route('teacher');

        return [
            'name'          => 'sometimes|required|string|max:100',
            'slug'          => 'sometimes|required|string|max:100|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|unique:teachers,slug,' . $teacherId,
            'description'   => 'nullable|string|max:5000',
            'exp'           => 'nullable|numeric|min:0|max:100',
            'image'         => 'nullable|url|max:2048',
            'date_of_birth' => 'nullable|date|before:today|after:1900-01-01',
            'status'        => 'nullable|integer|in:0,1',
        ];
    }

    /**
     * Custom messages cho validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required'          => 'Tên giảng viên là bắt buộc.',
            'name.max'               => 'Tên giảng viên tối đa 100 ký tự.',
            'slug.required'          => 'Slug là bắt buộc.',
            'slug.unique'            => 'Slug đã tồn tại.',
            'slug.regex'             => 'Slug chỉ chứa chữ thường, số và dấu gạch ngang.',
            'description.max'        => 'Mô tả tối đa 5000 ký tự.',
            'exp.numeric'            => 'Số năm kinh nghiệm phải là số.',
            'exp.min'                => 'Số năm kinh nghiệm không được nhỏ hơn 0.',
            'exp.max'                => 'Số năm kinh nghiệm tối đa 100.',
            'image.url'              => 'Ảnh phải là URL hợp lệ.',
            'image.max'              => 'URL ảnh tối đa 2048 ký tự.',
            'date_of_birth.date'     => 'Ngày sinh không hợp lệ.',
            'date_of_birth.before'   => 'Ngày sinh phải trước hôm nay.',
            'date_of_birth.after'    => 'Ngày sinh phải sau 01/01/1900.',
            'status.in'              => 'Trạng thái chỉ có thể là 0 hoặc 1.',
        ];
    }

    /**
     * Override: trả về JSON thay vì redirect khi validation fail (API-only).
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
