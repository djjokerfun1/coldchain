<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
            'pickup_address' => ['sometimes', 'array'],
            'pickup_address.line1' => ['required_with:pickup_address', 'string', 'max:255'],
            'pickup_address.line2' => ['nullable', 'string', 'max:255'],
            'pickup_address.city' => ['required_with:pickup_address', 'string', 'max:255'],
            'pickup_address.postal_code' => ['required_with:pickup_address', 'string', 'max:20'],
            'pickup_address.country' => ['required_with:pickup_address', 'string', 'size:2'],

            'delivery_address' => ['sometimes', 'array'],
            'delivery_address.line1' => ['required_with:delivery_address', 'string', 'max:255'],
            'delivery_address.line2' => ['nullable', 'string', 'max:255'],
            'delivery_address.city' => ['required_with:delivery_address', 'string', 'max:255'],
            'delivery_address.postal_code' => ['required_with:delivery_address', 'string', 'max:20'],
            'delivery_address.country' => ['required_with:delivery_address', 'string', 'size:2'],
        ];
    }
}
