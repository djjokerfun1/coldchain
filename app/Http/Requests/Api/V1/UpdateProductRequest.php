<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Domain\Ordering\Enums\StorageClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
{
    /**
     * Real authorization happens via ProductController::authorizeResource(),
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
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($this->route('product')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'storage_class' => ['sometimes', 'required', new Enum(StorageClass::class)],
        ];
    }
}
