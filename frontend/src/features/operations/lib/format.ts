export function money(value?: string | number | null, currency = 'NGN') {
  const amount = Number(value ?? 0)
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency,
    maximumFractionDigits: 2,
  }).format(Number.isFinite(amount) ? amount : 0)
}

export function quantity(value?: string | number | null) {
  const amount = Number(value ?? 0)
  return new Intl.NumberFormat('en-NG', {
    maximumFractionDigits: 3,
  }).format(Number.isFinite(amount) ? amount : 0)
}

export function dateTime(value?: string | null) {
  if (!value) return '—'
  const parsed = new Date(value)
  if (Number.isNaN(parsed.valueOf())) return value

  return new Intl.DateTimeFormat('en-NG', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(parsed)
}

export function dateOnly(value?: string | null) {
  if (!value) return '—'
  const parsed = new Date(`${value}T00:00:00`)
  if (Number.isNaN(parsed.valueOf())) return value

  return new Intl.DateTimeFormat('en-NG', {
    dateStyle: 'medium',
  }).format(parsed)
}

export function humanize(value?: string | null) {
  if (!value) return 'Unknown'
  return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}

export function idempotencyKey(prefix: string) {
  return `${prefix}-${crypto.randomUUID()}`
}
