<?php

namespace Modules\Upload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PresignedUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'type'      => 'required|in:video,document,image',
            'filename'  => 'required|string|max:255',
            'mime_type' => 'required|string|max:100',
            'size'      => 'required|integer|min:1|max:5368709120',
            'duration'  => 'nullable|integer|min:1',
            'width'     => 'nullable|integer|min:1',
            'height'    => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'      => 'Loại file là bắt buộc.',
            'type.in'            => 'Loại file phải là: video, document hoặc image.',
            'filename.required'  => 'Tên file là bắt buộc.',
            'mime_type.required' => 'MIME type là bắt buộc.',
            'size.required'      => 'Dung lượng file là bắt buộc.',
            'size.min'           => 'Dung lượng file phải lớn hơn 0.',
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
