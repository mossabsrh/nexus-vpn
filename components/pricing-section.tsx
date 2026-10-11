"use client"

import { useState } from "react"
import { motion, AnimatePresence } from "framer-motion"

import { formatPrice, type PricingPlan } from "@/lib/pricing"
import { usePricing } from "@/hooks/use-pricing"
import { PaymentDialog } from "./payment-dialog"
import { AuthDialog } from "./auth-dialog"
import { useAuth } from "./auth-provider"

/** Marketing comparison per plan slug; unknown plans show a dash instead of misaligned values. */
const comparisonBySlug: Record<string, Record<string, string>> = {
  essential: { Seats: '1', 'Team dashboard': '—', 'Priority support': 'Email', 'Billing model': 'Personal' },
  standard: { Seats: '1', 'Team dashboard': '—', 'Priority support': 'Priority', 'Billing model': 'Personal' },
  team: { Seats: '5–50', 'Team dashboard': 'Included', 'Priority support': 'Dedicated', 'Billing model': 'Business' },
}

export function PricingSection() {
  const auth = useAuth()
  const pricing = usePricing()
  const [isYearly, setIsYearly] = useState(true)
  const [authOpen, setAuthOpen] = useState(false)
  const [authMode, setAuthMode] = useState<"sign-in" | "sign-up">("sign-up")
  const [authPlan, setAuthPlan] = useState<string | undefined>(undefined)
  const [startingTrial, setStartingTrial] = useState<string | null>(null)
  const [trialError, setTrialError] = useState<string | null>(null)

  const formatDZD = (value: number | null) => formatPrice(value)

  const openAuth = (mode: "sign-in" | "sign-up", planSlug?: string) => {
    setAuthMode(mode)
    setAuthPlan(planSlug)
    setAuthOpen(true)
  }

  const handleStartTrial = async (planSlug: string) => {
    setStartingTrial(planSlug)
    setTrialError(null)
    const result = await auth.startTrial(planSlug)
    if (result.unauthenticated) {
      openAuth("sign-up", planSlug)
    } else if (!result.success) {
      setTrialError(result.error ?? 'Unable to start the trial.')
    }
    setStartingTrial(null)
  }

  const comparisonPlans = pricing.plans
  const marketingRow = (label: string) => comparisonPlans.map((plan) => comparisonBySlug[plan.slug]?.[label] ?? '—')
  const comparisonRows = [
    {
      label: 'Monthly price',
      values: comparisonPlans.map((plan) =>
        plan.monthlyPrice === null ? 'Custom' : `${plan.monthlyPrice.toLocaleString('en-US')} DZD`,
      ),
    },
    { label: 'Seats', values: marketingRow('Seats') },
    { label: 'Protocol', values: comparisonPlans.map(() => 'WireGuard®') },
    { label: 'Payment', values: comparisonPlans.map(() => 'EDAHABIA / CIB') },
    { label: 'Team dashboard', values: marketingRow('Team dashboard') },
    { label: 'Priority support', values: marketingRow('Priority support') },
    { label: 'Billing model', values: marketingRow('Billing model') },
  ]

  const user = auth.user
  const status = user?.subscriptionStatus ?? null
  const hasCurrent = status === 'active' || status === 'trialing'
  const everSubscribed = Boolean(user?.lastPlanId)

  const primaryClass = (plan: PricingPlan) => `w-full py-3 rounded-xl text-sm font-medium text-center transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60 ${
    plan.popular
      ? "bg-primary text-primary-foreground hover:bg-primary/90 glow-cyan"
      : "bg-muted border border-border text-foreground hover:bg-muted/80"
  }`
  const secondaryClass = "w-full rounded-xl border border-border bg-background py-3 text-center text-sm font-medium text-foreground transition-colors hover:bg-muted"

  const payButton = (plan: PricingPlan, label: string, className: string, note?: string) => (
    <PaymentDialog
      plan={plan}
      billingPeriod={isYearly ? 'yearly' : 'monthly'}
      note={note}
      trigger={<button type="button" className={className}>{label}</button>}
    />
  )

  const renderCta = (plan: PricingPlan) => {
    if (!plan.selfServe) {
      return (
        <a href="#contact" className={`${primaryClass(plan)} block`}>
          {plan.cta || 'Contact sales'}
        </a>
      )
    }

    if (!user) {
      return (
        <div className="grid gap-2">
          <button type="button" onClick={() => openAuth("sign-up", plan.slug)} className={primaryClass(plan)}>
            Start 7-day trial
          </button>
          <button type="button" onClick={() => openAuth("sign-in")} className={secondaryClass}>
            Sign in to subscribe
          </button>
        </div>
      )
    }

    if (user.team && !user.team.isOwner && user.hasVpnAccess) {
      return <p className="rounded-xl border border-border bg-muted/40 px-3 py-3 text-center text-sm text-muted-foreground">Covered by your team</p>
    }

    if (hasCurrent && user.planId === plan.slug) {
      return status === 'trialing'
        ? payButton(plan, 'Subscribe now', primaryClass(plan), 'Your trial becomes a paid subscription, starting today.')
        : payButton(plan, 'Extend subscription', secondaryClass, 'The new period is added after your current one ends.')
    }

    if (hasCurrent) {
      return payButton(plan, `Switch to ${plan.name}`, primaryClass(plan), `Your current ${user.planId} ${status === 'trialing' ? 'trial' : 'subscription'} ends when this payment succeeds.`)
    }

    if (everSubscribed) {
      return payButton(plan, user.lastPlanId === plan.slug ? 'Resubscribe' : 'Subscribe', primaryClass(plan))
    }

    return (
      <div className="grid gap-2">
        <button
          type="button"
          onClick={() => handleStartTrial(plan.slug)}
          disabled={startingTrial !== null}
          className={primaryClass(plan)}
        >
          {startingTrial === plan.slug ? 'Starting trial...' : 'Start 7-day trial'}
        </button>
        {payButton(plan, 'Subscribe now', secondaryClass)}
      </div>
    )
  }

  return (
    <section id="pricing" className="relative py-20 md:py-32">
      <div className="container mx-auto px-4 md:px-6 max-w-6xl">
        <motion.div
          initial={{ opacity: 0, y: 24 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6, ease: [0.22, 1, 0.36, 1] }}
          className="mb-12"
        >
          <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-border bg-muted/40 mb-5">
            <span className="text-xs text-primary font-medium tracking-wider uppercase">Pricing</span>
          </div>
          <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold tracking-tight mb-4">
            Straightforward.{" "}
            <span className="text-gradient">No traps.</span>
          </h2>
          <p className="text-muted-foreground text-lg max-w-lg leading-relaxed mb-8">
            One price, clear features, cancel any time. The 7-day trial is free — no card required.
          </p>

          {/* Billing toggle */}
          <div className="inline-flex items-center gap-1 p-1 rounded-xl bg-muted border border-border">
            <button
              onClick={() => setIsYearly(false)}
              className={`px-5 py-2 rounded-lg text-sm font-medium transition-all duration-200 ${
                !isYearly ? "bg-background text-foreground shadow-sm" : "text-muted-foreground hover:text-foreground"
              }`}
            >
              Monthly
            </button>
            <button
              onClick={() => setIsYearly(true)}
              className={`px-5 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 ${
                isYearly ? "bg-background text-foreground shadow-sm" : "text-muted-foreground hover:text-foreground"
              }`}
            >
              Yearly
              <span className="text-xs bg-emerald-500/15 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded-full font-medium">
                −50%
              </span>
            </button>
          </div>
        </motion.div>

        {pricing.status === "error" && (
          <div role="alert" className="mb-6 rounded-2xl border border-destructive/30 bg-destructive/10 p-5 text-sm text-destructive">
            Current prices could not be loaded, so none are shown.{" "}
            <button type="button" onClick={pricing.retry} className="font-medium underline">Try again</button>
          </div>
        )}

        {pricing.status === "loading" && comparisonPlans.length === 0 && (
          <div className="grid md:grid-cols-3 gap-4 lg:gap-5" aria-busy="true">
            {[0, 1, 2].map((key) => (
              <div key={key} className="h-[460px] animate-pulse rounded-2xl border border-border bg-muted/30" />
            ))}
          </div>
        )}

        <div className="grid md:grid-cols-3 gap-4 lg:gap-5">
          {comparisonPlans.map((plan, i) => (
            <motion.div
              key={plan.slug}
              initial={{ opacity: 0, y: 24 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: i * 0.1, ease: [0.22, 1, 0.36, 1] }}
              className={plan.popular ? "md:-mt-3 md:mb-3" : ""}
            >
              <div
                className={`relative rounded-2xl h-full flex flex-col overflow-hidden transition-all duration-400 ${
                  plan.popular
                    ? "glass-card glow-cyan-strong border border-primary/25"
                    : "glass-card hover:glow-cyan border border-border"
                }`}
              >
                {/* Popular badge */}
                {plan.popular && (
                  <div className="bg-primary text-primary-foreground text-xs font-semibold text-center py-1.5 tracking-wide">
                    Most popular
                  </div>
                )}

                <div className="p-6 flex flex-col flex-1">
                  {/* Plan header */}
                  <div className="mb-6">
                    <h3 className="text-xl font-bold text-foreground mb-1">{plan.name}</h3>
                    <p className="text-sm text-muted-foreground">{plan.description}</p>
                  </div>

                  {/* Price */}
                  <div className="mb-6 min-h-[72px]">
                    {plan.monthlyPrice !== null ? (
                      <AnimatePresence mode="wait">
                        <motion.div
                          key={isYearly ? "yearly" : "monthly"}
                          initial={{ opacity: 0, y: 8 }}
                          animate={{ opacity: 1, y: 0 }}
                          exit={{ opacity: 0, y: -8 }}
                          transition={{ duration: 0.2 }}
                        >
                          <div className="flex items-baseline gap-1.5">
                            <span className="text-4xl font-bold text-foreground">
                              {formatDZD(isYearly ? plan.yearlyPrice : plan.monthlyPrice)}
                            </span>
                            <span className="text-muted-foreground text-sm">/month</span>
                          </div>
                          {isYearly && plan.yearlyTotal && (
                            <p className="text-xs text-muted-foreground mt-1.5">
                              Billed {formatDZD(plan.yearlyTotal)} per year
                            </p>
                          )}
                        </motion.div>
                      </AnimatePresence>
                    ) : (
                      <div>
                        <span className="text-2xl font-bold text-foreground">Custom pricing</span>
                        <p className="text-xs text-muted-foreground mt-1.5">Per-seat pricing · Annual billing</p>
                      </div>
                    )}
                  </div>

                  {/* Features */}
                  <ul className="space-y-2.5 mb-7 flex-1">
                    {plan.features.map((feature) => (
                      <li key={feature} className="flex items-start gap-2.5">
                        <svg viewBox="0 0 16 16" fill="none" className="w-4 h-4 text-primary flex-shrink-0 mt-0.5">
                          <path d="M3 8l3.5 3.5L13 4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                        <span className="text-sm text-muted-foreground">{feature}</span>
                      </li>
                    ))}
                  </ul>

                  {/* CTA */}
                  {renderCta(plan)}
                </div>
              </div>
            </motion.div>
          ))}
        </div>

        {trialError && (
          <p role="alert" className="mt-4 text-sm text-destructive">
            {trialError}
          </p>
        )}

        <AuthDialog open={authOpen} onOpenChange={setAuthOpen} initialMode={authMode} initialPlan={authPlan} hideTrigger />

        {comparisonPlans.length > 0 && (
        <motion.div
          initial={{ opacity: 0, y: 18 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.5, delay: 0.2 }}
          className="mt-16"
        >
          <div className="mb-6 text-center">
            <p className="text-xs font-medium uppercase tracking-[0.2em] text-primary">Plan comparison</p>
            <h3 className="mt-3 text-2xl font-bold tracking-tight text-foreground">Choose the tier that matches your setup.</h3>
          </div>

          <div className="overflow-hidden rounded-2xl border border-border bg-card/60 backdrop-blur-sm shadow-[0_0_0_1px_rgba(255,255,255,0.02)]">
            <div className="overflow-x-auto">
              <table className="w-full min-w-[700px] border-separate border-spacing-0 text-left">
                <thead>
                  <tr>
                    <th className="border-b border-border bg-muted/40 px-5 py-4 text-sm font-semibold text-muted-foreground">Compare</th>
                    {comparisonPlans.map((plan) => (
                      <th
                        key={plan.slug}
                        className={`border-b border-border bg-muted/40 px-5 py-4 text-left ${
                          plan.popular ? 'text-primary' : 'text-foreground'
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          {plan.popular && (
                            <span className="rounded-full border border-primary/30 bg-primary/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-primary">
                              Popular
                            </span>
                          )}
                          <span className="text-base font-semibold">{plan.name}</span>
                        </div>
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {comparisonRows.map((row) => (
                    <tr key={row.label}>
                      <td className="border-b border-border bg-background/40 px-5 py-4 text-sm font-medium text-foreground">
                        {row.label}
                      </td>
                      {row.values.map((value, index) => (
                        <td
                          key={`${row.label}-${comparisonPlans[index]?.slug ?? index}`}
                          className="border-b border-border px-5 py-4 text-sm text-muted-foreground"
                        >
                          {value === 'Included' || value === '✓' ? (
                            <span className="inline-flex items-center gap-2 text-emerald-400">
                              <svg viewBox="0 0 16 16" fill="none" className="h-4 w-4 shrink-0">
                                <path d="M3 8l3.5 3.5L13 4" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
                              </svg>
                              {value}
                            </span>
                          ) : (
                            <span>{value}</span>
                          )}
                        </td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </motion.div>
        )}

        {/* Guarantee */}
        <motion.div
          initial={{ opacity: 0 }}
          whileInView={{ opacity: 1 }}
          viewport={{ once: true }}
          transition={{ duration: 0.5, delay: 0.3 }}
          className="flex flex-col sm:flex-row items-center justify-center gap-6 mt-10 text-sm text-muted-foreground"
        >
          <div className="flex items-center gap-2">
            <svg viewBox="0 0 16 16" fill="none" className="w-4 h-4 text-primary flex-shrink-0">
              <path d="M8 1L2 4v5c0 3.6 2.3 6.9 6 7.8C11.7 15.9 14 12.6 14 9V4L8 1z" stroke="currentColor" strokeWidth="1.2"/>
              <path d="M5.5 8l2 2L11 6" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"/>
            </svg>
            No activity logs kept
          </div>
          <div className="flex items-center gap-2">
            <svg viewBox="0 0 16 16" fill="none" className="w-4 h-4 text-primary flex-shrink-0">
              <rect x="2" y="4" width="12" height="9" rx="1.5" stroke="currentColor" strokeWidth="1.2"/>
              <path d="M5 4V3a3 3 0 016 0v1" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round"/>
            </svg>
            7-day free trial · No credit card needed
          </div>
          <div className="flex items-center gap-2">
            <svg viewBox="0 0 16 16" fill="none" className="w-4 h-4 text-primary flex-shrink-0">
              <circle cx="8" cy="8" r="6.5" stroke="currentColor" strokeWidth="1.2"/>
              <path d="M8 5v3.5l2 2" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round"/>
            </svg>
            Cancel any time
          </div>
        </motion.div>
      </div>
    </section>
  )
}
