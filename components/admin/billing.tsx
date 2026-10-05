"use client"

import { useState } from "react"
import { SubscriptionMetrics } from "./stats"
import {
  Badge,
  ErrorNote,
  Pagination,
  Panel,
  adminRequest,
  formatDate,
  formatDateTime,
  formatMoney,
  ghostButton,
  inputClass,
  primaryButton,
  statusTone,
  useAdminList,
} from "./shared"

type AdminSubscription = {
  id: number
  planId: string | null
  planName: string | null
  status: string
  billingPeriod: string
  seats: number
  startedAt: string | null
  trialEndsAt: string | null
  currentPeriodEnd: string | null
  canceledAt: string | null
  grantsAccess: boolean
  user: { id: number; name: string; email: string } | null
  teamName: string | null
  paidPayments: number
}

type AdminPayment = {
  id: number
  reference: string | null
  amount: number
  currency: string
  status: string
  planName: string | null
  billingPeriod: string | null
  checkoutId: string | null
  failureReason: string | null
  errorCode: string | null
  paidAt: string | null
  refundedAt: string | null
  refundReason: string | null
  createdAt: string | null
  user: { id: number; name: string; email: string } | null
}

type ContactRequest = {
  id: number
  name: string
  email: string
  company: string | null
  seats: number | null
  message: string
  status: "new" | "handled"
  createdAt: string | null
}

const message = (text: string | null, tone: "good" | "bad") =>
  text ? (
    <p className={`rounded-xl border p-3 text-sm ${tone === "good" ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400" : "border-destructive/30 bg-destructive/10 text-destructive"}`}>{text}</p>
  ) : null

export function SubscriptionsSection() {
  const [status, setStatus] = useState("")
  const [page, setPage] = useState(1)
  const [notice, setNotice] = useState<string | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)
  const [confirmCancel, setConfirmCancel] = useState<AdminSubscription | null>(null)
  const list = useAdminList<AdminSubscription>("/admin/subscriptions", "subscriptions", { status, page })

  const cancel = async (subscription: AdminSubscription) => {
    setConfirmCancel(null)
    const result = await adminRequest(`/admin/subscriptions/${subscription.id}/cancel`, "POST")
    if (!result.ok) {
      setActionError(result.error)
      return
    }
    setNotice(`Subscription #${subscription.id} ended and VPN access revoked.`)
    list.reload()
  }

  return (
    <section className="space-y-6">
      <SubscriptionMetrics />
      <TeamProvisioning onCreated={list.reload} />

      <Panel
        title="All subscriptions"
        action={
          <select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1) }} className={inputClass} aria-label="Filter by status">
            <option value="">All statuses</option>
            {["active", "trialing", "canceled", "expired"].map((value) => <option key={value} value={value}>{value}</option>)}
          </select>
        }
      >
        <ErrorNote message={list.error ?? actionError} />
        {message(notice, "good")}
        {confirmCancel && (
          <div className="mb-3 rounded-xl border border-destructive/30 bg-destructive/10 p-3 text-sm">
            <p>End {confirmCancel.user?.email}&apos;s {confirmCancel.planName} subscription now? Access stops immediately. Record any refund separately in Payments.</p>
            <div className="mt-2 flex gap-2">
              <button type="button" className="rounded-lg bg-destructive px-3 py-2 text-sm font-medium text-white" onClick={() => cancel(confirmCancel)}>End now</button>
              <button type="button" className={ghostButton} onClick={() => setConfirmCancel(null)}>Keep</button>
            </div>
          </div>
        )}
        <div className="overflow-x-auto">
          <table className="w-full min-w-[760px] text-left text-sm">
            <thead className="text-muted-foreground">
              <tr><th className="py-2 font-medium">Customer</th><th className="font-medium">Plan</th><th className="font-medium">Status</th><th className="font-medium">Period</th><th className="font-medium">Payments</th><th /></tr>
            </thead>
            <tbody className="divide-y divide-border">
              {list.items.map((subscription) => (
                <tr key={subscription.id}>
                  <td className="py-2">{subscription.user?.email ?? "—"}{subscription.teamName && <p className="text-xs text-muted-foreground">Team {subscription.teamName} · {subscription.seats} seats</p>}</td>
                  <td>{subscription.planName} <span className="text-xs text-muted-foreground">{subscription.billingPeriod}</span></td>
                  <td><Badge tone={statusTone(subscription.status)}>{subscription.status}</Badge></td>
                  <td className="text-xs text-muted-foreground">{formatDate(subscription.startedAt)} → {formatDate(subscription.currentPeriodEnd)}</td>
                  <td className="tabular-nums">{subscription.paidPayments}</td>
                  <td className="text-right">
                    {subscription.grantsAccess && (
                      <button type="button" onClick={() => setConfirmCancel(subscription)} className="text-xs font-medium text-destructive hover:underline">End now</button>
                    )}
                  </td>
                </tr>
              ))}
              {!list.loading && list.items.length === 0 && <tr><td colSpan={6} className="py-4 text-muted-foreground">No subscriptions.</td></tr>}
            </tbody>
          </table>
        </div>
        <Pagination meta={list.meta} onPage={setPage} />
      </Panel>

      <ContactRequests />
    </section>
  )
}

