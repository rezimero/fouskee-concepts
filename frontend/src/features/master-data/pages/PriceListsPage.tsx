import { BadgeDollarSign, Plus, ShieldCheck } from 'lucide-react'
import { useEffect, useState } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { listPriceLists } from '../api/pricingApi'
import { DataState } from '../components/DataState'
import { PriceEditor } from '../components/PriceEditor'
import { PriceListForm } from '../components/PriceListForm'
import { Sheet } from '../components/Sheet'
import { WorkspaceHeader } from '../components/WorkspaceHeader'
import type { PriceList } from '../types'

export function PriceListsPage() {
  const [priceLists, setPriceLists] = useState<PriceList[]>([])
  const [creating, setCreating] = useState(false)
  const [editing, setEditing] = useState<PriceList | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let cancelled = false
    void listPriceLists()
      .then((data) => {
        if (!cancelled) setPriceLists(data)
      })
      .catch((caught) => {
        if (!cancelled) setError(requestErrorMessage(caught))
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => { cancelled = true }
  }, [])

  return (
    <div className="page-stack">
      <WorkspaceHeader
        eyebrow="Margin protection"
        title="Price lists"
        description="Separate wholesale and retail pricing with explicit floors, discount limits, and audit reasons."
        actions={
          <button className="button button--primary" type="button" onClick={() => setCreating(true)}>
            <Plus size={17} /> New price list
          </button>
        }
      />
      <DataState
        loading={loading}
        error={error}
        empty={priceLists.length === 0}
        emptyMessage="No price lists exist yet."
      >
        <div className="price-list-grid">
          {priceLists.map((item) => (
            <article className="price-list-card" key={item.id}>
              <div className="product-card__top">
                <span className="record-card__icon"><BadgeDollarSign size={19} /></span>
                <span className="status-badge">{item.status}</span>
              </div>
              <strong>{item.name}</strong>
              <small>{item.code} · {item.currency_code}</small>
              <div className="price-list-card__guard">
                <ShieldCheck size={16} />
                Price changes require an attributable business reason.
              </div>
              <button
                className="button button--secondary"
                type="button"
                onClick={() => setEditing(item)}
              >
                Manage package prices
              </button>
            </article>
          ))}
        </div>
      </DataState>
      <Sheet
        open={creating}
        title="New price list"
        subtitle="Create the pricing context first; package prices are controlled separately."
        onClose={() => setCreating(false)}
      >
        <PriceListForm
          onCancel={() => setCreating(false)}
          onCreated={(item) => {
            setPriceLists((current) => [item, ...current])
            setCreating(false)
          }}
        />
      </Sheet>
      <Sheet
        open={editing !== null}
        title="Controlled pricing"
        subtitle="Every price change records the actor and business reason."
        onClose={() => setEditing(null)}
      >
        {editing ? <PriceEditor priceList={editing} /> : null}
      </Sheet>
    </div>
  )
}
