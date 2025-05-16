import { StrictMode } from "react"
import { createRoot } from "react-dom/client"
import App from "@/app"
// import "@/assets/css/import.css"

const appElement = document.getElementById("app")
if (!appElement) throw new Error("Failed to find the app element")

createRoot(appElement).render(
  <StrictMode>
    <App />
  </StrictMode>
) 