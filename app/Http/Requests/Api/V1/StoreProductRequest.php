<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Domain\Ordering\Enums\StorageClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProductRequest extends FormRequest
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
        return [
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'storage_class' => ['required', new Enum(StorageClass::class)],
        ];
    }
}
