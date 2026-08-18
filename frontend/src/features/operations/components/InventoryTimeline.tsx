import { StatusBadge } from './StatusBadge'
import { dateTime } from '../lib/format'
import type { InventoryMovement } from '../types'

export function InventoryTimeline({
  movements,
}: {
  movements: InventoryMovement[]
}) {
  return (
    <div className="ops-timeline">
      {movements.slice(0, 12).map((movement) => (
        <article className="ops-timeline__item" key={movement.id}>
          <span className="ops-timeline__marker" />
          <div>
            <strong>{movement.number}</strong>
            <small>{dateTime(movement.posted_at)}</small>
          </div>
          <StatusBadge value={movement.type} />
          <div className="ops-timeline__source">
            {movement.source.type ?? 'direct inventory'} ·
            {' '}{movement.lines?.length ?? 0} lines
          </div>
        </article>
      ))}
    </div>
  )
}
