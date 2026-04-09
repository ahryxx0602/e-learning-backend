<?php

namespace Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class CreateOrderRequest extends FormRequest
{
    /**
     * Xác định user có quyền thực hiện request này không.
     */
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    /**
     * Validation rules cho việc tạo đơn hàng.
     */
    public function rules(): array
    {
        return [
            'course_ids'   => 'required|array|min:1',
            'course_ids.*' => 'integer|exists:courses,id',
            'coupon_code'  => 'nullable|string|max:50',
        ];
    }

    /**
     * Custom validation: kiểm tra student đã enroll khóa học chưa.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $studentId = auth('api')->id();
            $courseIds  = $this->input('course_ids', []);

            // Kiểm tra các khóa học đã enroll
            $enrolledCourseIds = DB::table('students_course')
                ->where('student_id', $studentId)
                ->whereIn('course_id', $courseIds)
                ->pluck('course_id')
                ->toArray();

            if (!empty($enrolledCourseIds)) {
                // Lấy tên các khóa học đã mua để thông báo rõ ràng
                $courseNames = DB::table('courses')
                    ->whereIn('id', $enrolledCourseIds)
                    ->pluck('name')
                    ->toArray();

                $validator->errors()->add(
                    'course_ids',
                    'Bạn đã sở hữu các khóa học: ' . implode(', ', $courseNames) . '. Vui lòng bỏ chúng khỏi giỏ hàng.'
                );
            }

            // Kiểm tra khóa học có published không
            $publishedCount = DB::table('courses')
                ->whereIn('id', $courseIds)
                ->where('status', 1)
                ->count();

            if ($publishedCount !== count($courseIds)) {
                $validator->errors()->add(
                    'course_ids',
                    'Một hoặc nhiều khóa học không khả dụng.'
                );
            }
        });
    }

    /**
     * Custom messages cho validation errors.
     */
    public function messages(): array
    {
        return [
            'course_ids.required'   => 'Vui lòng chọn ít nhất một khóa học.',
            'course_ids.array'      => 'Danh sách khóa học phải là mảng.',
            'course_ids.min'        => 'Vui lòng chọn ít nhất một khóa học.',
            'course_ids.*.integer'  => 'ID khóa học phải là số nguyên.',
            'course_ids.*.exists'   => 'Một hoặc nhiều khóa học không tồn tại.',
            'coupon_code.max'       => 'Mã giảm giá tối đa 50 ký tự.',
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
