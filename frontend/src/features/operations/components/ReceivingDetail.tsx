import { PackageCheck, RotateCcw } from 'lucide-react'
import { ControlNotice } from './ControlNotice'
import { DetailFacts } from './DetailFacts'
import { StatusBadge } from './StatusBadge'
import { dateTime, money, quantity } from '../lib/format'
import type { GoodsReceipt } from '../types'

type Props = {
  receipt: GoodsReceipt
  canException: boolean
  canReverse: boolean
  onApprove: () => void
  onReject: () => void
  onReverse: () => void
}

export function ReceivingDetail({
  receipt,
  canException,
  canReverse,
  onApprove,
  onReject,
  onReverse,
}: Props) {
  return (
    <div className="form-stack">
      <DetailFacts facts={[
        { label: 'Status', value: <StatusBadge value={receipt.status} /> },
        {
          label: 'Purchase order',
          value: receipt.purchase_order_number ?? '—',
        },
        { label: 'Location', value: receipt.location?.name ?? '—' },
        {
          label: 'Physical receipt',
          value: dateTime(receipt.received_at),
        },
        {
          label: 'Inventory movement',
          value: receipt.movement_id ?? 'Not posted',
        },
      ]} />
      {receipt.status === 'pending_exception' ? (
        <ControlNotice title="Receiving exception" risk>
          This receipt is outside configured tolerance. Stock has not been posted.
        </ControlNotice>
      ) : null}
      <div className="ops-evidence-list">
        {receipt.lines?.map((line) => (
          <article key={line.id}>
            <PackageCheck size={17} />
            <div>
              <strong>{line.product_name ?? 'Product'}</strong>
              <small>
                {quantity(line.quantity)} entered ·
                {' '}{quantity(line.quantity_base)} base
              </small>
            </div>
            <strong>
              {money(Number(line.quantity_base) * Number(line.unit_cost_base))}
            </strong>
          </article>
        ))}
      </div>
      <div className="button-row">
        {canException && receipt.status === 'pending_exception' ? (
          <>
            <button className="button button--primary" onClick={onApprove}>
              Approve exception
            </button>
            <button className="button button--danger" onClick={onReject}>
              Reject exception
            </button>
          </>
        ) : null}
        {canReverse && receipt.status === 'posted' ? (
          <button className="button button--danger" onClick={onReverse}>
            <RotateCcw size={17} /> Reverse receipt
          </button>
        ) : null}
      </div>
    </div>
  )
}
