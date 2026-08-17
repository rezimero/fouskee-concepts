type BrandMarkProps = {
  compact?: boolean
}

export function BrandMark({ compact = false }: BrandMarkProps) {
  return (
    <div className={`brand-mark ${compact ? 'brand-mark--compact' : ''}`}>
      <img
        className="brand-mark__official"
        src="/branding/fouskee-concepts-logo.png"
        alt="Fouskee Concepts"
      />
    </div>
  )
}
