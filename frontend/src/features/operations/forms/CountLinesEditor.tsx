import { Plus, Trash2 } from 'lucide-react'
import type { Product } from '../../master-data/types'
import type { InventoryLot } from '../types'
import { ProductPackagingFields } from '../components/ProductPackagingFields'
import { countLineDraft, type CountLineDraft } from './lineDrafts'

type Props = {
  rows: CountLineDraft[]
  products: Product[]
  lots: InventoryLot[]
  onChange: (rows: CountLineDraft[]) => void
}

export function CountLinesEditor({ rows, products, lots, onChange }: Props) {
  function patch(key: string, values: Partial<CountLineDraft>) {
    onChange(rows.map((row) => row.key === key ? { ...row, ...values } : row))
  }

  return (
    <div className="ops-line-stack">
      {rows.map((row, index) => (
        <div className="ops-line-row" key={row.key}>
          <div className="ops-line-row__heading">
            <strong>Count line {index + 1}</strong>
            {rows.length > 1 ? (
              <button
                className="icon-button"
                type="button"
                aria-label="Remove count line"
                onClick={() => onChange(rows.filter((item) => item.key !== row.key))}
              >
                <Trash2 size={16} />
              </button>
            ) : null}
          </div>
          <div className="ops-line-grid ops-line-grid--3">
            <ProductPackagingFields
              products={products}
              lots={lots}
              productId={row.product_id}
              packagingId=""
              lotId={row.lot_id}
              showLot
              showPackaging={false}
              onProduct={(value) => patch(row.key, { product_id: value })}
              onPackaging={() => undefined}
              onLot={(value) => patch(row.key, { lot_id: value })}
            />
            <label className="field">
              Counted base quantity
              <input
                type="number"
                min="0"
                step="0.000001"
                required
                value={row.counted_quantity_base}
                onChange={(event) => patch(row.key, {
                  counted_quantity_base: event.target.value,
                })}
              />
            </label>
          </div>
          <label className="field">
            Unit cost for newly discovered stock
            <input
              type="number"
              min="0"
              step="0.000001"
              value={row.unit_cost_base}
              placeholder="Only required when new stock has no current cost"
              onChange={(event) => patch(row.key, {
                unit_cost_base: event.target.value,
              })}
            />
          </label>
        </div>
      ))}
      <button
        className="button button--secondary"
        type="button"
        onClick={() => onChange([...rows, countLineDraft()])}
      >
        <Plus size={17} /> Add count line
      </button>
    </div>
  )
}
