# Approval Engine Foundation

## Purpose

The v0.3B approval foundation supplies maker-checker evidence without embedding
approval logic directly in controllers.

`approval_requests` stores:
- organization;
- subject type and public identity;
- requested action;
- requester;
- status;
- decision maker;
- decision timestamps and reason;
- non-authoritative context JSON.

## Invariant

A pending approval cannot be approved or rejected by its requester.

The owning domain remains responsible for the business state transition after
approval. Approval does not directly mutate inventory or procurement records.

## Current consumer

Stock counts use approval when the absolute variance value exceeds the
organization inventory policy threshold.

## Future consumers

The design is intended for:
- purchase-order approval;
- goods-receipt exception approval;
- three-way-match exception approval;
- other maker-checker workflows.

## Audit

Request, approval, and rejection actions are written to the immutable audit
ledger with actor attribution.
