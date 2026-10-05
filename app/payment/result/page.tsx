"use client"

import Link from "next/link"
import { Suspense, useCallback, useEffect, useRef, useState } from "react"
import { useSearchParams } from "next/navigation"

import { useAuth } from "@/components/auth-provider"
import { Notice, PageShell, formatAmount, formatDate } from "@/components/page-shell"
import { apiErrorMessage, apiFetch } from "@/lib/api"

type VerifiedPayment = {
  id: number
  amount: number
  currency: string
  status: "pending" | "succeeded" | "failed" | "refunded"
  planName: string | null
  failureReason: string | null
}

const MAX_ATTEMPTS = 6

function PaymentResult() {
  const params = useSearchParams()
  const paymentId = params.get("payment")
  const auth = useAuth()
  const [payment, setPayment] = useState<VerifiedPayment | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [attempt, setAttempt] = useState(0)
  const timer = useRef<number | null>(null)
  const started = useRef(false)
  const setUserRef = useRef(auth.setUser)

  useEffect(() => {
    setUserRef.current = auth.setUser
  })

  /**
   * Asks the API to confirm the payment with Chargily. The webhook may still be on its way,
   * so a pending result is retried a few times before asking the user to check back later.
   */
  const verify = useCallback(async () => {
    if (!paymentId || !/^\d+$/.test(paymentId)) {
      setError("This payment link is incomplete.")
      return
    }

    try {
      const response = await apiFetch(`/payments/${paymentId}/verify`, {}, 20000)
      if (!response.ok) {
        setError(response.status === 404
          ? "We could not find this payment on your account. Make sure you are signed in with the account that paid."
          : await apiErrorMessage(response, "The payment could not be verified right now."))
        return
      }

      const data = await response.json()
      setPayment(data.payment)
      setError(null)
      if (data.user) setUserRef.current(data.user)

      if (data.payment.status === "pending") {
        setAttempt((current) => current + 1)
      }
    } catch {
      setError("The payment could not be verified right now.")
    }
  }, [paymentId])

  useEffect(() => {
    if (auth.status === "authenticated" && !started.current) {
      started.current = true
      verify()
    }
  }, [auth.status, verify])

  useEffect(() => {
    if (attempt === 0 || attempt >= MAX_ATTEMPTS || payment?.status !== "pending") return
    timer.current = window.setTimeout(verify, 3000)
    return () => {
      if (timer.current) window.clearTimeout(timer.current)
    }
  }, [attempt, payment?.status, verify])

  if (auth.status === "loading") {
    return <p className="text-sm text-muted-foreground">Checking your session...</p>
  }

  if (auth.status !== "authenticated") {
    return <Notice tone="info">Sign in with the account you paid with to see this payment. <Link href="/" className="font-medium underline">Go to sign in</Link></Notice>
  }

  if (error) {
    return (
      <div className="grid gap-3">
        <Notice tone="error">{error}</Notice>
        <div className="flex gap-3 text-sm">
          <button type="button" onClick={() => { setError(null); setAttempt(0); verify() }} className="rounded-lg border border-border px-3 py-2">Try again</button>
          <Link href="/dashboard" className="rounded-lg border border-border px-3 py-2">Go to dashboard</Link>
        </div>
      </div>
    )
  }

  if (!payment) {
    return <p className="text-sm text-muted-foreground">Confirming your payment with Chargily...</p>
  }

  if (payment.status === "succeeded") {
    const subscription = auth.user?.subscription
    return (
      <div className="grid gap-4">
        <Notice tone="success">
          Payment of {formatAmount(payment.amount, payment.currency)} received. Your {payment.planName} plan is active
          {subscription?.currentPeriodEnd ? ` until ${formatDate(subscription.currentPeriodEnd)}` : ""}.
        </Notice>
        <p className="text-sm text-muted-foreground">A receipt has been sent to {auth.user?.email}.</p>
        <Link href="/dashboard" className="w-fit rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">Open dashboard</Link>
      </div>
    )
  }

  if (payment.status === "pending") {
    return attempt >= MAX_ATTEMPTS ? (
      <div className="grid gap-3">
        <Notice tone="warning">
          Chargily has not confirmed this payment yet. If you completed the payment, it will be applied automatically within a few minutes.
        </Notice>
        <Link href="/dashboard" className="w-fit rounded-lg border border-border px-3 py-2 text-sm">Go to dashboard</Link>
      </div>
    ) : (
      <p className="text-sm text-muted-foreground">Waiting for Chargily to confirm the payment...</p>
    )
  }

  return (
    <div className="grid gap-3">
      <Notice tone="error">
        {payment.status === "refunded" ? "This payment was refunded." : "The payment was not completed."}
        {payment.failureReason ? ` (${payment.failureReason})` : ""} You have not been charged for a new period.
      </Notice>
      <Link href="/#pricing" className="w-fit rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">Try again</Link>
    </div>
  )
}

export default function PaymentResultPage() {
  return (
    <PageShell title="Payment">
      <Suspense fallback={<p className="text-sm text-muted-foreground">Loading...</p>}>
        <PaymentResult />
      </Suspense>
    </PageShell>
  )
}
