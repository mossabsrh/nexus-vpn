"use client"

import Link from "next/link"
import { useCallback, useEffect, useState } from "react"
import { useRouter } from "next/navigation"

import { useAuth, type SubscriptionSummary } from "@/components/auth-provider"
import { Card, Notice, PageShell, formatAmount, formatDate, formatDateTime } from "@/components/page-shell"
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { apiErrorMessage, apiFetch } from "@/lib/api"

type PaymentRecord = {
  id: number
  reference: string | null
  amount: number
  currency: string
  status: "pending" | "succeeded" | "failed" | "refunded"
  planName: string | null
  billingPeriod: string | null
  failureReason: string | null
  paidAt: string | null
  refundedAt: string | null
  refundReason: string | null
  createdAt: string | null
  checkoutId: string | null
}

type Meta = { currentPage: number; lastPage: number; total: number }

type VpnStatus = {
  hasAccess: boolean
  emailVerified: boolean
  serverConfigured: boolean
  credential: { publicKey: string; address: string; issuedAt: string | null } | null
}

type TeamInfo = {
  id: number
  name: string
  seats: number
  seatsUsed: number
  subscription: SubscriptionSummary | null
  members?: { id: number; name: string; email: string; role: string }[]
}

const statusLabel: Record<string, string> = {
  trialing: "Free trial",
  active: "Active",
  canceled: "Canceled",
  expired: "Expired",
  past_due: "Past due",
  pending: "Pending",
  succeeded: "Paid",
  failed: "Failed",
  refunded: "Refunded",
}

function StatusBadge({ status }: { status: string }) {
  const tone = ["active", "succeeded", "trialing"].includes(status)
    ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400"
    : ["failed", "expired", "refunded"].includes(status)
      ? "border-destructive/30 bg-destructive/10 text-destructive"
      : "border-border bg-muted text-muted-foreground"

  return <span className={`rounded-full border px-2 py-0.5 text-xs font-medium ${tone}`}>{statusLabel[status] ?? status}</span>
}

function Pager({ meta, onPage }: { meta: Meta | null; onPage: (page: number) => void }) {
  if (!meta || meta.lastPage <= 1) return null

  return (
    <div className="mt-3 flex items-center justify-end gap-2 text-sm">
      <button type="button" disabled={meta.currentPage <= 1} onClick={() => onPage(meta.currentPage - 1)} className="rounded-lg border border-border px-3 py-1 disabled:opacity-50">Previous</button>
      <span className="text-muted-foreground">Page {meta.currentPage} of {meta.lastPage}</span>
      <button type="button" disabled={meta.currentPage >= meta.lastPage} onClick={() => onPage(meta.currentPage + 1)} className="rounded-lg border border-border px-3 py-1 disabled:opacity-50">Next</button>
    </div>
  )
}

