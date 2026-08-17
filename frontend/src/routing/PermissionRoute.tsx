import { Navigate } from 'react-router-dom'
import type { ReactNode } from 'react'
import { useAuth } from '../features/auth/hooks/useAuth'
import { canAccess } from '../shell/config/navigation'

type PermissionRouteProps = {
  permission: string
  children: ReactNode
}

export function PermissionRoute({
  permission,
  children,
}: PermissionRouteProps) {
  const { user } = useAuth()

  if (!canAccess(user?.permissions ?? [], permission)) {
    return <Navigate to="/" replace />
  }

  return children
}
