import { LoaderCircle } from 'lucide-react'
import type { ReactNode } from 'react'

type DataStateProps = {
  loading: boolean
  error: string
  empty: boolean
  emptyMessage: string
  children: ReactNode
}

export function DataState({
  loading,
  error,
  empty,
  emptyMessage,
  children,
}: DataStateProps) {
  if (loading) {
    return (
      <div className="data-state">
        <LoaderCircle className="data-state__spinner" size={24} />
        Loading secure workspace data…
      </div>
    )
  }

  if (error) return <div className="alert alert--danger">{error}</div>
  if (empty) return <div className="empty-state">{emptyMessage}</div>

  return <>{children}</>
}
