# ADR-015 — Sales Fulfillment and Inventory-Ledger Authority

**Status:** Accepted
**Milestone:** v0.4B2
**Decision date:** 2026-08-19

## Context

Picking and dispatch are the point where reserved stock becomes a physical
outbound movement. This workflow must not bypass Inventory availability,
valuation, idempotency, immutable movement evidence, or reversal rules.

## Decision

A Sales fulfillment is a persistent document with draft, picked, dispatched,
and reversed states.

Picking must confirm the exact set of active `sales.order` reservations. One
fulfillment uses one location. The fulfillment snapshots each reservation,
product, lot, and base quantity. Pick evidence is frozen before the transaction
commits.

Dispatch consumes those exact reservations first inside the same transaction,
changing them from active to fulfilled. The Inventory poster then posts one
`sale_issue` movement sourced by `sales.fulfillment`. Because the reservations
are no longer active, the existing Inventory availability guard can debit the
reserved stock without double-subtracting the reservation.

The Inventory poster remains the only stock/projection writer. Its existing
balance locking, weighted-average valuation, idempotency fingerprint, immutable
ledger, and audit event remain authoritative.

A dispatched fulfillment is the physical-completion state. The Sales order
stays `approved` until invoice/open-balance semantics exist, so a dispatched
credit order remains in the current credit-exposure calculation. The
fulfillment guard blocks a second active fulfillment. Generic Inventory
reversal is forbidden for
`sales.fulfillment` movements. The Sales reversal workflow calls the owned
reversal path, restores stock through the ledger reversal, creates new active
Sales-owned reservations from the immutable fulfillment snapshots, without rewriting Sales-order approval evidence.

## Fraud controls

- exact-reservation pick set; no silent lot substitution;
- no release of reservations after pick;
- no direct Sales balance mutation;
- idempotent dispatch;
- one immutable outbound movement per dispatch;
- generic reversal cannot bypass Sales ownership;
- privileged reason required for dispatch reversal;
- original fulfilled reservations are retained; reversal creates replacement
  reservations rather than rewriting consumed evidence;
- fulfillment identity and line evidence are protected by MySQL triggers.

## Deferred

Partial fulfillment, split-location fulfillment, backorders, substitutions,
delivery routing, proof-of-delivery, invoicing, and payment gating remain later
slices.
