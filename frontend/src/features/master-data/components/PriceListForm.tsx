import { useState, type FormEvent } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { createPriceList } from '../api/pricingApi'
import type { PriceList } from '../types'

type PriceListFormProps = {
  onCreated: (priceList: PriceList) => void
  onCancel: () => void
}

export function PriceListForm({ onCreated, onCancel }: PriceListFormProps) {
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    setSaving(true)
    setError('')

    try {
      const priceList = await createPriceList({
        code: form.get('code'),
        name: form.get('name'),
        currency_code: 'NGN',
        effective_from: form.get('effective_from') || null,
        effective_to: form.get('effective_to') || null,
      })
      onCreated(priceList)
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Unable to create price list.'))
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
          <input name="code" required maxLength={50} autoFocus />
        </label>
        <label className="field">
          Name
          <input name="name" required maxLength={150} />
        </label>
      </div>
      <div className="form-grid">
        <label className="field">
          Effective from
          <input name="effective_from" type="date" />
        </label>
        <label className="field">
          Effective to
          <input name="effective_to" type="date" />
        </label>
      </div>
      <div className="form-actions">
        <button className="button button--secondary" type="button" onClick={onCancel}>
          Cancel
        </button>
        <button className="button button--primary" type="submit" disabled={saving}>
          {saving ? 'Creating…' : 'Create price list'}
        </button>
      </div>
    </form>
  )
}
