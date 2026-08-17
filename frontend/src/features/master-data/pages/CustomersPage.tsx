import { Plus, UsersRound } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { listCustomers } from '../api/masterDataApi'
import { listPriceLists } from '../api/pricingApi'
import { CustomerForm } from '../components/CustomerForm'
import { DataState } from '../components/DataState'
import { SearchBar } from '../components/SearchBar'
import { Sheet } from '../components/Sheet'
import { WorkspaceHeader } from '../components/WorkspaceHeader'
import type { Customer, PriceList } from '../types'

export function CustomersPage() {
  const [customers, setCustomers] = useState<Customer[]>([])
  const [priceLists, setPriceLists] = useState<PriceList[]>([])
  const [query, setQuery] = useState('')
  const [open, setOpen] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let cancelled = false
    void Promise.all([listCustomers(), listPriceLists()])
      .then(([customerData, priceData]) => {
        if (cancelled) return
        setCustomers(customerData)
        setPriceLists(priceData)
      })
      .catch((caught) => {
        if (!cancelled) setError(requestErrorMessage(caught))
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => { cancelled = true }
  }, [])

  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase()
    if (!needle) return customers
    return customers.filter((item) =>
      [item.display_name, item.number, item.phone ?? '']
        .some((value) => value.toLowerCase().includes(needle)),
    )
  }, [customers, query])

  return (
    <div className="page-stack">
      <WorkspaceHeader
        eyebrow="Commercial relationships"
        title="Customers"
        description="Wholesale and retail accounts, pricing relationships, and controlled credit limits."
        actions={
          <button className="button button--primary" type="button" onClick={() => setOpen(true)}>
            <Plus size={17} /> Add customer
          </button>
        }
      />
      <section className="workspace-panel">
        <div className="panel-toolbar">
          <SearchBar value={query} placeholder="Search customers…" onChange={setQuery} />
          <span className="count-chip">{customers.length} accounts</span>
        </div>
        <DataState
          loading={loading}
          error={error}
          empty={filtered.length === 0}
          emptyMessage="No customers match this view yet."
        >
          <div className="record-grid">
            {filtered.map((customer) => (
              <article className="record-card" key={customer.id}>
                <span className="record-card__icon"><UsersRound size={19} /></span>
                <div className="record-card__body">
                  <strong>{customer.display_name}</strong>
                  <small>{customer.number} · {customer.segment}</small>
                </div>
                <div className="record-card__meta">
                  <span>{customer.phone || 'No phone'}</span>
                  <strong>₦{Number(customer.credit_limit).toLocaleString()}</strong>
                </div>
              </article>
            ))}
          </div>
        </DataState>
      </section>
      <Sheet
        open={open}
        title="New customer"
        subtitle="Create a traceable commercial account."
        onClose={() => setOpen(false)}
      >
        <CustomerForm
          priceLists={priceLists}
          onCancel={() => setOpen(false)}
          onCreated={(customer) => {
            setCustomers((current) => [customer, ...current])
            setOpen(false)
          }}
        />
      </Sheet>
    </div>
  )
}
