# ADR-014 — Sales-Owned Inventory Reservations

**Status:** Accepted
**Milestone:** v0.4B1
**Decision date:** 2026-08-19

## Context

Approved Sales orders must hold physical availability without directly changing
on-hand stock. Inventory already owns balance locking, available-stock checks,
lot-scoped reservations, and transfer protection.

## Decision

Sales reservations are Inventory reservations whose source is `sales.order` and
whose source ID is the Sales order public ID.

Only approved orders may reserve. An authorized operator chooses one active
fulfillment location; the system transit location is forbidden.

Sales demand is aggregated by product in base units and allocated through
`InventoryReservationService`. Allocation is deterministic:
1. expiry-dated lots, earliest expiry first;
2. remaining tracked lots, oldest receipt first;
3. untracked balance scopes last.

Each allocation remains a genuine lot-scoped Inventory reservation, preserving
the existing transfer and availability controls.

`sales.order` is an owned reservation source. Generic Inventory create/release
paths cannot manipulate it; Sales orchestration uses explicit owned methods.
Release requires a privileged reason and creates both Inventory and Sales audit
evidence.

MySQL triggers freeze reservation identity fields and prohibit deletion. Runtime
SQL may perform the controlled release-state update but cannot rewrite the
location, product, lot, source, quantity, expiry, creator, or delete evidence.

## Consequences

Sales does not maintain a parallel stock number. Lot and non-lot balances can
both satisfy an order while retaining Inventory-domain enforcement. Repeated
reservation at the same location is idempotent when evidence matches exactly;
conflicting evidence stops with an integrity error rather than being repaired
silently.

Picking, dispatch, reservation consumption, backorders, substitutions, and
multi-location fulfillment remain separate later workflows.
