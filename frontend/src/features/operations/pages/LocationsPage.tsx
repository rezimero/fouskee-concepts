import { MapPin, Plus } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { DataState } from '../../master-data/components/DataState'
import { Sheet } from '../../master-data/components/Sheet'
import { WorkspaceHeader } from '../../master-data/components/WorkspaceHeader'
import { inventoryApi } from '../api/inventoryApi'
import { ControlNotice } from '../components/ControlNotice'
import { LocationTable } from '../components/LocationTable'
import { MetricCard } from '../components/MetricCard'
import { OperationsToolbar } from '../components/OperationsToolbar'
import { InventoryLocationForm } from '../forms/InventoryLocationForm'
import { usePermission } from '../hooks/usePermission'
import type { InventoryLocation } from '../types'

export function LocationsPage() {
  const [locations, setLocations] = useState<InventoryLocation[]>([])
  const [query, setQuery] = useState('')
  const [creating, setCreating] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const canManage = usePermission('inventory.locations.manage')

  useEffect(() => {
    let active = true
    void inventoryApi.locations().then((value) => {
      if (active) setLocations(value)
    }).catch((caught) => {
      if (active) {
        setError(requestErrorMessage(
          caught,
          'Unable to load inventory locations.',
        ))
      }
    }).finally(() => {
      if (active) setLoading(false)
    })
    return () => { active = false }
  }, [])

  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase()
    if (!needle) return locations

    return locations.filter((location) => [
      location.code,
      location.name,
      location.type,
      location.status,
    ].some((value) => value.toLowerCase().includes(needle)))
  }, [locations, query])

  const operational = locations.filter(
    (location) => location.type !== 'transit'
      && location.status === 'active',
  ).length
  const transit = locations.filter(
    (location) => location.type === 'transit'
      && location.status === 'active',
  ).length

  return (
    <div className="page-stack">
      <WorkspaceHeader
        eyebrow="Inventory topology"
        title="Locations"
        description="Organization-scoped warehouses, stores, and controlled transit custody."
        actions={canManage ? (
          <button
            className="button button--primary"
            type="button"
            onClick={() => setCreating(true)}
          >
            <Plus size={17} /> New location
          </button>
        ) : undefined}
      />
      <section className="ops-metric-grid">
        <MetricCard
          icon={MapPin}
          label="All locations"
          value={String(locations.length)}
          note="Visible in this organization"
        />
        <MetricCard
          icon={MapPin}
          label="Active operating sites"
          value={String(operational)}
          note="Warehouses and stores"
          tone="orange"
        />
        <MetricCard
          icon={MapPin}
          label="Transit locations"
          value={String(transit)}
          note="Transfer custody evidence"
          tone="charcoal"
        />
      </section>
      <ControlNotice title="Location lifecycle is intentionally controlled">
        Creation is available here. Deactivation or archival remains withheld
        until non-empty-location and movement-history rules are implemented.
      </ControlNotice>
      <section className="workspace-panel">
        <OperationsToolbar
          query={query}
          placeholder="Search location code, name, type, or status…"
          onQuery={setQuery}
        />
        <DataState
          loading={loading}
          error={error}
          empty={filtered.length === 0}
          emptyMessage="No inventory locations match this view."
        >
          <LocationTable locations={filtered} />
        </DataState>
      </section>
      <Sheet
        open={creating}
        title="New inventory location"
        subtitle="Create a traceable physical stock location."
        onClose={() => setCreating(false)}
      >
        <InventoryLocationForm
          onCancel={() => setCreating(false)}
          onCreated={(location) => {
            setCreating(false)
            setLocations((current) => [...current, location].sort(
              (a, b) => a.name.localeCompare(b.name),
            ))
          }}
        />
      </Sheet>
    </div>
  )
}
