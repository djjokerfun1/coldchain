# 0005. Where the transaction catch sits, and what stays synchronous

Date: 2026-08-17
Status: accepted

## Context

`POST /shipments/{id}/telemetry` has two separate correctness concerns that
are easy to conflate into one, but need different handling.

The first is idempotency: a device retries a ping it can't confirm was
received, so the same `external_event_id` can arrive twice. `RecordTelemetry`
relies on the unique `(shipment_id, external_event_id)` index from PR 2 to
make the second arrival a no-op rather than a duplicate row. The natural way
to write that is to insert inside `DB::transaction()` and catch
`UniqueConstraintViolationException` right there, returning `null` for
"already recorded." That's wrong on Postgres specifically: once a statement
inside a transaction is rejected, Postgres aborts the *entire* transaction
and refuses every further command over that connection — including the
implicit `COMMIT` that `DB::transaction()` issues when its closure returns
normally. A `catch` inside the closure that swallows the error and returns
`null` looks like a graceful no-op, but the connection is still in an
aborted transaction state, and the commit that follows throws anyway.

The second is speed: updating the shipment's denormalized position, deciding
whether the ping advances its status, and writing an audit entry are all
work that has no reason to make the device wait for a response.

## Decision

The row write (tracking event, and the temperature reading if one was sent)
happens inside `DB::transaction()`, but the `try/catch` for
`UniqueConstraintViolationException` wraps the *call* to `DB::transaction()`,
not anything inside its closure. That way, if Postgres aborts the
transaction, `DB::transaction()`'s own internal handling rolls it back and
rethrows, and the `catch` sees a clean, usable connection by the time it
runs.

Everything past that point is asynchronous. `RecordTelemetry` writes the row
synchronously and dispatches `TelemetryRecorded` only for a genuinely new
event; `UpdateShipmentPosition` and `RecordTelemetryAuditEntry` implement
`ShouldQueue` and run off a worker. The endpoint responds `202 Accepted`
right after the synchronous part completes.

The insert has to be synchronous, not just the transaction handling: the
idempotency guarantee only means something if the API can tell the device,
in this response, whether the ping was newly accepted or already on file.
Pushing the insert itself onto a queue would mean answering that question
later, asynchronously, which defeats the point of responding to the request
at all — the device needs the answer now, not in a job. The side effects
downstream of that (position, status, audit) don't carry the same
constraint: nothing about them needs to be known by the time the response is
written, so there's no reason to make the device wait for them.

## Consequences

Any future write that needs an idempotency guarantee enforced by a database
constraint has to follow the same shape: catch outside the transaction
wrapper, not inside it. This is a Postgres-specific behaviour (transaction
abort on the first error), so a future switch to a database engine with
different semantics (savepoint-per-statement, for instance) would be worth
rechecking against this assumption rather than assumed to carry over
unchanged.
