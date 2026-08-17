import type { ReactNode } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import { LoginPage } from '../features/auth/pages/LoginPage'
import { MfaSetupPage } from '../features/auth/pages/MfaSetupPage'
import { TwoFactorChallengePage } from '../features/auth/pages/TwoFactorChallengePage'
import { CatalogSetupPage } from '../features/master-data/pages/CatalogSetupPage'
import { CustomersPage } from '../features/master-data/pages/CustomersPage'
import { PriceListsPage } from '../features/master-data/pages/PriceListsPage'
import { ProductsPage } from '../features/master-data/pages/ProductsPage'
import { SuppliersPage } from '../features/master-data/pages/SuppliersPage'
import { SessionsPage } from '../features/security/pages/SessionsPage'
import { AppShell } from '../shell/components/AppShell'
import { DashboardPage } from '../shell/pages/DashboardPage'
import { GuestRoute } from './GuestRoute'
import { MfaGate } from './MfaGate'
import { PermissionRoute } from './PermissionRoute'
import { ProtectedRoute } from './ProtectedRoute'

const permitted = (permission: string, child: ReactNode) => (
  <PermissionRoute permission={permission}>{child}</PermissionRoute>
)

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
        <Route path="/customers" element={permitted('customers.view', <CustomersPage />)} />
        <Route path="/suppliers" element={permitted('suppliers.view', <SuppliersPage />)} />
        <Route path="/products" element={permitted('catalog.view', <ProductsPage />)} />
        <Route path="/catalog/setup" element={permitted('catalog.manage', <CatalogSetupPage />)} />
        <Route path="/price-lists" element={permitted('pricing.view', <PriceListsPage />)} />
        <Route path="/security/sessions" element={<SessionsPage />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
