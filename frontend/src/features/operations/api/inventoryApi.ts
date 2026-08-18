import { http } from '../../../lib/http'
import { idempotencyKey } from '../lib/format'
import type {
  InventoryBalance,
  InventoryLocation,
  InventoryLot,
  InventoryMovement,
  StockCount,
  StockTransfer,
} from '../types'

type Collection<T> = { data: T[] }
type Entity<T> = { data: T }

const keyHeaders = (prefix: string) => ({
  'Idempotency-Key': idempotencyKey(prefix),
})

const reasonHeaders = (reason: string, prefix?: string) => ({
  ...(prefix ? keyHeaders(prefix) : {}),
  'X-Privileged-Reason': reason,
})

export const inventoryApi = {
  async locations() {
    const { data } = await http.get<Collection<InventoryLocation>>(
      '/api/v1/inventory/locations',
    )
    return data.data
  },

  async lots(productId?: string) {
    const { data } = await http.get<Collection<InventoryLot>>(
      '/api/v1/inventory/lots',
      { params: productId ? { product_id: productId } : undefined },
    )
    return data.data
  },

  async balances() {
    const { data } = await http.get<Collection<InventoryBalance>>(
      '/api/v1/inventory/balances',
    )
    return data.data
  },

  async movements() {
    const { data } = await http.get<Collection<InventoryMovement>>(
      '/api/v1/inventory/movements',
      { params: { per_page: 100 } },
    )
    return data.data
  },

  async counts() {
    const { data } = await http.get<Collection<StockCount>>(
      '/api/v1/inventory/counts',
      { params: { per_page: 100 } },
    )
    return data.data
  },

  async count(id: string) {
    const { data } = await http.get<Entity<StockCount>>(
      `/api/v1/inventory/counts/${id}`,
    )
    return data.data
  },

  async createCount(payload: Record<string, unknown>) {
    const { data } = await http.post<Entity<StockCount>>(
      '/api/v1/inventory/counts',
      payload,
    )
    return data.data
  },

  async submitCount(id: string, reason: string) {
    const { data } = await http.post<Entity<StockCount>>(
      `/api/v1/inventory/counts/${id}/submit`,
      {},
      { headers: reasonHeaders(reason, 'count-submit') },
    )
    return data.data
  },

  async approveCount(id: string, reason: string) {
    const { data } = await http.post<Entity<StockCount>>(
      `/api/v1/inventory/counts/${id}/approve`,
      {},
      { headers: reasonHeaders(reason, 'count-approve') },
    )
    return data.data
  },

  async rejectCount(id: string, reason: string) {
    const { data } = await http.post<Entity<StockCount>>(
      `/api/v1/inventory/counts/${id}/reject`,
      {},
      { headers: reasonHeaders(reason) },
    )
    return data.data
  },

  async transfers() {
    const { data } = await http.get<Collection<StockTransfer>>(
      '/api/v1/inventory/transfer-orders',
      { params: { per_page: 100 } },
    )
    return data.data
  },

  async transfer(id: string) {
    const { data } = await http.get<Entity<StockTransfer>>(
      `/api/v1/inventory/transfer-orders/${id}`,
    )
    return data.data
  },

  async createTransfer(payload: Record<string, unknown>) {
    const { data } = await http.post<Entity<StockTransfer>>(
      '/api/v1/inventory/transfer-orders',
      payload,
    )
    return data.data
  },

  async dispatchTransfer(id: string) {
    const { data } = await http.post<Entity<StockTransfer>>(
      `/api/v1/inventory/transfer-orders/${id}/dispatch`,
      {},
      { headers: keyHeaders('transfer-dispatch') },
    )
    return data.data
  },

  async receiveTransfer(id: string) {
    const { data } = await http.post<Entity<StockTransfer>>(
      `/api/v1/inventory/transfer-orders/${id}/receive`,
      {},
      { headers: keyHeaders('transfer-receive') },
    )
    return data.data
  },

  async cancelTransfer(id: string, reason: string) {
    const { data } = await http.post<Entity<StockTransfer>>(
      `/api/v1/inventory/transfer-orders/${id}/cancel`,
      {},
      { headers: reasonHeaders(reason, 'transfer-cancel') },
    )
    return data.data
  },
}
