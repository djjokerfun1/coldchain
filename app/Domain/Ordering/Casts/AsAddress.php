<?php

declare(strict_types=1);

namespace App\Domain\Ordering\Casts;

use App\Domain\Ordering\ValueObjects\Address;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * TSet is left as mixed: values reaching set() come from mass assignment or
 * external mappers, not just typed callers, so the runtime check below is
 * a real guard rather than a redundant one.
 *
 * @implements CastsAttributes<Address, mixed>
 */
class AsAddress implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Address
    {
        if ($value === null) {
            return null;
        }

        /** @var array{line1: string, line2: string|null, city: string, postal_code: string, country: string} $decoded */
        $decoded = is_string($value) ? json_decode($value, true, flags: JSON_THROW_ON_ERROR) : $value;

        return Address::fromArray($decoded);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Address) {
            throw new InvalidArgumentException(sprintf('Expected %s to be set with an %s instance.', $key, Address::class));
        }

        return json_encode($value->toArray(), JSON_THROW_ON_ERROR);
    }
}
