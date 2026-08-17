import { Plus, Truck } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { listSuppliers } from '../api/masterDataApi'
import { DataState } from '../components/DataState'
import { SearchBar } from '../components/SearchBar'
import { Sheet } from '../components/Sheet'
import { SupplierForm } from '../components/SupplierForm'
import { WorkspaceHeader } from '../components/WorkspaceHeader'
import type { Supplier } from '../types'

export function SuppliersPage() {
  const [suppliers, setSuppliers] = useState<Supplier[]>([])
  const [query, setQuery] = useState('')
  const [open, setOpen] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let cancelled = false
    void listSuppliers()
      .then((data) => {
        if (!cancelled) setSuppliers(data)
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
    if (!needle) return suppliers
    return suppliers.filter((item) =>
      [item.display_name, item.number, item.phone ?? '']
        .some((value) => value.toLowerCase().includes(needle)),
    )
  }, [query, suppliers])

  return (
    <div className="page-stack">
      <WorkspaceHeader
        eyebrow="Procurement network"
        title="Suppliers"
        description="Trusted vendors and traceable procurement identities for future receiving and matching."
        actions={
          <button className="button button--primary" type="button" onClick={() => setOpen(true)}>
            <Plus size={17} /> Add supplier
          </button>
        }
      />
      <section className="workspace-panel">
        <div className="panel-toolbar">
          <SearchBar value={query} placeholder="Search suppliers…" onChange={setQuery} />
          <span className="count-chip">{suppliers.length} vendors</span>
        </div>
        <DataState
          loading={loading}
          error={error}
          empty={filtered.length === 0}
          emptyMessage="No suppliers match this view yet."
        >
          <div className="record-grid">
            {filtered.map((supplier) => (
              <article className="record-card" key={supplier.id}>
                <span className="record-card__icon record-card__icon--orange">
                  <Truck size={19} />
                </span>
                <div className="record-card__body">
                  <strong>{supplier.display_name}</strong>
                  <small>{supplier.number}</small>
                </div>
                <div className="record-card__meta">
                  <span>{supplier.phone || 'No phone'}</span>
                  <strong>{supplier.status}</strong>
                </div>
              </article>
            ))}
          </div>
        </DataState>
      </section>
      <Sheet
        open={open}
        title="New supplier"
        subtitle="Create the procurement identity before purchases begin."
        onClose={() => setOpen(false)}
      >
        <SupplierForm
          onCancel={() => setOpen(false)}
          onCreated={(supplier) => {
            setSuppliers((current) => [supplier, ...current])
            setOpen(false)
          }}
        />
      </Sheet>
    </div>
  )
}
