<?php

declare(strict_types=1);

namespace App\Domain\ColdChain\Casts;

use App\Domain\ColdChain\ValueObjects\Celsius;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<Celsius, mixed>
 */
class AsCelsius implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Celsius
    {
        return $value === null ? null : new Celsius((float) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?float
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Celsius) {
            throw new InvalidArgumentException(sprintf('Expected %s to be set with a %s instance.', $key, Celsius::class));
        }

        return $value->value;
    }
}
