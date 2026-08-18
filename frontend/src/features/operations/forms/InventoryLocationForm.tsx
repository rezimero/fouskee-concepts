import { useState, type FormEvent } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { inventoryApi } from '../api/inventoryApi'
import type {
  InventoryLocation,
  InventoryLocationType,
} from '../types'

type Props = {
  onCreated: (location: InventoryLocation) => void
  onCancel: () => void
}

export function InventoryLocationForm({ onCreated, onCancel }: Props) {
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    setSaving(true)
    setError('')

    try {
      const location = await inventoryApi.createLocation({
        code: String(form.get('code') ?? '').trim(),
        name: String(form.get('name') ?? '').trim(),
        type: String(form.get('type')) as InventoryLocationType,
      })
      onCreated(location)
    } catch (caught) {
      setError(requestErrorMessage(
        caught,
        'Unable to create inventory location.',
      ))
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="form-stack" onSubmit={(event) => void submit(event)}>
      {error ? <div className="alert alert--danger">{error}</div> : null}
      <div className="form-grid">
        <label className="field">
          Code
          <input
            name="code"
            required
            maxLength={60}
            autoFocus
            placeholder="e.g. UAT-WH-A"
          />
        </label>
        <label className="field">
          Name
          <input
            name="name"
            required
            maxLength={160}
            placeholder="e.g. Main Warehouse"
          />
        </label>
      </div>
      <label className="field">
        Location type
        <select name="type" required defaultValue="warehouse">
          <option value="warehouse">Warehouse</option>
          <option value="store">Store / shop</option>
          <option value="transit">Transit</option>
        </select>
      </label>
      <div className="control-notice">
        <div>
          <strong>Controlled transit code</strong>
          <p>
            __TRANSIT__ is reserved for the system transfer workflow.
            Normal warehouse codes should describe physical operating sites.
          </p>
        </div>
      </div>
      <div className="form-actions">
        <button
          className="button button--secondary"
          type="button"
          onClick={onCancel}
        >
          Cancel
        </button>
        <button
          className="button button--primary"
          type="submit"
          disabled={saving}
        >
          {saving ? 'Creating location…' : 'Create location'}
        </button>
      </div>
    </form>
  )
}
