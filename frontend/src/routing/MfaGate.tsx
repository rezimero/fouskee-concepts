import type { ReactNode } from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../features/auth/hooks/useAuth'

export function MfaGate({ children }: { children: ReactNode }) {
  const { user } = useAuth()
  const location = useLocation()

  if (
    user?.mfa.required &&
    !user.mfa.enrolled &&
    location.pathname !== '/security/mfa/setup'
  ) {
    return <Navigate to="/security/mfa/setup" replace />
  }

  return children
}
