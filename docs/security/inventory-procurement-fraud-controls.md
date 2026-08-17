# Inventory and Procurement Fraud-Control Matrix

## Objective

Inventory/procurement controls must make suspicious actions difficult to hide and easy to investigate.

| Risk | Preventive control | Detective evidence | Approval |
|---|---|---|---|
| Fake stock receipt | PO linkage, receiving permission, immutable receipt | receipt actor, PO variance, movement | exception approval |
| Receipt above PO | tolerance rule | quantity variance event | required above tolerance |
| Unauthorized stock adjustment | dedicated permission + reason | adjustment movement + audit | threshold maker-checker |
| Negative stock | blocked by default | override event | break-glass only |
| Double posting | idempotency key + DB unique constraint | duplicate-key attempt logs | none |
| Transfer theft | paired transfer legs, destination confirmation when needed | source/destination evidence | high-risk route policy |
| Backdated stock manipulation | limited posting window | occurred vs posted timestamps | privileged backdate |
| PO price manipulation | approval snapshot / amendment workflow | before/after + approver | reapproval |
| Duplicate supplier invoice | supplier invoice uniqueness | duplicate detection | exception handling |
| Invoice exceeds received goods | three-way match | match variance | exception approval |
| User approves own sensitive action | maker-checker rule | actor/approver comparison | second user |
| Superadmin override abuse | MFA + privileged reason | immutable audit + risk event | break-glass attribution |

## Proposed permission families

### Inventory
- `inventory.view`
- `inventory.locations.manage`
- `inventory.receive`
- `inventory.transfer`
- `inventory.reserve`
- `inventory.count`
- `inventory.adjust`
- `inventory.adjust.approve`
- `inventory.reverse`
- `inventory.backdate`
- `inventory.negative_stock.override`

### Procurement
- `procurement.view`
- `procurement.manage`
- `procurement.submit`
- `procurement.approve`
- `procurement.receive`
- `procurement.invoice`
- `procurement.match`
- `procurement.exception.approve`

## Privileged-reason policy

Require `X-Privileged-Reason` for at least:

- inventory adjustment posting;
- inventory reversal;
- negative-stock override;
- materially backdated posting;
- procurement exception approval;
- Superadmin break-glass approval.

## Maker-checker policy

At minimum, prevent self-approval for:

- stock adjustment above threshold;
- purchase order requiring approval;
- receiving exception above tolerance;
- three-way-match exception.

Thresholds must become organization configuration rather than constants buried in controllers or React components.

## Audit-event direction

Suggested event families:

- `inventory.location.created`
- `inventory.movement.posted`
- `inventory.movement.reversed`
- `inventory.adjustment.approved`
- `inventory.negative_stock.overridden`
- `inventory.reservation.created`
- `inventory.reservation.released`
- `procurement.purchase_order.submitted`
- `procurement.purchase_order.approved`
- `procurement.goods_receipt.posted`
- `procurement.supplier_invoice.recorded`
- `procurement.match.completed`
- `procurement.exception.approved`
