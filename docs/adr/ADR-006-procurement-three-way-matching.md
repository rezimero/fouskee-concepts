# ADR-006 — Procurement Lifecycle and Three-Way Matching

**Status:** Proposed for v0.3C implementation  
**Date:** 2026-08-17

## Context

Fouskee Concepts needs procurement controls that reduce supplier/staff collusion, false receiving, unauthorized price changes, duplicate invoices, and payment for goods that were not ordered or received.

## Decision

The procurement lifecycle will be:

`Purchase Order -> Approval -> Goods Receipt -> Supplier Invoice -> Three-Way Match`

The system will compare:

1. what was ordered;
2. what was physically received;
3. what the supplier invoiced.

Exceptions require explicit resolution and, above configured thresholds, maker-checker approval.

## Purchase orders

Purchase orders are organization-scoped and supplier-scoped.

Planned states:

- draft;
- submitted;
- approved;
- partially_received;
- received;
- closed;
- cancelled.

Approved order lines snapshot product, packaging, conversion, ordered quantity, unit price, and commercial terms required for later matching.

Material changes after approval require a controlled amendment or replacement order rather than silent mutation.

## Goods receipts

A goods receipt records physical receipt against purchase-order lines.

A posted receipt:

- is immutable;
- may be partial;
- may capture lot/batch data;
- snapshots package conversion;
- captures receipt cost evidence;
- posts an inventory receipt movement.

Over-receipt tolerance is configuration-driven. Receipts outside tolerance enter an exception workflow and cannot be silently normalized.

A receipt error is corrected by reversal and replacement, not in-place editing.

## Supplier invoices

Supplier invoices record the supplier's claim for payment and must be protected against duplicate invoice numbers within supplier/organization scope.

Invoice lines link to purchase-order lines when possible.

Accounting posting is deferred to the Accounting domain, but the procurement model must preserve all evidence required to create entries later.

## Three-way match

The match engine compares line-level and document-level:

- ordered quantity vs received quantity;
- ordered price vs invoiced price;
- received quantity vs invoiced quantity;
- expected amount vs invoiced amount.

Tolerance rules are configuration-driven and effective-dated where necessary.

Match results are one of:

- matched;
- within_tolerance;
- exception;
- exception_approved.

An exception approval records the approver, reason, values, and immutable audit event.

## Maker-checker controls

The user who creates or materially changes a purchase order should not approve that same order when maker-checker is required.

The user who records a receipt should not approve their own material receiving exception.

A Superadmin can exercise break-glass authority but must provide a privileged reason and remains fully auditable.

## Payment boundary

A matched/approved supplier invoice becomes eligible for downstream accounting and payment processes. Procurement itself does not mark a supplier invoice paid.

## Consequences

### Positive

- payment eligibility is linked to ordered and received evidence;
- partial receipts and discrepancies are explicit;
- supplier invoice duplication is detectable;
- receiving fraud becomes more visible;
- accounting can integrate later without losing procurement history.

### Trade-offs

- more workflow states and approvals;
- configuration for tolerances is required;
- receipt correction requires reversal records rather than edits.

## Rejected alternatives

### PO + supplier invoice only

Rejected because it cannot prove physical receipt.

### Receipt without PO linkage

Permitted only as a tightly controlled exceptional workflow, not the normal procurement path.

### Editable approved PO / posted receipt

Rejected because it weakens three-way-match evidence.
