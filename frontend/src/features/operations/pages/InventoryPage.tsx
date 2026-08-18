import { Boxes, Coins, Layers3, PackageCheck } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { listProducts } from '../../master-data/api/masterDataApi'
import { DataState } from '../../master-data/components/DataState'
import { WorkspaceHeader } from '../../master-data/components/WorkspaceHeader'
import type { Product } from '../../master-data/types'
import { inventoryApi } from '../api/inventoryApi'
import { InventoryBalanceTable } from '../components/InventoryBalanceTable'
import { InventoryTimeline } from '../components/InventoryTimeline'
import { MetricCard } from '../components/MetricCard'
import { OperationsToolbar } from '../components/OperationsToolbar'
import { money, quantity } from '../lib/format'
import type {
  InventoryBalance,
  InventoryLocation,
  InventoryLot,
  InventoryMovement,
} from '../types'

export function InventoryPage() {
  const [balances, setBalances] = useState<InventoryBalance[]>([])
  const [locations, setLocations] = useState<InventoryLocation[]>([])
  const [lots, setLots] = useState<InventoryLot[]>([])
  const [movements, setMovements] = useState<InventoryMovement[]>([])
  const [products, setProducts] = useState<Product[]>([])
  const [query, setQuery] = useState('')
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    void Promise.all([
      inventoryApi.balances(),
      inventoryApi.locations(),
      inventoryApi.lots(),
      inventoryApi.movements(),
      listProducts(),
    ]).then((values) => {
      if (!active) return
      setBalances(values[0])
      setLocations(values[1])
      setLots(values[2])
      setMovements(values[3])
      setProducts(values[4])
    }).catch((caught) => {
      if (active) {
        setError(requestErrorMessage(
          caught,
          'Unable to load inventory control data.',
        ))
      }
    }).finally(() => {
      if (active) setLoading(false)
    })

    return () => { active = false }
  }, [])

  const productMap = useMemo(
    () => new Map(products.map((item) => [item.id, item])),
    [products],
  )
  const locationMap = useMemo(
    () => new Map(locations.map((item) => [item.id, item])),
    [locations],
  )
  const lotMap = useMemo(
    () => new Map(lots.map((item) => [item.id, item])),
    [lots],
  )

  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase()
    if (!needle) return balances

    return balances.filter((row) => [
      productMap.get(row.product_id)?.sku ?? '',
      productMap.get(row.product_id)?.name ?? '',
      locationMap.get(row.location_id)?.code ?? '',
      locationMap.get(row.location_id)?.name ?? '',
      row.lot_id ? lotMap.get(row.lot_id)?.code ?? '' : '',
    ].some((value) => value.toLowerCase().includes(needle)))
  }, [balances, locationMap, lotMap, productMap, query])

  const totalValue = balances.reduce(
    (sum, row) => sum + Number(row.inventory_value),
    0,
  )
  const reserved = balances.reduce(
    (sum, row) => sum + Number(row.reserved_base),
    0,
  )

  return (
    <div className="page-stack">
      <WorkspaceHeader
        eyebrow="Inventory control"
        title="Stock intelligence"
        description="Ledger-backed on-hand, reserved, available, lot, value, and movement evidence."
      />
      <section className="ops-metric-grid">
        <MetricCard
          icon={Coins}
          label="Inventory value"
          value={money(totalValue)}
          note="Derived from immutable movement evidence"
        />
        <MetricCard
          icon={PackageCheck}
          label="Reserved base units"
          value={quantity(reserved)}
          note="Unavailable for transfer or sale"
          tone="orange"
        />
        <MetricCard
          icon={Layers3}
          label="Active lots"
          value={String(lots.filter((lot) => lot.status === 'active').length)}
          note="Explicit lot-scoped stock"
        />
        <MetricCard
          icon={Boxes}
          label="Locations"
          value={String(locations.length)}
          note="Warehouses, shops, and transit"
          tone="charcoal"
        />
      </section>
      <section className="workspace-panel">
        <OperationsToolbar
          query={query}
          placeholder="Search SKU, product, location, or lot…"
          onQuery={setQuery}
        />
        <DataState
          loading={loading}
          error={error}
          empty={filtered.length === 0}
          emptyMessage="No inventory balances match this view."
        >
          <InventoryBalanceTable
            rows={filtered}
            products={productMap}
            locations={locationMap}
            lots={lotMap}
          />
        </DataState>
      </section>
      <section className="workspace-panel">
        <div className="section-heading">
          <div>
            <span className="eyebrow">Immutable ledger</span>
            <h2>Recent movements</h2>
          </div>
          <span className="security-chip">Source evidence retained</span>
        </div>
        <InventoryTimeline movements={movements} />
      </section>
    </div>
  )
}
