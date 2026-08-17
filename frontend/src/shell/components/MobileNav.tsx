import { NavLink } from 'react-router-dom'
import { useAuth } from '../../features/auth/hooks/useAuth'
import { canAccess, navigation } from '../config/navigation'

export function MobileNav() {
  const { user } = useAuth()
  const permissions = user?.permissions ?? []

  return (
    <nav className="mobile-nav" aria-label="Mobile navigation">
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
                `mobile-nav__link ${isActive ? 'mobile-nav__link--active' : ''}`
              }
            >
              <Icon size={19} />
              <span>{item.label}</span>
            </NavLink>
          )
        })}
    </nav>
  )
}
