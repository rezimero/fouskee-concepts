# ADR-013 — Database Hardening of Sales Commercial Evidence

**Status:** Accepted
**Milestone:** v0.4A3
**Decision date:** 2026-08-19

## Context

Application-level guards are necessary but insufficient for anti-fraud
evidence. The runtime database user can perform ordinary `UPDATE` and `DELETE`
operations, so a compromised application path must not be able to silently
rewrite submitted orders or sent quotations.

## Decision

Install MySQL `BEFORE` triggers, using a privileged deployment operation, to
protect Sales commercial evidence after it leaves draft state.

For Sales orders:
- immutable commercial header fields cannot change once either the old or new
  state is non-draft;
- a non-draft order cannot be reopened as draft;
- non-draft orders cannot be deleted;
- lines cannot be inserted, updated, or deleted while the parent is non-draft.

For quotations the same boundary begins when the quotation leaves draft.

Workflow fields such as submission, approval, acceptance, conversion, actor
IDs, timestamps, credit snapshots, and exception evidence remain updateable by
the controlled services because they are lifecycle evidence rather than quoted
commercial terms.

The runtime `fouskee_app` identity does not receive `TRIGGER` authority.
Trigger installation remains a controlled privileged operation and trigger
definers must not be the runtime application account.

## Consequences

A direct runtime SQL path cannot rewrite already-issued commercial evidence.
Drafts remain editable because they are not yet committed evidence. Future
Sales lifecycle additions must preserve this boundary and review whether any
new commercial columns belong inside the immutable-field trigger comparisons.
