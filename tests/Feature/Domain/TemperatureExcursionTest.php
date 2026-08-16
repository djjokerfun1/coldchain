<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Domain\ColdChain\Enums\ExcursionStatus;
use App\Domain\ColdChain\Models\TemperatureExcursion;
use App\Domain\ColdChain\ValueObjects\Celsius;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemperatureExcursionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_opens_as_a_candidate_without_a_closed_at(): void
    {
        $excursion = TemperatureExcursion::factory()->create();

        $this->assertSame(ExcursionStatus::Candidate, $excursion->status);
        $this->assertNull($excursion->closed_at);
    }

    public function test_resolving_sets_the_status_and_closed_at(): void
    {
        $excursion = TemperatureExcursion::factory()->resolved()->create();

        $this->assertSame(ExcursionStatus::Resolved, $excursion->status);
        $this->assertNotNull($excursion->closed_at);
    }

    public function test_min_and_max_cast_to_celsius(): void
    {
        $excursion = TemperatureExcursion::factory()->create([
            'min_celsius' => new Celsius(9.0),
            'max_celsius' => new Celsius(11.5),
        ]);

        $fresh = $excursion->fresh();

        $this->assertInstanceOf(Celsius::class, $fresh->min_celsius);
        $this->assertTrue($fresh->max_celsius->equals(new Celsius(11.5)));
    }
}
