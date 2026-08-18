# ADR-007 — Operational Inventory Controls

**Status:** Accepted for v0.3B implementation
**Date:** 2026-08-18

## Context

The v0.3A ledger made posted stock immutable and reconstructable, but operational
workflows still need controlled stock counts, valuation, lot-aware handling,
asynchronous transfers, backdating rules, and maker-checker approval.

## Decision

### Weighted-average valuation

Fouskee Concepts will use perpetual weighted-average cost for the initial
inventory valuation model.

Every new physical movement line receives an `inventory_value_delta`. Receipts
establish value from the posted base-unit cost. Negative adjustments consume the
current weighted-average cost. Transfers move value with quantity so
organization inventory value is conserved. Reversals negate the original value
effect.

`inventory_balances` remains a derived projection.

### Lot-aware stock scope

A balance key is:

`organization + location + product + optional lot`

A lot-specific movement, reservation, transfer, or stock count operates only on
that lot balance. A command without a lot operates on the non-lot balance. The
system will not silently allocate a no-lot command across multiple lots.

### Stock counts

A stock count is a workflow document, not a mutable stock balance.

Creation snapshots quantity, weighted-average cost, and balance version.
Submission fails if stock changed after the snapshot.

Variance is posted as an immutable adjustment movement linked back to the stock
count document.

### Maker-checker

An organization-configurable absolute variance-value threshold determines when
a stock count requires independent approval.

Above threshold:
- the count becomes `pending_approval`;
- an approval request is created;
- the maker cannot decide their own request;
- a distinct user with `inventory.adjust.approve` must approve or reject.

The approval service is intentionally generic so procurement can reuse it.

### Controlled transfers

Immediate transfers remain available only while organization policy permits.

The controlled stock-transfer workflow supports:
- draft transfer evidence;
- dispatch from source to a system transit location;
- receipt from transit to destination;
- optional separate receiver enforcement;
- cancellation that returns in-transit stock to source.

Transfer lines snapshot packaging conversion and are not edited after creation.

### Backdating

Each organization has a configurable backdate window.

Posting outside that window requires:
- `inventory.backdate`;
- MFA through the existing protected API shell;
- a privileged business reason;
- immutable movement/audit attribution.

Negative-stock override remains deferred.

### Projection reconciliation

`inventory:reconcile-projections` compares the rebuildable balance projection
with the immutable movement ledger.

`--rebuild` replaces only the derived projection. It never rewrites ledger
movements.

## Consequences

Positive:
- count variance is evidence-backed;
- large adjustments require a second person;
- transfer stock can remain visible while physically in transit;
- inventory valuation becomes transactionally consistent;
- lots are operational rather than schema-only;
- projection drift becomes detectable and repairable.

Trade-offs:
- stock operations use more workflow records;
- transfer confirmation can require a second staff account;
- historical v0.3A movements with no cost evidence rebuild at zero value;
- no-lot commands do not automatically allocate across lots.

## Rejected alternatives

### Editing on-hand quantity after a count

Rejected because it destroys evidence.

### Approver equals maker for material variance

Rejected because it defeats the fraud-control objective.

### One-step transfer for every scenario

Rejected because dispatch and physical receipt can happen at different times.

### Recomputing old movement quantities after packaging changes

Rejected because transactional snapshots are authoritative.
