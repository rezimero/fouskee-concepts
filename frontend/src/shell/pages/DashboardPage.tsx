import {
  BadgeCheck,
  BadgeDollarSign,
  Boxes,
  ShieldAlert,
  Truck,
  UsersRound,
} from 'lucide-react'
import { Link } from 'react-router-dom'
import { useAuth } from '../../features/auth/hooks/useAuth'

const modules = [
  { label: 'Customers', path: '/customers', icon: UsersRound, note: 'Wholesale + retail accounts' },
  { label: 'Suppliers', path: '/suppliers', icon: Truck, note: 'Procurement identities' },
  { label: 'Products', path: '/products', icon: Boxes, note: 'Multi-unit packaging' },
  { label: 'Price lists', path: '/price-lists', icon: BadgeDollarSign, note: 'Floor-protected pricing' },
]

export function DashboardPage() {
  const { user } = useAuth()

  return (
    <div className="page-stack">
      <section className="hero-card">
        <div>
          <span className="eyebrow">Command overview</span>
          <h1>Commercial control is online.</h1>
          <p>
            Customer, supplier, catalog, packaging, and pricing master data now
            sit behind your authenticated, MFA-protected operating workspace.
          </p>
        </div>
        <div className="hero-card__seal">
          <BadgeCheck size={30} />
          <span>
            <strong>{user?.organization?.name ?? 'Fouskee Concepts'}</strong>
            <small>Identity + commercial controls active</small>
          </span>
        </div>
      </section>

      <section className="metric-grid">
        <article className="metric-card">
          <span>Commercial foundation</span>
          <strong>Live</strong>
          <small>Organization-scoped master data</small>
        </article>
        <article className="metric-card">
          <span>Pricing posture</span>
          <strong>Controlled</strong>
          <small>Price floors + audit reasons</small>
        </article>
        <article className="metric-card">
          <span>Inventory posture</span>
          <strong>Ledger-ready</strong>
          <small>No mutable stock field on products</small>
        </article>
      </section>

      <section>
        <div className="section-heading">
          <div>
            <span className="eyebrow">Operational workspace</span>
            <h2>Commercial master data</h2>
          </div>
          <span className="security-chip">
            <ShieldAlert size={16} /> Fraud controls by design
          </span>
        </div>
        <div className="module-grid">
          {modules.map(({ label, path, icon: Icon, note }) => (
            <Link className="module-card module-card--link" to={path} key={label}>
              <span className="module-card__icon"><Icon size={21} /></span>
              <strong>{label}</strong>
              <small>{note}</small>
            </Link>
          ))}
        </div>
      </section>
    </div>
  )
}
