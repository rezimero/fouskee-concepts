import { Boxes, Plus, Settings2 } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { requestErrorMessage } from '../../../lib/errors'
import {
  getCatalogReferences,
  listProducts,
} from '../api/masterDataApi'
import { DataState } from '../components/DataState'
import { ProductForm } from '../components/ProductForm'
import { SearchBar } from '../components/SearchBar'
import { Sheet } from '../components/Sheet'
import { WorkspaceHeader } from '../components/WorkspaceHeader'
import type { CatalogReferences, Product } from '../types'

const emptyReferences: CatalogReferences = { units: [], brands: [], categories: [] }

export function ProductsPage() {
  const [products, setProducts] = useState<Product[]>([])
  const [references, setReferences] = useState(emptyReferences)
  const [query, setQuery] = useState('')
  const [open, setOpen] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let cancelled = false
    void Promise.all([listProducts(), getCatalogReferences()])
      .then(([productData, referenceData]) => {
        if (cancelled) return
        setProducts(productData)
        setReferences(referenceData)
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
    if (!needle) return products
    return products.filter((item) =>
      [item.name, item.sku, item.brand?.name ?? '']
        .some((value) => value.toLowerCase().includes(needle)),
    )
  }, [products, query])

  return (
    <div className="page-stack">
      <WorkspaceHeader
        eyebrow="Sellable catalog"
        title="Products & packaging"
        description="Define rice and other goods once, then sell or purchase them through controlled package conversions."
        actions={
          <>
            <Link className="button button--secondary" to="/catalog/setup">
              <Settings2 size={17} /> Catalog setup
            </Link>
            <button className="button button--primary" type="button" onClick={() => setOpen(true)}>
              <Plus size={17} /> Add product
            </button>
          </>
        }
      />
      <section className="workspace-panel">
        <div className="panel-toolbar">
          <SearchBar value={query} placeholder="Search products or SKU…" onChange={setQuery} />
          <span className="count-chip">{products.length} products</span>
        </div>
        <DataState
          loading={loading}
          error={error}
          empty={filtered.length === 0}
          emptyMessage="No products match this view yet."
        >
          <div className="product-grid">
            {filtered.map((product) => (
              <article className="product-card" key={product.id}>
                <div className="product-card__top">
                  <span className="record-card__icon"><Boxes size={19} /></span>
                  <span className="status-badge">{product.status}</span>
                </div>
                <strong>{product.name}</strong>
                <small>{product.sku} · {product.brand?.name ?? 'Unbranded'}</small>
                <div className="package-chips">
                  {product.packagings.map((item) => (
                    <span key={item.id}>{item.name} · ×{Number(item.conversion_to_base)}</span>
                  ))}
                </div>
              </article>
            ))}
          </div>
        </DataState>
      </section>
      <Sheet
        open={open}
        title="New product"
        subtitle="Stock quantity is deliberately not stored here; inventory will use its own ledger."
        onClose={() => setOpen(false)}
      >
        <ProductForm
          references={references}
          onCancel={() => setOpen(false)}
          onCreated={(product) => {
            setProducts((current) => [product, ...current])
            setOpen(false)
          }}
        />
      </Sheet>
    </div>
  )
}