export default function DashboardPage() {
  const router = useRouter()
  const auth = useAuth()
  const [payments, setPayments] = useState<PaymentRecord[]>([])
  const [paymentsMeta, setPaymentsMeta] = useState<Meta | null>(null)
  const [subscriptions, setSubscriptions] = useState<SubscriptionSummary[]>([])
  const [vpn, setVpn] = useState<VpnStatus | null>(null)
  const [team, setTeam] = useState<TeamInfo | null>(null)
  const [receipt, setReceipt] = useState<PaymentRecord | null>(null)
  const [notice, setNotice] = useState<{ tone: "error" | "success" | "info"; text: string } | null>(null)
  const [memberEmail, setMemberEmail] = useState("")
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    if (auth.status === "guest") router.replace("/")
  }, [auth.status, router])

  const loadPayments = useCallback(async (page = 1) => {
    const response = await apiFetch(`/account/payments?page=${page}`)
    if (!response.ok) throw new Error()
    const data = await response.json()
    setPayments(data.payments)
    setPaymentsMeta(data.meta)
  }, [])

  const loadAll = useCallback(async () => {
    try {
      const [subscriptionsResponse, vpnResponse, teamResponse] = await Promise.all([
        apiFetch("/account/subscriptions"),
        apiFetch("/vpn/credentials"),
        apiFetch("/team"),
        loadPayments(1),
      ])
      if (!subscriptionsResponse.ok || !vpnResponse.ok || !teamResponse.ok) throw new Error()
      setSubscriptions((await subscriptionsResponse.json()).subscriptions)
      setVpn(await vpnResponse.json())
      setTeam((await teamResponse.json()).team)
    } catch {
      setNotice({ tone: "error", text: "Some account information could not be loaded. Refresh the page to try again." })
    }
  }, [loadPayments])

  useEffect(() => {
    if (auth.status !== "authenticated") return
    const timer = window.setTimeout(loadAll, 0)
    return () => window.clearTimeout(timer)
  }, [auth.status, loadAll])

  const downloadConfig = async () => {
    setBusy(true)
    setNotice(null)
    try {
      const response = await apiFetch("/vpn/config", {}, 15000)
      if (!response.ok) {
        setNotice({ tone: "error", text: await apiErrorMessage(response, "The configuration could not be generated.") })
        return
      }
      const url = URL.createObjectURL(await response.blob())
      const link = document.createElement("a")
      link.href = url
      link.download = "nexusvpn.conf"
      link.click()
      URL.revokeObjectURL(url)
      setVpn((await (await apiFetch("/vpn/credentials")).json()) as VpnStatus)
    } finally {
      setBusy(false)
    }
  }

  const resend = async () => {
    const result = await auth.resendVerification()
    setNotice(result.success
      ? { tone: "success", text: result.message ?? "Verification email sent." }
      : { tone: "error", text: result.error ?? "Unable to send the email." })
  }

  const teamRequest = async (path: string, method: "POST" | "DELETE", body?: object) => {
    setBusy(true)
    setNotice(null)
    try {
      const response = await apiFetch(path, { method, body: body ? JSON.stringify(body) : undefined })
      if (!response.ok) {
        setNotice({ tone: "error", text: await apiErrorMessage(response, "The team could not be updated.") })
        return
      }
      setTeam((await response.json()).team)
      setMemberEmail("")
    } finally {
      setBusy(false)
    }
  }

  if (auth.status === "loading" || auth.status === "guest") {
    return <PageShell title="Dashboard"><p className="text-sm text-muted-foreground">Loading your account...</p></PageShell>
  }

  if (auth.status === "error" || !auth.user) {
    return (
      <PageShell title="Dashboard">
        <Notice tone="error">
          We could not reach the server to check your session.{" "}
          <button type="button" onClick={auth.refresh} className="font-medium underline">Try again</button>
        </Notice>
      </PageShell>
    )
  }

  const user = auth.user
  const current = user.subscription
  const isOwner = Boolean(team && user.team?.isOwner)

  return (
    <PageShell title={`Hello, ${user.name}`}>
      <div className="grid gap-5">
        {notice && <Notice tone={notice.tone}>{notice.text}</Notice>}

        {!user.emailVerified && (
          <Notice tone="warning">
            Please confirm your email address ({user.email}) to download your VPN configuration and to subscribe.{" "}
            <button type="button" onClick={resend} className="font-medium underline">Resend the link</button>
          </Notice>
        )}

        <div className="grid gap-5 md:grid-cols-2">
          <Card title="Subscription">
            {current ? (
              <dl className="grid gap-2 text-sm">
                <div className="flex justify-between gap-3"><dt className="text-muted-foreground">Plan</dt><dd className="font-medium">{current.planName ?? "—"}</dd></div>
                <div className="flex justify-between gap-3"><dt className="text-muted-foreground">Status</dt><dd><StatusBadge status={current.status} /></dd></div>
                {current.status === "trialing" && (
                  <div className="flex justify-between gap-3"><dt className="text-muted-foreground">Trial ends</dt><dd>{formatDate(current.trialEndsAt)}</dd></div>
                )}
                {current.status !== "trialing" && (
                  <div className="flex justify-between gap-3">
                    <dt className="text-muted-foreground">{current.grantsAccess ? "Access until" : "Ended"}</dt>
                    <dd>{formatDate(current.currentPeriodEnd)}</dd>
                  </div>
                )}
                <div className="flex justify-between gap-3"><dt className="text-muted-foreground">Billing</dt><dd className="capitalize">{current.billingPeriod}{current.seats > 1 ? ` · ${current.seats} seats` : ""}</dd></div>
                {current.status === "canceled" && current.grantsAccess && (
                  <p className="text-xs text-muted-foreground">Canceled on {formatDate(current.canceledAt)}. You keep access until the paid period ends.</p>
                )}
                {user.team && !user.team.isOwner && <p className="text-xs text-muted-foreground">Provided by team {user.team.name}.</p>}
                <Link href="/#pricing" className="mt-2 inline-block text-sm font-medium text-primary hover:underline">
                  {current.status === "trialing" ? "Subscribe to keep access" : current.grantsAccess ? "Extend or change plan" : "Choose a plan"}
                </Link>
              </dl>
            ) : (
              <div className="text-sm text-muted-foreground">
                You do not have a subscription yet. <Link href="/#pricing" className="font-medium text-primary hover:underline">Start a free trial</Link>
              </div>
            )}
          </Card>

          <Card title="VPN access">
            {!vpn ? (
              <p className="text-sm text-muted-foreground">Loading...</p>
            ) : !vpn.hasAccess ? (
              <p className="text-sm text-muted-foreground">You need an active trial or subscription to connect.</p>
            ) : !vpn.emailVerified ? (
              <p className="text-sm text-muted-foreground">Confirm your email address to get your configuration.</p>
            ) : !vpn.serverConfigured ? (
              <p className="text-sm text-muted-foreground">VPN servers are being set up. Your configuration will be available here soon.</p>
            ) : (
              <div className="grid gap-3 text-sm">
                <p className="text-muted-foreground">
                  Import this file into the official WireGuard app on your device. Keep it private: it contains your personal key.
                </p>
                {vpn.credential && (
                  <p className="text-xs text-muted-foreground">Tunnel address {vpn.credential.address} · issued {formatDate(vpn.credential.issuedAt)}</p>
                )}
                <button type="button" onClick={downloadConfig} disabled={busy} className="w-fit rounded-lg bg-primary px-4 py-2 font-medium text-primary-foreground disabled:opacity-60">
                  {busy ? "Preparing..." : "Download WireGuard config"}
                </button>
              </div>
            )}
          </Card>
        </div>

        {team && (
          <Card title={`Team · ${team.name}`} action={<span className="text-sm text-muted-foreground">{team.seatsUsed}/{team.seats} seats</span>}>
            {isOwner && team.members ? (
              <div className="grid gap-3">
                <ul className="divide-y divide-border rounded-xl border border-border">
                  {team.members.map((member) => (
                    <li key={member.id} className="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                      <span className="min-w-0 truncate">{member.name} · <span className="text-muted-foreground">{member.email}</span></span>
                      {member.role === "owner" ? (
                        <span className="text-xs text-muted-foreground">Owner</span>
                      ) : (
                        <button type="button" disabled={busy} onClick={() => teamRequest(`/team/members/${member.id}`, "DELETE")} className="text-xs font-medium text-destructive hover:underline">
                          Remove
                        </button>
                      )}
                    </li>
                  ))}
                </ul>
                <form
                  className="flex flex-wrap gap-2"
                  onSubmit={(event) => {
                    event.preventDefault()
                    teamRequest("/team/members", "POST", { email: memberEmail })
                  }}
                >
                  <Input type="email" required placeholder="colleague@company.com" value={memberEmail} onChange={(event) => setMemberEmail(event.target.value)} className="max-w-xs" />
                  <button type="submit" disabled={busy || team.seatsUsed >= team.seats} className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60">
                    Add member
                  </button>
                </form>
                <p className="text-xs text-muted-foreground">Members need a NexusVPN account. Each member gets their own VPN configuration.</p>
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">You have a seat on this team. Contact the team owner for changes.</p>
            )}
          </Card>
        )}

        <Card title="Payment history">
          {payments.length === 0 ? (
            <p className="text-sm text-muted-foreground">No payments yet.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full min-w-[520px] text-left text-sm">
                <thead className="text-muted-foreground">
                  <tr><th className="py-2 font-medium">Date</th><th className="font-medium">Plan</th><th className="font-medium">Amount</th><th className="font-medium">Status</th><th /></tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {payments.map((payment) => (
                    <tr key={payment.id}>
                      <td className="py-2">{formatDate(payment.paidAt ?? payment.createdAt)}</td>
                      <td>{payment.planName ?? "—"} <span className="text-muted-foreground">{payment.billingPeriod}</span></td>
                      <td>{formatAmount(payment.amount, payment.currency)}</td>
                      <td><StatusBadge status={payment.status} /></td>
                      <td className="text-right">
                        <button type="button" onClick={() => setReceipt(payment)} className="text-xs font-medium text-primary hover:underline">Details</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
          <Pager meta={paymentsMeta} onPage={(page) => loadPayments(page).catch(() => undefined)} />
        </Card>

        <Card title="Subscription history">
          {subscriptions.length === 0 ? (
            <p className="text-sm text-muted-foreground">No subscriptions yet.</p>
          ) : (
            <ul className="divide-y divide-border text-sm">
              {subscriptions.map((subscription) => (
                <li key={subscription.id} className="flex flex-wrap items-center justify-between gap-2 py-2">
                  <span>{subscription.planName ?? "—"} · {formatDate(subscription.startedAt ?? subscription.createdAt)} → {formatDate(subscription.currentPeriodEnd)}</span>
                  <StatusBadge status={subscription.status} />
                </li>
              ))}
            </ul>
          )}
        </Card>
      </div>

      <Dialog open={receipt !== null} onOpenChange={(open) => !open && setReceipt(null)}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>Payment receipt</DialogTitle>
          </DialogHeader>
          {receipt && (
            <dl className="grid gap-2 text-sm">
              {[
                ["Reference", receipt.reference ?? `#${receipt.id}`],
                ["Customer", `${user.name} (${user.email})`],
                ["Plan", `${receipt.planName ?? "—"} · ${receipt.billingPeriod ?? ""}`],
                ["Amount", formatAmount(receipt.amount, receipt.currency)],
                ["Status", statusLabel[receipt.status] ?? receipt.status],
                ["Created", formatDateTime(receipt.createdAt)],
                ["Paid", formatDateTime(receipt.paidAt)],
                ["Chargily checkout", receipt.checkoutId ?? "—"],
                ...(receipt.failureReason ? [["Failure reason", receipt.failureReason]] : []),
                ...(receipt.refundedAt ? [["Refunded", `${formatDateTime(receipt.refundedAt)}${receipt.refundReason ? ` · ${receipt.refundReason}` : ""}`]] : []),
              ].map(([label, value]) => (
                <div key={label} className="flex justify-between gap-4">
                  <dt className="text-muted-foreground">{label}</dt>
                  <dd className="text-right break-all">{value}</dd>
                </div>
              ))}
            </dl>
          )}
        </DialogContent>
      </Dialog>
    </PageShell>
  )
}