function TeamProvisioning({ onCreated }: { onCreated: () => void }) {
  const [form, setForm] = useState({ email: "", plan_slug: "team", team_name: "", seats: "5", months: "12" })
  const [feedback, setFeedback] = useState<{ text: string; tone: "good" | "bad" } | null>(null)
  const [saving, setSaving] = useState(false)

  const submit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setSaving(true)
    const result = await adminRequest("/admin/subscriptions/team", "POST", { ...form, seats: Number(form.seats), months: Number(form.months) })
    setSaving(false)
    if (!result.ok) {
      setFeedback({ text: result.error, tone: "bad" })
      return
    }
    setFeedback({ text: `Team ${result.data.team.name} created. The owner can now add members from their dashboard.`, tone: "good" })
    setForm({ ...form, email: "", team_name: "" })
    onCreated()
  }

  const update = (key: keyof typeof form) => (event: React.ChangeEvent<HTMLInputElement>) => setForm({ ...form, [key]: event.target.value })

  return (
    <Panel title="Provision a Team subscription">
      <p className="mb-3 text-sm text-muted-foreground">After the price is agreed with the customer, create their team here. The owner needs an existing account.</p>
      <form onSubmit={submit} className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <input required type="email" placeholder="Owner email" value={form.email} onChange={update("email")} className={inputClass} aria-label="Owner email" />
        <input required placeholder="Team name" value={form.team_name} onChange={update("team_name")} className={inputClass} aria-label="Team name" />
        <input required placeholder="Plan slug" value={form.plan_slug} onChange={update("plan_slug")} className={inputClass} aria-label="Plan slug" />
        <label className="flex items-center gap-2 text-xs text-muted-foreground">Seats<input required type="number" min={2} max={500} value={form.seats} onChange={update("seats")} className={`${inputClass} w-full`} /></label>
        <label className="flex items-center gap-2 text-xs text-muted-foreground">Months<input required type="number" min={1} max={36} value={form.months} onChange={update("months")} className={`${inputClass} w-full`} /></label>
        <div className="sm:col-span-2 lg:col-span-5">
          <button type="submit" disabled={saving} className={primaryButton}>{saving ? "Creating..." : "Create team subscription"}</button>
        </div>
      </form>
      {feedback && <div className="mt-3">{message(feedback.text, feedback.tone)}</div>}
    </Panel>
  )
}

function ContactRequests() {
  const [status, setStatus] = useState("new")
  const [page, setPage] = useState(1)
  const list = useAdminList<ContactRequest>("/admin/contact-requests", "requests", { status, page })

  const mark = async (request: ContactRequest, next: ContactRequest["status"]) => {
    const result = await adminRequest(`/admin/contact-requests/${request.id}`, "PATCH", { status: next })
    if (result.ok) list.reload()
  }

  return (
    <Panel
      title={`Team inquiries${list.extra.newCount ? ` (${list.extra.newCount} new)` : ""}`}
      action={
        <select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1) }} className={inputClass} aria-label="Filter inquiries">
          <option value="new">New</option>
          <option value="handled">Handled</option>
          <option value="">All</option>
        </select>
      }
    >
      <ErrorNote message={list.error} />
      {list.items.length === 0 ? (
        <p className="text-sm text-muted-foreground">No inquiries.</p>
      ) : (
        <ul className="divide-y divide-border text-sm">
          {list.items.map((request) => (
            <li key={request.id} className="grid gap-1 py-3">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <span className="font-medium">{request.name} · <span className="text-muted-foreground">{request.email}</span></span>
                <span className="flex items-center gap-2">
                  <Badge tone={statusTone(request.status)}>{request.status}</Badge>
                  <button type="button" onClick={() => mark(request, request.status === "new" ? "handled" : "new")} className="text-xs font-medium text-primary hover:underline">
                    Mark {request.status === "new" ? "handled" : "new"}
                  </button>
                </span>
              </div>
              <p className="text-xs text-muted-foreground">{request.company ?? "No company"} · {request.seats ?? "?"} seats · {formatDateTime(request.createdAt)}</p>
              <p className="whitespace-pre-line">{request.message}</p>
            </li>
          ))}
        </ul>
      )}
      <Pagination meta={list.meta} onPage={setPage} />
    </Panel>
  )
}

