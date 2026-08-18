import { useState } from 'react'
import { Outlet } from 'react-router-dom'
import { MobileNav } from './MobileNav'
import { Sidebar } from './Sidebar'
import { Topbar } from './Topbar'

const SIDEBAR_PREFERENCE_KEY = 'fouskee.sidebar.collapsed'

function initialSidebarState() {
  try {
    const stored = window.localStorage.getItem(SIDEBAR_PREFERENCE_KEY)
    if (stored === 'true') return true
    if (stored === 'false') return false
  } catch {
    // Browser storage can be unavailable in hardened/private environments.
  }

  return window.matchMedia('(max-width: 980px)').matches
}

export function AppShell() {
  const [sidebarCollapsed, setSidebarCollapsed] = useState(
    initialSidebarState,
  )

  function toggleSidebar() {
    setSidebarCollapsed((current) => {
      const next = !current

      try {
        window.localStorage.setItem(
          SIDEBAR_PREFERENCE_KEY,
          String(next),
        )
      } catch {
        // Keep the in-memory preference when browser storage is unavailable.
      }

      return next
    })
  }

  return (
    <div
      className={[
        'app-shell',
        sidebarCollapsed ? 'app-shell--sidebar-collapsed' : '',
      ].filter(Boolean).join(' ')}
    >
      <Sidebar
        collapsed={sidebarCollapsed}
        onToggle={toggleSidebar}
      />
      <div className="app-shell__main">
        <Topbar />
        <main className="workspace"><Outlet /></main>
        <MobileNav />
      </div>
    </div>
  )
}
