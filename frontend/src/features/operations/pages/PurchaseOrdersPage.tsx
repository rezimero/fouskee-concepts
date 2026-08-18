import { Plus } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import {
  listProducts,
  listSuppliers,
} from '../../master-data/api/masterDataApi'
import { DataState } from '../../master-data/components/DataState'
import { Sheet } from '../../master-data/components/Sheet'
import { WorkspaceHeader } from '../../master-data/components/WorkspaceHeader'
import type { Product, Supplier } from '../../master-data/types'
import { procurementApi } from '../api/procurementApi'
import { ControlNotice } from '../components/ControlNotice'
import { OperationsToolbar } from '../components/OperationsToolbar'
import { PrivilegedActionSheet } from '../components/PrivilegedActionSheet'
import { PurchaseOrderDetail } from '../components/PurchaseOrderDetail'
import { PurchaseOrderList } from '../components/PurchaseOrderList'
import { PurchaseOrderForm } from '../forms/PurchaseOrderForm'
import { usePermission } from '../hooks/usePermission'
import type { PurchaseOrder } from '../types'
type Decision = 'approve' | 'reject' | 'cancel' | null
export function PurchaseOrdersPage() {
  const [orders, setOrders] = useState<PurchaseOrder[]>([])
  const [suppliers, setSuppliers] = useState<Supplier[]>([])
  const [products, setProducts] = useState<Product[]>([])
  const [selected, setSelected] = useState<PurchaseOrder | null>(null)
  const [creating, setCreating] = useState(false)
  const [decision, setDecision] = useState<Decision>(null)
  const [query, setQuery] = useState('')
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const canManage = usePermission('procurement.manage')
  const canSubmit = usePermission('procurement.submit')
  const canApprove = usePermission('procurement.approve')
  async function refresh() {
    const values = await Promise.all([
      procurementApi.purchaseOrders(),
      listSuppliers(),
      listProducts(),
    ])
    setOrders(values[0])
    setSuppliers(values[1])
    setProducts(values[2])
  }
  useEffect(() => {
    let active = true
    void Promise.all([
      procurementApi.purchaseOrders(),
      listSuppliers(),
      listProducts(),
    ]).then((values) => {
      if (!active) return
      setOrders(values[0])
      setSuppliers(values[1])
      setProducts(values[2])
    }).catch((caught) => {
      if (active) {
        setError(requestErrorMessage(
          caught,
          'Unable to load purchase orders.',
        ))
      }
    }).finally(() => {
      if (active) setLoading(false)
    })
    return () => { active = false }
  }, [])
  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase()
    if (!needle) return orders
    return orders.filter((item) => [
      item.number,
      item.status,
      item.supplier?.name ?? '',
      item.supplier?.number ?? '',
    ].some((value) => value.toLowerCase().includes(needle)))
  }, [orders, query])
  async function openDetail(id: string) {
    try {
      setSelected(await procurementApi.purchaseOrder(id))
    } catch (caught) {
      setError(requestErrorMessage(
        caught,
        'Unable to load purchase-order evidence.',
      ))
    }
  }
  async function mutate(action: () => Promise<PurchaseOrder>) {
    setBusy(true)
    setError('')
    try {
      const next = await action()
      setSelected(next)
      await refresh()
    } catch (caught) {
      setError(requestErrorMessage(
        caught,
        'Purchase-order action failed.',
      ))
    } finally {
      setBusy(false)
    }
  }
  async function decide(reason: string) {
    if (!selected || !decision) return
    await mutate(() => decision === 'approve'
      ? procurementApi.approvePurchaseOrder(selected.id, reason)
      : decision === 'reject'
        ? procurementApi.rejectPurchaseOrder(selected.id, reason)
        : procurementApi.cancelPurchaseOrder(selected.id, reason))
    setDecision(null)
  }
  const decisionCopy = decision === 'approve'
    ? ['Approve purchase order', 'Authorize frozen terms as an independent checker.', 'Approve order']
    : decision === 'reject'
      ? ['Reject purchase order', 'Reject this submitted order and retain decision evidence.', 'Reject order']
      : ['Cancel purchase order', 'Cancel this order without deleting its commercial history.', 'Cancel order']
  return (
    <div className="page-stack">
      <WorkspaceHeader
        eyebrow="Controlled purchasing"
        title="Purchase orders"
        description="Frozen supplier terms, configurable thresholds, and independent authorization."
        actions={canManage ? (
          <button className="button button--primary" onClick={() => setCreating(true)}>
            <Plus size={17} /> New purchase order
          </button>
        ) : undefined}
      />
      <ControlNotice title="Commercial terms freeze at submission">
        Submitted PO lines cannot be silently changed at application or database level.
      </ControlNotice>
      <section className="workspace-panel">
        <OperationsToolbar
          query={query}
          placeholder="Search PO, supplier, or status…"
          onQuery={setQuery}
        />
        <DataState
          loading={loading}
          error={error}
          empty={filtered.length === 0}
          emptyMessage="No purchase orders have been recorded."
        >
          <PurchaseOrderList orders={filtered} onOpen={(id) => void openDetail(id)} />
        </DataState>
      </section>
      <Sheet wide
        open={Boolean(selected)}
        title={selected?.number ?? 'Purchase order'}
        subtitle="Frozen commercial evidence."
        onClose={() => setSelected(null)}
      >
        {selected ? (
          <PurchaseOrderDetail
            order={selected}
            canManage={canManage}
            canSubmit={canSubmit}
            canApprove={canApprove}
            busy={busy}
            onSubmit={() => void mutate(
              () => procurementApi.submitPurchaseOrder(selected.id),
            )}
            onApprove={() => setDecision('approve')}
            onReject={() => setDecision('reject')}
            onCancel={() => setDecision('cancel')}
          />
        ) : null}
      </Sheet>
      <Sheet wide
        open={creating}
        title="New purchase order"
        subtitle="Draft supplier terms before maker-checker control."
        onClose={() => setCreating(false)}
      >
        <PurchaseOrderForm
          suppliers={suppliers}
          products={products}
          onCancel={() => setCreating(false)}
          onCreated={(order) => {
            setCreating(false)
            setSelected(order)
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
