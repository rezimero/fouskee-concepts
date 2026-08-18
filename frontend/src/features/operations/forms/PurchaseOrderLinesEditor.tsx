import { Plus, Trash2 } from 'lucide-react'
import type { Product } from '../../master-data/types'
import { ProductPackagingFields } from '../components/ProductPackagingFields'
import { purchaseLineDraft, type PurchaseLineDraft } from './lineDrafts'

type Props = {
  rows: PurchaseLineDraft[]
  products: Product[]
  onChange: (rows: PurchaseLineDraft[]) => void
}

export function PurchaseOrderLinesEditor({ rows, products, onChange }: Props) {
  function patch(key: string, values: Partial<PurchaseLineDraft>) {
    onChange(rows.map((row) => row.key === key ? { ...row, ...values } : row))
  }

  return (
    <div className="ops-line-stack">
      {rows.map((row, index) => (
        <div className="ops-line-row" key={row.key}>
          <div className="ops-line-row__heading">
            <strong>Purchase line {index + 1}</strong>
            {rows.length > 1 ? (
              <button
                className="icon-button"
                type="button"
                aria-label="Remove purchase line"
                onClick={() => onChange(rows.filter((item) => item.key !== row.key))}
              >
                <Trash2 size={16} />
              </button>
            ) : null}
          </div>
          <div className="ops-line-grid ops-line-grid--4">
            <ProductPackagingFields
              products={products}
              productId={row.product_id}
              packagingId={row.packaging_id}
              onProduct={(value) => patch(row.key, { product_id: value })}
              onPackaging={(value) => patch(row.key, { packaging_id: value })}
            />
            <label className="field">
              Quantity
              <input
                type="number"
                min="0.000001"
                step="0.000001"
                required
                value={row.quantity}
                onChange={(event) => patch(row.key, { quantity: event.target.value })}
              />
            </label>
            <label className="field">
              Unit price (NGN)
              <input
                type="number"
                min="0"
                step="0.01"
                required
                value={row.unit_price}
                onChange={(event) => patch(row.key, { unit_price: event.target.value })}
              />
            </label>
          </div>
        </div>
      ))}
      <button
        className="button button--secondary"
        type="button"
        onClick={() => onChange([...rows, purchaseLineDraft()])}
      >
        <Plus size={17} /> Add purchase line
      </button>
    </div>
  )
}
