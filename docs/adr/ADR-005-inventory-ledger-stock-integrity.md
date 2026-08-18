# ADR-005 — Inventory Ledger and Stock Integrity

**Status:** Accepted / implemented in v0.3A; extended by v0.3B
**Date:** 2026-08-17

## Context

Fouskee Concepts requires fraud-resistant stock control across wholesale and retail operations. Product master data already supports a stable base unit and multiple package conversions. Inventory quantity is deliberately absent from the product model.

A mutable stock field would make unexplained adjustments easy, weaken historical evidence, and create race conditions around simultaneous receipts, sales, transfers, and corrections.

## Decision

Inventory will use an append-only movement ledger as the authoritative stock record.

A posted movement contains one or more immutable movement lines. Each line records a signed quantity in the product base unit for one inventory location.

Examples:

- purchase receipt: positive quantity at the receiving location;
- sale/issue: negative quantity at the issuing location;
- transfer: negative line at source plus positive line at destination;
- stock adjustment: explicit signed variance with controlled reason;
- reversal: a new movement that negates the original movement.

Posted movements and movement lines are never updated or deleted.

## Balance projection

`inventory_balances` may hold derived on-hand balances for fast operational queries and concurrency control. It is not the source of truth and must always be rebuildable from posted movement lines.

Posting locks the affected balance rows inside the same MySQL transaction before checking the no-negative-stock invariant and writing ledger/projection changes.

## Negative stock

Negative stock is blocked by default.

A future explicit override may exist only for a narrowly granted permission. It must require MFA, a privileged business reason, immutable audit evidence, and a separate fraud-risk signal. Superadmin may exercise the override but is not exempt from attribution.

## Corrections

A posted movement is corrected by reversal plus, when necessary, a replacement movement. Historical movements are never edited in place.

The reversal stores `reversal_of_movement_id` and must reproduce the inverse economic/quantity effect of the original lines.

## Idempotency

All posting commands require an idempotency key. The database enforces uniqueness within organization scope so retries or double submissions cannot post stock twice.

## Packaging snapshots

Transactional movement lines store:

- product identifier;
- product packaging identifier when applicable;
- entered package quantity;
- conversion-to-base snapshot;
- resulting base quantity.

Historical stock meaning therefore does not change if commercial packaging master data is later amended.

## Reservations

Reservations are not physical stock movements. They are a separate commitment record.

`available = on_hand - active_reserved`

Reservation create/release/expiry actions are auditable and organization-scoped.

## Lot/batch readiness

Movement lines may reference an optional inventory lot. Lot tracking is not mandatory for every product in v0.3A, but the ledger must support it without a schema redesign.

## Costing

Initial inventory costing will use perpetual weighted-average cost.

Purchase receipt lines capture unit cost. The balance projection may maintain quantity and inventory value to derive the weighted-average cost after receipts.

The ledger retains enough quantity/cost evidence to introduce FIFO reporting or lot-specific costing later without changing historical movement quantities.

## Database hardening

Posted inventory movements and movement lines should receive database-level UPDATE/DELETE prevention similar to the audit ledger. Trigger installation is a controlled privileged database operation, not a runtime-app privilege.

## Consequences

### Positive

- unexplained stock edits become structurally difficult;
- transfers conserve quantity explicitly;
- balances can be reconstructed;
- retries can be made safe;
- historical package conversions remain stable;
- concurrency rules are centralized.

### Trade-offs

- posting services are more complex than direct quantity updates;
- balance projections require reconciliation tooling;
- reversals create more records, by design;
- weighted-average costing requires careful transactional locking.

## Rejected alternatives

### Mutable `products.quantity`

Rejected because it provides weak evidence, poor concurrency semantics, and no reconstructable stock history.

### Editable movement records

Rejected because historical evidence could be rewritten after fraud or error.

### Reservation as negative stock movement

Rejected because reservation is a commitment, not a physical stock event.
