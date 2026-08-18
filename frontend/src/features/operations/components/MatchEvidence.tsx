import { ShieldAlert } from 'lucide-react'
import { StatusBadge } from './StatusBadge'
import { dateTime, money } from '../lib/format'
import type { MatchRun } from '../types'

export function MatchEvidence({ run }: { run: MatchRun }) {
  const exceptions = run.lines?.flatMap((line) => line.exception_codes) ?? []

  return (
    <section
      className={`ops-match-card ${
        run.result === 'exception' ? 'ops-match-card--risk' : ''
      }`}
    >
      <div className="ops-match-card__header">
        <div>
          <span className="eyebrow">Match run #{run.run_number}</span>
          <h3>Three-way result</h3>
        </div>
        <StatusBadge
          value={run.result}
          risk={run.result === 'exception'}
        />
      </div>
      <div className="ops-match-summary">
        <span>
          Expected <strong>{money(run.expected_amount)}</strong>
        </span>
        <span>
          Invoiced <strong>{money(run.invoiced_amount)}</strong>
        </span>
        <span>
          Variance <strong>{money(run.amount_variance)}</strong>
        </span>
        <span>
          Matched <strong>{dateTime(run.matched_at)}</strong>
        </span>
      </div>
      {exceptions.length > 0 ? (
        <div className="ops-exception-list">
          <ShieldAlert size={17} />
          <div>
            {exceptions.map((code, index) => (
              <span key={`${code}-${index}`}>
                {code.replaceAll('_', ' ')}
              </span>
            ))}
          </div>
        </div>
      ) : null}
    </section>
  )
}
