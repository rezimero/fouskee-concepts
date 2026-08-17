import { createContext } from 'react'
import type { AuthUser, LoginPayload } from '../types'

export type AuthStatus = 'loading' | 'guest' | 'authenticated'

export type AuthContextValue = {
  status: AuthStatus
  user: AuthUser | null
  login: (payload: LoginPayload) => Promise<'challenge' | 'authenticated'>
  logout: () => Promise<void>
  refresh: () => Promise<AuthUser | null>
}

export const AuthContext = createContext<AuthContextValue | null>(null)
