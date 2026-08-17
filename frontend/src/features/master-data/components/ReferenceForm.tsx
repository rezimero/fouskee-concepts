import { useState, type FormEvent } from 'react'
import { requestErrorMessage } from '../../../lib/errors'
import type { ReferenceItem } from '../types'

type ReferenceFormProps = {
  noun: string
  create: (payload: { code: string; name: string }) => Promise<ReferenceItem>
  onCreated: (item: ReferenceItem) => void
}

export function ReferenceForm({
  noun,
  create,
  onCreated,
}: ReferenceFormProps) {
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const formElement = event.currentTarget
    const form = new FormData(formElement)
    setSaving(true)
    setError('')

    try {
      const item = await create({
        code: String(form.get('code') ?? ''),
        name: String(form.get('name') ?? ''),
      })
      onCreated(item)
      formElement.reset()
    } catch (caught) {
      setError(requestErrorMessage(caught, `Unable to create ${noun}.`))
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="reference-form" onSubmit={(event) => void submit(event)}>
      {error ? <div className="alert alert--danger">{error}</div> : null}
      <div className="form-grid">
        <label className="field">
          Code
          <input name="code" required maxLength={50} />
        </label>
        <label className="field">
          Name
          <input name="name" required maxLength={150} />
        </label>
      </div>
      <button className="button button--secondary" type="submit" disabled={saving}>
        {saving ? 'Adding…' : `Add ${noun}`}
      </button>
    </form>
  )
}
