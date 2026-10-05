"use client"

import { useEffect, useState } from "react"
import { apiFetch } from "@/lib/api"
import { BarChart, ErrorNote, Panel, StatTile, formatMoney } from "./shared"

export type AdminStats = {
  users: { total: number; admins: number; verified: number; newLast30Days: number; newPrevious30Days: number; signupsByDay: Record<string, number> }
  subscriptions: {
    active: number
    trialing: number
    canceledWithAccess: number
    expired: number
    byPlan: { planId: string; name: string; active: number; trialing: number }[]
    trialUsers: number
    convertedTrialUsers: number
    trialConversionRate: number | null
  }
  revenue: { currency: string; total: number; thisMonth: number; last30Days: number; refunded: number; renewalsThisMonth: number; byMonth: Record<string, number> }
  payments: Record<string, number>
}

export function useAdminStats() {
  const [stats, setStats] = useState<AdminStats | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    apiFetch("/admin/stats")
      .then(async (response) => {
        if (!response.ok) throw new Error()
        setStats(await response.json())
      })
      .catch(() => setError("Statistics could not be loaded."))
  }, [])

  return { stats, error }
}

const growth = (current: number, previous: number) => {
  if (previous === 0) return current > 0 ? "new this period" : "no change"
  const change = Math.round(((current - previous) / previous) * 100)
  return `${change >= 0 ? "+" : ""}${change}% vs previous 30 days`
}

export function OverviewSection() {
  const { stats, error } = useAdminStats()

  if (error) return <ErrorNote message={error} />
  if (!stats) return <p className="text-sm text-muted-foreground">Loading statistics...</p>

  return (
    <div className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatTile label="Registered users" value={stats.users.total} hint={`${stats.users.newLast30Days} new in 30 days`} />
        <StatTile label="Paying subscriptions" value={stats.subscriptions.active} hint={`${stats.subscriptions.canceledWithAccess} canceled, still in paid period`} />
        <StatTile label="Active trials" value={stats.subscriptions.trialing} />
        <StatTile label="Revenue this month" value={formatMoney(stats.revenue.thisMonth, stats.revenue.currency)} hint={`${stats.revenue.renewalsThisMonth} renewal payment(s)`} />
      </div>
      <Panel title="Revenue by month">
        <BarChart
          title={`Net revenue (${stats.revenue.currency}), last 6 months`}
          data={Object.entries(stats.revenue.byMonth).map(([label, value]) => ({ label, value }))}
          format={(value) => formatMoney(value, stats.revenue.currency)}
        />
      </Panel>
    </div>
  )
}

export function AnalyticsSection() {
  const { stats, error } = useAdminStats()

  if (error) return <ErrorNote message={error} />
  if (!stats) return <p className="text-sm text-muted-foreground">Loading statistics...</p>

  const verifiedRate = stats.users.total ? Math.round((stats.users.verified / stats.users.total) * 100) : 0

  return (
    <div className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatTile label="New signups (30 days)" value={stats.users.newLast30Days} hint={growth(stats.users.newLast30Days, stats.users.newPrevious30Days)} />
        <StatTile
          label="Trial conversion"
          value={stats.subscriptions.trialConversionRate === null ? "—" : `${stats.subscriptions.trialConversionRate}%`}
          hint={`${stats.subscriptions.convertedTrialUsers} of ${stats.subscriptions.trialUsers} trial users paid`}
        />
        <StatTile label="Verified emails" value={`${verifiedRate}%`} hint={`${stats.users.verified} of ${stats.users.total} accounts`} />
        <StatTile label="Revenue (30 days)" value={formatMoney(stats.revenue.last30Days, stats.revenue.currency)} hint={`${formatMoney(stats.revenue.refunded, stats.revenue.currency)} refunded all-time`} />
      </div>

      <Panel title="Signups">
        <BarChart
          title="New accounts per day, last 30 days"
          data={Object.entries(stats.users.signupsByDay).map(([label, value]) => ({ label: label.slice(5), value }))}
        />
      </Panel>

      <div className="grid gap-6 lg:grid-cols-2">
        <Panel title="Payments by status">
          <table className="w-full text-left text-sm">
            <tbody className="divide-y divide-border">
              {["succeeded", "pending", "failed", "refunded"].map((status) => (
                <tr key={status}>
                  <td className="py-2 capitalize text-muted-foreground">{status}</td>
                  <td className="py-2 text-right tabular-nums">{stats.payments[status] ?? 0}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </Panel>
        <Panel title="Lifetime revenue">
          <p className="text-3xl font-semibold tabular-nums">{formatMoney(stats.revenue.total, stats.revenue.currency)}</p>
          <p className="mt-1 text-xs text-muted-foreground">Succeeded payments, refunds excluded.</p>
        </Panel>
      </div>
    </div>
  )
}

export function SubscriptionMetrics() {
  const { stats, error } = useAdminStats()

  if (error) return <ErrorNote message={error} />
  if (!stats) return null

  return (
    <div className="space-y-4">
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatTile label="Paying" value={stats.subscriptions.active} />
        <StatTile label="Trialing" value={stats.subscriptions.trialing} />
        <StatTile label="Canceled, access left" value={stats.subscriptions.canceledWithAccess} />
        <StatTile label="Expired" value={stats.subscriptions.expired} />
      </div>
      <Panel title="Subscriptions by plan">
        <table className="w-full text-left text-sm">
          <thead className="text-muted-foreground">
            <tr><th className="py-2 font-medium">Plan</th><th className="py-2 text-right font-medium">Paying</th><th className="py-2 text-right font-medium">Trialing</th></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {stats.subscriptions.byPlan.map((plan) => (
              <tr key={plan.planId}>
                <td className="py-2">{plan.name}</td>
                <td className="py-2 text-right tabular-nums">{plan.active}</td>
                <td className="py-2 text-right tabular-nums">{plan.trialing}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </Panel>
    </div>
  )
}
