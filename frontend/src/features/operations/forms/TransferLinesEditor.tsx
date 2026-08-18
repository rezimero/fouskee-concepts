import { Plus, Trash2 } from 'lucide-react'
import type { Product } from '../../master-data/types'
import { ProductPackagingFields } from '../components/ProductPackagingFields'
import type { InventoryLot } from '../types'
import {
  productQuantityDraft,
  type ProductQuantityDraft,
} from './lineDrafts'

type Props = {
  rows: ProductQuantityDraft[]
  products: Product[]
  lots: InventoryLot[]
  onChange: (rows: ProductQuantityDraft[]) => void
}

export function TransferLinesEditor({
  rows,
  products,
  lots,
  onChange,
}: Props) {
  function patch(key: string, values: Partial<ProductQuantityDraft>) {
    onChange(rows.map((row) => row.key === key ? { ...row, ...values } : row))
  }

  return (
    <div className="ops-line-stack">
      {rows.map((row, index) => (
        <div className="ops-line-row" key={row.key}>
          <div className="ops-line-row__heading">
            <strong>Transfer line {index + 1}</strong>
            {rows.length > 1 ? (
              <button
                className="icon-button"
                type="button"
                aria-label="Remove transfer line"
                onClick={() => onChange(rows.filter((item) => item.key !== row.key))}
              >
                <Trash2 size={16} />
              </button>
            ) : null}
          </div>
          <div className="ops-line-grid ops-line-grid--4">
            <ProductPackagingFields
              products={products}
              lots={lots}
              productId={row.product_id}
              packagingId={row.packaging_id}
              lotId={row.lot_id}
              showLot
              onProduct={(value) => patch(row.key, { product_id: value })}
              onPackaging={(value) => patch(row.key, { packaging_id: value })}
              onLot={(value) => patch(row.key, { lot_id: value })}
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
          </div>
        </div>
      ))}
      <button
        className="button button--secondary"
        type="button"
        onClick={() => onChange([...rows, productQuantityDraft()])}
      >
        <Plus size={17} /> Add transfer line
      </button>
    </div>
  )
}
