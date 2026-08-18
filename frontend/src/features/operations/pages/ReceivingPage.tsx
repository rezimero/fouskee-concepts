import { PackageCheck, Plus } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { DataState } from '../../master-data/components/DataState'
import { Sheet } from '../../master-data/components/Sheet'
import { WorkspaceHeader } from '../../master-data/components/WorkspaceHeader'
import { inventoryApi } from '../api/inventoryApi'
import { procurementApi } from '../api/procurementApi'
import { ControlNotice } from '../components/ControlNotice'
import { OperationsToolbar } from '../components/OperationsToolbar'
import { PrivilegedActionSheet } from '../components/PrivilegedActionSheet'
import { ReceivingDetail } from '../components/ReceivingDetail'
import { RecordCard } from '../components/RecordCard'
import { StatusBadge } from '../components/StatusBadge'
import { GoodsReceiptForm } from '../forms/GoodsReceiptForm'
import { usePermission } from '../hooks/usePermission'
import { dateTime } from '../lib/format'
import type { GoodsReceipt, InventoryLocation, PurchaseOrder } from '../types'
type Decision = 'approve' | 'reject' | 'reverse' | null
export function ReceivingPage() {
  const [receipts, setReceipts] = useState<GoodsReceipt[]>([])
  const [orders, setOrders] = useState<PurchaseOrder[]>([])
  const [locations, setLocations] = useState<InventoryLocation[]>([])
  const [selected, setSelected] = useState<GoodsReceipt | null>(null)
  const [creating, setCreating] = useState(false)
  const [decision, setDecision] = useState<Decision>(null)
  const [query, setQuery] = useState('')
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const canReceive = usePermission('procurement.receive')
  const canException = usePermission('procurement.exception.approve')
  const canReverse = usePermission('inventory.reverse')
  async function refresh() {
    const values = await Promise.all([
      procurementApi.receipts(),
      procurementApi.purchaseOrders(),
      inventoryApi.locations(),
    ])
    setReceipts(values[0])
    setOrders(values[1])
    setLocations(values[2])
  }
  useEffect(() => {
    let active = true
    void Promise.all([
      procurementApi.receipts(), procurementApi.purchaseOrders(),
      inventoryApi.locations(),
    ]).then((values) => {
      if (!active) return
      setReceipts(values[0])
      setOrders(values[1])
      setLocations(values[2])
    }).catch((caught) => {
      if (active) setError(requestErrorMessage(caught, 'Unable to load receiving evidence.'))
    }).finally(() => {
      if (active) setLoading(false)
    })
    return () => { active = false }
  }, [])
  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase()
    if (!needle) return receipts
    return receipts.filter((item) => [
      item.number,
      item.purchase_order_number ?? '',
      item.status,
      item.location?.name ?? '',
    ].some((value) => value.toLowerCase().includes(needle)))
  }, [query, receipts])
  async function openDetail(id: string) {
    try {
      setSelected(await procurementApi.receipt(id))
    } catch (caught) {
      setError(requestErrorMessage(
        caught,
        'Unable to load receipt evidence.',
      ))
    }
  }
  async function decide(reason: string) {
    if (!selected || !decision) return
    setBusy(true)
    setError('')
    try {
      const next = decision === 'approve'
        ? await procurementApi.approveReceiptException(selected.id, reason)
        : decision === 'reject'
          ? await procurementApi.rejectReceiptException(selected.id, reason)
          : await procurementApi.reverseReceipt(selected.id, reason)
      setSelected(next)
      setDecision(null)
      await refresh()
    } catch (caught) {
      setError(requestErrorMessage(
        caught,
        'Receiving control action failed.',
      ))
    } finally {
      setBusy(false)
    }
  }
  const decisionCopy = decision === 'approve'
    ? ['Approve receiving exception', 'Authorize this over-tolerance receipt after independent verification.', 'Approve receipt']
    : decision === 'reject'
      ? ['Reject receiving exception', 'Reject this receipt without posting inventory.', 'Reject receipt']
      : ['Reverse goods receipt', 'Correct a posted receipt through immutable reversal evidence.', 'Reverse receipt']
  return (
    <div className="page-stack">
      <WorkspaceHeader
        eyebrow="Physical receiving"
        title="Receiving desk"
        description="Match physical arrivals to approved PO lines before stock becomes available."
        actions={canReceive ? (
          <button className="button button--primary" onClick={() => setCreating(true)}>
            <Plus size={17} /> Record goods receipt
          </button>
        ) : undefined}
      />
      <ControlNotice title="No blind receiving">
        Over-tolerance receipts do not post inventory until an independent decision.
      </ControlNotice>
      <section className="workspace-panel">
        <OperationsToolbar
          query={query}
          placeholder="Search GRN, PO, location, or status…"
          onQuery={setQuery}
        />
        <DataState
          loading={loading}
          error={error}
          empty={filtered.length === 0}
          emptyMessage="No goods receipts have been recorded."
        >
          <div className="ops-record-list">
            {filtered.map((receipt) => (
              <RecordCard
                key={receipt.id}
                icon={PackageCheck}
                title={receipt.number}
                subtitle={`${receipt.purchase_order_number ?? 'PO'} · ${receipt.location?.name ?? 'Location'}`}
                meta={dateTime(receipt.received_at)}
                status={<StatusBadge
                  value={receipt.status}
                  risk={receipt.status === 'pending_exception'}
                />}
                onClick={() => void openDetail(receipt.id)}
              />
            ))}
          </div>
        </DataState>
      </section>
      <Sheet wide
        open={Boolean(selected)}
        title={selected?.number ?? 'Goods receipt'}
        subtitle="Physical receipt and inventory-posting evidence."
        onClose={() => setSelected(null)}
      >
        {selected ? (
          <ReceivingDetail
            receipt={selected}
            canException={canException}
            canReverse={canReceive && canReverse}
            onApprove={() => setDecision('approve')}
            onReject={() => setDecision('reject')}
            onReverse={() => setDecision('reverse')}
          />
        ) : null}
      </Sheet>
      <Sheet wide
        open={creating}
        title="Record goods receipt"
        subtitle="Receive against approved terms and preserve lot evidence."
        onClose={() => setCreating(false)}
      >
        <GoodsReceiptForm
          orders={orders}
          locations={locations}
          onCancel={() => setCreating(false)}
          onCreated={(receipt) => {
            setCreating(false)
            setSelected(receipt)
            void refresh()
          }}
        />
      </Sheet>
      <PrivilegedActionSheet
        open={Boolean(decision)}
        title={decisionCopy[0]}
        description={decisionCopy[1]}
        confirmLabel={decisionCopy[2]}
        danger={decision !== 'approve'}
        busy={busy}
        onClose={() => setDecision(null)}
        onConfirm={decide}
      />
    </div>
  )
}
