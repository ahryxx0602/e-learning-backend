<?php

namespace Modules\Upload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:pdf,doc,docx,txt|max:20480',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn file tài liệu.',
            'file.file'     => 'Dữ liệu phải là file.',
            'file.mimes'    => 'Chỉ chấp nhận định dạng: PDF, DOC, DOCX, TXT.',
            'file.max'      => 'Dung lượng tài liệu tối đa 20MB.',
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
