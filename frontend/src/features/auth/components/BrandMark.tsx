export function BrandMark({ compact = false }: { compact?: boolean }) {
  return (
    <div className={`brand-mark ${compact ? 'brand-mark--compact' : ''}`}>
      <span className="brand-mark__monogram" aria-hidden="true">
        <span className="brand-mark__f">F</span>
        <span className="brand-mark__c">C</span>
      </span>
      {!compact && (
        <span className="brand-mark__wordmark">
          <strong>Fouskee</strong>
          <small>Concepts</small>
        </span>
      )}
    </div>
  )
}
