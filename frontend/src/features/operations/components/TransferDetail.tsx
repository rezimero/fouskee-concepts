import { ArrowRight, PackageCheck } from 'lucide-react'
import { DetailFacts } from './DetailFacts'
import { StatusBadge } from './StatusBadge'
import { dateTime, quantity } from '../lib/format'
import type { StockTransfer } from '../types'

type Props = {
  transfer: StockTransfer
  canTransfer: boolean
  busy: boolean
  onDispatch: () => void
  onReceive: () => void
  onCancel: () => void
}

export function TransferDetail({
  transfer,
  canTransfer,
  busy,
  onDispatch,
  onReceive,
  onCancel,
}: Props) {
  return (
    <div className="form-stack">
      <DetailFacts facts={[
        { label: 'Status', value: <StatusBadge value={transfer.status} /> },
        { label: 'Source', value: transfer.source?.name ?? '—' },
        { label: 'Transit', value: transfer.transit?.name ?? 'Not dispatched' },
        { label: 'Destination', value: transfer.destination?.name ?? '—' },
        { label: 'Dispatched', value: dateTime(transfer.dispatched_at) },
        { label: 'Received', value: dateTime(transfer.received_at) },
      ]} />
      <div className="ops-evidence-list">
        {transfer.lines?.map((line, index) => (
          <article key={`${line.product_id}-${index}`}>
            <PackageCheck size={17} />
            <div>
              <strong>{line.product_name ?? 'Product'}</strong>
              <small>
                {quantity(line.entered_quantity)} entered ·
                {' '}{quantity(line.quantity_base)} base
              </small>
            </div>
            <ArrowRight size={17} />
          </article>
        ))}
      </div>
      {canTransfer ? (
        <div className="button-row">
          {transfer.status === 'draft' ? (
            <button
              className="button button--primary"
              disabled={busy}
              onClick={onDispatch}
            >
              Dispatch to transit
            </button>
          ) : null}
          {transfer.status === 'in_transit' ? (
            <button
              className="button button--primary"
              disabled={busy}
              onClick={onReceive}
            >
              Confirm destination receipt
            </button>
          ) : null}
          {['draft', 'in_transit'].includes(transfer.status) ? (
            <button className="button button--danger" onClick={onCancel}>
              Cancel transfer
            </button>
          ) : null}
        </div>
      ) : null}
    </div>
  )
}
