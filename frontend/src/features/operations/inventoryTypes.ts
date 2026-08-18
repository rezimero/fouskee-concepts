export type InventoryLocation = {
  id: string
  code: string
  name: string
  type: string
  status: string
}

export type InventoryLot = {
  id: string
  code: string
  product_id: string
  product_name: string
  expiry_date?: string | null
  status: string
}

export type InventoryBalance = {
  location_id: string
  product_id: string
  lot_id?: string | null
  on_hand_base: string
  reserved_base: string
  available_base: string
  inventory_value: string
  weighted_average_unit_cost: string
}

export type InventoryMovementLine = {
  location_id?: string | null
  product_id?: string | null
  packaging_id?: string | null
  lot_id?: string | null
  entered_quantity: string
  conversion_to_base: string
  quantity_base: string
  unit_cost?: string | null
  inventory_value_delta?: string | null
}

export type InventoryMovement = {
  id: string
  number: string
  type: string
  occurred_at?: string | null
  posted_at?: string | null
  business_reason?: string | null
  source: { type?: string | null; id?: string | null }
  reversal_of?: string | null
  lines?: InventoryMovementLine[]
}

export type StockCountLine = {
  product_id?: string | null
  product_name?: string | null
  lot_id?: string | null
  system_quantity_base: string
  counted_quantity_base: string
  variance_quantity_base: string
  unit_cost_snapshot: string
  variance_value: string
}

export type StockCount = {
  id: string
  number: string
  status: string
  location: { id?: string | null; name?: string | null }
  reason_code: string
  notes?: string | null
  total_abs_variance_value: string
  approval_id?: string | null
  posted_movement_id?: string | null
  lines?: StockCountLine[]
}

export type StockTransferLine = {
  product_id?: string | null
  product_name?: string | null
  packaging_id?: string | null
  lot_id?: string | null
  entered_quantity: string
  conversion_to_base: string
  quantity_base: string
}

export type LocationSummary = {
  id: string
  code: string
  name: string
}

export type StockTransfer = {
  id: string
  number: string
  status: string
  source?: LocationSummary | null
  destination?: LocationSummary | null
  transit?: LocationSummary | null
  notes?: string | null
  dispatched_at?: string | null
  received_at?: string | null
  cancelled_at?: string | null
  lines?: StockTransferLine[]
}
