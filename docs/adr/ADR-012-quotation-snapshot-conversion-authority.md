# ADR-012 — Quotation Snapshot and Conversion Authority

**Status:** Accepted
**Milestone:** v0.4A2 Sales quotation foundation
**Decision date:** 2026-08-19

## Context

A quotation is a commercial promise. If catalog names, packaging conversions,
or price lists change after a quote is sent, the historical quotation must not
silently change. Conversion to an order must also preserve the accepted
commercial terms while still passing the order's price/credit approval controls.

## Decision

Quotations are persistent organization-scoped documents with draft, sent,
accepted, converted, and cancelled states.

The backend resolves the customer's active effective price list and snapshots
the same commercial facts used by Sales orders: product identity, packaging,
conversion-to-base, list price, effective price floor, requested/effective
selling price, quantities, line totals, and price-exception evidence.

Sent quotation lines become application-immutable. Accepted quotations may be
converted exactly once. Conversion copies the quotation snapshots into a new
draft Sales order and records `source_quotation_id`. The order is not
auto-submitted: existing Sales-order price and credit controls still decide
whether independent approval is required.

Expired quotations cannot be accepted. Conversion is idempotent through the
unique source-quotation relationship.

## Consequences

Historical quotations survive later master-data changes and accepted pricing is
not silently recalculated during conversion. A below-floor quoted price remains
visible as exception evidence and therefore cannot bypass the order approval
workflow.

Database trigger hardening for Sales commercial evidence remains a dedicated
closure step before the v0.4 Sales core is declared hardened.
