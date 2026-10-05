"use client"

import Link from "next/link"
import { Suspense, useState } from "react"
import { useSearchParams } from "next/navigation"

import { Notice, PageShell } from "@/components/page-shell"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { apiErrorMessage, apiFetch } from "@/lib/api"

function ResetPasswordForm() {
  const params = useSearchParams()
  const token = params.get("token") ?? ""
  const email = params.get("email") ?? ""
  const [password, setPassword] = useState("")
  const [confirmation, setConfirmation] = useState("")
  const [status, setStatus] = useState<"idle" | "saving" | "done">("idle")
  const [error, setError] = useState<string | null>(null)

  if (!token || !email) {
    return <Notice tone="error">This reset link is incomplete. Request a new one from the sign-in dialog.</Notice>
  }

  if (status === "done") {
    return (
      <div className="grid gap-4">
        <Notice tone="success">Your password has been changed and other sessions were signed out. You can now sign in.</Notice>
        <Link href="/" className="w-fit rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">Go to sign in</Link>
      </div>
    )
  }

  const submit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setError(null)

    if (password !== confirmation) {
      setError("The passwords do not match.")
      return
    }

    setStatus("saving")
    try {
      const response = await apiFetch("/auth/reset-password", {
        method: "POST",
        body: JSON.stringify({ token, email, password, password_confirmation: confirmation }),
      })

      if (!response.ok) {
        setError(response.status === 429
          ? "Too many attempts. Please wait a minute and try again."
          : await apiErrorMessage(response, "This reset link is invalid or has expired."))
        setStatus("idle")
        return
      }

      setStatus("done")
    } catch {
      setError("The password could not be reset. Please try again.")
      setStatus("idle")
    }
  }

  return (
    <form onSubmit={submit} className="grid max-w-md gap-4">
      <p className="text-sm text-muted-foreground">Choose a new password for <strong className="text-foreground">{email}</strong>.</p>
      <div className="grid gap-2">
        <Label htmlFor="new-password">New password</Label>
        <Input id="new-password" type="password" autoComplete="new-password" required minLength={8} value={password} onChange={(event) => setPassword(event.target.value)} />
      </div>
      <div className="grid gap-2">
        <Label htmlFor="confirm-password">Confirm new password</Label>
        <Input id="confirm-password" type="password" autoComplete="new-password" required minLength={8} value={confirmation} onChange={(event) => setConfirmation(event.target.value)} />
      </div>
      {error && <Notice tone="error">{error}</Notice>}
      <button type="submit" disabled={status === "saving"} className="w-fit rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60">
        {status === "saving" ? "Saving..." : "Reset password"}
      </button>
    </form>
  )
}

export default function ResetPasswordPage() {
  return (
    <PageShell title="Reset your password">
      <Suspense fallback={<p className="text-sm text-muted-foreground">Loading...</p>}>
        <ResetPasswordForm />
      </Suspense>
    </PageShell>
  )
}
