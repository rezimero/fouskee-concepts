import { X } from 'lucide-react'
import type { ReactNode } from 'react'

type SheetProps = {
  open: boolean
  title: string
  subtitle?: string
  children: ReactNode
  wide?: boolean
  onClose: () => void
}

export function Sheet({
  open,
  title,
  subtitle,
  children,
  wide = false,
  onClose,
}: SheetProps) {
  if (!open) return null

  return (
    <div className="sheet-backdrop" role="presentation" onMouseDown={onClose}>
      <aside
        className={`sheet ${wide ? 'sheet--wide' : ''}`}
        role="dialog"
        aria-modal="true"
        aria-label={title}
        onMouseDown={(event) => event.stopPropagation()}
      >
        <header className="sheet__header">
          <div>
            <span className="eyebrow">Fouskee workspace</span>
            <h2>{title}</h2>
            {subtitle ? <p>{subtitle}</p> : null}
          </div>
          <button
            className="icon-button"
            type="button"
            aria-label={`Close ${title}`}
            onClick={onClose}
          >
            <X size={18} />
          </button>
        </header>
        <div className="sheet__body">{children}</div>
      </aside>
    </div>
  )
}
