import type { ReactNode } from 'react'
import { SearchBar } from '../../master-data/components/SearchBar'

type Props = {
  query: string
  placeholder: string
  onQuery: (value: string) => void
  children?: ReactNode
}

export function OperationsToolbar({
  query,
  placeholder,
  onQuery,
  children,
}: Props) {
  return (
    <div className="ops-toolbar">
      <SearchBar value={query} placeholder={placeholder} onChange={onQuery} />
      {children ? <div className="ops-toolbar__actions">{children}</div> : null}
    </div>
  )
}
