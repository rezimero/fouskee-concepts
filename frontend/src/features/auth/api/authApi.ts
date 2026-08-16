import { http } from '../../../lib/http'
import type {
  IdentityContext,
  LoginCredentials,
  MfaChallenge,
  SecuritySession,
} from '../types'

export const authApi = {
  async csrf(): Promise<void> {
    await http.get('/sanctum/csrf-cookie')
  },

  async login(credentials: LoginCredentials): Promise<{ two_factor?: boolean }> {
    await this.csrf()
    const response = await http.post('/login', credentials)
    return response.data
  },

  async challenge(payload: MfaChallenge): Promise<void> {
    await http.post('/two-factor-challenge', payload)
  },

  async me(): Promise<IdentityContext> {
    const response = await http.get('/api/v1/me')
    return response.data.data
  },

  async logout(): Promise<void> {
    await http.post('/logout')
  },

  async confirmPassword(password: string): Promise<void> {
    await http.post('/user/confirm-password', { password })
  },

  async enableMfa(): Promise<void> {
    await http.post('/user/two-factor-authentication')
  },

  async mfaQrCode(): Promise<string> {
    const response = await http.get('/user/two-factor-qr-code')
    return response.data.svg
  },

  async confirmMfa(code: string): Promise<void> {
    await http.post('/user/confirmed-two-factor-authentication', { code })
  },

  async recoveryCodes(): Promise<string[]> {
    const response = await http.get('/user/two-factor-recovery-codes')
    return response.data
  },

  async sessions(): Promise<SecuritySession[]> {
    const response = await http.get('/api/v1/security/sessions')
    return response.data.data
  },

  async revokeSession(
    reference: string,
    password: string,
    reason: string,
  ): Promise<void> {
    await http.delete(`/api/v1/security/sessions/${reference}`, {
      data: { password },
      headers: { 'X-Privileged-Reason': reason },
    })
  },
}
