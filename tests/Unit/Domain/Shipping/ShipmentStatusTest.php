<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shipping;

use App\Domain\Shipping\Enums\ShipmentStatus;
use PHPUnit\Framework\TestCase;

class ShipmentStatusTest extends TestCase
{
    public function test_the_happy_path_is_allowed(): void
    {
        $this->assertTrue(ShipmentStatus::Pending->canTransitionTo(ShipmentStatus::PickedUp));
        $this->assertTrue(ShipmentStatus::PickedUp->canTransitionTo(ShipmentStatus::InTransit));
        $this->assertTrue(ShipmentStatus::InTransit->canTransitionTo(ShipmentStatus::Delivered));
    }

    public function test_delivered_is_terminal(): void
    {
        foreach (ShipmentStatus::cases() as $status) {
            $this->assertFalse(ShipmentStatus::Delivered->canTransitionTo($status));
        }
    }

    public function test_backwards_jumps_are_rejected(): void
    {
        $this->assertFalse(ShipmentStatus::Delivered->canTransitionTo(ShipmentStatus::PickedUp));
        $this->assertFalse(ShipmentStatus::InTransit->canTransitionTo(ShipmentStatus::Pending));
    }

    public function test_an_exception_can_recover_into_transit_or_resolve_as_delivered(): void
    {
        $this->assertTrue(ShipmentStatus::Exception->canTransitionTo(ShipmentStatus::InTransit));
        $this->assertTrue(ShipmentStatus::Exception->canTransitionTo(ShipmentStatus::Delivered));
        $this->assertFalse(ShipmentStatus::Exception->canTransitionTo(ShipmentStatus::Pending));
    }

    public function test_any_active_status_can_fall_into_exception(): void
    {
        $this->assertTrue(ShipmentStatus::Pending->canTransitionTo(ShipmentStatus::Exception));
        $this->assertTrue(ShipmentStatus::PickedUp->canTransitionTo(ShipmentStatus::Exception));
        $this->assertTrue(ShipmentStatus::InTransit->canTransitionTo(ShipmentStatus::Exception));
    }
}
