<?php

declare(strict_types=1);

namespace App\Domain\Ordering\ValueObjects;

final readonly class Address
{
    public function __construct(
        public string $line1,
        public ?string $line2,
        public string $city,
        public string $postalCode,
        public string $country,
    ) {}

    /**
     * line2 may be entirely absent, not just null: request payloads omit
     * optional keys rather than sending them explicitly, unlike the shape
     * toArray() produces for persistence.
     *
     * @param  array{line1: string, line2?: string|null, city: string, postal_code: string, country: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            line1: $data['line1'],
            line2: $data['line2'] ?? null,
            city: $data['city'],
            postalCode: $data['postal_code'],
            country: $data['country'],
        );
    }

    /**
     * @return array{line1: string, line2: string|null, city: string, postal_code: string, country: string}
     */
    public function toArray(): array
    {
        return [
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
        ];
    }

    public function __toString(): string
    {
        $line2 = $this->line2 !== null ? ", {$this->line2}" : '';

        return "{$this->line1}{$line2}, {$this->city} {$this->postalCode}, {$this->country}";
    }
}
