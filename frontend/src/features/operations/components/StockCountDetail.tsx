import { Scale, ShieldCheck } from 'lucide-react'
import { DetailFacts } from './DetailFacts'
import { StatusBadge } from './StatusBadge'
import { money, quantity } from '../lib/format'
import type { StockCount } from '../types'

type Props = {
  count: StockCount
  canCount: boolean
  canApprove: boolean
  onSubmit: () => void
  onApprove: () => void
  onReject: () => void
}

export function StockCountDetail({
  count,
  canCount,
  canApprove,
  onSubmit,
  onApprove,
  onReject,
}: Props) {
  return (
    <div className="form-stack">
      <DetailFacts facts={[
        { label: 'Status', value: <StatusBadge value={count.status} /> },
        { label: 'Location', value: count.location.name ?? '—' },
        { label: 'Reason', value: count.reason_code },
        {
          label: 'Absolute variance value',
          value: money(count.total_abs_variance_value),
        },
      ]} />
      <div className="ops-evidence-list">
        {count.lines?.map((line, index) => (
          <article key={`${line.product_id}-${line.lot_id}-${index}`}>
            <Scale size={17} />
            <div>
              <strong>{line.product_name ?? 'Product'}</strong>
              <small>
                System {quantity(line.system_quantity_base)} ·
                {' '}counted {quantity(line.counted_quantity_base)}
              </small>
            </div>
            <strong>{quantity(line.variance_quantity_base)}</strong>
          </article>
        ))}
      </div>
      <div className="button-row">
        {canCount && count.status === 'draft' ? (
          <button className="button button--primary" onClick={onSubmit}>
            <ShieldCheck size={17} /> Submit count
          </button>
        ) : null}
        {canApprove && count.status === 'pending_approval' ? (
          <>
            <button className="button button--primary" onClick={onApprove}>
              Approve
            </button>
            <button className="button button--danger" onClick={onReject}>
              Reject
            </button>
          </>
        ) : null}
      </div>
    </div>
  )
}
