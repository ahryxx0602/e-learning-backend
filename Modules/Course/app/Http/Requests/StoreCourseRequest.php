<?php

namespace Modules\Course\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCourseRequest extends FormRequest
{
    /**
     * Xác định user có quyền thực hiện request này không.
     */
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * Validation rules cho việc tạo mới Course.
     */
    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'slug'           => 'required|string|max:255|unique:courses,slug|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'teacher_id'     => 'required|integer|exists:teachers,id',
            'category_id'    => 'nullable|integer|exists:categories,id',
            'category_ids'   => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'description'    => 'nullable|string|max:10000',
            'thumbnail'      => 'nullable|string|max:2048',
            'price'          => 'required|numeric|min:0',
            'sale_price'     => 'nullable|numeric|min:0|lte:price',
            'level'          => 'required|in:beginner,intermediate,advanced',
            'status'         => 'nullable|in:0,1',
        ];
    }

    /**
     * Custom messages cho validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required'           => 'Tên khóa học là bắt buộc.',
            'name.max'                => 'Tên khóa học tối đa 255 ký tự.',
            'slug.required'           => 'Slug là bắt buộc.',
            'slug.unique'             => 'Slug đã tồn tại.',
            'slug.regex'              => 'Slug chỉ chứa chữ thường, số và dấu gạch ngang.',
            'teacher_id.required'     => 'Giảng viên là bắt buộc.',
            'teacher_id.exists'       => 'Giảng viên không tồn tại.',
            'category_id.exists'      => 'Danh mục không tồn tại.',
            'category_ids.array'      => 'Danh sách danh mục phải là mảng.',
            'category_ids.*.exists'   => 'Một hoặc nhiều danh mục không tồn tại.',
            'description.max'         => 'Mô tả tối đa 10000 ký tự.',
            'thumbnail.url'           => 'Thumbnail phải là URL hợp lệ.',
            'price.required'          => 'Giá là bắt buộc.',
            'price.numeric'           => 'Giá phải là số.',
            'price.min'               => 'Giá không được nhỏ hơn 0.',
            'sale_price.numeric'      => 'Giá khuyến mãi phải là số.',
            'sale_price.min'          => 'Giá khuyến mãi không được nhỏ hơn 0.',
            'sale_price.lte'          => 'Giá khuyến mãi phải nhỏ hơn hoặc bằng giá gốc.',
            'level.required'          => 'Trình độ là bắt buộc.',
            'level.in'                => 'Trình độ phải là: beginner, intermediate, hoặc advanced.',
            'status.in'               => 'Trạng thái chỉ có thể là 0 hoặc 1.',
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
