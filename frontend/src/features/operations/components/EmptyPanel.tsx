import { PackageOpen } from 'lucide-react'

export function EmptyPanel({ message }: { message: string }) {
  return (
    <div className="ops-empty">
      <PackageOpen size={24} />
      <span>{message}</span>
    </div>
  )
}
