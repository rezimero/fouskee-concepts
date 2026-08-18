import type { PurchaseOrderLine } from '../types'

export type InvoiceDraft = {
  line_id: string
  quantity: string
  unit_price: string
}

type Props = {
  lines: PurchaseOrderLine[]
  rows: InvoiceDraft[]
  onPatch: (id: string, values: Partial<InvoiceDraft>) => void
}

export function InvoiceOrderLines({ lines, rows, onPatch }: Props) {
  return (
    <div className="ops-line-stack">
      {lines.map((line) => {
        const row = rows.find((item) => item.line_id === line.id)
        if (!row) return null

        return (
          <div className="ops-line-row" key={line.id}>
            <div className="ops-line-row__heading">
              <strong>{line.sku} · {line.product_name}</strong>
              <small>
                PO price {line.unit_price} · ordered {line.quantity}
              </small>
            </div>
            <div className="form-grid">
              <label className="field">
                Invoiced quantity
                <input
                  type="number"
                  min="0"
                  step="0.000001"
                  value={row.quantity}
                  onChange={(event) => onPatch(line.id, {
                    quantity: event.target.value,
                  })}
                />
              </label>
              <label className="field">
                Supplier unit price
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  value={row.unit_price}
                  onChange={(event) => onPatch(line.id, {
                    unit_price: event.target.value,
                  })}
                />
              </label>
            </div>
          </div>
        )
      })}
    </div>
  )
}
