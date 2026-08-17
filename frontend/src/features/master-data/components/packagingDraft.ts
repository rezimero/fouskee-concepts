export type PackagingDraft = {
  key: number
  unit_id: string
  code: string
  name: string
  barcode: string
  conversion_to_base: string
  is_default_sale: boolean
  is_default_purchase: boolean
}

export function newPackagingDraft(key: number): PackagingDraft {
  return {
    key,
    unit_id: '',
    code: '',
    name: '',
    barcode: '',
    conversion_to_base: '1',
    is_default_sale: true,
    is_default_purchase: true,
  }
}
