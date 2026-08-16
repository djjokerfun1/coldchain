# ColdChain

A logistics platform for temperature-controlled freight. It covers order intake,
shipment tracking from on-vehicle devices, integrations with external partner
systems, and the cold-chain compliance record that pharmaceutical distribution
(GDP) requires.

This is a personal project. It is built to be read, so the interesting parts are
the domain rules and the integration boundaries rather than the CRUD around them.

Work in progress. See `docs/decisions/` for the reasoning behind the structural
choices.

## Domain

Code is organised by bounded context under `app/Domain`, not by technical layer:
`Ordering`, `Shipping`, `ColdChain`, `Auditing`. Each has its own models, enums
and value objects; a shipment's status, for instance, is a `ShipmentStatus` enum
that guards its own transitions rather than a string anyone can set.

```mermaid
erDiagram
    Client ||--o{ Order : places
    Order ||--o{ OrderLine : contains
    Product ||--o{ OrderLine : "ordered as"
    Order ||--o{ Shipment : fulfilled_by
    Driver ||--o{ Vehicle : drives
    Driver ||--o{ Shipment : assigned_to
    Vehicle ||--o{ Shipment : assigned_to
    Shipment ||--o{ TrackingEvent : logs
    Shipment ||--o{ TemperatureReading : logs
    Shipment ||--o{ TemperatureExcursion : "may open"
```

`TrackingEvent`, `TemperatureReading` and `AuditEntry` are append-only: no
update route exists or ever will for them. A shipment's position and status
are projections over its tracking events, not fields mutated directly.
`TemperatureExcursion` is the one mutable record in ColdChain — it is opened
as a candidate and updated as it is confirmed or resolved.

## API

`/api/v1` is a token-authenticated REST API (Sanctum). `POST /api/v1/login`
exchanges credentials for a token; every other endpoint requires it as a
Bearer token and is scoped by the caller's role:

- **planner** — full access to everything.
- **driver** — can only see shipments (and the vehicle) assigned to them.
- **client** — can only see their own orders and the shipments on them.

Policies (`app/Policies`) gate access to a single record; list endpoints
additionally scope their query by role in the controller, since a policy
can authorize "can this user view *this* shipment" but has no say over
which rows a listing query returns in the first place.

## Stack

- PHP 8.4, Laravel 13
- PostgreSQL 17, Redis 7
- Docker Compose for the local environment
- PHPUnit, Pint, Larastan (level 6)

## Running it locally

```
cp .env.example .env
composer install
docker compose up -d
php artisan key:generate
php artisan migrate
```

The application is served at http://localhost:8100.

Postgres and Redis are published on 55432 and 56379 so the stack does not
collide with anything already running on the default ports. `.env` points at
those, which means `artisan` and `phpunit` work from the host; inside the
Compose network the `app` service overrides the host and port with the service
names.

## Tests

```
php artisan test
```

Tests run against a real `coldchain_testing` database, created by
`docker/postgres/init/01-test-database.sh` when the Postgres volume is first
initialised. If you already have a volume from before that script existed, run
`docker compose down -v` once to recreate it.

## Checks

```
vendor/bin/pint --test
vendor/bin/phpstan analyse
```
