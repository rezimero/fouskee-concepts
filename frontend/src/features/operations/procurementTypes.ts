export type PurchaseOrderLine = {
  id: string
  line_number: number
  product_id?: string | null
  sku: string
  product_name: string
  packaging_id?: string | null
  packaging_name?: string | null
  conversion_to_base: string
  quantity: string
  quantity_base: string
  unit_price: string
  unit_price_base: string
  line_total: string
}

export type PurchaseOrder = {
  id: string
  number: string
  status: string
  supplier?: { id: string; number: string; name: string }
  currency_code: string
  expected_delivery_date?: string | null
  total_amount: string
  notes?: string | null
  submitted_at?: string | null
  approved_at?: string | null
  lines?: PurchaseOrderLine[]
}

export type GoodsReceiptLine = {
  id: string
  purchase_order_line_id?: string | null
  product_id?: string | null
  product_name?: string | null
  packaging_id?: string | null
  quantity: string
  quantity_base: string
  unit_cost_base: string
  lot_code?: string | null
  expiry_date?: string | null
}

export type GoodsReceipt = {
  id: string
  number: string
  status: string
  purchase_order_id?: string | null
  purchase_order_number?: string | null
  location?: {
    id: string
    code: string
    name: string
  } | null
  received_at?: string | null
  posted_at?: string | null
  movement_id?: string | null
  notes?: string | null
  lines?: GoodsReceiptLine[]
}

export type SupplierInvoiceLine = {
  id: string
  purchase_order_line_id?: string | null
  product_id?: string | null
  sku: string
  product_name: string
  quantity: string
  quantity_base: string
  unit_price: string
  unit_price_base: string
  line_amount: string
}

export type SupplierInvoice = {
  id: string
  invoice_number: string
  invoice_date: string
  status: string
  eligible_for_payment: boolean
  supplier?: { id: string; name: string }
  currency_code: string
  subtotal_amount: string
  tax_amount: string
  total_amount: string
  latest_match_run_id?: string | null
  lines?: SupplierInvoiceLine[]
}

export type MatchLine = {
  invoice_line_id?: string | null
  purchase_order_line_id?: string | null
  result: string
  ordered_quantity_base?: string | null
  received_quantity_base: string
  invoiced_quantity_base: string
  cumulative_invoiced_quantity_base: string
  ordered_unit_price_base?: string | null
  invoiced_unit_price_base: string
  expected_amount: string
  invoiced_amount: string
  amount_variance: string
  exception_codes: string[]
}

export type MatchRun = {
  id: string
  run_number: number
  result: string
  supplier_invoice_id?: string | null
  expected_amount: string
  invoiced_amount: string
  amount_variance: string
  matched_at?: string | null
  lines?: MatchLine[]
}
