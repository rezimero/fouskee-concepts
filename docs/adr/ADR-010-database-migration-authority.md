# ADR-010 — Separate Database Migration Authority

**Status:** Accepted
**Milestone:** v0.4A Sales / POS foundation
**Decision date:** 2026-08-19

## Context

The v0.3 closure reduced the Laravel runtime identity `fouskee_app` to
`SELECT`, `INSERT`, `UPDATE`, and `DELETE`. v0.4 requires new schema changes
without restoring runtime DDL or trigger authority.

## Decision

Use a separate local development identity, `fouskee_migrator`, scoped only to
`fouskee_concepts` and `fouskee_concepts_test`.

Granted: `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `ALTER`, `DROP`,
`INDEX`, `REFERENCES`.

Not granted: `TRIGGER`, `GRANT OPTION`, roles, or server-administration
privileges.

Protected evidence triggers remain controlled by separate privileged hardening
operations. Application `.env` continues to use `fouskee_app`; migration
credentials are injected only for the migration process.

## Consequences

Runtime compromise does not automatically grant schema-change authority.
Migrations become an explicit deployment operation. Future privileges must be
reviewed and added deliberately instead of inheriting schema `ALL PRIVILEGES`.

## Rejected alternatives

Restoring schema `ALL PRIVILEGES` to `fouskee_app` is rejected because it
reverses the v0.3 hardening boundary. Routine use of MySQL root for migrations
is rejected because it materially exceeds ordinary schema-migration needs.
`TRIGGER` is excluded so anti-tamper trigger authority remains separate.
