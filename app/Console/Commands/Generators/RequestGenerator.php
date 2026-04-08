<?php

namespace App\Console\Commands\Generators;

class RequestGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $messages = [];

        // Store Request
        $storePath = "{$this->modulePath}/app/Http/Requests/Store{$this->name}Request.php";
        $this->putFile($storePath, $this->getStoreStub());
        $messages[] = "  ✔ StoreRequest: Http/Requests/Store{$this->name}Request.php";

        // Update Request
        $updatePath = "{$this->modulePath}/app/Http/Requests/Update{$this->name}Request.php";
        $this->putFile($updatePath, $this->getUpdateStub());
        $messages[] = "  ✔ UpdateRequest: Http/Requests/Update{$this->name}Request.php";

        return $messages;
    }

    protected function getStoreStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->name}\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;
use Illuminate\\Contracts\\Validation\\Validator;
use Illuminate\\Http\\Exceptions\\HttpResponseException;

class Store{$this->name}Request extends FormRequest
{
    /**
     * Xác định user có quyền thực hiện request này không.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules cho việc tạo mới {$this->name}.
     * TODO: Thêm rules cần thiết.
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    /**
     * Custom messages cho validation errors.
     */
    public function messages(): array
    {
        return [
            //
        ];
    }

    /**
     * Override: trả về JSON thay vì redirect khi validation fail (API-only).
     */
    protected function failedValidation(Validator \$validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => \$validator->errors(),
        ], 422));
    }
}

PHP;
    }

    protected function getUpdateStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->name}\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;
use Illuminate\\Contracts\\Validation\\Validator;
use Illuminate\\Http\\Exceptions\\HttpResponseException;

class Update{$this->name}Request extends FormRequest
{
    /**
     * Xác định user có quyền thực hiện request này không.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules cho việc cập nhật {$this->name}.
     * TODO: Thêm rules cần thiết.
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    /**
     * Custom messages cho validation errors.
     */
    public function messages(): array
    {
        return [
            //
        ];
    }

    /**
     * Override: trả về JSON thay vì redirect khi validation fail (API-only).
     */
    protected function failedValidation(Validator \$validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => \$validator->errors(),
        ], 422));
    }
}

PHP;
    }
}
