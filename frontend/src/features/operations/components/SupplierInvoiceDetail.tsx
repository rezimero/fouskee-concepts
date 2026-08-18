import { BadgeCheck, FileSearch } from 'lucide-react'
import { ControlNotice } from './ControlNotice'
import { DetailFacts } from './DetailFacts'
import { MatchEvidence } from './MatchEvidence'
import { StatusBadge } from './StatusBadge'
import { dateOnly, money, quantity } from '../lib/format'
import type { MatchRun, SupplierInvoice } from '../types'

type Props = {
  invoice: SupplierInvoice
  match: MatchRun | null
  canMatch: boolean
  canApprove: boolean
  busy: boolean
  onMatch: () => void
  onApprove: () => void
  onReject: () => void
}

export function SupplierInvoiceDetail({
  invoice,
  match,
  canMatch,
  canApprove,
  busy,
  onMatch,
  onApprove,
  onReject,
}: Props) {
  return (
    <div className="form-stack">
      <DetailFacts facts={[
        { label: 'Status', value: <StatusBadge value={invoice.status} /> },
        { label: 'Supplier', value: invoice.supplier?.name ?? '—' },
        { label: 'Invoice date', value: dateOnly(invoice.invoice_date) },
        {
          label: 'Total',
          value: money(invoice.total_amount, invoice.currency_code),
        },
        {
          label: 'Payment eligibility',
          value: invoice.eligible_for_payment ? 'Eligible' : 'Blocked',
        },
      ]} />
      <div className="ops-evidence-list">
        {invoice.lines?.map((line) => (
          <article key={line.id}>
            <BadgeCheck size={17} />
            <div>
              <strong>{line.sku} · {line.product_name}</strong>
              <small>
                {quantity(line.quantity)} invoiced · PO link
                {' '}{line.purchase_order_line_id ? 'present' : 'missing'}
              </small>
            </div>
            <strong>{money(line.line_amount, invoice.currency_code)}</strong>
          </article>
        ))}
      </div>
      {match ? (
        <MatchEvidence run={match} />
      ) : (
        <ControlNotice title="Not yet matched" risk>
          This invoice is not procurement-eligible until three-way verification succeeds.
        </ControlNotice>
      )}
      <div className="button-row">
        {canMatch ? (
          <button
            className="button button--primary"
            disabled={busy}
            onClick={onMatch}
          >
            <FileSearch size={17} />
            {match ? 'Run new match' : 'Run three-way match'}
          </button>
        ) : null}
        {canApprove
          && invoice.status === 'exception'
          && match?.result === 'exception' ? (
            <>
              <button className="button button--primary" onClick={onApprove}>
                Approve exception
              </button>
              <button className="button button--danger" onClick={onReject}>
                Reject exception
              </button>
            </>
          ) : null}
      </div>
    </div>
  )
}
