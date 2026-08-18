# ADR-008 — Procurement Cost and Evidence Boundary

**Status:** Accepted for v0.3C
**Date:** 2026-08-18

## Context

The inventory domain already maintains perpetual weighted-average value while
the Accounting and Tax domains are not yet implemented.

Procurement must therefore establish a defensible inventory cost without
pretending that supplier invoice variance, recoverable tax, freight, or landed
cost accounting already exists.

## Decision

### Provisional inventory cost

Goods receipt inventory value uses the approved purchase-order base-unit price
snapshot.

This is the provisional acquisition cost for inventory at physical receipt.

A later supplier invoice price variance does not silently rewrite historical
inventory movements or weighted-average cost in v0.3C.

Accounting will later decide whether a price variance becomes:

- purchase-price variance;
- payable variance;
- inventory adjustment;
- landed-cost allocation;
- another accounting treatment.

### Currency boundary

v0.3C accepts procurement only in the organization's configured functional
currency. Foreign-currency purchase orders and supplier invoices are rejected
until the FX/accounting layer can apply exchange rates and valuation rules.

### Supplier tax evidence

Supplier invoice tax amount is stored as supplier evidence, but v0.3C does not
classify or post tax.

The future Tax domain will determine recoverability, withholding, tax codes, and
ledger treatment.

### Cumulative invoice matching

Three-way matching evaluates cumulative invoiced quantity for each PO line, not
only the current invoice line. This prevents split invoices from bypassing the
received-quantity control.

### Immutable match history

Every match run is append-only evidence.

Changes in receipt evidence create a new match run rather than modifying an old
run.

## Consequences

The current implementation preserves procurement truth without coupling
inventory to unfinished accounting rules.

The accounting milestone must consume these snapshots and explicitly resolve
price/tax/landed-cost variances rather than retroactively mutating procurement
history.
