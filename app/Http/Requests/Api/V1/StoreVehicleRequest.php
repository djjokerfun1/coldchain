<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    /**
     * Real authorization happens via VehicleController::authorizeResource(),
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
            'registration' => ['required', 'string', 'max:20', 'unique:vehicles,registration'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
        ];
    }
}
