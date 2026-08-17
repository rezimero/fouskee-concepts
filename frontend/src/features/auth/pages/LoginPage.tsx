import { useState, type FormEvent } from 'react'
import { ArrowRight, LockKeyhole } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { requestErrorMessage } from '../../../lib/errors'
import { AuthLayout } from '../components/AuthLayout'
import { PasswordField } from '../components/PasswordField'
import { useAuth } from '../hooks/useAuth'

export function LoginPage() {
  const navigate = useNavigate()
  const { login } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [remember, setRemember] = useState(false)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  async function submit(event: FormEvent) {
    event.preventDefault()
    setError('')
    setBusy(true)
    try {
      const outcome = await login({ email, password, remember })
      navigate(outcome === 'challenge' ? '/two-factor-challenge' : '/', {
        replace: true,
      })
    } catch (caught) {
      setError(requestErrorMessage(caught, 'Email or password is incorrect.'))
    } finally {
      setBusy(false)
    }
  }

  return (
    <AuthLayout
      eyebrow="Staff sign in"
      title="Welcome back"
      description="Use your assigned Fouskee account to continue."
    >
      <form className="auth-form" onSubmit={submit}>
        {error && <div className="alert alert--danger">{error}</div>}
        <label className="field">
          <span>Email address</span>
          <input
            type="email"
            value={email}
            autoComplete="username"
            onChange={(event) => setEmail(event.target.value)}
            required
          />
        </label>
        <PasswordField value={password} onChange={setPassword} />
        <label className="check-row">
          <input
            type="checkbox"
            checked={remember}
            onChange={(event) => setRemember(event.target.checked)}
          />
          <span>Keep me signed in on this trusted device</span>
        </label>
        <button className="button button--primary" disabled={busy}>
          <LockKeyhole size={18} />
          {busy ? 'Verifying…' : 'Sign in securely'}
          {!busy && <ArrowRight size={18} />}
        </button>
      </form>
    </AuthLayout>
  )
}
