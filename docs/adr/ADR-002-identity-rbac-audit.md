# ADR-002: Identity, scoped RBAC, and append-only audit

## Status
Accepted for Milestone 0.2A.

## Context
Fouskee Concepts requires strong staff controls, organization-aware authorization, and a Superadmin who has maximum application authority without the ability to silently erase audit history.

MySQL binary logging is enabled in the local environment. Trigger creation therefore crosses a database-administration privilege boundary that the normal Laravel application account should not be granted.

## Decision
1. Keep numeric database primary keys for efficient internal joins.
2. Add ULID `public_id` values for externally safe identifiers.
3. Model permissions as explicit codes grouped by domain.
4. Model roles as either `global` or `organization` scoped.
5. Treat `superadmin` as a global system role. It bypasses named permission checks only while the user is active.
6. Store role assignments separately with a deterministic `scope_key`.
7. Store security/business audit events in an append-only `audit_events` ledger.
8. Reject audit mutation in Eloquent and with MySQL UPDATE/DELETE prevention triggers.
9. Keep trigger installation outside ordinary Laravel migrations. A privileged deployment/operator step installs the source-controlled trigger definitions.
10. Require MFA for Superadmin accounts; privileged UI/API access remains disabled until MFA enrollment is implemented.

## Consequences
- Authorization remains application-owned and can evolve without a third-party RBAC schema.
- Organization scope is explicit and testable.
- Superadmin power remains visible and attributable.
- Routine application migrations do not receive server-administration privileges merely to create triggers.
- Trigger SQL is version-controlled at `backend/database/security/audit-events-append-only.sql`.
- Local hardening is applied with `scripts/database/harden-audit-ledger.ps1`.
- Production deployment must execute the hardening SQL with a controlled deployment credential, never a runtime application credential.
- Off-site/WORM replication and audit signing remain future defense-in-depth increments.
