<?php

namespace Modules\Students\Http\Requests;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseBulkRequest extends FormRequest
{
    use ApiResponse;

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->error('Dữ liệu không hợp lệ.', 422, $validator->errors()->toArray())
        );
    }
}
