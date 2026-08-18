import type { ReactNode } from 'react'

type Fact = {
  label: string
  value: ReactNode
}

export function DetailFacts({ facts }: { facts: Fact[] }) {
  return (
    <dl className="ops-facts">
      {facts.map((fact) => (
        <div key={fact.label}>
          <dt>{fact.label}</dt>
          <dd>{fact.value}</dd>
        </div>
      ))}
    </dl>
  )
}
