<?php

namespace Modules\Categories\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCategoriesRequest extends FormRequest
{
    /**
     * Xác định user có quyền thực hiện request này không.
     * Routes đã có middleware auth:admin nên return true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules cho việc cập nhật Category.
     * Tất cả fields đều sometimes (chỉ validate khi gửi lên).
     */
    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'name'        => 'sometimes|required|string|max:255',
            'slug'        => 'sometimes|required|string|max:255|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|unique:categories,slug,' . $categoryId,
            'description' => 'nullable|string|max:1000',
            'icon'        => 'nullable|string|max:255',
            'status'      => 'nullable|integer|in:0,1',
            'order'       => 'nullable|integer|min:0',
            'parent_id'   => 'nullable|integer|exists:categories,id,deleted_at,NULL',
        ];
    }

    /**
     * Custom messages cho validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required'   => 'Tên danh mục là bắt buộc.',
            'name.max'        => 'Tên danh mục tối đa 255 ký tự.',
            'slug.required'   => 'Slug là bắt buộc.',
            'slug.unique'     => 'Slug đã tồn tại.',
            'slug.regex'      => 'Slug chỉ chứa chữ thường, số và dấu gạch ngang.',
            'description.max' => 'Mô tả tối đa 1000 ký tự.',
            'status.in'       => 'Trạng thái chỉ có thể là 0 hoặc 1.',
            'order.min'       => 'Thứ tự không được nhỏ hơn 0.',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',
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
