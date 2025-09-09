import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.tsx'

// Get the root element
const rootElement = document.getElementById('root')!;

// Read topic from data attribute if available
const topic = rootElement.getAttribute('data-topic') || undefined;

createRoot(rootElement).render(
  <StrictMode>
    <App topic={topic} />
  </StrictMode>,
)
