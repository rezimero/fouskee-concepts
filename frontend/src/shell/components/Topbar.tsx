import { LogOut, ShieldCheck } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../../features/auth/hooks/useAuth'

export function Topbar() {
  const navigate = useNavigate()
  const { user, logout } = useAuth()

  async function signOut() {
    await logout()
    navigate('/login', { replace: true })
  }

  return (
    <header className="topbar">
      <div>
        <span className="eyebrow">Fouskee operations</span>
        <strong>{user?.organization?.name ?? 'Fouskee Concepts'}</strong>
      </div>
      <div className="topbar__actions">
        <span className="security-chip"><ShieldCheck size={16} /> MFA protected</span>
        <div className="user-chip">
          <span className="user-chip__avatar">
            {user?.name?.slice(0, 1).toUpperCase()}
          </span>
          <span>
            <strong>{user?.name}</strong>
            <small>{user?.email}</small>
          </span>
        </div>
        <button
          className="icon-button"
          type="button"
          aria-label="Sign out"
          onClick={() => void signOut()}
        >
          <LogOut size={18} />
        </button>
      </div>
    </header>
  )
}
