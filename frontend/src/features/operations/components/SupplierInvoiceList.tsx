import { FileSearch } from 'lucide-react'
import { RecordCard } from './RecordCard'
import { StatusBadge } from './StatusBadge'
import { dateOnly, money } from '../lib/format'
import type { SupplierInvoice } from '../types'

export function SupplierInvoiceList({
  invoices,
  onOpen,
}: {
  invoices: SupplierInvoice[]
  onOpen: (id: string) => void
}) {
  return (
    <div className="ops-record-list">
      {invoices.map((invoice) => (
        <RecordCard
          key={invoice.id}
          icon={FileSearch}
          title={invoice.invoice_number}
          subtitle={`${invoice.supplier?.name ?? 'Supplier'} · ${
            dateOnly(invoice.invoice_date)
          }`}
          meta={money(invoice.total_amount, invoice.currency_code)}
          status={
            <StatusBadge
              value={invoice.status}
              risk={invoice.status === 'exception'}
            />
          }
          onClick={() => onOpen(invoice.id)}
        />
      ))}
    </div>
  )
}
