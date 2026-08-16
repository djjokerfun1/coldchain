<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:50', 'unique:drivers,license_number'],
        ];
    }
}
