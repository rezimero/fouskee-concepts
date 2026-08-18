import type { PurchaseOrderLine } from '../types'

export type ReceiptDraft = {
  line_id: string
  quantity: string
  lot_code: string
  expiry_date: string
}

type Props = {
  lines: PurchaseOrderLine[]
  rows: ReceiptDraft[]
  onPatch: (id: string, values: Partial<ReceiptDraft>) => void
}

export function ReceiptOrderLines({ lines, rows, onPatch }: Props) {
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
                Ordered {line.quantity} {line.packaging_name ?? 'base units'}
              </small>
            </div>
            <div className="ops-line-grid ops-line-grid--3">
              <label className="field">
                Received quantity
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
                Lot / batch code
                <input
                  value={row.lot_code}
                  maxLength={100}
                  onChange={(event) => onPatch(line.id, {
                    lot_code: event.target.value,
                  })}
                />
              </label>
              <label className="field">
                Expiry date
                <input
                  type="date"
                  value={row.expiry_date}
                  onChange={(event) => onPatch(line.id, {
                    expiry_date: event.target.value,
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
