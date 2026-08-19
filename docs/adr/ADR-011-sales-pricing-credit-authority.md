# ADR-011 — Sales Pricing and Credit Authority

**Status:** Accepted
**Milestone:** v0.4A1 Sales order foundation
**Decision date:** 2026-08-19

## Context

Salespersons must not be able to invent authoritative prices, bypass configured
price floors, or move a customer beyond approved credit exposure without
independent authorization.

## Decision

The backend owns all sales pricing decisions.

A sales order identifies the customer and packaging, but does not accept a
price-list selection from the browser. The customer's active effective default
price list is resolved server-side.

Each order line snapshots:
- product SKU and name;
- packaging name and conversion-to-base;
- list unit price;
- effective floor price;
- effective selling price;
- entered quantity and base quantity;
- line total;
- whether a pricing exception exists.

The floor is the stricter of:
- configured minimum selling price; and
- the floor implied by maximum discount percentage.

A requested unit price below that floor may exist only as explicit exception
evidence. Submitting such an order creates a generic approval request and the
maker cannot approve their own request.

Credit orders snapshot the customer's credit limit and the pre-submit exposure.
The customer row is locked while exposure is evaluated. Exceeding the credit
limit becomes the same independent exception workflow instead of a silent
override.

Normal orders without exceptions may auto-approve at submission. This decision
does not reserve stock yet; stock reservation belongs to v0.4B.

## Consequences

Frontend price manipulation cannot make an unauthorized price authoritative.
Historical commercial meaning survives later catalog/package/price changes.
Price and credit exceptions are visible, auditable, and maker-checker controlled.

The first slice intentionally excludes tax, invoicing, payment execution,
inventory reservation, picking, dispatch, refunds, and cashier sessions.
