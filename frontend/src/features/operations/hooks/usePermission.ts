import { useAuth } from '../../auth/hooks/useAuth'
import { canAccess } from '../../../shell/config/navigation'

export function usePermission(permission: string) {
  const { user } = useAuth()
  return canAccess(user?.permissions ?? [], permission)
}

export function useAnyPermission(permissions: string[]) {
  const { user } = useAuth()
  const available = user?.permissions ?? []
  return permissions.some((permission) => canAccess(available, permission))
}
