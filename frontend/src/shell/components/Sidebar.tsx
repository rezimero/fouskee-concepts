import { NavLink } from 'react-router-dom'
import { BrandMark } from '../../features/auth/components/BrandMark'
import { useAuth } from '../../features/auth/hooks/useAuth'
import { canAccess, navigation } from '../config/navigation'

export function Sidebar() {
  const { user } = useAuth()
  const permissions = user?.permissions ?? []

  return (
    <aside className="sidebar">
      <div className="sidebar__brand"><BrandMark /></div>
      <nav className="sidebar__nav" aria-label="Primary navigation">
        {navigation
          .filter((item) => canAccess(permissions, item.permission))
          .map((item) => {
            const Icon = item.icon
            return (
              <NavLink
                key={item.path}
                to={item.path}
                end={item.path === '/'}
                className={({ isActive }) =>
                  `nav-link ${isActive ? 'nav-link--active' : ''}`
                }
              >
                <Icon size={19} />
                <span>{item.label}</span>
              </NavLink>
            )
          })}
      </nav>
      <div className="sidebar__footer">
        <span className="status-dot" />
        <div>
          <strong>Security controls active</strong>
          <small>Audit trail protected</small>
        </div>
      </div>
    </aside>
  )
}
