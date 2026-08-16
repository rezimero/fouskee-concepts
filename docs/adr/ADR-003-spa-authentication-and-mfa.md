# ADR-003: SPA authentication and mandatory MFA

## Status
Accepted for Milestone 0.2B1.

## Context
Fouskee Concepts has a separate React/Vite SPA and Laravel API. Authentication
must resist credential theft and session abuse without inventing custom
cryptography or bearer-token storage in browser JavaScript.

## Decision
- Use Laravel Sanctum for first-party SPA session-cookie authentication.
- Use Laravel Fortify as the headless login and TOTP two-factor backend.
- Disable public self-registration. Staff identities remain administrator-managed.
- Require confirmed TOTP enrollment for users whose `mfa_required` flag is set.
- Superadmin bootstrap continues to set `mfa_required=true`.
- Throttle login and two-factor challenges by identity/session plus IP address.
- Reject suspended and disabled accounts during login and on authenticated API requests.
- Expose `/api/v1/me` as the SPA identity/authorization bootstrap endpoint.
- Use explicit permission middleware for privileged routes.
- Require a human-readable reason for privileged mutations that opt into
  `privileged.reason`.
- Store browser sessions server-side and expose only HMAC references, never raw
  session identifiers.

## Consequences
- Browser authentication uses HttpOnly session cookies and CSRF protection rather
  than localStorage bearer tokens.
- Fortify owns TOTP secret and recovery-code mechanics.
- MFA setup remains possible immediately after login, while business routes may
  require confirmed MFA.
- Future mobile/third-party API tokens can use Sanctum abilities without changing
  the first-party SPA model.
