import { useState } from 'react'
import { Eye, EyeOff } from 'lucide-react'

type Props = {
  value: string
  onChange: (value: string) => void
  autoComplete?: string
  label?: string
}

export function PasswordField({
  value,
  onChange,
  autoComplete = 'current-password',
  label = 'Password',
}: Props) {
  const [visible, setVisible] = useState(false)

  return (
    <label className="field">
      <span>{label}</span>
      <span className="field__control">
        <input
          type={visible ? 'text' : 'password'}
          value={value}
          autoComplete={autoComplete}
          onChange={(event) => onChange(event.target.value)}
          required
        />
        <button
          className="field__icon"
          type="button"
          onClick={() => setVisible((current) => !current)}
          aria-label={visible ? 'Hide password' : 'Show password'}
        >
          {visible ? <EyeOff size={18} /> : <Eye size={18} />}
        </button>
      </span>
    </label>
  )
}
