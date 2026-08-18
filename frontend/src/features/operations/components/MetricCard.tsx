import type { LucideIcon } from 'lucide-react'

type Props = {
  label: string
  value: string
  note: string
  icon: LucideIcon
  tone?: 'green' | 'orange' | 'charcoal'
}

export function MetricCard({
  label,
  value,
  note,
  icon: Icon,
  tone = 'green',
}: Props) {
  return (
    <article className={`ops-metric ops-metric--${tone}`}>
      <span className="ops-metric__icon"><Icon size={19} /></span>
      <div>
        <span>{label}</span>
        <strong>{value}</strong>
        <small>{note}</small>
      </div>
    </article>
  )
}
