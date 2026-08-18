import { NavLink } from 'react-router-dom'
import { BrandMark } from '../../features/auth/components/BrandMark'
import { useAuth } from '../../features/auth/hooks/useAuth'
import {
  canAccess,
  navigation,
  navigationSections,
} from '../config/navigation'

export function Sidebar() {
  const { user } = useAuth()
  const permissions = user?.permissions ?? []
  const visible = navigation.filter(
    (item) => canAccess(permissions, item.permission),
  )

  return (
    <aside className="sidebar">
      <div className="sidebar__brand"><BrandMark /></div>
      <nav className="sidebar__nav" aria-label="Primary navigation">
        {navigationSections.map((section) => {
          const items = visible.filter((item) => item.section === section)
          if (items.length === 0) return null

          return (
            <div className="nav-section" key={section}>
              <span className="nav-section__label">{section}</span>
              {items.map((item) => {
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
            </div>
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
