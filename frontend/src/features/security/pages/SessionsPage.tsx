import { useEffect, useState, type FormEvent } from 'react'
import { Laptop, RefreshCw, ShieldX } from 'lucide-react'
import { requestErrorMessage } from '../../../lib/errors'
import { authApi } from '../../auth/api/authApi'
import { PasswordField } from '../../auth/components/PasswordField'
import type { SecuritySession } from '../../auth/types'

const loadError = 'Unable to load active sessions.'

export function SessionsPage() {
  const [sessions, setSessions] = useState<SecuritySession[]>([])
  const [selected, setSelected] = useState<SecuritySession | null>(null)
  const [password, setPassword] = useState('')
  const [reason, setReason] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(true)

  async function load() {
    setBusy(true)
    setError('')

    try {
      setSessions(await authApi.sessions())
    } catch (caught) {
      setError(requestErrorMessage(caught, loadError))
    } finally {
      setBusy(false)
    }
  }

  useEffect(() => {
    let active = true

    authApi.sessions()
      .then((nextSessions) => {
        if (!active) return
        setSessions(nextSessions)
      })
      .catch((caught) => {
        if (!active) return
        setError(requestErrorMessage(caught, loadError))
      })
      .finally(() => {
        if (!active) return
        setBusy(false)
      })

    return () => {
      active = false
    }
  }, [])

  async function revoke(event: FormEvent) {
    event.preventDefault()
    if (!selected) return

    setBusy(true)
    setError('')

    try {
      await authApi.revokeSession(selected.reference, password, reason)
      setSelected(null)
      setPassword('')
      setReason('')
      await load()
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Unable to revoke that session.'))
      setBusy(false)
    }
  }

  return (
    <div className="page-stack">
      <section className="section-heading">
        <div>
          <span className="eyebrow">Account security</span>
          <h1>Sessions & devices</h1>
          <p>Review server-side sessions and revoke access you do not recognize.</p>
        </div>
        <button className="button button--secondary" onClick={() => void load()}>
          <RefreshCw size={17} /> Refresh
        </button>
      </section>

      {error && <div className="alert alert--danger">{error}</div>}

      <section className="session-list">
        {!busy && sessions.length === 0 && (
          <div className="empty-state">No active server-side sessions found.</div>
        )}

        {sessions.map((session) => (
          <article className="session-card" key={session.reference}>
            <span className="session-card__icon"><Laptop size={22} /></span>
            <div className="session-card__body">
              <strong>{deviceLabel(session.user_agent)}</strong>
              <small>{session.ip_address ?? 'IP unavailable'}</small>
              <small>{activityLabel(session.last_active_at)}</small>
            </div>

            {session.current ? (
              <span className="pill pill--success">This device</span>
            ) : (
              <button
                className="button button--danger"
                onClick={() => setSelected(session)}
              >
                <ShieldX size={17} /> Revoke
              </button>
            )}
          </article>
        ))}
      </section>

      {selected && (
        <section className="security-action-card">
          <div>
            <span className="eyebrow">Privileged action</span>
            <h2>Revoke this session</h2>
            <p>Confirm your password and provide an auditable business reason.</p>
          </div>

          <form className="security-action-form" onSubmit={revoke}>
            <PasswordField value={password} onChange={setPassword} />
            <label className="field">
              <span>Reason</span>
              <textarea
                minLength={10}
                maxLength={500}
                value={reason}
                onChange={(event) => setReason(event.target.value)}
                required
              />
            </label>

            <div className="button-row">
              <button className="button button--danger" disabled={busy}>
                Revoke session
              </button>
              <button
                className="button button--ghost"
                type="button"
                onClick={() => setSelected(null)}
              >
                Cancel
              </button>
            </div>
          </form>
        </section>
      )}
    </div>
  )
}

function deviceLabel(userAgent?: string | null) {
  if (!userAgent) return 'Unknown device'
  if (/Windows/i.test(userAgent)) return 'Windows device'
  if (/Macintosh|Mac OS/i.test(userAgent)) return 'Mac device'
  if (/iPhone|iPad/i.test(userAgent)) return 'Apple mobile device'
  if (/Android/i.test(userAgent)) return 'Android device'
  return 'Browser session'
}

function activityLabel(value?: string | number | null) {
  if (!value) return 'Activity time unavailable'

  const date = typeof value === 'number'
    ? new Date(value * 1000)
    : new Date(value)

  return Number.isNaN(date.getTime())
    ? 'Activity time unavailable'
    : `Last active ${date.toLocaleString()}`
}