export function PaymentsSection() {
  const [status, setStatus] = useState("")
  const [search, setSearch] = useState("")
  const [page, setPage] = useState(1)
  const [refunding, setRefunding] = useState<AdminPayment | null>(null)
  const [reason, setReason] = useState("")
  const [endSubscription, setEndSubscription] = useState(true)
  const [notice, setNotice] = useState<string | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)
  const list = useAdminList<AdminPayment>("/admin/payments", "payments", { status, q: search.trim(), page })

  const refund = async () => {
    if (!refunding) return
    setActionError(null)
    const result = await adminRequest(`/admin/payments/${refunding.id}/refund`, "POST", { reason, end_subscription: endSubscription })
    if (!result.ok) {
      setActionError(result.error)
      return
    }
    setNotice(`Refund of ${formatMoney(refunding.amount, refunding.currency)} recorded for ${refunding.user?.email}.`)
    setRefunding(null)
    setReason("")
    list.reload()
  }

  return (
    <section className="space-y-4">
      <div className="flex flex-wrap gap-2">
        <input value={search} onChange={(event) => { setSearch(event.target.value); setPage(1) }} placeholder="Customer email" className={`${inputClass} min-w-56 flex-1`} aria-label="Search by email" />
        <select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1) }} className={inputClass} aria-label="Filter by status">
          <option value="">All statuses</option>
          {["succeeded", "pending", "failed", "refunded"].map((value) => <option key={value} value={value}>{value}</option>)}
        </select>
      </div>

      <ErrorNote message={list.error ?? actionError} />
      {message(notice, "good")}

      {refunding && (
        <div className="grid gap-3 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm">
          <p>
            Record a refund of <strong>{formatMoney(refunding.amount, refunding.currency)}</strong> for {refunding.user?.email}.
            Chargily has no refund API: send the money back from the Chargily dashboard first, then record it here.
          </p>
          <input value={reason} onChange={(event) => setReason(event.target.value)} placeholder="Reason (required)" className={inputClass} aria-label="Refund reason" />
          <label className="flex items-center gap-2">
            <input type="checkbox" checked={endSubscription} onChange={(event) => setEndSubscription(event.target.checked)} />
            End the subscription paid by this payment and revoke VPN access
          </label>
          <div className="flex gap-2">
            <button type="button" disabled={!reason.trim()} onClick={refund} className={primaryButton}>Record refund</button>
            <button type="button" onClick={() => setRefunding(null)} className={ghostButton}>Cancel</button>
          </div>
        </div>
      )}

      <div className="overflow-x-auto rounded-2xl border border-border bg-card">
        <table className="w-full min-w-[820px] text-left text-sm">
          <thead className="border-b border-border text-muted-foreground">
            <tr><th className="px-4 py-3 font-medium">Date</th><th className="px-4 py-3 font-medium">Customer</th><th className="px-4 py-3 font-medium">Plan</th><th className="px-4 py-3 font-medium">Amount</th><th className="px-4 py-3 font-medium">Status</th><th className="px-4 py-3" /></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {list.items.map((payment) => (
              <tr key={payment.id}>
                <td className="px-4 py-3 text-xs text-muted-foreground">{formatDateTime(payment.paidAt ?? payment.createdAt)}</td>
                <td className="px-4 py-3">{payment.user?.email ?? "—"}<p className="text-xs text-muted-foreground">{payment.checkoutId ?? payment.reference}</p></td>
                <td className="px-4 py-3">{payment.planName} <span className="text-xs text-muted-foreground">{payment.billingPeriod}</span></td>
                <td className="px-4 py-3 tabular-nums">{formatMoney(payment.amount, payment.currency)}</td>
                <td className="px-4 py-3">
                  <Badge tone={statusTone(payment.status)}>{payment.status}</Badge>
                  {(payment.failureReason || payment.errorCode) && <p className="mt-1 text-xs text-muted-foreground">{payment.errorCode} {payment.failureReason}</p>}
                  {payment.refundReason && <p className="mt-1 text-xs text-muted-foreground">Refund: {payment.refundReason}</p>}
                </td>
                <td className="px-4 py-3 text-right">
                  {payment.status === "succeeded" && (
                    <button type="button" onClick={() => { setRefunding(payment); setReason(""); setEndSubscription(true) }} className="text-xs font-medium text-primary hover:underline">Refund</button>
                  )}
                </td>
              </tr>
            ))}
            {!list.loading && list.items.length === 0 && <tr><td colSpan={6} className="px-4 py-6 text-muted-foreground">No payments.</td></tr>}
          </tbody>
        </table>
      </div>
      <Pagination meta={list.meta} onPage={setPage} />
    </section>
  )
}
