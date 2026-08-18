import { FileCheck2, Send, ShieldCheck } from 'lucide-react'
import { DetailFacts } from './DetailFacts'
import { StatusBadge } from './StatusBadge'
import { dateOnly, money, quantity } from '../lib/format'
import type { PurchaseOrder } from '../types'

type Props = {
  order: PurchaseOrder
  canManage: boolean
  canSubmit: boolean
  canApprove: boolean
  busy: boolean
  onSubmit: () => void
  onApprove: () => void
  onReject: () => void
  onCancel: () => void
}

export function PurchaseOrderDetail({
  order,
  canManage,
  canSubmit,
  canApprove,
  busy,
  onSubmit,
  onApprove,
  onReject,
  onCancel,
}: Props) {
  return (
    <div className="form-stack">
      <DetailFacts facts={[
        { label: 'Status', value: <StatusBadge value={order.status} /> },
        { label: 'Supplier', value: order.supplier?.name ?? '—' },
        {
          label: 'Expected delivery',
          value: dateOnly(order.expected_delivery_date),
        },
        {
          label: 'Order value',
          value: money(order.total_amount, order.currency_code),
        },
      ]} />
      <div className="ops-evidence-list">
        {order.lines?.map((line) => (
          <article key={line.id}>
            <FileCheck2 size={17} />
            <div>
              <strong>{line.sku} · {line.product_name}</strong>
              <small>
                {quantity(line.quantity)} {line.packaging_name ?? 'base units'}
              </small>
            </div>
            <strong>{money(line.line_total, order.currency_code)}</strong>
          </article>
        ))}
      </div>
      <div className="button-row">
        {canSubmit && order.status === 'draft' ? (
          <button
            className="button button--primary"
            disabled={busy}
            onClick={onSubmit}
          >
            <Send size={17} /> Submit for control
          </button>
        ) : null}
        {canApprove && order.status === 'submitted' ? (
          <>
            <button className="button button--primary" onClick={onApprove}>
              <ShieldCheck size={17} /> Approve
            </button>
            <button className="button button--danger" onClick={onReject}>
              Reject
            </button>
          </>
        ) : null}
        {canManage
          && ['draft', 'submitted', 'approved'].includes(order.status) ? (
            <button className="button button--secondary" onClick={onCancel}>
              Cancel order
            </button>
          ) : null}
      </div>
    </div>
  )
}
