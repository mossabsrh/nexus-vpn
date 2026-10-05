"use client"

import { useSyncExternalStore } from "react"
import { useTheme } from "next-themes"
import { Sun, Moon } from "lucide-react"

const subscribeNever = () => () => {}

export function ThemeToggle() {
  const { theme, setTheme } = useTheme()
  // false during SSR and hydration, true afterwards: prevents a theme icon mismatch.
  const mounted = useSyncExternalStore(subscribeNever, () => true, () => false)

  if (!mounted) {
    return (
      <button
        className="p-2 rounded-lg text-foreground hover:bg-muted transition-colors"
        aria-label="Toggle theme"
        disabled
      />
    )
  }

  return (
    <button
      onClick={() => setTheme(theme === "dark" ? "light" : "dark")}
      className="p-2 rounded-lg text-foreground hover:bg-muted transition-colors"
      aria-label="Toggle theme"
      title={`Switch to ${theme === "dark" ? "light" : "dark"} mode`}
    >
      {theme === "dark" ? (
        <Sun className="h-5 w-5" />
      ) : (
        <Moon className="h-5 w-5" />
      )}
    </button>
  )
}
