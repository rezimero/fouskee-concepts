import { MinusCircle, PlusCircle } from 'lucide-react'
import type { UnitReference } from '../types'
import {
  newPackagingDraft,
  type PackagingDraft,
} from './packagingDraft'

type PackagingEditorProps = {
  rows: PackagingDraft[]
  units: UnitReference[]
  onChange: (rows: PackagingDraft[]) => void
}

export function PackagingEditor({
  rows,
  units,
  onChange,
}: PackagingEditorProps) {
  function patch(key: number, values: Partial<PackagingDraft>) {
    onChange(rows.map((row) => row.key === key ? { ...row, ...values } : row))
  }

  return (
    <div className="packaging-editor">
      <div className="panel-toolbar">
        <div>
          <strong>Packaging conversions</strong>
          <small>Each factor converts one package into the product base unit.</small>
        </div>
        <button
          className="button button--secondary"
          type="button"
          onClick={() => onChange([
            ...rows,
            newPackagingDraft(Date.now()),
          ])}
        >
          <PlusCircle size={16} /> Add package
        </button>
      </div>
      {rows.map((row, index) => (
        <div className="packaging-row" key={row.key}>
          <label className="field">
            Package
            <input
              value={row.name}
              placeholder="50kg Bag"
              required
              onChange={(event) => patch(row.key, {
                name: event.target.value,
              })}
            />
          </label>
          <label className="field">
            Code
            <input
              value={row.code}
              placeholder="BAG50"
              required
              onChange={(event) => patch(row.key, {
                code: event.target.value,
              })}
            />
          </label>
          <label className="field">
            Unit
            <select
              value={row.unit_id}
              required
              onChange={(event) => patch(row.key, {
                unit_id: event.target.value,
              })}
            >
              <option value="">Select</option>
              {units.map((unit) => (
                <option key={unit.id} value={unit.id}>
                  {unit.name}
                </option>
              ))}
            </select>
          </label>
          <label className="field">
            Factor
            <input
              value={row.conversion_to_base}
              type="number"
              min="0.000001"
              step="0.000001"
              required
              onChange={(event) => patch(row.key, {
                conversion_to_base: event.target.value,
              })}
            />
          </label>
          <button
            className="icon-button"
            type="button"
            aria-label={`Remove package ${index + 1}`}
            disabled={rows.length === 1}
            onClick={() => onChange(
              rows.filter((item) => item.key !== row.key),
            )}
          >
            <MinusCircle size={17} />
          </button>
        </div>
      ))}
    </div>
  )
}
