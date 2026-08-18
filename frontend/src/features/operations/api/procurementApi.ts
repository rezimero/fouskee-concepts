import { http } from '../../../lib/http'
import { idempotencyKey } from '../lib/format'
import type {
  GoodsReceipt,
  MatchRun,
  PurchaseOrder,
  SupplierInvoice,
} from '../types'

type Collection<T> = { data: T[] }
type Entity<T> = { data: T }

const idem = (prefix: string) => ({
  'Idempotency-Key': idempotencyKey(prefix),
})

const reason = (value: string, prefix?: string) => ({
  ...(prefix ? idem(prefix) : {}),
  'X-Privileged-Reason': value,
})

export const procurementApi = {
  async purchaseOrders() {
    const { data } = await http.get<Collection<PurchaseOrder>>(
      '/api/v1/procurement/purchase-orders',
      { params: { per_page: 100 } },
    )
    return data.data
  },

  async purchaseOrder(id: string) {
    const { data } = await http.get<Entity<PurchaseOrder>>(
      `/api/v1/procurement/purchase-orders/${id}`,
    )
    return data.data
  },

  async createPurchaseOrder(payload: Record<string, unknown>) {
    const { data } = await http.post<Entity<PurchaseOrder>>(
      '/api/v1/procurement/purchase-orders',
      payload,
      { headers: idem('purchase-order') },
    )
    return data.data
  },

  async submitPurchaseOrder(id: string) {
    const { data } = await http.post<Entity<PurchaseOrder>>(
      `/api/v1/procurement/purchase-orders/${id}/submit`,
    )
    return data.data
  },

  async approvePurchaseOrder(id: string, reasonValue: string) {
    const { data } = await http.post<Entity<PurchaseOrder>>(
      `/api/v1/procurement/purchase-orders/${id}/approve`,
      {},
      { headers: reason(reasonValue) },
    )
    return data.data
  },

  async rejectPurchaseOrder(id: string, reasonValue: string) {
    const { data } = await http.post<Entity<PurchaseOrder>>(
      `/api/v1/procurement/purchase-orders/${id}/reject`,
      {},
      { headers: reason(reasonValue) },
    )
    return data.data
  },

  async cancelPurchaseOrder(id: string, reasonValue: string) {
    const { data } = await http.post<Entity<PurchaseOrder>>(
      `/api/v1/procurement/purchase-orders/${id}/cancel`,
      {},
      { headers: reason(reasonValue) },
    )
    return data.data
  },

  async receipts() {
    const { data } = await http.get<Collection<GoodsReceipt>>(
      '/api/v1/procurement/goods-receipts',
      { params: { per_page: 100 } },
    )
    return data.data
  },

  async receipt(id: string) {
    const { data } = await http.get<Entity<GoodsReceipt>>(
      `/api/v1/procurement/goods-receipts/${id}`,
    )
    return data.data
  },

  async createReceipt(payload: Record<string, unknown>, reasonValue?: string) {
    const { data } = await http.post<Entity<GoodsReceipt>>(
      '/api/v1/procurement/goods-receipts',
      payload,
      {
        headers: {
          ...idem('goods-receipt'),
          ...(reasonValue ? { 'X-Privileged-Reason': reasonValue } : {}),
        },
      },
    )
    return data.data
  },

  async approveReceiptException(id: string, reasonValue: string) {
    const { data } = await http.post<Entity<GoodsReceipt>>(
      `/api/v1/procurement/goods-receipts/${id}/approve-exception`,
      {},
      { headers: reason(reasonValue) },
    )
    return data.data
  },

  async rejectReceiptException(id: string, reasonValue: string) {
    const { data } = await http.post<Entity<GoodsReceipt>>(
      `/api/v1/procurement/goods-receipts/${id}/reject-exception`,
      {},
      { headers: reason(reasonValue) },
    )
    return data.data
  },

  async reverseReceipt(id: string, reasonValue: string) {
    const { data } = await http.post<Entity<GoodsReceipt>>(
      `/api/v1/procurement/goods-receipts/${id}/reverse`,
      {},
      { headers: reason(reasonValue, 'goods-receipt-reverse') },
    )
    return data.data
  },

  async invoices() {
    const { data } = await http.get<Collection<SupplierInvoice>>(
      '/api/v1/procurement/supplier-invoices',
      { params: { per_page: 100 } },
    )
    return data.data
  },

  async invoice(id: string) {
    const { data } = await http.get<Entity<SupplierInvoice>>(
      `/api/v1/procurement/supplier-invoices/${id}`,
    )
    return data.data
  },

  async createInvoice(payload: Record<string, unknown>) {
    const { data } = await http.post<Entity<SupplierInvoice>>(
      '/api/v1/procurement/supplier-invoices',
      payload,
      { headers: idem('supplier-invoice') },
    )
    return data.data
  },

  async matchInvoice(id: string) {
    const { data } = await http.post<Entity<MatchRun>>(
      `/api/v1/procurement/supplier-invoices/${id}/match`,
      {},
      { headers: idem('three-way-match') },
    )
    return data.data
  },

  async match(id: string) {
    const { data } = await http.get<Entity<MatchRun>>(
      `/api/v1/procurement/match-runs/${id}`,
    )
    return data.data
  },

  async approveMatch(id: string, reasonValue: string) {
    const { data } = await http.post<Entity<MatchRun>>(
      `/api/v1/procurement/match-runs/${id}/approve-exception`,
      {},
      { headers: reason(reasonValue) },
    )
    return data.data
  },

  async rejectMatch(id: string, reasonValue: string) {
    const { data } = await http.post<Entity<MatchRun>>(
      `/api/v1/procurement/match-runs/${id}/reject-exception`,
      {},
      { headers: reason(reasonValue) },
    )
    return data.data
  },
}
