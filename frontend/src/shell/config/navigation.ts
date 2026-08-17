import { LayoutDashboard, MonitorSmartphone, type LucideIcon } from 'lucide-react'

export type NavigationItem = {
  label: string
  path: string
  icon: LucideIcon
  permission?: string
}

export const navigation: NavigationItem[] = [
  { label: 'Command overview', path: '/', icon: LayoutDashboard },
  {
    label: 'Sessions & devices',
    path: '/security/sessions',
    icon: MonitorSmartphone,
  },
]

export function canAccess(permissions: string[], permission?: string) {
  if (!permission) return true
  return permissions.includes('*') || permissions.includes(permission)
}
