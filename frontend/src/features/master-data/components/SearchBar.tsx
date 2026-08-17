import { Search } from 'lucide-react'

type SearchBarProps = {
  value: string
  placeholder: string
  onChange: (value: string) => void
}

export function SearchBar({ value, placeholder, onChange }: SearchBarProps) {
  return (
    <label className="search-bar">
      <Search size={17} />
      <input
        value={value}
        placeholder={placeholder}
        onChange={(event) => onChange(event.target.value)}
      />
    </label>
  )
}
