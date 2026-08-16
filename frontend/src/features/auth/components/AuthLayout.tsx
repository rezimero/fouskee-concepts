import type { ReactNode } from 'react'
import { BrandMark } from './BrandMark'

type Props = {
  eyebrow: string
  title: string
  description: string
  children: ReactNode
}

export function AuthLayout({ eyebrow, title, description, children }: Props) {
  return (
    <main className="auth-stage">
      <section className="auth-story">
        <BrandMark />
        <div className="auth-story__content">
          <span className="eyebrow">Secure commerce operations</span>
          <h1>Control every bag. Verify every action.</h1>
          <p>
            Fouskee Concepts combines sales, inventory, payments, controls,
            and audit intelligence in one accountable operating system.
          </p>
        </div>
        <div className="auth-story__footer">
          <span className="status-dot" />
          Protected enterprise workspace
        </div>
      </section>

      <section className="auth-panel">
        <div className="auth-card">
          <div className="auth-card__heading">
            <span className="eyebrow">{eyebrow}</span>
            <h2>{title}</h2>
            <p>{description}</p>
          </div>
          {children}
        </div>
        <p className="auth-panel__legal">
          Access is monitored and security events are recorded.
        </p>
      </section>
    </main>
  )
}
