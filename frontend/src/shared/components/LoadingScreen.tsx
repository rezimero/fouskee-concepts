import { BrandMark } from '../../features/auth/components/BrandMark'

export function LoadingScreen() {
  return (
    <main className="loading-screen">
      <BrandMark />
      <span className="loading-spinner" aria-label="Loading" />
    </main>
  )
}
