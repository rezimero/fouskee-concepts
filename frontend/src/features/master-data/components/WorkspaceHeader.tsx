import type { ReactNode } from 'react'

type WorkspaceHeaderProps = {
  eyebrow: string
  title: string
  description: string
  actions?: ReactNode
}

export function WorkspaceHeader({
  eyebrow,
  title,
  description,
  actions,
}: WorkspaceHeaderProps) {
  return (
    <div className="workspace-header">
      <div>
        <span className="eyebrow">{eyebrow}</span>
        <h1>{title}</h1>
        <p>{description}</p>
      </div>
      {actions ? <div className="workspace-header__actions">{actions}</div> : null}
    </div>
  )
}
