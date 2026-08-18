import {
  BadgeDollarSign,
  Boxes,
  ClipboardCheck,
  FileSearch,
  LayoutDashboard,
  MapPin,
  MonitorSmartphone,
  PackageCheck,
  ShoppingCart,
  Tags,
  Truck,
  UsersRound,
  type LucideIcon,
} from 'lucide-react'

export type NavigationSection =
  | 'Command'
  | 'Commerce'
  | 'Inventory'
  | 'Procurement'
  | 'Security'

export type NavigationItem = {
  label: string
  path: string
  icon: LucideIcon
  permission?: string
  section: NavigationSection
}

export const navigation: NavigationItem[] = [
  {
    label: 'Command overview',
    path: '/',
    icon: LayoutDashboard,
    section: 'Command',
  },
  {
    label: 'Customers',
    path: '/customers',
    icon: UsersRound,
    permission: 'customers.view',
    section: 'Commerce',
  },
  {
    label: 'Suppliers',
    path: '/suppliers',
    icon: Truck,
    permission: 'suppliers.view',
    section: 'Commerce',
  },
  {
    label: 'Products',
    path: '/products',
    icon: Boxes,
    permission: 'catalog.view',
    section: 'Commerce',
  },
  {
    label: 'Catalog setup',
    path: '/catalog/setup',
    icon: Tags,
    permission: 'catalog.manage',
    section: 'Commerce',
  },
  {
    label: 'Price lists',
    path: '/price-lists',
    icon: BadgeDollarSign,
    permission: 'pricing.view',
    section: 'Commerce',
  },
  {
    label: 'Stock intelligence',
    path: '/inventory',
    icon: Boxes,
    permission: 'inventory.view',
    section: 'Inventory',
  },
  {
    label: 'Locations',
    path: '/inventory/locations',
    icon: MapPin,
    permission: 'inventory.view',
    section: 'Inventory',
  },
  {
    label: 'Stock counts',
    path: '/inventory/counts',
    icon: ClipboardCheck,
    permission: 'inventory.view',
    section: 'Inventory',
  },
  {
    label: 'Transfers',
    path: '/inventory/transfers',
    icon: Truck,
    permission: 'inventory.view',
    section: 'Inventory',
  },
  {
    label: 'Purchase orders',
    path: '/procurement/purchase-orders',
    icon: ShoppingCart,
    permission: 'procurement.view',
    section: 'Procurement',
  },
  {
    label: 'Receiving desk',
    path: '/procurement/receiving',
    icon: PackageCheck,
    permission: 'procurement.view',
    section: 'Procurement',
  },
  {
    label: 'Supplier invoices',
    path: '/procurement/invoices',
    icon: FileSearch,
    permission: 'procurement.view',
    section: 'Procurement',
  },
  {
    label: 'Sessions & devices',
    path: '/security/sessions',
    icon: MonitorSmartphone,
    section: 'Security',
  },
]

export const navigationSections: NavigationSection[] = [
  'Command',
  'Commerce',
  'Inventory',
  'Procurement',
  'Security',
]

export function canAccess(permissions: string[], permission?: string) {
  if (!permission) return true
  return permissions.includes('*') || permissions.includes(permission)
}
