<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Models;

use App\Domain\ColdChain\Models\TemperatureReading;
use App\Domain\Ordering\Models\Order;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Exceptions\InvalidStatusTransition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    /** @use HasFactory<\Database\Factories\Domain\Shipping\ShipmentFactory> */
    use HasFactory;

    protected $fillable = ['order_id', 'driver_id', 'vehicle_id', 'reference', 'status'];

    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
        ];
    }

    /**
     * @throws InvalidStatusTransition
     */
    public function transitionTo(ShipmentStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new InvalidStatusTransition($this->status, $next);
        }

        $this->update(['status' => $next]);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return HasMany<TrackingEvent, $this>
     */
    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class);
    }

    /**
     * @return HasMany<TemperatureReading, $this>
     */
    public function temperatureReadings(): HasMany
    {
        return $this->hasMany(TemperatureReading::class);
    }
}
