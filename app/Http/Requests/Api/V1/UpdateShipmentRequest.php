<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Only dispatch assignment (driver, vehicle) goes through this endpoint.
 * Status is not here on purpose: it is a projection over tracking events,
 * guarded by Shipment::transitionTo(), not a field anyone sets directly.
 */
class UpdateShipmentRequest extends FormRequest
{
    /**
     * Real authorization happens via ShipmentController::authorizeResource(),
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
            'driver_id' => ['sometimes', 'nullable', 'integer', 'exists:drivers,id'],
            'vehicle_id' => ['sometimes', 'nullable', 'integer', 'exists:vehicles,id'],
        ];
    }
}
