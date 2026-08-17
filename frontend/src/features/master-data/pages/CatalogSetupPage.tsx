import { Tags } from 'lucide-react'
import { useEffect, useState } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import {
  createBrand,
  createCategory,
  getCatalogReferences,
} from '../api/masterDataApi'
import { DataState } from '../components/DataState'
import { ReferenceForm } from '../components/ReferenceForm'
import { WorkspaceHeader } from '../components/WorkspaceHeader'
import type { CatalogReferences } from '../types'

const emptyReferences: CatalogReferences = {
  units: [],
  brands: [],
  categories: [],
}

export function CatalogSetupPage() {
  const [references, setReferences] = useState(emptyReferences)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let cancelled = false
    void getCatalogReferences()
      .then((data) => {
        if (!cancelled) setReferences(data)
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
        eyebrow="Catalog governance"
        title="Catalog setup"
        description="Maintain the controlled brand and category vocabulary used by the product catalog."
      />
      <DataState
        loading={loading}
        error={error}
        empty={false}
        emptyMessage=""
      >
        <div className="setup-grid">
          <section className="workspace-panel">
            <div className="panel-title">
              <span className="record-card__icon"><Tags size={19} /></span>
              <div><strong>Brands</strong><small>{references.brands.length} active</small></div>
            </div>
            <div className="reference-list">
              {references.brands.map((item) => (
                <span className="reference-chip" key={item.id}>{item.code} · {item.name}</span>
              ))}
            </div>
            <ReferenceForm
              noun="brand"
              create={createBrand}
              onCreated={(item) => setReferences((current) => ({
                ...current,
                brands: [...current.brands, item],
              }))}
            />
          </section>
          <section className="workspace-panel">
            <div className="panel-title">
              <span className="record-card__icon record-card__icon--orange"><Tags size={19} /></span>
              <div><strong>Categories</strong><small>{references.categories.length} active</small></div>
            </div>
            <div className="reference-list">
              {references.categories.map((item) => (
                <span className="reference-chip" key={item.id}>{item.code} · {item.name}</span>
              ))}
            </div>
            <ReferenceForm
              noun="category"
              create={createCategory}
              onCreated={(item) => setReferences((current) => ({
                ...current,
                categories: [...current.categories, item],
              }))}
            />
          </section>
        </div>
      </DataState>
    </div>
  )
}
