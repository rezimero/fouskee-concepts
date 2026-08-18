import type { Product } from '../../master-data/types'
import { money, quantity } from '../lib/format'
import type {
  InventoryBalance,
  InventoryLocation,
  InventoryLot,
} from '../types'

type Props = {
  rows: InventoryBalance[]
  products: Map<string, Product>
  locations: Map<string, InventoryLocation>
  lots: Map<string, InventoryLot>
}

export function InventoryBalanceTable({
  rows,
  products,
  locations,
  lots,
}: Props) {
  return (
    <div className="ops-table-wrap">
      <table className="ops-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Location / lot</th>
            <th>On hand</th>
            <th>Reserved</th>
            <th>Available</th>
            <th>WAC</th>
            <th>Value</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => {
            const product = products.get(row.product_id)
            const location = locations.get(row.location_id)
            const lot = row.lot_id ? lots.get(row.lot_id) : null
            const key = `${row.location_id}-${row.product_id}-${row.lot_id ?? 'none'}`

            return (
              <tr key={key}>
                <td>
                  <strong>{product?.name ?? 'Unknown product'}</strong>
                  <small>{product?.sku ?? row.product_id}</small>
                </td>
                <td>
                  <strong>{location?.name ?? row.location_id}</strong>
                  <small>{lot ? `Lot ${lot.code}` : 'Non-lot stock'}</small>
                </td>
                <td>{quantity(row.on_hand_base)}</td>
                <td>{quantity(row.reserved_base)}</td>
                <td><strong>{quantity(row.available_base)}</strong></td>
                <td>{money(row.weighted_average_unit_cost)}</td>
                <td>{money(row.inventory_value)}</td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </div>
  )
}
