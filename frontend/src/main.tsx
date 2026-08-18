import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import App from './App'
import './styles/tokens.css'
import './styles/base.css'
import './styles/auth.css'
import './styles/shell.css'
import './styles/components.css'
import './styles/branding.css'
import './styles/master-data.css'
import './styles/forms.css'
import './styles/operations.css'
import './styles/operations-tables.css'
import './styles/operations-timeline.css'
import './styles/operations-forms.css'
import './styles/operations-responsive.css'
import './styles/responsive.css'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
