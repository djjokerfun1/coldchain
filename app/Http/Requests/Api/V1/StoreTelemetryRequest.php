<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Domain\Shipping\Enums\TrackingEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTelemetryRequest extends FormRequest
{
    /**
     * Real authorization happens via ShipmentController::store()'s explicit
     * $this->authorize('recordTelemetry', $shipment) call — the shipment
     * being authorized against is a route parameter, not something this
     * request can resolve on its own.
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
            'external_event_id' => ['required', 'string', 'max:255'],
            'type' => ['sometimes', new Enum(TrackingEventType::class)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'recorded_at' => ['sometimes', 'date'],
            'temperature_celsius' => ['sometimes', 'nullable', 'numeric'],
        ];
    }
}
