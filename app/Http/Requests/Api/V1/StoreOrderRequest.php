<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Real authorization happens via OrderController::authorizeResource(),
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
            'client_id' => ['required', 'integer', 'exists:clients,id'],

            'pickup_address' => ['required', 'array'],
            'pickup_address.line1' => ['required', 'string', 'max:255'],
            'pickup_address.line2' => ['nullable', 'string', 'max:255'],
            'pickup_address.city' => ['required', 'string', 'max:255'],
            'pickup_address.postal_code' => ['required', 'string', 'max:20'],
            'pickup_address.country' => ['required', 'string', 'size:2'],

            'delivery_address' => ['required', 'array'],
            'delivery_address.line1' => ['required', 'string', 'max:255'],
            'delivery_address.line2' => ['nullable', 'string', 'max:255'],
            'delivery_address.city' => ['required', 'string', 'max:255'],
            'delivery_address.postal_code' => ['required', 'string', 'max:20'],
            'delivery_address.country' => ['required', 'string', 'size:2'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
