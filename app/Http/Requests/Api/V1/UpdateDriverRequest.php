<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDriverRequest extends FormRequest
{
    /**
     * Real authorization happens via DriverController::authorizeResource(),
     * which runs before this request is even resolved.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'license_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('drivers', 'license_number')->ignore($this->route('driver')),
            ],
        ];
    }
}
