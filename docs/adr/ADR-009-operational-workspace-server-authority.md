# ADR-009 — Operational Workspace and Server Authority

**Status:** Accepted for v0.3D
**Date:** 2026-08-18

## Context

v0.3A-v0.3C established inventory and procurement workflows with immutable
evidence, maker-checker approval, idempotency, privileged reasons, and
organization-scoped permissions.

The staff interface now needs to expose those workflows without weakening them
through client-side shortcuts or duplicating server-side business rules.

## Decision

### Workflow-oriented workspaces

Inventory and procurement are presented as operational workflows rather than
generic CRUD screens.

Primary workspaces are:

- command overview;
- stock intelligence;
- stock counts;
- controlled transfers;
- purchase orders;
- receiving desk;
- supplier invoices and three-way matching.

### Server remains authoritative

React decides presentation, navigation, and whether to surface controls based on
the authenticated permission context.

Laravel remains authoritative for:

- organization scope;
- permission enforcement;
- lifecycle transitions;
- idempotency;
- inventory sufficiency;
- maker-checker separation;
- tolerance decisions;
- privileged-reason requirements;
- immutable evidence;
- payment eligibility.

The frontend must not reproduce those business rules as an alternative decision
engine.

### Permission-aware controls

Mutation controls are hidden when the authenticated user lacks the relevant
permission.

This is usability and least-privilege presentation, not a security boundary.
Every request remains protected by server middleware and domain services.

### Privileged actions

Actions such as variance approval, rejection, transfer cancellation, purchase
order decisions, receiving exceptions, and reversals ask for a business reason
through a dedicated privileged-action sheet.

The reason is sent using the established `X-Privileged-Reason` contract.

### Idempotency

Frontend commands that require an idempotency key generate one with
`crypto.randomUUID()` immediately before the request.

The backend remains responsible for detecting replay and payload conflicts.

### Procurement-first receiving

The normal operational receiving workspace uses procurement goods receipts
against approved purchase-order lines.

The lower-level direct inventory receipt API remains available for controlled
future use but is not promoted as the normal staff receiving path.

### No new UI framework

v0.3D extends the existing React, React Router, Axios, Lucide, and Fouskee CSS
design system.

A new component framework is not introduced because the current stack can
deliver the required enterprise interactions without another design/runtime
dependency.

## Consequences

Positive:

- staff screens mirror the evidence model;
- view-only users do not see mutation controls;
- server-side fraud controls remain authoritative;
- the receiving path naturally reinforces purchase-order controls;
- operational navigation is grouped without changing the authentication shell;
- existing brand and responsive behavior remain consistent.

Trade-offs:

- the current master-data product/supplier list helpers fetch up to 100 records;
  large-dataset server-side lookup/search is deferred;
- no frontend test framework is added in this milestone;
- browser validation remains an explicit release gate;
- direct inventory adjustment/reservation screens are deferred until their
  operational personas are defined.

## Rejected alternatives

### Reimplement approval/tolerance logic in React

Rejected because duplicated business rules drift and can weaken controls.

### Show every mutation to every viewer and rely only on 403 responses

Rejected because it creates misleading and noisy user experience.

### Replace the existing design system with a component framework

Rejected because it adds migration cost without solving a current constraint.

### Make direct inventory receipt the primary receiving screen

Rejected because procurement-backed receiving provides stronger commercial and
physical evidence.
