import { http } from '../../../lib/http'
import type { PriceList, PriceListItem } from '../types'

type Collection<T> = { data: T[] }
type Entity<T> = { data: T }

export async function listPriceLists() {
  const { data } = await http.get<Collection<PriceList>>(
    '/api/v1/master-data/price-lists',
    { params: { per_page: 100 } },
  )
  return data.data
}

export async function createPriceList(payload: Record<string, unknown>) {
  const { data } = await http.post<Entity<PriceList>>(
    '/api/v1/master-data/price-lists',
    payload,
  )
  return data.data
}

export async function listPriceItems(priceListId: string) {
  const { data } = await http.get<Collection<PriceListItem>>(
    `/api/v1/master-data/price-lists/${priceListId}/items`,
  )
  return data.data
}

export async function setPriceItem(
  priceListId: string,
  packagingId: string,
  payload: Record<string, unknown>,
  reason: string,
) {
  const { data } = await http.put(
    `/api/v1/master-data/price-lists/${priceListId}/items/${packagingId}`,
    payload,
    { headers: { 'X-Privileged-Reason': reason } },
  )
  return data.data
}
