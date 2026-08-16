# Audit ledger database hardening

The `audit_events` table is append-only by application contract.

Defense in depth is implemented in two layers:

1. `AuditEvent` rejects Eloquent update and delete operations.
2. MySQL triggers reject direct SQL UPDATE and DELETE operations.

The trigger definitions are version-controlled in:

`backend/database/security/audit-events-append-only.sql`

They are intentionally installed by a privileged deployment/operator step rather than by the normal Laravel migration account.

On Windows development machines, run:

`.\scripts\database\harden-audit-ledger.ps1`

The operator wrapper prompts MySQL for the local privileged password. Do not store that password in `.env`, Git, scripts, CI logs, or documentation.

Production must use a controlled deployment credential and must not grant server-administration privileges to the runtime Laravel database account.
