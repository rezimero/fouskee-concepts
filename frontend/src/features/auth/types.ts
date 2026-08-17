export type OrganizationSummary = {
  id: string
  code: string
  name: string
}

export type AuthUser = {
  id: string
  name: string
  email: string
  status: string
  mfa: {
    required: boolean
    enrolled: boolean
  }
  organization: OrganizationSummary | null
  roles: string[]
  permissions: string[]
}

export type LoginPayload = {
  email: string
  password: string
  remember?: boolean
}

export type LoginResult = {
  two_factor?: boolean
}

export type SecuritySession = {
  reference: string
  ip_address?: string | null
  user_agent?: string | null
  last_active_at: string
  current?: boolean
}
