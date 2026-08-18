import type { Product } from '../../master-data/types'
import type { InventoryLot } from '../types'

type Props = {
  products: Product[]
  lots?: InventoryLot[]
  productId: string
  packagingId: string
  lotId?: string
  showLot?: boolean
  showPackaging?: boolean
  onProduct: (value: string) => void
  onPackaging: (value: string) => void
  onLot?: (value: string) => void
}

export function ProductPackagingFields({
  products,
  lots = [],
  productId,
  packagingId,
  lotId = '',
  showLot = false,
  showPackaging = true,
  onProduct,
  onPackaging,
  onLot,
}: Props) {
  const product = products.find((item) => item.id === productId)
  const productLots = lots.filter((item) => item.product_id === productId)

  return (
    <>
      <label className="field">
        Product
        <select
          value={productId}
          required
          onChange={(event) => {
            onProduct(event.target.value)
            onPackaging('')
            onLot?.('')
          }}
        >
          <option value="">Select product</option>
          {products.map((item) => (
            <option key={item.id} value={item.id}>
              {item.sku} · {item.name}
            </option>
          ))}
        </select>
      </label>
      {showPackaging ? (
        <label className="field">
          Packaging
          <select
            value={packagingId}
            onChange={(event) => onPackaging(event.target.value)}
          >
            <option value="">Base unit</option>
            {product?.packagings.map((item) => (
              <option key={item.id} value={item.id}>
                {item.name} × {item.conversion_to_base}
              </option>
            ))}
          </select>
        </label>
      ) : null}
      {showLot ? (
        <label className="field">
          Lot / batch
          <select
            value={lotId}
            onChange={(event) => onLot?.(event.target.value)}
          >
            <option value="">Non-lot stock</option>
            {productLots.map((item) => (
              <option key={item.id} value={item.id}>
                {item.code}{item.expiry_date ? ` · exp ${item.expiry_date}` : ''}
              </option>
            ))}
          </select>
        </label>
      ) : null}
    </>
  )
}
