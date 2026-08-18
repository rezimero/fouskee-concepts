import { useState, type FormEvent } from 'react'
import { ShieldCheck } from 'lucide-react'
import { Sheet } from '../../master-data/components/Sheet'

type Props = {
  open: boolean
  title: string
  description: string
  confirmLabel: string
  danger?: boolean
  busy?: boolean
  onClose: () => void
  onConfirm: (reason: string) => Promise<void>
}

export function PrivilegedActionSheet({
  open,
  title,
  description,
  confirmLabel,
  danger = false,
  busy = false,
  onClose,
  onConfirm,
}: Props) {
  const [reason, setReason] = useState('')

  async function submit(event: FormEvent) {
    event.preventDefault()
    if (reason.trim().length < 10) return
    await onConfirm(reason.trim())
    setReason('')
  }

  return (
    <Sheet
      open={open}
      title={title}
      subtitle={description}
      onClose={onClose}
    >
      <form className="form-stack" onSubmit={(event) => void submit(event)}>
        <div className="control-notice">
          <span className="control-notice__icon"><ShieldCheck size={18} /></span>
          <div>
            <strong>Privileged action</strong>
            <p>
              Your identity, reason, and resulting evidence will be retained
              in the immutable audit trail.
            </p>
          </div>
        </div>
        <label className="field">
          Business reason
          <textarea
            value={reason}
            minLength={10}
            maxLength={2000}
            required
            autoFocus
            onChange={(event) => setReason(event.target.value)}
            placeholder="Explain why this controlled action is necessary."
          />
        </label>
        <div className="form-actions">
          <button
            className="button button--secondary"
            type="button"
            onClick={onClose}
          >
            Cancel
          </button>
          <button
            className={`button ${danger ? 'button--danger' : 'button--primary'}`}
            type="submit"
            disabled={busy || reason.trim().length < 10}
          >
            {busy ? 'Recording…' : confirmLabel}
          </button>
        </div>
      </form>
    </Sheet>
  )
}
