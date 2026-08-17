# ADR-004 — Commercial master-data boundaries

## Status
Accepted for Milestone 0.2C1.

## Decision
Customers, suppliers, catalog, units/packaging, and price lists are organization
scoped and remain inside the Laravel modular monolith.

Products do not carry mutable stock quantities. Inventory remains a separate,
ledger-based domain. Product packaging stores a conversion factor to the product
base unit so rice can be purchased or sold as bags while stock later remains
expressible in a stable base quantity.

Standard units may be global system records. Brands, categories, products,
customers, suppliers, and price lists are organization records.

Pricing stores both selling price and an optional minimum selling price. Changing
a package price is a privileged audited action requiring a business reason.

## Integrity
No delete API is introduced for these records. Lifecycle removal will use
explicit archival/status transitions so historical transactions never lose their
commercial references.

## Consequences
Inventory, purchasing, sales, tax, and accounting can reference stable public IDs
and organization-scoped master data without duplicating product or party models.
