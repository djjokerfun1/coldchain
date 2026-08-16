# 0001. Test against real Postgres, not SQLite in-memory

Date: 2026-08-16
Status: accepted

## Context

Laravel's default test setup points at an in-memory SQLite database. It's
fast and needs no services running, which is why it's the default. But this
schema leans on Postgres-specific features from the start: `jsonb` columns
for the pickup/delivery addresses and audit payloads, partial and composite
indexes for the tracking queries, and later stages will add `DISTINCT ON`
queries for "latest reading per shipment" lookups.

SQLite doesn't enforce most of that. It would happily run a migration using
`jsonb`, unique constraints with Postgres NULL semantics, or a partial index,
accept it silently, and let the test suite pass — right up until the same
migration hits a real Postgres database in production and behaves
differently or fails outright. A green test suite that isn't actually
testing the database it will run on is worse than no test suite, because it
creates false confidence.

## Decision

Tests run against a real PostgreSQL 17 database (`coldchain_testing`),
created via `docker/postgres/init/01-test-database.sh` when the Postgres
volume is first initialised. `phpunit.xml` sets `DB_CONNECTION=pgsql` and
`DB_DATABASE=coldchain_testing`. CI spins up an actual `postgres:17` service
container rather than mocking or stubbing the database layer.

## Consequences

The suite is slower than SQLite in-memory would be — each test run pays for
real Postgres connections and transactions instead of an in-process file.
That cost was accepted deliberately: catching a schema bug locally in a few
extra seconds is cheaper than catching it after a migration reaches
production. It also means both local development and CI need a Postgres
service available before `php artisan test` can run, which is more setup
than SQLite requires, but Docker Compose (locally) and GitHub Actions service
containers (in CI) make that automatic rather than a manual step.
