# Procurement Domain Model — v0.3C

## Aggregate boundaries

### Purchase Order

`purchase_orders` owns `purchase_order_lines`.

Lines snapshot commercial terms. Once the parent leaves draft, application and
database controls freeze line edits.

### Goods Receipt

`goods_receipts` owns `goods_receipt_lines`.

A receipt references one approved purchase order and one inventory location.
Posted receipt lines feed the inventory ledger through a source-referenced
receipt movement.

### Supplier Invoice

`supplier_invoices` owns `supplier_invoice_lines`.

Invoice number duplicate protection is scoped by organization + supplier using
a normalized invoice-number key.

### Three-Way Match

`procurement_match_runs` owns `procurement_match_lines`.

Runs are append-only evidence. The supplier invoice points to the latest run,
but old runs remain available.

### Procurement Policy

`procurement_policy_versions` is effective-dated configuration.

Operational documents reference the policy version used for their decision.

## State transitions

Purchase order:

`draft -> submitted -> approved -> partially_received -> received`

Alternative terminal transitions:

`draft/submitted/approved -> cancelled`

Goods receipt:

`pending_posting -> posted`

or:

`pending_exception -> posted`

or:

`pending_exception -> rejected`

Correction:

`posted -> reversed`

Supplier invoice:

`recorded -> matched`

`recorded -> within_tolerance`

`recorded -> exception -> exception_approved`

A later match run may replace the supplier invoice's current match status while
preserving all earlier match evidence.

## Cross-domain contracts

Procurement may call Inventory only through inventory application services.

Goods receipt movement source reference:

- type: `procurement.goods_receipt`
- public id: goods receipt public id

Raw inventory reversal refuses procurement-owned receipt movements. The
procurement reversal workflow is the owning correction path.

Generic approval records authorization evidence but does not own procurement
state transitions.
