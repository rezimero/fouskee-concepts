import { useState, type FormEvent } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { createCustomer } from '../api/masterDataApi'
import type { Customer, PriceList } from '../types'

type CustomerFormProps = {
  priceLists: PriceList[]
  onCreated: (customer: Customer) => void
  onCancel: () => void
}

export function CustomerForm({
  priceLists,
  onCreated,
  onCancel,
}: CustomerFormProps) {
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    setSaving(true)
    setError('')

    try {
      const customer = await createCustomer({
        segment: form.get('segment'),
        display_name: form.get('display_name'),
        legal_name: form.get('legal_name') || null,
        phone: form.get('phone') || null,
        email: form.get('email') || null,
        credit_limit: Number(form.get('credit_limit') || 0),
        price_list_id: form.get('price_list_id') || null,
      })
      onCreated(customer)
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Unable to create customer.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="form-stack" onSubmit={(event) => void submit(event)}>
      {error ? <div className="alert alert--danger">{error}</div> : null}
      <div className="form-grid">
        <label className="field">
          Customer name
          <input name="display_name" required maxLength={200} autoFocus />
        </label>
        <label className="field">
          Segment
          <select name="segment" defaultValue="wholesale">
            <option value="wholesale">Wholesale</option>
            <option value="retail">Retail</option>
            <option value="hybrid">Wholesale + retail</option>
          </select>
        </label>
      </div>
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
      <div className="form-grid">
        <label className="field">
          Credit limit (NGN)
          <input name="credit_limit" type="number" min="0" step="0.01" defaultValue="0" />
        </label>
        <label className="field">
          Default price list
          <select name="price_list_id" defaultValue="">
            <option value="">None</option>
            {priceLists.map((item) => (
              <option key={item.id} value={item.id}>{item.name}</option>
            ))}
          </select>
        </label>
      </div>
      <div className="form-actions">
        <button className="button button--secondary" type="button" onClick={onCancel}>
          Cancel
        </button>
        <button className="button button--primary" type="submit" disabled={saving}>
          {saving ? 'Creating…' : 'Create customer'}
        </button>
      </div>
    </form>
  )
}
