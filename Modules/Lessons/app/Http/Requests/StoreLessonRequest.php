<?php

namespace Modules\Lessons\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreLessonRequest extends FormRequest
{
    /**
     * Xác định user có quyền thực hiện request này không.
     */
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * Validation rules cho việc tạo mới Lesson.
     */
    public function rules(): array
    {
        return [
            'section_id'  => 'nullable|integer|exists:sections,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'required|in:video,document,text',
            'content'     => 'required_if:type,text|nullable|string',
            'video_id'    => 'required_if:type,video|nullable|integer|exists:media_files,id',
            'document_id' => 'required_if:type,document|nullable|integer|exists:media_files,id',
            'duration'    => 'nullable|integer|min:0',
            'order'       => 'nullable|integer|min:0',
            'is_preview'  => 'nullable|boolean',
            'status'      => 'nullable|integer|in:0,1',
        ];
    }

    /**
     * Custom messages cho validation errors.
     */
    public function messages(): array
    {
        return [
            'title.required'        => 'Tiêu đề bài giảng là bắt buộc.',
            'title.string'          => 'Tiêu đề bài giảng phải là chuỗi ký tự.',
            'title.max'             => 'Tiêu đề bài giảng tối đa 255 ký tự.',
            'description.string'    => 'Mô tả phải là chuỗi ký tự.',
            'type.required'         => 'Loại bài giảng là bắt buộc.',
            'type.in'               => 'Loại bài giảng phải là: video, document hoặc text.',
            'content.required_if'   => 'Nội dung là bắt buộc khi loại bài giảng là text.',
            'content.string'        => 'Nội dung phải là chuỗi ký tự.',
            'video_id.required_if'  => 'Video là bắt buộc khi loại bài giảng là video.',
            'video_id.integer'      => 'ID video phải là số nguyên.',
            'video_id.exists'       => 'Video không tồn tại trong hệ thống.',
            'document_id.required_if' => 'Tài liệu là bắt buộc khi loại bài giảng là document.',
            'document_id.integer'   => 'ID tài liệu phải là số nguyên.',
            'document_id.exists'    => 'Tài liệu không tồn tại trong hệ thống.',
            'order.integer'         => 'Thứ tự phải là số nguyên.',
            'order.min'             => 'Thứ tự không được nhỏ hơn 0.',
            'is_preview.boolean'    => 'Trạng thái xem trước phải là true hoặc false.',
            'status.in'             => 'Trạng thái chỉ có thể là 0 hoặc 1.',
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
