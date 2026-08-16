# ADR-001: Start as a Modular Monolith

## Status
Accepted

## Decision
Build the initial platform as a modular monolith using Laravel with explicit domain boundaries.

## Reasons
- Lower operational complexity than microservices.
- Strong transaction support for sales, inventory, payments and accounting.
- Faster development and easier local setup.
- Clear module boundaries preserve future extraction options.
- Avoids premature distributed-system complexity.

## Consequence
Developers must respect domain boundaries and avoid unnecessary cross-domain coupling.
