# 0004. Policies authorize an instance; listing endpoints scope the query themselves

Date: 2026-08-17
Status: accepted

## Context

Four roles-aware endpoints now exist — `OrderController` and
`ShipmentController` are the two that matter here, since `client` and
`driver` users can only see a subset of orders and shipments. The natural
first instinct is: write a Policy (`OrderPolicy`, `ShipmentPolicy`), call
`$this->authorizeResource()` in the controller, done.

That gets `show`, `update`, and `destroy` right for free — each is called
with a specific model instance already loaded, and the policy's `view`,
`update`, `delete` methods answer a yes/no question about that one record.
But `index` doesn't have a model instance to check. `viewAny` can only
answer "is this role allowed to list orders at all" — it has no way to see,
let alone filter, the actual rows a query is about to return. There is no
hook in Laravel's authorization system that lets a Policy rewrite or
constrain the SQL an `index()` action runs. This isn't a gap that was missed
in this codebase specifically — it's how the framework's authorization layer
is shaped: policies gate access to things you already have, not queries you
haven't run yet.

Concretely, this meant `OrderPolicy::viewAny()` returning `true` for a
`client` role was, on its own, indistinguishable from that client being
allowed to list *every* order in the system — the policy alone can't stop
that.

## Decision

The query scope for `index()` is applied explicitly in the controller, as
plain Eloquent, alongside (not instead of) the policy check:

```php
if ($request->user()?->role === UserRole::Client) {
    $orders->where('client_id', $request->user()->client_id);
}
```

`ShipmentController::index()` does the equivalent for both `driver`
(`where('driver_id', ...)`) and `client` (`whereHas('order', ...)`). The
policy still runs first via `authorizeResource()` and still governs `show`,
`update`, and `destroy` on individual records — this scope is additive, not
a replacement for it.

## Consequences

Every new role-scoped listing endpoint needs this same explicit scoping
written by hand in its controller; it isn't something adding a Policy method
alone will ever provide, so it's easy to forget the *second* time a scoped
resource is added, not just the first. `OrderApiTest` includes a test for
exactly the mistake this would produce: a `client` passing
`filter[client_id]` for a different client's ID gets zero rows back, not
someone else's orders, because the forced scope is an unconditional
`WHERE` that any request-supplied filter can only narrow further, never
escape.

The same reasoning shaped `UpdateShipmentRequest`: it accepts only
`driver_id` and `vehicle_id`, never `status`. Letting this endpoint touch
`status` would reopen exactly what ADR 0002 closed — a write path to a
shipment's status that bypasses `Shipment::transitionTo()` and its
transition guard. Status changes belong to the tracking-event flow instead,
which is where the actual event history that justifies a status lives.
