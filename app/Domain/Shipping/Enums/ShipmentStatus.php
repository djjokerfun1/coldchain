<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Exception = 'exception';

    /**
     * The status is a projection over tracking events, not a field anyone
     * sets directly, so invalid jumps (delivered -> picked_up) are rejected
     * here rather than trusted to whoever calls save().
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::PickedUp, self::Exception],
            self::PickedUp => [self::InTransit, self::Exception],
            self::InTransit => [self::Delivered, self::Exception],
            // Exception is recoverable: the shipment can resume transit or,
            // if the exception is resolved on delivery, go straight to Delivered.
            self::Exception => [self::InTransit, self::Delivered],
            self::Delivered => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), strict: true);
    }
}
