import { ShoppingCart } from 'lucide-react'
import { RecordCard } from './RecordCard'
import { StatusBadge } from './StatusBadge'
import { money } from '../lib/format'
import type { PurchaseOrder } from '../types'

export function PurchaseOrderList({
  orders,
  onOpen,
}: {
  orders: PurchaseOrder[]
  onOpen: (id: string) => void
}) {
  return (
    <div className="ops-record-list">
      {orders.map((order) => (
        <RecordCard
          key={order.id}
          icon={ShoppingCart}
          title={order.number}
          subtitle={order.supplier?.name ?? 'Supplier'}
          meta={money(order.total_amount, order.currency_code)}
          status={<StatusBadge value={order.status} />}
          onClick={() => onOpen(order.id)}
        />
      ))}
    </div>
  )
}
