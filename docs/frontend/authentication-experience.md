# Authentication experience

## Purpose
Milestone 0.2B2 turns the 0.2B1 Fortify/Sanctum security foundation into the
first production-grade React user journey.

## Routes
- `/login` — username/password login.
- `/two-factor-challenge` — TOTP or recovery-code challenge.
- `/security/mfa/setup` — mandatory password-confirmed MFA enrollment.
- `/` — authenticated shell and command overview.
- `/security/sessions` — session/device review and privileged revocation.

## Security flow
The SPA initializes Sanctum CSRF state before login. Fortify's XHR login response
is inspected for `two_factor`; challenged users are not treated as fully signed in
until `/two-factor-challenge` succeeds.

Users whose `/api/v1/me` response reports `mfa.required=true` and
`mfa.enrolled=false` are routed to MFA setup before the normal shell is available.
The setup confirms the current password, enables TOTP, renders the server-generated
QR code, confirms a six-digit code, and then requires explicit recovery-code
acknowledgment.

The HTTP client retries a single 419 response after refreshing the Sanctum CSRF
cookie. A 401 broadcasts an authentication-expired event that clears React auth
state.

## Permission-aware shell
Navigation entries support an optional permission requirement. A permission is
visible when the user has the exact permission or the Superadmin wildcard `*`.
This lets later domain milestones add navigation without duplicating authorization
logic in components.

## Session security
The sessions page consumes the 0.2B1 server-side session endpoint. Revocation
requires both the user's current password and an `X-Privileged-Reason`, preserving
the privileged-action audit model.

## Deliberate exclusions
- Public self-registration remains disabled.
- Passkeys remain disabled.
- Superadmin bootstrap is still deferred until this UI has passed local and CI
  validation.
- Dark mode and command palette remain part of the wider application-shell design
  work after the authenticated foundation is established.
