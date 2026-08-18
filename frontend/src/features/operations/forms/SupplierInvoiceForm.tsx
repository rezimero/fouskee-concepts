import { useEffect, useMemo, useState, type FormEvent } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import type { Supplier } from '../../master-data/types'
import { procurementApi } from '../api/procurementApi'
import type { PurchaseOrder, SupplierInvoice } from '../types'
import {
  InvoiceOrderLines,
  type InvoiceDraft,
} from './InvoiceOrderLines'
type Props = {
  suppliers: Supplier[]
  orders: PurchaseOrder[]
  onCreated: (invoice: SupplierInvoice) => void
  onCancel: () => void
}
export function SupplierInvoiceForm({
  suppliers,
  orders,
  onCreated,
  onCancel,
}: Props) {
  const [supplierId, setSupplierId] = useState('')
  const [orderId, setOrderId] = useState('')
  const [order, setOrder] = useState<PurchaseOrder | null>(null)
  const [rows, setRows] = useState<InvoiceDraft[]>([])
  const [loadingOrder, setLoadingOrder] = useState(false)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const supplierOrders = useMemo(
    () => orders.filter((item) =>
      item.supplier?.id === supplierId
      && ['approved', 'partially_received', 'received', 'closed']
        .includes(item.status),
    ),
    [orders, supplierId],
  )
  useEffect(() => {
    if (!orderId) return
    let active = true
    procurementApi.purchaseOrder(orderId)
      .then((value) => {
        if (!active) return
        setOrder(value)
        setRows((value.lines ?? []).map((line) => ({
          line_id: line.id,
          quantity: '',
          unit_price: line.unit_price,
        })))
      })
      .catch((caught) => {
        if (active) {
          setError(requestErrorMessage(
            caught,
            'Unable to load purchase order.',
          ))
        }
      })
      .finally(() => {
        if (active) setLoadingOrder(false)
      })
    return () => { active = false }
  }, [orderId])
  function patch(lineId: string, values: Partial<InvoiceDraft>) {
    setRows((current) => current.map((row) =>
      row.line_id === lineId ? { ...row, ...values } : row,
    ))
  }
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    const lines = rows.filter((row) => Number(row.quantity) > 0)
    if (!order || lines.length === 0) {
      setError('Select a PO and enter at least one invoiced quantity.')
      return
    }
    setSaving(true)
    setError('')
    try {
      const invoice = await procurementApi.createInvoice({
        supplier_id: supplierId,
        invoice_number: form.get('invoice_number'),
        invoice_date: form.get('invoice_date'),
        tax_amount: Number(form.get('tax_amount') || 0),
        lines: lines.map((row) => {
          const poLine = order.lines?.find((item) => item.id === row.line_id)
          return {
            purchase_order_line_id: row.line_id,
            product_id: poLine?.product_id,
            packaging_id: poLine?.packaging_id || null,
            quantity: Number(row.quantity),
            unit_price: Number(row.unit_price),
          }
        }),
      })
      onCreated(invoice)
    } catch (caught) {
      setError(requestErrorMessage(
        caught,
        'Unable to record supplier invoice.',
      ))
    } finally {
      setSaving(false)
    }
  }
  return (
    <form className="form-stack" onSubmit={(event) => void submit(event)}>
      {error ? <div className="alert alert--danger">{error}</div> : null}
      <div className="form-grid">
        <label className="field">
          Supplier
          <select
            required
            autoFocus
            value={supplierId}
            onChange={(event) => {
              setSupplierId(event.target.value)
              setOrderId('')
              setOrder(null)
              setRows([])
              setLoadingOrder(false)
            }}
          >
            <option value="">Select supplier</option>
            {suppliers.map((supplier) => (
              <option value={supplier.id} key={supplier.id}>
                {supplier.number} · {supplier.display_name}
              </option>
            ))}
          </select>
        </label>
        <label className="field">
          Linked purchase order
          <select
            required
            value={orderId}
            disabled={!supplierId}
            onChange={(event) => {
              const value = event.target.value
              setOrderId(value)
              setOrder(null)
              setRows([])
              setLoadingOrder(Boolean(value))
            }}
          >
            <option value="">Select purchase order</option>
            {supplierOrders.map((item) => (
              <option value={item.id} key={item.id}>{item.number}</option>
            ))}
          </select>
        </label>
      </div>
      <div className="form-grid">
        <label className="field">
          Supplier invoice number
          <input name="invoice_number" required maxLength={160} />
        </label>
        <label className="field">
          Invoice date
          <input name="invoice_date" type="date" required />
        </label>
      </div>
      <label className="field">
        Supplier-stated tax amount (NGN)
        <input
          name="tax_amount"
          type="number"
          min="0"
          step="0.01"
          defaultValue="0"
        />
      </label>
      {loadingOrder
        ? <div className="ops-empty">Loading frozen PO terms…</div>
        : null}
      {order ? (
        <InvoiceOrderLines
          lines={order.lines ?? []}
          rows={rows}
          onPatch={patch}
        />
      ) : null}
      <div className="form-actions">
        <button
          className="button button--secondary"
          type="button"
          onClick={onCancel}
        >
          Cancel
        </button>
        <button
          className="button button--primary"
          type="submit"
          disabled={saving}
        >
          {saving ? 'Recording invoice…' : 'Record supplier invoice'}
        </button>
      </div>
    </form>
  )
}
