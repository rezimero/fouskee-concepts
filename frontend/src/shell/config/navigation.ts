import {
  BadgeDollarSign,
  Boxes,
  LayoutDashboard,
  MonitorSmartphone,
  Tags,
  Truck,
  UsersRound,
  type LucideIcon,
} from 'lucide-react'

export type NavigationItem = {
  label: string
  path: string
  icon: LucideIcon
  permission?: string
}

export const navigation: NavigationItem[] = [
  { label: 'Command overview', path: '/', icon: LayoutDashboard },
  { label: 'Customers', path: '/customers', icon: UsersRound, permission: 'customers.view' },
  { label: 'Suppliers', path: '/suppliers', icon: Truck, permission: 'suppliers.view' },
  { label: 'Products', path: '/products', icon: Boxes, permission: 'catalog.view' },
  { label: 'Catalog setup', path: '/catalog/setup', icon: Tags, permission: 'catalog.manage' },
  { label: 'Price lists', path: '/price-lists', icon: BadgeDollarSign, permission: 'pricing.view' },
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
