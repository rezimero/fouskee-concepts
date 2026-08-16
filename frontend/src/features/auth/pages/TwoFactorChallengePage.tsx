import { useState, type FormEvent } from 'react'
import { KeyRound, ShieldCheck } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { requestErrorMessage } from '../../../lib/errors'
import { authApi } from '../api/authApi'
import { AuthLayout } from '../components/AuthLayout'
import { useAuth } from '../hooks/useAuth'

export function TwoFactorChallengePage() {
  const navigate = useNavigate()
  const { refresh } = useAuth()
  const [recoveryMode, setRecoveryMode] = useState(false)
  const [value, setValue] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  async function submit(event: FormEvent) {
    event.preventDefault()
    setError('')
    setBusy(true)
    try {
      await authApi.challenge(
        recoveryMode ? { recovery_code: value } : { code: value },
      )
      await refresh()
      navigate('/', { replace: true })
    } catch (caught) {
      setError(requestErrorMessage(caught, 'That verification code is invalid.'))
    } finally {
      setBusy(false)
    }
  }

  return (
    <AuthLayout
      eyebrow="Identity verification"
      title="Complete your sign in"
      description={recoveryMode
        ? 'Enter one of your saved recovery codes.'
        : 'Enter the six-digit code from your authenticator app.'}
    >
      <form className="auth-form" onSubmit={submit}>
        {error && <div className="alert alert--danger">{error}</div>}
        <label className="field">
          <span>{recoveryMode ? 'Recovery code' : 'Authenticator code'}</span>
          <input
            className={recoveryMode ? '' : 'otp-input'}
            inputMode={recoveryMode ? 'text' : 'numeric'}
            autoComplete="one-time-code"
            value={value}
            onChange={(event) => setValue(event.target.value)}
            required
            autoFocus
          />
        </label>
        <button className="button button--primary" disabled={busy}>
          <ShieldCheck size={18} />
          {busy ? 'Checking…' : 'Verify and continue'}
        </button>
        <button
          className="button button--ghost"
          type="button"
          onClick={() => {
            setRecoveryMode((current) => !current)
            setValue('')
            setError('')
          }}
        >
          <KeyRound size={18} />
          {recoveryMode ? 'Use authenticator code' : 'Use a recovery code'}
        </button>
      </form>
    </AuthLayout>
  )
}
