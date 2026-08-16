# Privileged Access Baseline

The system will contain a Superadmin with maximum application-level authority.

- Mandatory MFA for privileged accounts.
- Sensitive Superadmin actions are audited.
- Financial records are not silently deleted.
- Break-glass actions require an explicit reason and critical audit event.
- Impersonation records both the real actor and impersonated user.
- Secrets are never committed to Git.
- Authorization is enforced server-side.

Principle: Superadmin means maximum authority, not invisibility.
