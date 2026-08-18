# 0006. The fleet simulator drives the HTTP API, not the domain layer

Date: 2026-08-18
Status: accepted

## Context

`fleet:simulate` needs to generate a stream of telemetry pings that look like
they came from real on-vehicle devices, so the rest of the tracking stack
(live position, status transitions, excursion detection once it exists) has
something to react to during a demo.

The direct route would be to call `RecordTelemetry` from the command,
passing it a shipment and a payload straight from PHP. It's fewer moving
parts, no HTTP round trip, no token to mint or clean up, and no dependency
on the stack actually being reachable at `APP_URL`.

## Decision

The command instead sends real `POST /api/v1/shipments/{id}/telemetry`
requests over HTTP, authenticated with a Sanctum token minted for a planner
user and deleted again in a `finally` block once the run ends.

A device in the field never calls `RecordTelemetry`. It calls the endpoint.
Everything between the route and that action — Sanctum authentication, the
`recordTelemetry` policy check, `FormRequest` validation, the idempotency
guard on `external_event_id`, the `202` response — is exactly the part of
the system a real device interacts with and a real integration bug would
show up in. Calling the domain action directly would exercise the write
logic but silently skip all of it. A bug in the route's auth middleware, a
validation rule that's stricter than intended, a policy that blocks the
wrong role — none of that would ever surface from a simulator that bypasses
the HTTP layer to reach the action directly.

The cost is real: the command only works against a running, reachable
stack, and every tick pays for an actual HTTP request instead of a function
call. For a simulator whose entire purpose is to look like the thing it's
standing in for, that cost is the point, not a tradeoff to optimise away.

## Consequences

`fleet:simulate` has to run from the host, not from inside the `app`
container, because `config('app.url')` resolves to the port Docker
publishes to the host, not the internal service name Docker Compose uses
between containers — the same reason `php artisan test` runs from the host
too. This is documented in the README rather than worked around, since
routing it through the internal service name would make the command behave
differently depending on where it's invoked from, which is worse than a
documented constraint.

Any future simulator or synthetic-load tool for this project should default
to the same choice: go through the API unless there's a specific reason not
to. The one case where calling a domain action directly would be
justified is a tool whose entire purpose is testing that action in
isolation — which is what the test suite is for, not a demo command.
