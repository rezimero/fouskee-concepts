import {
  BadgeCheck,
  Boxes,
  CircleDollarSign,
  ShieldAlert,
  ShoppingCart,
  UsersRound,
} from 'lucide-react'
import { useAuth } from '../../features/auth/hooks/useAuth'

const modules = [
  { label: 'Sales & POS', icon: ShoppingCart, note: 'Next domain increment' },
  { label: 'Inventory', icon: Boxes, note: 'Ledger-ready architecture' },
  { label: 'Customers', icon: UsersRound, note: 'Wholesale + retail' },
  { label: 'Payments', icon: CircleDollarSign, note: 'Provider adapters planned' },
]

export function DashboardPage() {
  const { user } = useAuth()

  return (
    <div className="page-stack">
      <section className="hero-card">
        <div>
          <span className="eyebrow">Command overview</span>
          <h1>Good to see you, {user?.name?.split(' ')[0]}.</h1>
          <p>
            Your secure Fouskee workspace is ready. Operational modules will
            activate as each audited domain milestone is released.
          </p>
        </div>
        <div className="hero-card__seal">
          <BadgeCheck size={30} />
          <span>
            <strong>Identity verified</strong>
            <small>Session + MFA controls active</small>
          </span>
        </div>
      </section>

      <section className="metric-grid">
        <article className="metric-card">
          <span>Account security</span>
          <strong>Protected</strong>
          <small>MFA enrollment confirmed</small>
        </article>
        <article className="metric-card">
          <span>Access model</span>
          <strong>{user?.permissions.includes('*') ? 'Superadmin' : 'Scoped'}</strong>
          <small>{user?.permissions.length ?? 0} permission entries</small>
        </article>
        <article className="metric-card">
          <span>Audit posture</span>
          <strong>Append-only</strong>
          <small>Privileged activity attributable</small>
        </article>
      </section>

      <section>
        <div className="section-heading">
          <div>
            <span className="eyebrow">Platform roadmap</span>
            <h2>Operational domains</h2>
          </div>
          <span className="security-chip">
            <ShieldAlert size={16} /> Fraud controls by design
          </span>
        </div>
        <div className="module-grid">
          {modules.map(({ label, icon: Icon, note }) => (
            <article className="module-card" key={label}>
              <span className="module-card__icon"><Icon size={21} /></span>
              <strong>{label}</strong>
              <small>{note}</small>
            </article>
          ))}
        </div>
      </section>
    </div>
  )
}
