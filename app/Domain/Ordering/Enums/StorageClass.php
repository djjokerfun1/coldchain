<?php

declare(strict_types=1);

namespace App\Domain\Ordering\Enums;

use App\Domain\ColdChain\ValueObjects\Celsius;
use App\Domain\ColdChain\ValueObjects\TemperatureRange;

enum StorageClass: string
{
    case Ambient = 'ambient';
    case Refrigerated = 'refrigerated';
    case Frozen = 'frozen';

    /**
     * The GDP-acceptable range for products stored at this class. Ambient
     * has no cold-chain requirement, so it carries no meaningful range.
     */
    public function temperatureRange(): ?TemperatureRange
    {
        return match ($this) {
            self::Ambient => null,
            self::Refrigerated => new TemperatureRange(new Celsius(2.0), new Celsius(8.0)),
            self::Frozen => new TemperatureRange(new Celsius(-25.0), new Celsius(-15.0)),
        };
    }
}
