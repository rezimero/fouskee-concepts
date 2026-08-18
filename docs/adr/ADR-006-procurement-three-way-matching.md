# ADR-006 — Procurement Lifecycle and Three-Way Matching

**Status:** Accepted / implemented in v0.3C
**Date:** 2026-08-17
**Implemented:** 2026-08-18

## Context

Fouskee Concepts needs procurement controls that reduce supplier/staff collusion,
false receiving, unauthorized price changes, duplicate invoices, and payment
for goods that were not ordered or received.

## Decision

The procurement lifecycle is:

`Purchase Order -> Approval -> Goods Receipt -> Supplier Invoice -> Three-Way Match`

The system compares:

1. what was ordered;
2. what was physically received;
3. what the supplier invoiced.

Exceptions require explicit resolution and maker-checker approval.

## Purchase orders

Purchase orders are organization-scoped and supplier-scoped.

Implemented states:

- draft;
- submitted;
- approved;
- partially_received;
- received;
- closed;
- cancelled.

Order lines snapshot product, packaging, conversion, ordered quantity, unit
price, base-unit price, and line amount.

The active procurement policy determines the PO value threshold that requires a
second approver. The default threshold is zero, so positive-value orders require
maker-checker approval unless an organization deliberately configures otherwise.

Submitted order lines cannot be silently changed in place.

## Goods receipts

A goods receipt records physical receipt against approved purchase-order lines.

A posted receipt:

- is immutable at line level;
- may be partial;
- may capture lot/batch data;
- snapshots package conversion and provisional base-unit cost;
- posts an inventory receipt movement;
- links that movement back to the procurement receipt.

Cumulative over-receipt is evaluated under a serialized purchase-order lock.
A receipt outside tolerance enters `pending_exception` and does not post stock
until independently approved.

A posted receipt error is corrected through the owning reversal workflow rather
than a raw inventory reversal or in-place edit.

## Supplier invoices

Supplier invoice numbers are normalized for duplicate protection inside
organization + supplier scope.

Invoice lines preserve product, packaging, conversion, quantity, unit price,
base-unit price, and line amount. Lines link to purchase-order lines where
possible. Unlinked invoice lines are permitted as evidence but will produce a
three-way-match exception.

Procurement never marks an invoice paid.

## Three-way match

Each match run is new immutable evidence. Re-running a match creates another
run; previous evidence is not rewritten.

The engine compares:

- ordered quantity against posted received quantity;
- cumulative invoiced quantity against posted received quantity;
- ordered base-unit price against invoiced base-unit price;
- expected line amount against invoiced line amount;
- purchase-order currency against invoice currency;
- document expected amount against invoice subtotal.

Results are:

- matched;
- within_tolerance;
- exception.

An exception creates a generic approval request. Independent approval changes
the supplier invoice to `exception_approved`; the immutable match run itself is
not rewritten.

## Tolerances

Procurement policy is versioned and effective-dated.

Each policy version stores:

- purchase-order approval value threshold;
- over-receipt tolerance percentage;
- invoice quantity tolerance percentage;
- invoice price tolerance percentage;
- invoice amount tolerance percentage.

Purchase orders, goods receipts, and match runs reference the policy version
used for their decision.

## Maker-checker controls

The requester cannot decide their own generic approval request.

This applies to:

- purchase-order approval above threshold;
- over-receipt exception approval;
- three-way-match exception approval.

Privileged reasons are required on sensitive approval and correction routes.

## Payment boundary

Only supplier invoices with status `matched`, `within_tolerance`, or
`exception_approved` are procurement-eligible for downstream payment/accounting.

Eligibility is not payment execution.

## Consequences

Positive:

- ordered, received, and invoiced evidence remain independently attributable;
- partial receipts are explicit;
- duplicate supplier invoices are blocked at the database boundary;
- cumulative invoicing detects split-invoice overbilling;
- receiving and matching exceptions require a second person;
- accounting can integrate later without losing procurement history.

Trade-offs:

- more workflow records and approvals;
- draft PO editing UI remains for the later workspace milestone;
- inventory is provisionally valued at approved PO cost until the accounting
  domain introduces purchase-price variance / landed-cost treatment.

## Rejected alternatives

### PO + supplier invoice only

Rejected because it cannot prove physical receipt.

### Receipt without PO linkage as normal workflow

Rejected because it weakens receiving fraud controls.

### Editable approved PO / posted receipt

Rejected because it weakens three-way-match evidence.

### Supplier invoice directly marking payment

Rejected because procurement and payment authority must remain separate.
