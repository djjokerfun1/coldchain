<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * No rules() here on purpose: the request shape is entirely up to the
 * partner and validated by their ClientOrderAdapter, not a fixed contract
 * this class could describe. Authorization is the HMAC signature, checked
 * by VerifyClientOrderSignature before this request is even resolved.
 */
class StoreClientOrderWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
