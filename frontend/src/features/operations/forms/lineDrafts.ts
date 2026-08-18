export type ProductQuantityDraft = {
  key: string
  product_id: string
  packaging_id: string
  lot_id: string
  quantity: string
}

export type CountLineDraft = {
  key: string
  product_id: string
  lot_id: string
  counted_quantity_base: string
  unit_cost_base: string
}

export type PurchaseLineDraft = {
  key: string
  product_id: string
  packaging_id: string
  quantity: string
  unit_price: string
}

export const productQuantityDraft = (): ProductQuantityDraft => ({
  key: crypto.randomUUID(),
  product_id: '',
  packaging_id: '',
  lot_id: '',
  quantity: '',
})

export const countLineDraft = (): CountLineDraft => ({
  key: crypto.randomUUID(),
  product_id: '',
  lot_id: '',
  counted_quantity_base: '',
  unit_cost_base: '',
})

export const purchaseLineDraft = (): PurchaseLineDraft => ({
  key: crypto.randomUUID(),
  product_id: '',
  packaging_id: '',
  quantity: '',
  unit_price: '',
})
