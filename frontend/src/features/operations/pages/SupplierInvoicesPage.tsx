import { Plus } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { listSuppliers } from '../../master-data/api/masterDataApi'
import { DataState } from '../../master-data/components/DataState'
import { Sheet } from '../../master-data/components/Sheet'
import { WorkspaceHeader } from '../../master-data/components/WorkspaceHeader'
import type { Supplier } from '../../master-data/types'
import { procurementApi } from '../api/procurementApi'
import { ControlNotice } from '../components/ControlNotice'
import { OperationsToolbar } from '../components/OperationsToolbar'
import { PrivilegedActionSheet } from '../components/PrivilegedActionSheet'
import { SupplierInvoiceDetail } from '../components/SupplierInvoiceDetail'
import { SupplierInvoiceList } from '../components/SupplierInvoiceList'
import { SupplierInvoiceForm } from '../forms/SupplierInvoiceForm'
import { usePermission } from '../hooks/usePermission'
import type { MatchRun, PurchaseOrder, SupplierInvoice } from '../types'
type Decision = 'approve' | 'reject' | null
export function SupplierInvoicesPage() {
  const [invoices, setInvoices] = useState<SupplierInvoice[]>([])
  const [orders, setOrders] = useState<PurchaseOrder[]>([])
  const [suppliers, setSuppliers] = useState<Supplier[]>([])
  const [selected, setSelected] = useState<SupplierInvoice | null>(null)
  const [match, setMatch] = useState<MatchRun | null>(null)
  const [creating, setCreating] = useState(false)
  const [decision, setDecision] = useState<Decision>(null)
  const [query, setQuery] = useState('')
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const canInvoice = usePermission('procurement.invoice')
  const canMatch = usePermission('procurement.match')
  const canApprove = usePermission('procurement.exception.approve')
  async function refresh() {
    const values = await Promise.all([
      procurementApi.invoices(),
      procurementApi.purchaseOrders(),
      listSuppliers(),
    ])
    setInvoices(values[0])
    setOrders(values[1])
    setSuppliers(values[2])
  }
  useEffect(() => {
    let active = true
    void Promise.all([
      procurementApi.invoices(), procurementApi.purchaseOrders(),
      listSuppliers(),
    ]).then((values) => {
      if (!active) return
      setInvoices(values[0])
      setOrders(values[1])
      setSuppliers(values[2])
    }).catch((caught) => {
      if (active) setError(requestErrorMessage(caught, 'Unable to load supplier invoices.'))
    }).finally(() => {
      if (active) setLoading(false)
    })
    return () => { active = false }
  }, [])
  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase()
    if (!needle) return invoices
    return invoices.filter((item) => [
      item.invoice_number,
      item.status,
      item.supplier?.name ?? '',
    ].some((value) => value.toLowerCase().includes(needle)))
  }, [invoices, query])
  async function openDetail(id: string) {
    try {
      const invoice = await procurementApi.invoice(id)
      setSelected(invoice)
      setMatch(invoice.latest_match_run_id
        ? await procurementApi.match(invoice.latest_match_run_id)
        : null)
    } catch (caught) {
      setError(requestErrorMessage(
        caught,
        'Unable to load invoice evidence.',
      ))
    }
  }
  async function runMatch() {
    if (!selected) return
    setBusy(true)
    setError('')
    try {
      const nextMatch = await procurementApi.matchInvoice(selected.id)
      setMatch(nextMatch)
      setSelected(await procurementApi.invoice(selected.id))
      await refresh()
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Three-way match failed.'))
    } finally {
      setBusy(false)
    }
  }
  async function decide(reason: string) {
    if (!match || !decision || !selected) return
    setBusy(true)
    setError('')
    try {
      const next = decision === 'approve'
        ? await procurementApi.approveMatch(match.id, reason)
        : await procurementApi.rejectMatch(match.id, reason)
      setMatch(next)
      setSelected(await procurementApi.invoice(selected.id))
      setDecision(null)
      await refresh()
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Exception decision failed.'))
    } finally {
      setBusy(false)
    }
  }
  return (
    <div className="page-stack">
      <WorkspaceHeader
        eyebrow="Three-way verification"
        title="Supplier invoices"
        description="Compare ordered, received, and invoiced evidence before downstream payment."
        actions={canInvoice ? (
          <button className="button button--primary" onClick={() => setCreating(true)}>
            <Plus size={17} /> Record invoice
          </button>
        ) : undefined}
      />
      <ControlNotice title="Procurement does not pay invoices">
        Match status establishes eligibility only. Payment remains a separate authority.
      </ControlNotice>
      <section className="workspace-panel">
        <OperationsToolbar
          query={query}
          placeholder="Search invoice number, supplier, or match status…"
          onQuery={setQuery}
        />
        <DataState
          loading={loading}
          error={error}
          empty={filtered.length === 0}
          emptyMessage="No supplier invoices have been recorded."
        >
          <SupplierInvoiceList invoices={filtered} onOpen={(id) => void openDetail(id)} />
        </DataState>
      </section>
      <Sheet wide
        open={Boolean(selected)}
        title={selected?.invoice_number ?? 'Supplier invoice'}
        subtitle="Supplier evidence and latest three-way match."
        onClose={() => {
          setSelected(null)
          setMatch(null)
        }}
      >
        {selected ? (
          <SupplierInvoiceDetail
            invoice={selected}
            match={match}
            canMatch={canMatch}
            canApprove={canApprove}
            busy={busy}
            onMatch={() => void runMatch()}
            onApprove={() => setDecision('approve')}
            onReject={() => setDecision('reject')}
          />
        ) : null}
      </Sheet>
      <Sheet wide
        open={creating}
        title="Record supplier invoice"
        subtitle="Link supplier billing evidence to frozen PO lines."
        onClose={() => setCreating(false)}
      >
        <SupplierInvoiceForm
          suppliers={suppliers}
          orders={orders}
          onCancel={() => setCreating(false)}
          onCreated={(invoice) => {
            setCreating(false)
            setSelected(invoice)
            setMatch(null)
            void refresh()
          }}
        />
      </Sheet>
      <PrivilegedActionSheet
        open={Boolean(decision)}
        title={decision === 'approve' ? 'Approve match exception' : 'Reject match exception'}
        description="This maker-checker decision remains separate from immutable match evidence."
        confirmLabel={decision === 'approve' ? 'Approve exception' : 'Reject exception'}
        danger={decision === 'reject'}
        busy={busy}
        onClose={() => setDecision(null)}
        onConfirm={decide}
      />
    </div>
  )
}
