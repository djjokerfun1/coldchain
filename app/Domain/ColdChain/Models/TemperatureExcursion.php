<?php

declare(strict_types=1);

namespace App\Domain\ColdChain\Models;

use App\Domain\ColdChain\Casts\AsCelsius;
use App\Domain\ColdChain\Enums\ExcursionStatus;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Unlike TrackingEvent and TemperatureReading, an excursion is mutable by
 * design: it is opened as a candidate and updated in place as it is
 * confirmed and later resolved, so it keeps normal updated_at tracking.
 */
class TemperatureExcursion extends Model
{
    /** @use HasFactory<\Database\Factories\Domain\ColdChain\TemperatureExcursionFactory> */
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'status',
        'min_celsius',
        'max_celsius',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExcursionStatus::class,
            'min_celsius' => AsCelsius::class,
            'max_celsius' => AsCelsius::class,
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
