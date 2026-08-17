import { useEffect, useMemo, useState, type FormEvent } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { listProducts } from '../api/masterDataApi'
import { listPriceItems, setPriceItem } from '../api/pricingApi'
import type { PriceList, PriceListItem, Product } from '../types'

type PriceEditorProps = {
  priceList: PriceList
}

export function PriceEditor({ priceList }: PriceEditorProps) {
  const [products, setProducts] = useState<Product[]>([])
  const [items, setItems] = useState<PriceListItem[]>([])
  const [packagingId, setPackagingId] = useState('')
  const [error, setError] = useState('')
  const [saved, setSaved] = useState('')
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    let cancelled = false
    void Promise.all([listProducts(), listPriceItems(priceList.id)])
      .then(([productData, itemData]) => {
        if (cancelled) return
        setProducts(productData)
        setItems(itemData)
      })
      .catch((caught) => {
        if (!cancelled) setError(requestErrorMessage(caught))
      })
    return () => { cancelled = true }
  }, [priceList.id])

  const packagings = useMemo(
    () => products.flatMap((product) => product.packagings.map((packaging) => ({
      ...packaging,
      product_name: product.name,
    }))),
    [products],
  )

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const formElement = event.currentTarget
    const form = new FormData(formElement)
    setSaving(true)
    setError('')
    setSaved('')

    try {
      await setPriceItem(
        priceList.id,
        packagingId,
        {
          selling_price: Number(form.get('selling_price')),
          minimum_selling_price: form.get('minimum_selling_price')
            ? Number(form.get('minimum_selling_price'))
            : null,
          maximum_discount_percent: form.get('maximum_discount_percent')
            ? Number(form.get('maximum_discount_percent'))
            : null,
        },
        String(form.get('reason') ?? ''),
      )
      const refreshed = await listPriceItems(priceList.id)
      setItems(refreshed)
      setSaved('Price control saved and audit reason recorded.')
      formElement.reset()
      setPackagingId('')
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Unable to save price control.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="form-stack">
      <div className="price-summary">
        <strong>{priceList.name}</strong>
        <small>{items.length} package prices configured · {priceList.currency_code}</small>
      </div>
      {error ? <div className="alert alert--danger">{error}</div> : null}
      {saved ? <div className="alert alert--success">{saved}</div> : null}
      <div className="price-item-list">
        {items.map((item) => (
          <div className="price-item" key={item.product_packaging_id}>
            <span><strong>{item.product_name}</strong><small>{item.packaging_name}</small></span>
            <span><strong>₦{Number(item.selling_price).toLocaleString()}</strong>
              <small>Floor {item.minimum_selling_price
                ? `₦${Number(item.minimum_selling_price).toLocaleString()}`
                : 'not set'}</small>
            </span>
          </div>
        ))}
      </div>
      <form className="form-stack form-stack--nested" onSubmit={(event) => void submit(event)}>
        <label className="field">
          Product package
          <select
            required
            value={packagingId}
            onChange={(event) => setPackagingId(event.target.value)}
          >
            <option value="">Select package</option>
            {packagings.map((item) => (
              <option key={item.id} value={item.id}>
                {item.product_name} · {item.name}
              </option>
            ))}
          </select>
        </label>
        <div className="form-grid">
          <label className="field">
            Selling price
            <input name="selling_price" type="number" min="0.01" step="0.01" required />
          </label>
          <label className="field">
            Minimum selling price
            <input name="minimum_selling_price" type="number" min="0.01" step="0.01" />
          </label>
        </div>
        <label className="field">
          Maximum discount %
          <input name="maximum_discount_percent" type="number" min="0" max="100" step="0.01" />
        </label>
        <label className="field">
          Business reason
          <textarea
            name="reason"
            minLength={10}
            maxLength={500}
            required
            placeholder="Why is this price being established or changed?"
          />
        </label>
        <button className="button button--primary" type="submit" disabled={saving || !packagingId}>
          {saving ? 'Saving…' : 'Save audited price'}
        </button>
      </form>
    </div>
  )
}
