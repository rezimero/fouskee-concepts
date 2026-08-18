import { StatusBadge } from './StatusBadge'
import { humanize } from '../lib/format'
import type { InventoryLocation } from '../types'

type Props = {
  locations: InventoryLocation[]
}

export function LocationTable({ locations }: Props) {
  return (
    <div className="ops-table-wrap">
      <table className="ops-table">
        <thead>
          <tr>
            <th>Code</th>
            <th>Location</th>
            <th>Type</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          {locations.map((location) => (
            <tr key={location.id}>
              <td>
                <strong>{location.code}</strong>
                {location.code === '__TRANSIT__'
                  ? <small>System-controlled custody location</small>
                  : null}
              </td>
              <td>{location.name}</td>
              <td>{humanize(location.type)}</td>
              <td><StatusBadge value={location.status} /></td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
