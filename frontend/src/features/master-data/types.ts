export type CustomerSegment = 'wholesale' | 'retail' | 'hybrid'

export type Customer = {
  id: string
  number: string
  segment: CustomerSegment
  display_name: string
  legal_name?: string | null
  email?: string | null
  phone?: string | null
  tax_identifier?: string | null
  credit_limit: string
  status: string
  price_list_id?: string | null
}

export type Supplier = {
  id: string
  number: string
  display_name: string
  legal_name?: string | null
  email?: string | null
  phone?: string | null
  tax_identifier?: string | null
  status: string
}

export type ReferenceItem = {
  id: string
  code: string
  name: string
}

export type UnitReference = ReferenceItem & {
  symbol: string
  kind: string
}

export type CatalogReferences = {
  units: UnitReference[]
  brands: ReferenceItem[]
  categories: ReferenceItem[]
}

export type Packaging = {
  id: string
  code: string
  name: string
  barcode?: string | null
  conversion_to_base: string
  unit: Pick<ReferenceItem, 'id' | 'code'>
}

export type Product = {
  id: string
  sku: string
  name: string
  description?: string | null
  status: string
  brand?: Pick<ReferenceItem, 'id' | 'name'> | null
  category?: Pick<ReferenceItem, 'id' | 'name'> | null
  base_unit: UnitReference
  packagings: Packaging[]
}

export type PriceList = {
  id: string
  code: string
  name: string
  currency_code: string
  status: string
  effective_from?: string | null
  effective_to?: string | null
}

export type PriceListItem = {
  product_packaging_id: string
  product_name: string
  packaging_name: string
  selling_price: string
  minimum_selling_price?: string | null
  maximum_discount_percent?: string | null
}
