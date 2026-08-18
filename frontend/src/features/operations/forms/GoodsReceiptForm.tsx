import { useEffect, useMemo, useState, type FormEvent } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import { procurementApi } from '../api/procurementApi'
import type {
  GoodsReceipt,
  InventoryLocation,
  PurchaseOrder,
} from '../types'
import {
  ReceiptOrderLines,
  type ReceiptDraft,
} from './ReceiptOrderLines'

type Props = {
  orders: PurchaseOrder[]
  locations: InventoryLocation[]
  onCreated: (receipt: GoodsReceipt) => void
  onCancel: () => void
}

export function GoodsReceiptForm({
  orders,
  locations,
  onCreated,
  onCancel,
}: Props) {
  const receivable = useMemo(
    () => orders.filter((order) =>
      ['approved', 'partially_received'].includes(order.status),
    ),
    [orders],
  )
  const [orderId, setOrderId] = useState('')
  const [order, setOrder] = useState<PurchaseOrder | null>(null)
  const [rows, setRows] = useState<ReceiptDraft[]>([])
  const [loadingOrder, setLoadingOrder] = useState(false)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

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
          lot_code: '',
          expiry_date: '',
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

  function patch(lineId: string, values: Partial<ReceiptDraft>) {
    setRows((current) => current.map((row) =>
      row.line_id === lineId ? { ...row, ...values } : row,
    ))
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    const lines = rows.filter((row) => Number(row.quantity) > 0)

    if (!order || lines.length === 0) {
      setError('Enter a received quantity for at least one PO line.')
      return
    }

    setSaving(true)
    setError('')

    try {
      const receipt = await procurementApi.createReceipt(
        {
          purchase_order_id: order.id,
          location_id: form.get('location_id'),
          received_at: form.get('received_at') || null,
          notes: form.get('notes') || null,
          lines: lines.map((row) => ({
            purchase_order_line_id: row.line_id,
            quantity: Number(row.quantity),
            lot_code: row.lot_code || null,
            expiry_date: row.expiry_date || null,
          })),
        },
        String(form.get('backdate_reason') || '') || undefined,
      )
      onCreated(receipt)
    } catch (caught) {
      setError(requestErrorMessage(
        caught,
        'Unable to record goods receipt.',
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
          Approved purchase order
          <select
            required
            autoFocus
            value={orderId}
            onChange={(event) => {
              const value = event.target.value
              setOrderId(value)
              setOrder(null)
              setRows([])
              setLoadingOrder(Boolean(value))
            }}
          >
            <option value="">Select order</option>
            {receivable.map((item) => (
              <option value={item.id} key={item.id}>
                {item.number} · {item.supplier?.name ?? 'Supplier'}
              </option>
            ))}
          </select>
        </label>
        <label className="field">
          Receiving location
          <select name="location_id" required defaultValue="">
            <option value="">Select location</option>
            {locations.map((location) => (
              <option value={location.id} key={location.id}>
                {location.code} · {location.name}
              </option>
            ))}
          </select>
        </label>
      </div>
      <label className="field">
        Physical receipt time
        <input name="received_at" type="datetime-local" />
      </label>
      {loadingOrder
        ? <div className="ops-empty">Loading approved terms…</div>
        : null}
      {order ? (
        <ReceiptOrderLines
          lines={order.lines ?? []}
          rows={rows}
          onPatch={patch}
        />
      ) : null}
      <label className="field">
        Backdate reason
        <textarea
          name="backdate_reason"
          maxLength={2000}
          placeholder="Required only when physical receipt time is outside the normal backdate window."
        />
      </label>
      <label className="field">
        Receiving notes
        <textarea name="notes" maxLength={4000} />
      </label>
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
          {saving ? 'Posting evidence…' : 'Record physical receipt'}
        </button>
      </div>
    </form>
  )
}
