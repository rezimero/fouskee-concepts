import {
  BadgeCheck,
  Boxes,
  ClipboardCheck,
  FileSearch,
  PackageCheck,
  ShieldAlert,
  ShoppingCart,
  Truck,
} from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { requestErrorMessage } from '../../lib/errors'
import { useAuth } from '../../features/auth/hooks/useAuth'
import { inventoryApi } from '../../features/operations/api/inventoryApi'
import { procurementApi } from '../../features/operations/api/procurementApi'
import { MetricCard } from '../../features/operations/components/MetricCard'
import { StatusBadge } from '../../features/operations/components/StatusBadge'
import { money } from '../../features/operations/lib/format'
import type {
  InventoryBalance,
  StockTransfer,
  PurchaseOrder,
  GoodsReceipt,
  SupplierInvoice,
} from '../../features/operations/types'
import { canAccess } from '../config/navigation'

const modules = [
  { label: 'Stock intelligence', path: '/inventory', icon: Boxes, note: 'Balances, lots, value, ledger', permission: 'inventory.view' },
  { label: 'Stock counts', path: '/inventory/counts', icon: ClipboardCheck, note: 'Variance + maker-checker', permission: 'inventory.view' },
  { label: 'Transfers', path: '/inventory/transfers', icon: Truck, note: 'Dispatch, transit, receipt', permission: 'inventory.view' },
  { label: 'Purchase orders', path: '/procurement/purchase-orders', icon: ShoppingCart, note: 'Frozen supplier terms', permission: 'procurement.view' },
  { label: 'Receiving desk', path: '/procurement/receiving', icon: PackageCheck, note: 'PO-backed physical receipt', permission: 'procurement.view' },
  { label: 'Supplier invoices', path: '/procurement/invoices', icon: FileSearch, note: 'Three-way verification', permission: 'procurement.view' },
]

export function DashboardPage() {
  const { user } = useAuth()
  const permissions = useMemo(() => user?.permissions ?? [], [user?.permissions])
  const inventoryVisible = canAccess(permissions, 'inventory.view')
  const procurementVisible = canAccess(permissions, 'procurement.view')
  const [balances, setBalances] = useState<InventoryBalance[]>([])
  const [transfers, setTransfers] = useState<StockTransfer[]>([])
  const [orders, setOrders] = useState<PurchaseOrder[]>([])
  const [receipts, setReceipts] = useState<GoodsReceipt[]>([])
  const [invoices, setInvoices] = useState<SupplierInvoice[]>([])
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    const inventory = inventoryVisible
      ? Promise.all([inventoryApi.balances(), inventoryApi.transfers()])
      : Promise.resolve([[], []] as [InventoryBalance[], StockTransfer[]])
    const procurement = procurementVisible
      ? Promise.all([
        procurementApi.purchaseOrders(),
        procurementApi.receipts(),
        procurementApi.invoices(),
      ])
      : Promise.resolve([[], [], []] as [
        PurchaseOrder[],
        GoodsReceipt[],
        SupplierInvoice[],
      ])

    void Promise.all([inventory, procurement])
      .then(([[balanceData, transferData], [orderData, receiptData, invoiceData]]) => {
        if (!active) return
        setBalances(balanceData)
        setTransfers(transferData)
        setOrders(orderData)
        setReceipts(receiptData)
        setInvoices(invoiceData)
      })
      .catch((caught) => {
        if (active) setError(requestErrorMessage(caught, 'Unable to load command overview.'))
      })

    return () => { active = false }
  }, [inventoryVisible, procurementVisible])

  const inventoryValue = balances.reduce(
    (sum, item) => sum + Number(item.inventory_value),
    0,
  )
  const exceptions = orders.filter((item) => item.status === 'submitted').length
    + receipts.filter((item) => item.status === 'pending_exception').length
    + invoices.filter((item) => item.status === 'exception').length
  const inTransit = transfers.filter((item) => item.status === 'in_transit').length
  const eligible = invoices.filter((item) => item.eligible_for_payment).length
  const visibleModules = useMemo(
    () => modules.filter((item) => canAccess(permissions, item.permission)),
    [permissions],
  )

  return (
    <div className="page-stack">
      <section className="hero-card hero-card--operations">
        <div>
          <span className="eyebrow">Command overview</span>
          <h1>Every bag has evidence.</h1>
          <p>
            Inventory and procurement controls are live from physical stock
            through supplier invoice verification.
          </p>
        </div>
        <div className="hero-card__seal">
          <BadgeCheck size={30} />
          <span>
            <strong>{user?.organization?.name ?? 'Fouskee Concepts'}</strong>
            <small>Ledger + maker-checker controls active</small>
          </span>
        </div>
      </section>

      {error ? <div className="alert alert--danger">{error}</div> : null}

      <section className="ops-metric-grid">
        <MetricCard icon={Boxes} label="Inventory value" value={money(inventoryValue)} note="Perpetual weighted-average projection" />
        <MetricCard icon={Truck} label="Transfers in transit" value={String(inTransit)} note="Physical custody not yet confirmed" tone="orange" />
        <MetricCard icon={ShieldAlert} label="Control queue" value={String(exceptions)} note="Approvals or exceptions requiring attention" tone="orange" />
        <MetricCard icon={PackageCheck} label="Payment eligible" value={String(eligible)} note="Procurement eligibility, not payment" tone="charcoal" />
      </section>

      <section>
        <div className="section-heading">
          <div>
            <span className="eyebrow">Operational workspaces</span>
            <h2>Inventory + procurement</h2>
          </div>
          <StatusBadge value="audit_protected" />
        </div>
        <div className="module-grid ops-module-grid">
          {visibleModules.map(({ label, path, icon: Icon, note }) => (
            <Link className="module-card module-card--link" to={path} key={label}>
              <span className="module-card__icon"><Icon size={21} /></span>
              <strong>{label}</strong>
              <small>{note}</small>
            </Link>
          ))}
        </div>
      </section>
    </div>
  )
}
