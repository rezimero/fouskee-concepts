import {
  PanelLeftClose,
  PanelLeftOpen,
} from 'lucide-react'
import { NavLink } from 'react-router-dom'
import { BrandMark } from '../../features/auth/components/BrandMark'
import { useAuth } from '../../features/auth/hooks/useAuth'
import {
  canAccess,
  navigation,
  navigationSections,
} from '../config/navigation'

type Props = {
  collapsed: boolean
  onToggle: () => void
}

export function Sidebar({ collapsed, onToggle }: Props) {
  const { user } = useAuth()
  const permissions = user?.permissions ?? []
  const visible = navigation.filter(
    (item) => canAccess(permissions, item.permission),
  )
  const toggleLabel = collapsed
    ? 'Expand navigation'
    : 'Collapse navigation'
  const ToggleIcon = collapsed ? PanelLeftOpen : PanelLeftClose

  return (
    <aside
      className="sidebar"
      aria-label={collapsed ? 'Collapsed primary navigation' : undefined}
    >
      <div className="sidebar__brand">
        <BrandMark compact={collapsed} />
      </div>
      <div className="sidebar__rail-control">
        <button
          className="sidebar-toggle"
          type="button"
          aria-controls="primary-navigation"
          aria-expanded={!collapsed}
          aria-label={toggleLabel}
          title={toggleLabel}
          onClick={onToggle}
        >
          <ToggleIcon size={18} />
          <span>{toggleLabel}</span>
        </button>
      </div>
      <nav
        id="primary-navigation"
        className="sidebar__nav"
        aria-label="Primary navigation"
      >
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
                    aria-label={collapsed ? item.label : undefined}
                    title={item.label}
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
