"use client"

import { Suspense, useEffect, useRef, useState } from "react"
import { useSearchParams } from "next/navigation"

import { useAuth } from "@/components/auth-provider"
import { Notice, PageShell } from "@/components/page-shell"
import { apiFetch } from "@/lib/api"

function VerifyEmail() {
  const params = useSearchParams()
  const auth = useAuth()
  const linkIsComplete = ["id", "hash", "expires", "signature"].every((name) => params.get(name))
  const [state, setState] = useState<"verifying" | "verified" | "invalid">(linkIsComplete ? "verifying" : "invalid")
  const [resendMessage, setResendMessage] = useState<string | null>(null)
  const started = useRef(false)
  const refreshRef = useRef(auth.refresh)

  useEffect(() => {
    refreshRef.current = auth.refresh
  })

  useEffect(() => {
    if (started.current) return
    started.current = true

    const id = params.get("id")
    const hash = params.get("hash")
    const expires = params.get("expires")
    const signature = params.get("signature")

    if (!id || !hash || !expires || !signature) return

    const query = new URLSearchParams({ expires, signature }).toString()
    apiFetch(`/email/verify/${encodeURIComponent(id)}/${encodeURIComponent(hash)}?${query}`)
      .then((response) => {
        setState(response.ok ? "verified" : "invalid")
        if (response.ok) refreshRef.current()
      })
      .catch(() => setState("invalid"))
  }, [params])

  if (state === "verifying") {
    return <p className="text-sm text-muted-foreground">Confirming your email address...</p>
  }

  if (state === "verified") {
    return (
      <div className="grid gap-4">
        <Notice tone="success">Your email address is confirmed.</Notice>
        <a href="/dashboard" className="w-fit rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">Go to dashboard</a>
      </div>
    )
  }

  return (
    <div className="grid gap-4">
      <Notice tone="error">This link is invalid or has expired. Links are valid for 60 minutes.</Notice>
      {auth.isAuthenticated ? (
        <button
          type="button"
          onClick={async () => {
            const result = await auth.resendVerification()
            setResendMessage(result.success ? result.message ?? "A new link has been sent." : result.error ?? "Unable to send a new link.")
          }}
          className="w-fit rounded-lg border border-border px-4 py-2 text-sm"
        >
          Send a new link
        </button>
      ) : null}
      {auth.isAuthenticated ? (
        resendMessage && <p className="text-sm text-muted-foreground">{resendMessage}</p>
      ) : (
        <p className="text-sm text-muted-foreground">Sign in and request a new link from your dashboard.</p>
      )}
    </div>
  )
}

export default function VerifyEmailPage() {
  return (
    <PageShell title="Confirm your email">
      <Suspense fallback={<p className="text-sm text-muted-foreground">Loading...</p>}>
        <VerifyEmail />
      </Suspense>
    </PageShell>
  )
}
