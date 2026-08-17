import {
  useCallback,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react'
import axios from 'axios'
import { authApi } from '../api/authApi'
import type { AuthUser, LoginPayload } from '../types'
import { AuthContext, type AuthStatus } from './authContext'

export function AuthProvider({ children }: { children: ReactNode }) {
  const [status, setStatus] = useState<AuthStatus>('loading')
  const [user, setUser] = useState<AuthUser | null>(null)

  const refresh = useCallback(async () => {
    try {
      const nextUser = await authApi.me()
      setUser(nextUser)
      setStatus('authenticated')
      return nextUser
    } catch (error) {
      if (axios.isAxiosError(error) && error.response?.status === 401) {
        setUser(null)
        setStatus('guest')
        return null
      }

      setStatus('guest')
      throw error
    }
  }, [])

  useEffect(() => {
    let active = true

    authApi.me()
      .then((nextUser) => {
        if (!active) return
        setUser(nextUser)
        setStatus('authenticated')
      })
      .catch(() => {
        if (!active) return
        setUser(null)
        setStatus('guest')
      })

    const expire = () => {
      setUser(null)
      setStatus('guest')
    }

    window.addEventListener('fouskee:auth-expired', expire)

    return () => {
      active = false
      window.removeEventListener('fouskee:auth-expired', expire)
    }
  }, [])

  const login = useCallback(
    async (payload: LoginPayload) => {
      const result = await authApi.login(payload)

      if (result.two_factor) return 'challenge' as const

      await refresh()
      return 'authenticated' as const
    },
    [refresh],
  )

  const logout = useCallback(async () => {
    try {
      await authApi.logout()
    } finally {
      setUser(null)
      setStatus('guest')
    }
  }, [])

  const value = useMemo(
    () => ({ status, user, login, logout, refresh }),
    [status, user, login, logout, refresh],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
