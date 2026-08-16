# Database testing strategy

## Decision

Database-backed feature tests use a dedicated MySQL schema named
`fouskee_concepts_test`.

The production/development schema is never used by the automated test suite.

## Why MySQL instead of SQLite

The Identity/Audit milestone contains MySQL-specific behavior, including
database triggers that make `audit_events` append-only. SQLite in-memory tests
cannot validate those MySQL controls.

## Local configuration

`backend/.env.testing` is generated locally from `backend/.env` and is ignored
by Git because it contains the local database password.

`backend/phpunit.xml` selects:

- `DB_CONNECTION=mysql`
- `DB_DATABASE=fouskee_concepts_test`

The remaining connection credentials come from `.env.testing`.

## Test database lifecycle

The local recovery/bootstrap script:

1. Creates `fouskee_concepts_test` with a privileged local MySQL account.
2. Grants the existing `fouskee_app` account access to the test schema.
3. Runs `migrate:fresh` against the test schema.
4. Installs the append-only audit triggers in the test schema.
5. Runs `php artisan test`.

## Safety invariant

Never point automated tests at `fouskee_concepts`.

A dedicated test schema prevents destructive test setup or rollback behavior
from affecting development data.
