# Inventory Domain Model — v0.3

## Authoritative model

The authoritative stock record is the sum of immutable posted movement lines.

```text
Organization
  └── InventoryLocation
        └── InventoryMovementLine (+/- base quantity)
              ├── Product
              ├── ProductPackaging snapshot
              └── InventoryLot (optional)

InventoryMovement
  ├── movement type
  ├── occurred/posted timestamps
  ├── idempotency key
  ├── source reference
  ├── reversal link
  └── InventoryMovementLine[]

InventoryBalance (derived/rebuildable)
InventoryReservation (commitment, not physical movement)
```

## Planned entities

### InventoryLocation

Organization-scoped stock-holding location.

Recommended fields:

- `public_id`;
- `organization_id`;
- `code`;
- `name`;
- `type`: warehouse, store, transit;
- `status`: active, inactive, archived;
- address/metadata as later required.

Location code is unique per organization.

### InventoryMovement

Immutable posted ledger header.

Recommended fields:

- `public_id`;
- `organization_id`;
- `movement_number`;
- `movement_type`;
- `occurred_at`;
- `posted_at`;
- `posted_by_user_id`;
- `idempotency_key`;
- `source_reference_type`;
- `source_reference_public_id`;
- `reversal_of_movement_id`;
- `business_reason`;
- `metadata` JSON for non-authoritative integration context.

Unique:

- `(organization_id, movement_number)`;
- `(organization_id, idempotency_key)`.

### InventoryMovementLine

Immutable physical quantity delta at one location.

Recommended fields:

- `inventory_movement_id`;
- `inventory_location_id`;
- `product_id`;
- `product_packaging_id` nullable;
- `inventory_lot_id` nullable;
- `entered_quantity`;
- `conversion_to_base_snapshot`;
- `quantity_base` signed decimal;
- `unit_cost_snapshot` nullable;
- `inventory_value_delta` nullable.

Use high-precision decimals. Never use floating point.

### InventoryBalance

Derived projection for operational reads and posting locks.

Recommended key:

`organization + location + product + lot(optional)`

Recommended values:

- `quantity_on_hand_base`;
- `inventory_value`;
- `weighted_average_unit_cost`;
- `version`;
- projection timestamps.

This table is rebuildable from the movement ledger.

### InventoryReservation

Commitment against available stock.

Recommended fields:

- organization;
- location;
- product;
- optional lot;
- source type/public id;
- reserved quantity in base units;
- status: active, released, fulfilled, expired;
- expires_at;
- actor and timestamps.

Reservation actions are never represented as physical ledger quantity deltas.

### InventoryLot

Optional lot/batch identity.

Recommended fields:

- organization;
- product;
- lot/batch code;
- received/manufactured date where relevant;
- expiry date nullable;
- supplier context nullable;
- status.

## Quantity invariant

For each product/location/lot balance:

`on_hand = SUM(posted movement line quantity_base)`

`available = on_hand - SUM(active reservation quantity_base)`

## Transfer invariant

A posted internal transfer conserves base quantity:

`SUM(transfer movement quantity_base across all locations) = 0`

The source leg is negative and destination leg is positive.

## Reversal invariant

A reversal exactly negates the original movement's quantity and inventory-value effects while keeping both records visible.

## Packaging invariant

A transaction stores the package conversion used at posting time.

Future master-data changes never reinterpret old quantities.

## Default packaging hardening

Before sales/procurement depends on defaults, enforce at most:

- one default sale packaging per product;
- one default purchase packaging per product.

Prefer service-level validation plus a database-safe constraint strategy.
