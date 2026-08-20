# 0007. Webhook idempotency: an existence check, backed by a partial unique index

Date: 2026-08-19
Status: accepted

## Context

A partner's webhook can arrive more than once for the same order: the
partner's own retry logic resends a request it can't confirm was received,
the same way a device retries a telemetry ping. `IngestClientOrder` needs to
treat a resend as a no-op — return the order that already exists, not
create a second one — using `(source_partner_key, external_reference)` as
the natural key for "have we already seen this."

The obvious first draft is a `SELECT ... WHERE source_partner_key = ? AND
external_reference = ?` before doing anything else, and skip creation if a
row comes back. That's necessary, but not sufficient on its own: nothing
stops two requests for the same webhook — a genuine retry that overlaps
with the first attempt still being processed, not just a sequential resend
— from both running that `SELECT`, both finding nothing, and both
proceeding to insert. An existence check followed by a create is two
separate statements with no atomicity between them; the gap between them is
exactly where a race lives.

## Decision

The existence check stays, because it's what makes a resend cheap in the
common case — it skips the whole normalize/map/insert path when nothing
has to happen. But it isn't what actually enforces the guarantee. A partial
unique index does that:

```sql
create unique index orders_partner_reference_unique
on orders (source_partner_key, external_reference)
where source_partner_key is not null
```

`IngestClientOrder::handle()` wraps the insert in the same catch-outside-
the-transaction shape ADR 0005 established for telemetry: if the index
rejects a concurrent insert, `DB::transaction()` rolls back and rethrows
`UniqueConstraintViolationException` on a clean connection, the `catch`
sees it, and the method re-queries for the row the other request just
created and returns that instead. The existence check is the fast path;
the index is the guarantee. Either one alone is wrong — the check alone
has the race, and the index alone would mean paying for a full normalize-
and-map cycle on every routine resend just to hit a constraint violation
at the very end.

The index is partial, not a plain `unique(source_partner_key,
external_reference)`, because most orders don't come from a partner at
all. Orders created through the dashboard have `source_partner_key` and
`external_reference` both `null`, and Postgres does not treat `null =
null` as a match for uniqueness purposes — a plain composite unique index
would actually be safe on that account. But it's still the wrong index:
it would build and maintain unique-tracking machinery over every
dashboard-created row, checking a uniqueness property that only means
anything for the subset of orders that are `where source_partner_key is
not null`. The partial index scopes the constraint, and the index itself,
to the rows it actually applies to.

## Consequences

Any future integration channel that ingests something idempotently from
an external system — the EDI parser included — should default to this
same two-part shape: a cheap existence check for the common case, and a
database constraint as the actual guarantee, wrapped the ADR-0005 way. A
uniqueness rule that only applies to a subset of rows (because the other
rows don't have the concept at all, as here, rather than because they're
soft-deleted or archived) is worth checking for a partial index rather
than reaching for a plain composite unique by default.
