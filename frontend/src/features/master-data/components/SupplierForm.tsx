import { useState, type FormEvent } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { createSupplier } from '../api/masterDataApi'
import type { Supplier } from '../types'

type SupplierFormProps = {
  onCreated: (supplier: Supplier) => void
  onCancel: () => void
}

export function SupplierForm({ onCreated, onCancel }: SupplierFormProps) {
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    setSaving(true)
    setError('')

    try {
      const supplier = await createSupplier({
        display_name: form.get('display_name'),
        legal_name: form.get('legal_name') || null,
        phone: form.get('phone') || null,
        email: form.get('email') || null,
        tax_identifier: form.get('tax_identifier') || null,
      })
      onCreated(supplier)
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Unable to create supplier.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="form-stack" onSubmit={(event) => void submit(event)}>
      {error ? <div className="alert alert--danger">{error}</div> : null}
      <label className="field">
        Supplier name
        <input name="display_name" required maxLength={200} autoFocus />
      </label>
      <label className="field">
        Legal name
        <input name="legal_name" maxLength={200} />
      </label>
      <div className="form-grid">
        <label className="field">
          Phone
          <input name="phone" maxLength={50} />
        </label>
        <label className="field">
          Email
          <input name="email" type="email" maxLength={255} />
        </label>
      </div>
      <label className="field">
        Tax identifier
        <input name="tax_identifier" maxLength={100} />
      </label>
      <div className="form-actions">
        <button className="button button--secondary" type="button" onClick={onCancel}>
          Cancel
        </button>
        <button className="button button--primary" type="submit" disabled={saving}>
          {saving ? 'Creating…' : 'Create supplier'}
        </button>
      </div>
    </form>
  )
}
