<?php

namespace Modules\Lessons\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'order'       => 'nullable|integer|min:0',
            'status'      => 'nullable|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề chương là bắt buộc.',
            'title.max'      => 'Tiêu đề chương tối đa 255 ký tự.',
            'order.integer'  => 'Thứ tự phải là số nguyên.',
            'order.min'      => 'Thứ tự không được nhỏ hơn 0.',
            'status.in'      => 'Trạng thái chỉ có thể là 0 hoặc 1.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
