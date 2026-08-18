import { useState, type FormEvent } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import type { Product } from '../../master-data/types'
import { inventoryApi } from '../api/inventoryApi'
import type { InventoryLocation, InventoryLot, StockCount } from '../types'
import { CountLinesEditor } from './CountLinesEditor'
import { countLineDraft, type CountLineDraft } from './lineDrafts'

type Props = {
  locations: InventoryLocation[]
  lots: InventoryLot[]
  products: Product[]
  onCreated: (count: StockCount) => void
  onCancel: () => void
}

export function StockCountForm({
  locations,
  lots,
  products,
  onCreated,
  onCancel,
}: Props) {
  const [rows, setRows] = useState<CountLineDraft[]>([countLineDraft()])
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    setSaving(true)
    setError('')

    try {
      const count = await inventoryApi.createCount({
        location_id: form.get('location_id'),
        reason_code: form.get('reason_code'),
        notes: form.get('notes') || null,
        lines: rows.map((row) => ({
          product_id: row.product_id,
          lot_id: row.lot_id || null,
          counted_quantity_base: Number(row.counted_quantity_base),
          ...(row.unit_cost_base
            ? { unit_cost_base: Number(row.unit_cost_base) }
            : {}),
        })),
      })
      onCreated(count)
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Unable to create stock count.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="form-stack" onSubmit={(event) => void submit(event)}>
      {error ? <div className="alert alert--danger">{error}</div> : null}
      <div className="form-grid">
        <label className="field">
          Count location
          <select name="location_id" required autoFocus defaultValue="">
            <option value="">Select location</option>
            {locations.map((location) => (
              <option value={location.id} key={location.id}>
                {location.code} · {location.name}
              </option>
            ))}
          </select>
        </label>
        <label className="field">
          Reason
          <select name="reason_code" defaultValue="cycle_count" required>
            <option value="cycle_count">Cycle count</option>
            <option value="periodic_count">Periodic count</option>
            <option value="investigation">Investigation</option>
            <option value="damage_review">Damage review</option>
          </select>
        </label>
      </div>
      <label className="field">
        Notes
        <textarea
          name="notes"
          maxLength={4000}
          placeholder="Physical count context, witnesses, or observations."
        />
      </label>
      <CountLinesEditor
        rows={rows}
        products={products}
        lots={lots}
        onChange={setRows}
      />
      <div className="form-actions">
        <button className="button button--secondary" type="button" onClick={onCancel}>
          Cancel
        </button>
        <button className="button button--primary" type="submit" disabled={saving}>
          {saving ? 'Creating evidence…' : 'Create stock count'}
        </button>
      </div>
    </form>
  )
}
