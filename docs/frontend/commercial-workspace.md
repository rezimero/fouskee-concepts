# Commercial master-data workspace

Milestone 0.2C2 turns the 0.2C1 API foundation into an authenticated operating
workspace for customers, suppliers, products, packaging, catalog references, and
price lists.

## UX principles
- Permission-aware navigation and route guards.
- No destructive delete controls.
- Product packaging explicitly shows conversion to a stable base unit.
- Stock quantity is not editable from product screens.
- Price changes require a business reason and display the minimum-price control.
- Drawers keep creation workflows contextual without losing list state.
- Responsive layouts remain usable on warehouse tablets and mobile devices.

## Security
All data mutations continue to execute server-side through Laravel authorization,
organization scoping, validation, and audit services. The React interface never
decides whether a user is ultimately authorized.

Price changes send the required `X-Privileged-Reason` header and are revalidated
by the backend before persistence.
