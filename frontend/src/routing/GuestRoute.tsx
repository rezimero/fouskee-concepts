import type { ReactNode } from 'react'
import { Navigate } from 'react-router-dom'
import { useAuth } from '../features/auth/hooks/useAuth'
import { LoadingScreen } from '../shared/components/LoadingScreen'

export function GuestRoute({ children }: { children: ReactNode }) {
  const { status, user } = useAuth()

  if (status === 'loading') return <LoadingScreen />
  if (user) return <Navigate to="/" replace />

  return children
}
