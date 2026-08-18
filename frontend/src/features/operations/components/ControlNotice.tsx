import { ShieldCheck, TriangleAlert } from 'lucide-react'

type Props = {
  title: string
  children: string
  risk?: boolean
}

export function ControlNotice({ title, children, risk = false }: Props) {
  const Icon = risk ? TriangleAlert : ShieldCheck

  return (
    <div className={`control-notice ${risk ? 'control-notice--risk' : ''}`}>
      <span className="control-notice__icon"><Icon size={18} /></span>
      <div>
        <strong>{title}</strong>
        <p>{children}</p>
      </div>
    </div>
  )
}
