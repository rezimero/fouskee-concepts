import { ClipboardCheck, Plus } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { listProducts } from '../../master-data/api/masterDataApi'
import { DataState } from '../../master-data/components/DataState'
import { Sheet } from '../../master-data/components/Sheet'
import { WorkspaceHeader } from '../../master-data/components/WorkspaceHeader'
import type { Product } from '../../master-data/types'
import { inventoryApi } from '../api/inventoryApi'
import { ControlNotice } from '../components/ControlNotice'
import { OperationsToolbar } from '../components/OperationsToolbar'
import { PrivilegedActionSheet } from '../components/PrivilegedActionSheet'
import { RecordCard } from '../components/RecordCard'
import { StatusBadge } from '../components/StatusBadge'
import { StockCountDetail } from '../components/StockCountDetail'
import { StockCountForm } from '../forms/StockCountForm'
import { usePermission } from '../hooks/usePermission'
import { money } from '../lib/format'
import type { InventoryLocation, InventoryLot, StockCount } from '../types'
type Action = 'submit' | 'approve' | 'reject' | null
export function StockCountsPage() {
  const [counts, setCounts] = useState<StockCount[]>([])
  const [locations, setLocations] = useState<InventoryLocation[]>([])
  const [lots, setLots] = useState<InventoryLot[]>([])
  const [products, setProducts] = useState<Product[]>([])
  const [selected, setSelected] = useState<StockCount | null>(null)
  const [creating, setCreating] = useState(false)
  const [action, setAction] = useState<Action>(null)
  const [query, setQuery] = useState('')
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const canCount = usePermission('inventory.count')
  const canApprove = usePermission('inventory.adjust.approve')
  async function refresh() {
    const values = await Promise.all([
      inventoryApi.counts(),
      inventoryApi.locations(),
      inventoryApi.lots(),
      listProducts(),
    ])
    setCounts(values[0])
    setLocations(values[1])
    setLots(values[2])
    setProducts(values[3])
  }
  useEffect(() => {
    let active = true
    void Promise.all([
      inventoryApi.counts(), inventoryApi.locations(),
      inventoryApi.lots(), listProducts(),
    ]).then((values) => {
      if (!active) return
      setCounts(values[0])
      setLocations(values[1])
      setLots(values[2])
      setProducts(values[3])
    }).catch((caught) => {
      if (active) setError(requestErrorMessage(caught, 'Unable to load stock counts.'))
    }).finally(() => {
      if (active) setLoading(false)
    })
    return () => { active = false }
  }, [])
  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase()
    if (!needle) return counts
    return counts.filter((item) => [
      item.number,
      item.location.name ?? '',
      item.status,
      item.reason_code,
    ].some((value) => value.toLowerCase().includes(needle)))
  }, [counts, query])
  async function openDetail(id: string) {
    try {
      setSelected(await inventoryApi.count(id))
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Unable to load count evidence.'))
    }
  }
  async function decide(reason: string) {
    if (!selected || !action) return
    setBusy(true)
    try {
      const next = action === 'submit'
        ? await inventoryApi.submitCount(selected.id, reason)
        : action === 'approve'
          ? await inventoryApi.approveCount(selected.id, reason)
          : await inventoryApi.rejectCount(selected.id, reason)
      setSelected(next)
      setAction(null)
      await refresh()
    } catch (caught) {
      setError(requestErrorMessage(
        caught,
        'Controlled stock-count action failed.',
      ))
    } finally {
      setBusy(false)
    }
  }
  const actionCopy = action === 'submit'
    ? ['Submit stock count', 'Post or route this variance for independent approval.', 'Submit count']
    : action === 'approve'
      ? ['Approve stock variance', 'Confirm this independently reviewed count variance.', 'Approve variance']
      : ['Reject stock variance', 'Reject this count and preserve decision evidence.', 'Reject variance']
  return (
    <div className="page-stack">
      <WorkspaceHeader
        eyebrow="Inventory integrity"
        title="Stock counts"
        description="Physical counts become evidence-backed variance workflows, never direct balance edits."
        actions={canCount ? (
          <button className="button button--primary" onClick={() => setCreating(true)}>
            <Plus size={17} /> New stock count
          </button>
        ) : undefined}
      />
      <ControlNotice title="Maker-checker enforced">
        Material variance cannot be approved by the user who created the approval request.
      </ControlNotice>
      <section className="workspace-panel">
        <OperationsToolbar
          query={query}
          placeholder="Search count number, location, status, or reason…"
          onQuery={setQuery}
        />
        <DataState
          loading={loading}
          error={error}
          empty={filtered.length === 0}
          emptyMessage="No stock-count evidence is available."
        >
          <div className="ops-record-list">
            {filtered.map((count) => (
              <RecordCard
                key={count.id}
                icon={ClipboardCheck}
                title={count.number}
                subtitle={`${count.location.name ?? 'Location'} · ${count.reason_code}`}
                meta={money(count.total_abs_variance_value)}
                status={<StatusBadge value={count.status} />}
                onClick={() => void openDetail(count.id)}
              />
            ))}
          </div>
        </DataState>
      </section>
      <Sheet wide
        open={Boolean(selected)}
        title={selected?.number ?? 'Stock count'}
        subtitle="Physical count evidence and variance control."
        onClose={() => setSelected(null)}
      >
        {selected ? (
          <StockCountDetail
            count={selected}
            canCount={canCount}
            canApprove={canApprove}
            onSubmit={() => setAction('submit')}
            onApprove={() => setAction('approve')}
            onReject={() => setAction('reject')}
          />
        ) : null}
      </Sheet>
      <Sheet wide
        open={creating}
        title="New stock count"
        subtitle="Capture physical quantity before any variance can affect stock."
        onClose={() => setCreating(false)}
      >
        <StockCountForm
          locations={locations}
          lots={lots}
          products={products}
          onCancel={() => setCreating(false)}
          onCreated={(count) => {
            setCreating(false)
            setSelected(count)
            void refresh()
          }}
        />
      </Sheet>
      <PrivilegedActionSheet
        open={Boolean(action)}
        title={actionCopy[0]}
        description={actionCopy[1]}
        confirmLabel={actionCopy[2]}
        danger={action === 'reject'}
        busy={busy}
        onClose={() => setAction(null)}
        onConfirm={decide}
      />
    </div>
  )
}
