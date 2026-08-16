# ColdChain

A logistics platform for temperature-controlled freight. It covers order intake,
shipment tracking from on-vehicle devices, integrations with external partner
systems, and the cold-chain compliance record that pharmaceutical distribution
(GDP) requires.

This is a personal project. It is built to be read, so the interesting parts are
the domain rules and the integration boundaries rather than the CRUD around them.

Work in progress. See `docs/decisions/` for the reasoning behind the structural
choices.

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
