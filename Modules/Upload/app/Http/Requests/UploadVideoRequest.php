<?php

namespace Modules\Upload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimetypes:video/mp4,video/webm,video/quicktime|max:512000',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required'  => 'Vui lòng chọn file video.',
            'file.file'      => 'Dữ liệu phải là file.',
            'file.mimetypes' => 'Chỉ chấp nhận định dạng: MP4, WebM, QuickTime.',
            'file.max'       => 'Dung lượng video tối đa 500MB.',
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
