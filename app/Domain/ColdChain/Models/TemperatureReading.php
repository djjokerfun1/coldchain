<?php

declare(strict_types=1);

namespace App\Domain\ColdChain\Models;

use App\Domain\ColdChain\Casts\AsCelsius;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only, like TrackingEvent: a reading is never corrected in place,
 * only ever superseded by a later one.
 */
class TemperatureReading extends Model
{
    /** @use HasFactory<\Database\Factories\Domain\ColdChain\TemperatureReadingFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = ['shipment_id', 'celsius', 'recorded_at'];

    protected function casts(): array
    {
        return [
            'celsius' => AsCelsius::class,
            'recorded_at' => 'datetime',
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
