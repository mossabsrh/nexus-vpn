"use client"

import { useState } from "react"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from "./ui/dialog"
import { Button } from "./ui/button"
import { apiFetch } from "@/lib/api"
import { formatPrice, type PricingPlan } from "@/lib/pricing"
import { useAuth } from "./auth-provider"

interface PaymentDialogProps {
  plan: PricingPlan
  billingPeriod: "monthly" | "yearly"
  trigger: React.ReactNode
  /** Explains what the payment does to the user's current subscription. */
  note?: string
}

export function PaymentDialog({ plan, billingPeriod, trigger, note }: PaymentDialogProps) {
  const auth = useAuth()
  const [status, setStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle')
  const [message, setMessage] = useState('')
  const [needsVerification, setNeedsVerification] = useState(false)

  const amount = billingPeriod === 'yearly' ? plan.yearlyTotal : plan.monthlyPrice
  const formattedAmount = formatPrice(amount, plan.currency)

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setStatus('loading')
    setNeedsVerification(false)
    setMessage('Creating secure checkout...')

    try {
      const response = await apiFetch('/checkout', {
        method: 'POST',
        body: JSON.stringify({
          plan_slug: plan.slug,
          billing_period: billingPeriod,
        }),
      }, 20000)

      const data = await response.json().catch(() => ({}))

      if (!response.ok) {
        setStatus('error')
        setNeedsVerification(data.code === 'email_unverified')
        setMessage(response.status === 401
          ? 'Sign in before subscribing, then try again.'
          : response.status === 429
            ? 'Too many checkout attempts. Please wait a minute and try again.'
            : data.message || 'Unable to create checkout. Please try again.')
        return
      }

      setStatus('success')
      setMessage('Redirecting to Chargily...')
      window.location.assign(data.checkout_url)
    } catch {
      setStatus('error')
      setMessage('Unable to create checkout. Please check your connection and try again.')
    }
  }

  const resend = async () => {
    const result = await auth.resendVerification()
    setMessage(result.success ? 'Verification email sent. Open the link in it, then try again.' : result.error ?? 'Unable to send the email.')
  }

  return (
    <Dialog>
      <DialogTrigger asChild>{trigger}</DialogTrigger>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>Subscribe to {plan.name}</DialogTitle>
          <DialogDescription>
            Continue to Chargily to pay securely with EDAHABIA or CIB. Payments do not renew automatically.
          </DialogDescription>
        </DialogHeader>

        <form className="grid gap-4 py-2" onSubmit={handleSubmit}>
          <div className="flex items-center justify-between rounded-md border border-border bg-muted/50 px-4 py-3 text-sm">
            <span>{plan.name} · {billingPeriod === 'yearly' ? '12 months' : '1 month'}</span>
            <strong>{formattedAmount}</strong>
          </div>

          {note && <p className="text-sm text-muted-foreground">{note}</p>}

          {message && (
            <div className={`rounded-md border p-3 text-sm ${status === 'error' ? 'border-destructive/30 bg-destructive/10 text-destructive' : 'border-border bg-muted/50 text-foreground'}`}>
              {message}
              {needsVerification && (
                <button type="button" onClick={resend} className="ml-1 font-medium underline">
                  Resend verification email
                </button>
              )}
            </div>
          )}

          <DialogFooter className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <Button type="submit" className="w-full sm:w-auto" disabled={status === 'loading' || status === 'success' || amount === null}>
              {status === 'loading' ? 'Connecting...' : 'Continue to Chargily'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
