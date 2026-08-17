import { useState, type FormEvent } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { createProduct } from '../api/masterDataApi'
import type { CatalogReferences, Product } from '../types'
import { PackagingEditor } from './PackagingEditor'
import {
  newPackagingDraft,
  type PackagingDraft,
} from './packagingDraft'

type ProductFormProps = {
  references: CatalogReferences
  onCreated: (product: Product) => void
  onCancel: () => void
}

export function ProductForm({
  references,
  onCreated,
  onCancel,
}: ProductFormProps) {
  const [packagings, setPackagings] = useState<PackagingDraft[]>([
    newPackagingDraft(1),
  ])
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    setSaving(true)
    setError('')

    try {
      const product = await createProduct({
        sku: form.get('sku'),
        name: form.get('name'),
        description: form.get('description') || null,
        brand_id: form.get('brand_id') || null,
        category_id: form.get('category_id') || null,
        base_unit_id: form.get('base_unit_id'),
        packagings: packagings.map((row) => ({
          unit_id: row.unit_id,
          code: row.code,
          name: row.name,
          barcode: row.barcode || null,
          conversion_to_base: Number(row.conversion_to_base),
          is_default_sale: row.is_default_sale,
          is_default_purchase: row.is_default_purchase,
        })),
      })
      onCreated(product)
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Unable to create product.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="form-stack" onSubmit={(event) => void submit(event)}>
      {error ? <div className="alert alert--danger">{error}</div> : null}
      <div className="form-grid">
        <label className="field">
          SKU
          <input name="sku" required maxLength={80} autoFocus />
        </label>
        <label className="field">
          Product name
          <input name="name" required maxLength={200} />
        </label>
      </div>
      <div className="form-grid">
        <label className="field">
          Brand
          <select name="brand_id" defaultValue="">
            <option value="">Unbranded</option>
            {references.brands.map((item) => (
              <option key={item.id} value={item.id}>{item.name}</option>
            ))}
          </select>
        </label>
        <label className="field">
          Category
          <select name="category_id" defaultValue="">
            <option value="">Uncategorized</option>
            {references.categories.map((item) => (
              <option key={item.id} value={item.id}>{item.name}</option>
            ))}
          </select>
        </label>
      </div>
      <label className="field">
        Base stock unit
        <select name="base_unit_id" required defaultValue="">
          <option value="">Select base unit</option>
          {references.units.map((unit) => (
            <option key={unit.id} value={unit.id}>
              {unit.name} ({unit.symbol})
            </option>
          ))}
        </select>
      </label>
      <label className="field">
        Description
        <textarea name="description" maxLength={4000} />
      </label>
      <PackagingEditor
        rows={packagings}
        units={references.units}
        onChange={setPackagings}
      />
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
          {saving ? 'Creating…' : 'Create product'}
        </button>
      </div>
    </form>
  )
}
