import { useState, type FormEvent } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import type { Product } from '../../master-data/types'
import { inventoryApi } from '../api/inventoryApi'
import type {
  InventoryLocation,
  InventoryLot,
  StockTransfer,
} from '../types'
import { TransferLinesEditor } from './TransferLinesEditor'
import {
  productQuantityDraft,
  type ProductQuantityDraft,
} from './lineDrafts'

type Props = {
  locations: InventoryLocation[]
  lots: InventoryLot[]
  products: Product[]
  onCreated: (transfer: StockTransfer) => void
  onCancel: () => void
}

export function StockTransferForm({
  locations,
  lots,
  products,
  onCreated,
  onCancel,
}: Props) {
  const [rows, setRows] = useState<ProductQuantityDraft[]>([
    productQuantityDraft(),
  ])
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    setSaving(true)
    setError('')

    try {
      const transfer = await inventoryApi.createTransfer({
        source_location_id: form.get('source_location_id'),
        destination_location_id: form.get('destination_location_id'),
        notes: form.get('notes') || null,
        lines: rows.map((row) => ({
          product_id: row.product_id,
          packaging_id: row.packaging_id || null,
          lot_id: row.lot_id || null,
          quantity: Number(row.quantity),
        })),
      })
      onCreated(transfer)
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Unable to create stock transfer.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="form-stack" onSubmit={(event) => void submit(event)}>
      {error ? <div className="alert alert--danger">{error}</div> : null}
      <div className="form-grid">
        <label className="field">
          Source location
          <select name="source_location_id" required autoFocus defaultValue="">
            <option value="">Select source</option>
            {locations.map((location) => (
              <option value={location.id} key={location.id}>
                {location.code} · {location.name}
              </option>
            ))}
          </select>
        </label>
        <label className="field">
          Destination location
          <select name="destination_location_id" required defaultValue="">
            <option value="">Select destination</option>
            {locations.map((location) => (
              <option value={location.id} key={location.id}>
                {location.code} · {location.name}
              </option>
            ))}
          </select>
        </label>
      </div>
      <label className="field">
        Transfer notes
        <textarea
          name="notes"
          maxLength={4000}
          placeholder="Dispatch context or physical handling instructions."
        />
      </label>
      <TransferLinesEditor
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
          {saving ? 'Creating transfer…' : 'Create controlled transfer'}
        </button>
      </div>
    </form>
  )
}
