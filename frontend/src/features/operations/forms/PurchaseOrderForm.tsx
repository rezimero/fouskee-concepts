import { useState, type FormEvent } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import type { Product, Supplier } from '../../master-data/types'
import { procurementApi } from '../api/procurementApi'
import type { PurchaseOrder } from '../types'
import { PurchaseOrderLinesEditor } from './PurchaseOrderLinesEditor'
import { purchaseLineDraft, type PurchaseLineDraft } from './lineDrafts'

type Props = {
  suppliers: Supplier[]
  products: Product[]
  onCreated: (order: PurchaseOrder) => void
  onCancel: () => void
}

export function PurchaseOrderForm({
  suppliers,
  products,
  onCreated,
  onCancel,
}: Props) {
  const [rows, setRows] = useState<PurchaseLineDraft[]>([purchaseLineDraft()])
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    setSaving(true)
    setError('')

    try {
      const order = await procurementApi.createPurchaseOrder({
        supplier_id: form.get('supplier_id'),
        expected_delivery_date: form.get('expected_delivery_date') || null,
        notes: form.get('notes') || null,
        lines: rows.map((row) => ({
          product_id: row.product_id,
          packaging_id: row.packaging_id || null,
          quantity: Number(row.quantity),
          unit_price: Number(row.unit_price),
        })),
      })
      onCreated(order)
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Unable to create purchase order.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="form-stack" onSubmit={(event) => void submit(event)}>
      {error ? <div className="alert alert--danger">{error}</div> : null}
      <div className="form-grid">
        <label className="field">
          Supplier
          <select name="supplier_id" required autoFocus defaultValue="">
            <option value="">Select supplier</option>
            {suppliers.map((supplier) => (
              <option value={supplier.id} key={supplier.id}>
                {supplier.number} · {supplier.display_name}
              </option>
            ))}
          </select>
        </label>
        <label className="field">
          Expected delivery
          <input name="expected_delivery_date" type="date" />
        </label>
      </div>
      <label className="field">
        Notes
        <textarea
          name="notes"
          maxLength={4000}
          placeholder="Commercial terms or delivery instructions."
        />
      </label>
      <PurchaseOrderLinesEditor
        rows={rows}
        products={products}
        onChange={setRows}
      />
      <div className="form-actions">
        <button className="button button--secondary" type="button" onClick={onCancel}>
          Cancel
        </button>
        <button className="button button--primary" type="submit" disabled={saving}>
          {saving ? 'Creating draft…' : 'Create purchase order'}
        </button>
      </div>
    </form>
  )
}
