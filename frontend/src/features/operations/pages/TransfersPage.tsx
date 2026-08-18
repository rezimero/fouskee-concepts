import { Plus, Truck } from 'lucide-react'
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
import { TransferDetail } from '../components/TransferDetail'
import { StockTransferForm } from '../forms/StockTransferForm'
import { usePermission } from '../hooks/usePermission'
import { dateTime } from '../lib/format'
import type {
  InventoryLocation,
  InventoryLot,
  StockTransfer,
} from '../types'
export function TransfersPage() {
  const [transfers, setTransfers] = useState<StockTransfer[]>([])
  const [locations, setLocations] = useState<InventoryLocation[]>([])
  const [lots, setLots] = useState<InventoryLot[]>([])
  const [products, setProducts] = useState<Product[]>([])
  const [selected, setSelected] = useState<StockTransfer | null>(null)
  const [creating, setCreating] = useState(false)
  const [cancelOpen, setCancelOpen] = useState(false)
  const [query, setQuery] = useState('')
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const canTransfer = usePermission('inventory.transfer')
  async function refresh() {
    const values = await Promise.all([
      inventoryApi.transfers(),
      inventoryApi.locations(),
      inventoryApi.lots(),
      listProducts(),
    ])
    setTransfers(values[0])
    setLocations(values[1])
    setLots(values[2])
    setProducts(values[3])
  }
  useEffect(() => {
    let active = true
    void Promise.all([
      inventoryApi.transfers(), inventoryApi.locations(),
      inventoryApi.lots(), listProducts(),
    ]).then((values) => {
      if (!active) return
      setTransfers(values[0])
      setLocations(values[1])
      setLots(values[2])
      setProducts(values[3])
    }).catch((caught) => {
      if (active) setError(requestErrorMessage(caught, 'Unable to load transfers.'))
    }).finally(() => {
      if (active) setLoading(false)
    })
    return () => { active = false }
  }, [])
  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase()
    if (!needle) return transfers
    return transfers.filter((item) => [
      item.number,
      item.status,
      item.source?.name ?? '',
      item.destination?.name ?? '',
    ].some((value) => value.toLowerCase().includes(needle)))
  }, [query, transfers])
  async function openDetail(id: string) {
    try {
      setSelected(await inventoryApi.transfer(id))
    } catch (caught) {
      setError(requestErrorMessage(
        caught,
        'Unable to load transfer evidence.',
      ))
    }
  }
  async function run(action: () => Promise<StockTransfer>) {
    setBusy(true)
    setError('')
    try {
      const next = await action()
      setSelected(next)
      await refresh()
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Transfer action failed.'))
    } finally {
      setBusy(false)
    }
  }
  async function cancel(reason: string) {
    if (!selected) return
    await run(() => inventoryApi.cancelTransfer(selected.id, reason))
    setCancelOpen(false)
  }
  return (
    <div className="page-stack">
      <WorkspaceHeader
        eyebrow="Chain of custody"
        title="Stock transfers"
        description="Dispatch into transit, preserve value, and require physical receipt confirmation."
        actions={canTransfer ? (
          <button className="button button--primary" onClick={() => setCreating(true)}>
            <Plus size={17} /> New transfer
          </button>
        ) : undefined}
      />
      <ControlNotice title="Transit is visible">
        Dispatched stock remains on the ledger until destination receipt.
      </ControlNotice>
      <section className="workspace-panel">
        <OperationsToolbar
          query={query}
          placeholder="Search transfer, source, destination, or status…"
          onQuery={setQuery}
        />
        <DataState
          loading={loading}
          error={error}
          empty={filtered.length === 0}
          emptyMessage="No controlled transfers have been recorded."
        >
          <div className="ops-record-list">
            {filtered.map((transfer) => (
              <RecordCard
                key={transfer.id}
                icon={Truck}
                title={transfer.number}
                subtitle={`${transfer.source?.name ?? 'Source'} → ${transfer.destination?.name ?? 'Destination'}`}
                meta={transfer.dispatched_at
                  ? dateTime(transfer.dispatched_at)
                  : 'Not dispatched'}
                status={<StatusBadge value={transfer.status} />}
                onClick={() => void openDetail(transfer.id)}
              />
            ))}
          </div>
        </DataState>
      </section>
      <Sheet wide
        open={Boolean(selected)}
        title={selected?.number ?? 'Transfer'}
        subtitle="Chain-of-custody evidence."
        onClose={() => setSelected(null)}
      >
        {selected ? (
          <TransferDetail
            transfer={selected}
            canTransfer={canTransfer}
            busy={busy}
            onDispatch={() => void run(
              () => inventoryApi.dispatchTransfer(selected.id),
            )}
            onReceive={() => void run(
              () => inventoryApi.receiveTransfer(selected.id),
            )}
            onCancel={() => setCancelOpen(true)}
          />
        ) : null}
      </Sheet>
      <Sheet wide
        open={creating}
        title="New controlled transfer"
        subtitle="Create dispatch evidence before stock leaves its source."
        onClose={() => setCreating(false)}
      >
        <StockTransferForm
          locations={locations}
          lots={lots}
          products={products}
          onCancel={() => setCreating(false)}
          onCreated={(transfer) => {
            setCreating(false)
            setSelected(transfer)
            void refresh()
          }}
        />
      </Sheet>
      <PrivilegedActionSheet
        open={cancelOpen}
        title="Cancel stock transfer"
        description="Cancellation preserves evidence and returns in-transit stock to source."
        confirmLabel="Cancel transfer"
        danger
        busy={busy}
        onClose={() => setCancelOpen(false)}
        onConfirm={cancel}
      />
    </div>
  )
}
