export type LoginCredentials = {
  email: string
  password: string
  remember?: boolean
}

export type MfaChallenge = {
  code?: string
  recovery_code?: string
}

export type IdentityContext = {
  id: string
  name: string
  email: string
  status: string
  mfa: {
    required: boolean
    enrolled: boolean
  }
  organization: {
    id: string
    code: string
    name: string
  } | null
  roles: string[]
  permissions: string[]
}

export type SecuritySession = {
  reference: string
  ip_address: string | null
  user_agent: string | null
  last_active_at: string
  current: boolean
}
