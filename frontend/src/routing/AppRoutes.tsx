import { Navigate, Route, Routes } from 'react-router-dom'
import { LoginPage } from '../features/auth/pages/LoginPage'
import { MfaSetupPage } from '../features/auth/pages/MfaSetupPage'
import { TwoFactorChallengePage } from '../features/auth/pages/TwoFactorChallengePage'
import { SessionsPage } from '../features/security/pages/SessionsPage'
import { AppShell } from '../shell/components/AppShell'
import { DashboardPage } from '../shell/pages/DashboardPage'
import { GuestRoute } from './GuestRoute'
import { MfaGate } from './MfaGate'
import { ProtectedRoute } from './ProtectedRoute'

export function AppRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<GuestRoute><LoginPage /></GuestRoute>} />
      <Route path="/two-factor-challenge" element={<TwoFactorChallengePage />} />
      <Route
        path="/security/mfa/setup"
        element={<ProtectedRoute><MfaSetupPage /></ProtectedRoute>}
      />
      <Route
        element={
          <ProtectedRoute>
            <MfaGate><AppShell /></MfaGate>
          </ProtectedRoute>
        }
      >
        <Route index element={<DashboardPage />} />
        <Route path="/security/sessions" element={<SessionsPage />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
