import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.tsx'

// Get the root element
const rootElement = document.getElementById('root')!;

// Read topic and quiz from data attributes if available
const topic = rootElement.getAttribute('data-topic') || undefined;
const quiz = rootElement.getAttribute('data-quiz') || undefined;

createRoot(rootElement).render(
  <StrictMode>
    <App topic={topic} initialQuiz={quiz} />
  </StrictMode>,
)
