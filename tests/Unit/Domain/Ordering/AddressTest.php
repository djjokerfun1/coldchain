<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ordering;

use App\Domain\Ordering\ValueObjects\Address;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    public function test_it_round_trips_through_an_array(): void
    {
        $address = new Address(
            line1: '1 Cold Storage Way',
            line2: 'Unit 4',
            city: 'Rotterdam',
            postalCode: '3011AA',
            country: 'NL',
        );

        $rebuilt = Address::fromArray($address->toArray());

        $this->assertEquals($address, $rebuilt);
    }

    public function test_string_representation_omits_a_missing_second_line(): void
    {
        $address = new Address('1 Cold Storage Way', null, 'Rotterdam', '3011AA', 'NL');

        $this->assertSame('1 Cold Storage Way, Rotterdam 3011AA, NL', (string) $address);
    }
}
