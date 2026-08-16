# 0002. Guard shipment status transitions on the enum

Date: 2026-08-16
Status: accepted

## Context

`Shipment.status` moves through a small set of values — `pending`,
`picked_up`, `in_transit`, `delivered`, `exception` — as a shipment
progresses. The simplest implementation is a plain string (or string-backed
enum) column that any code path can set to any value: a controller, a job, a
seeder, a one-off `tinker` session.

That simplicity is also the problem. Nothing stops that code from writing
`delivered` directly onto a shipment that's still `pending`, or moving a
`delivered` shipment back to `picked_up`. Those aren't hypothetical typos —
they're the kind of bug that shows up months later from a job with a wrong
condition, or a support fix run by hand against production, and by the time
it's noticed the bad state has already been read and acted on elsewhere
(a compliance report, a client-facing status page). Validating this only at
the HTTP request layer (a form request) doesn't help either, since none of
those other code paths go through a request.

## Decision

`ShipmentStatus` (the enum) owns the transition rules itself, via
`allowedTransitions()` and `canTransitionTo()`. `Shipment::transitionTo()` is
the only method that changes a shipment's status, and it throws
`InvalidStatusTransition` if the move isn't allowed. There is no other write
path to the `status` column in application code.

The alternative considered was a plain string column with validation living
in the form request layer only. It was rejected for the reason above: it
protects the API, but not the model, so anything that isn't a validated HTTP
request — jobs, seeders, artisan commands, future integrations writing
directly via Eloquent — could still produce an impossible state. Putting the
guard on the enum means the rule holds everywhere the enum is used, not just
behind one entry point.

## Consequences

Every status change has to go through `transitionTo()`, which is a small
amount of extra ceremony compared to `$shipment->update(['status' => ...])`.
In exchange, an invalid state transition is impossible to produce by
accident from anywhere in the codebase — it fails loudly, at the point of
the mistake, instead of silently persisting and surfacing as a confusing bug
somewhere downstream. If the status rules ever need to depend on more than
the current and target status (e.g. who is allowed to make the change), that
logic will need a home beyond the enum — the enum only knows about the
states themselves, not who's requesting the move.
