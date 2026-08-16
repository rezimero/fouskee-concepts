import { http, initializeCsrf } from '../../../lib/http'
import type {
  AuthUser,
  LoginPayload,
  LoginResult,
  SecuritySession,
} from '../types'

export const authApi = {
  csrf: initializeCsrf,

  async login(payload: LoginPayload) {
    await initializeCsrf()
    const response = await http.post<LoginResult>('/login', payload)
    return response.data
  },

  async challenge(payload: { code?: string; recovery_code?: string }) {
    await http.post('/two-factor-challenge', payload)
  },

  async me() {
    const response = await http.get<{ data: AuthUser }>('/api/v1/me')
    return response.data.data
  },

  async logout() {
    await http.post('/logout')
  },

  async confirmPassword(password: string) {
    await http.post('/user/confirm-password', { password })
  },

  async enableMfa() {
    await http.post('/user/two-factor-authentication')
  },

  async qrCode() {
    const response = await http.get<{ svg: string }>('/user/two-factor-qr-code')
    return response.data.svg
  },

  async confirmMfa(code: string) {
    await http.post('/user/confirmed-two-factor-authentication', { code })
  },

  async recoveryCodes() {
    const response = await http.get<string[]>('/user/two-factor-recovery-codes')
    return response.data
  },

  async regenerateRecoveryCodes() {
    await http.post('/user/two-factor-recovery-codes')
    return authApi.recoveryCodes()
  },

  async sessions() {
    const response = await http.get<{ data: SecuritySession[] }>(
      '/api/v1/security/sessions',
    )
    return response.data.data
  },

  async revokeSession(reference: string, password: string, reason: string) {
    await http.delete(`/api/v1/security/sessions/${reference}`, {
      data: { password },
      headers: { 'X-Privileged-Reason': reason },
    })
  },
}
