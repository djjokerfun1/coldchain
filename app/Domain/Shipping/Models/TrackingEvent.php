<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Models;

use App\Domain\Shipping\Enums\TrackingEventType;
use App\Domain\Shipping\ValueObjects\GeoPoint;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracking events are an append-only log: no update route will ever exist
 * for one, and the shipment's status is a projection over this history
 * rather than a field mutated directly.
 */
class TrackingEvent extends Model
{
    /** @use HasFactory<\Database\Factories\Domain\Shipping\TrackingEventFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'shipment_id',
        'type',
        'external_event_id',
        'latitude',
        'longitude',
        'payload',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => TrackingEventType::class,
            'payload' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<GeoPoint|null, GeoPoint|null>
     */
    protected function position(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?GeoPoint => isset($attributes['latitude'], $attributes['longitude'])
                ? new GeoPoint((float) $attributes['latitude'], (float) $attributes['longitude'])
                : null,
            set: fn (?GeoPoint $point): array => [
                'latitude' => $point?->latitude,
                'longitude' => $point?->longitude,
            ],
        );
    }

    /**
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
