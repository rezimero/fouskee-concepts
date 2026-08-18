import { ChevronRight, type LucideIcon } from 'lucide-react'
import type { ReactNode } from 'react'

type Props = {
  icon: LucideIcon
  title: string
  subtitle: string
  meta?: ReactNode
  status?: ReactNode
  onClick: () => void
}

export function RecordCard({
  icon: Icon,
  title,
  subtitle,
  meta,
  status,
  onClick,
}: Props) {
  return (
    <button className="ops-record" type="button" onClick={onClick}>
      <span className="ops-record__icon"><Icon size={18} /></span>
      <span className="ops-record__body">
        <strong>{title}</strong>
        <small>{subtitle}</small>
      </span>
      {meta ? <span className="ops-record__meta">{meta}</span> : null}
      {status}
      <ChevronRight className="ops-record__chevron" size={18} />
    </button>
  )
}
