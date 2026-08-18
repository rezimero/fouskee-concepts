import { humanize } from '../lib/format'

type Props = {
  value: string
  risk?: boolean
}

const success = new Set([
  'approved',
  'posted',
  'received',
  'matched',
  'within_tolerance',
  'exception_approved',
  'active',
])

const danger = new Set([
  'rejected',
  'cancelled',
  'exception',
  'pending_exception',
])

const warning = new Set([
  'submitted',
  'pending_approval',
  'partially_received',
  'in_transit',
  'draft',
])

export function StatusBadge({ value, risk = false }: Props) {
  const tone = risk || danger.has(value)
    ? 'danger'
    : success.has(value)
      ? 'success'
      : warning.has(value)
        ? 'warning'
        : 'neutral'

  return (
    <span className={`ops-status ops-status--${tone}`}>
      <span className="ops-status__dot" />
      {humanize(value)}
    </span>
  )
}
