import { http } from '../../../lib/http'
import type {
  CatalogReferences,
  Customer,
  Product,
  ReferenceItem,
  Supplier,
} from '../types'

type Collection<T> = { data: T[] }
type Entity<T> = { data: T }

export async function listCustomers() {
  const { data } = await http.get<Collection<Customer>>(
    '/api/v1/master-data/customers',
    { params: { per_page: 100 } },
  )
  return data.data
}

export async function createCustomer(payload: Record<string, unknown>) {
  const { data } = await http.post<Entity<Customer>>(
    '/api/v1/master-data/customers',
    payload,
  )
  return data.data
}

export async function listSuppliers() {
  const { data } = await http.get<Collection<Supplier>>(
    '/api/v1/master-data/suppliers',
    { params: { per_page: 100 } },
  )
  return data.data
}

export async function createSupplier(payload: Record<string, unknown>) {
  const { data } = await http.post<Entity<Supplier>>(
    '/api/v1/master-data/suppliers',
    payload,
  )
  return data.data
}

export async function listProducts() {
  const { data } = await http.get<Collection<Product>>(
    '/api/v1/master-data/products',
    { params: { per_page: 100 } },
  )
  return data.data
}

export async function createProduct(payload: Record<string, unknown>) {
  const { data } = await http.post<Entity<Product>>(
    '/api/v1/master-data/products',
    payload,
  )
  return data.data
}

export async function getCatalogReferences() {
  const { data } = await http.get<Entity<CatalogReferences>>(
    '/api/v1/master-data/catalog/references',
  )
  return data.data
}

export async function createBrand(payload: { code: string; name: string }) {
  const { data } = await http.post<Entity<ReferenceItem>>(
    '/api/v1/master-data/brands',
    payload,
  )
  return data.data
}

export async function createCategory(payload: { code: string; name: string }) {
  const { data } = await http.post<Entity<ReferenceItem>>(
    '/api/v1/master-data/categories',
    payload,
  )
  return data.data
}
