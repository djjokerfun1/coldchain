# 0003. Shared, whitelisted index queries; atomic order creation

Date: 2026-08-17
Status: accepted

## Context

Three listing endpoints (`clients`, `products`, `orders`) all need the same
shape of behaviour: paginate the results, optionally filter by a column,
optionally sort by a column. The naive approach is to let each endpoint read
whatever the client sends — `?sort=` becomes the column name in `orderBy()`,
`?filter[x]=` becomes a `where()` on column `x` — directly.

That's a real vulnerability, not a theoretical one: a client controls those
query parameters completely. Passing them straight into `orderBy()` or
`where()` means a request can probe or sort by *any* column that exists on
the table, including ones the endpoint never meant to expose — internal
timestamps, foreign keys, anything. There's no SQL injection here (Eloquent
parameterises the values), but there is an authorization gap: the client
gets to choose which columns are queryable, not the endpoint.

Separately, creating an order needs at least one line — a `client_id` and a
pair of addresses with nothing attached to ship isn't a real order. If
creation happened as `POST /orders` followed by `POST /orders/{id}/lines`,
a client that successfully creates the order but then fails, times out, or
crashes before sending the second request leaves a real, persisted order
with zero lines sitting in the database — an invalid state that's now
somebody else's problem to notice and clean up.

## Decision

`App\Http\Support\IndexQuery` is a single class shared by all three index
endpoints. Each endpoint constructs it with an explicit list of which
columns may be filtered, searched, and sorted; a request cannot query a
column that isn't on that list. This puts the whitelist in one reviewable
place instead of duplicating (and potentially getting wrong) the same
parsing logic three times.

`OrderController::store()` creates the order and its lines inside a single
`DB::transaction()`. The API accepts one payload — `client_id`, both
addresses, and a non-empty `lines` array — and either all of it is persisted
or none of it is. There is no code path that produces an order with zero
lines.

Delete endpoints (`ClientController`, `ProductController`, `OrderController`)
check the blocking relation with `->exists()` before calling `delete()`,
returning `409 Conflict` if something still references the record. The
alternative — attempt the delete and catch the `QueryException` a foreign
key violation would raise — was rejected because it's a worse API on its own
terms: the explicit check reads as the actual business rule ("clients with
orders can't be deleted") rather than as recovery from a low-level database
error, and it's directly testable without needing to provoke a real
constraint violation.

## Consequences

Adding a new filterable or sortable column to any endpoint means adding it
to that endpoint's whitelist — a one-line, deliberate change — rather than
it becoming queryable automatically. Order creation always round-trips
client + addresses + lines in one request; there is currently no endpoint to
add a line to an existing order, which is a real gap if a client needs to
amend an order after the fact rather than recreate it — that's deferred
rather than solved here. The `exists()`-before-delete pattern needs to be
repeated for each new relation a model gains that should block deletion; it
doesn't generalise the way a single `try/catch` around every `delete()`
would have, but the trade was made deliberately for clarity and
testability.
