"use client"

import { useCallback, useEffect, useState } from "react"
import { apiErrorMessage, apiFetch } from "@/lib/api"

export type PageMeta = { currentPage: number; lastPage: number; perPage: number; total: number }

async function fetchList(url: string): Promise<{ data?: Record<string, unknown>; error?: string }> {
  try {
    const response = await apiFetch(url)
    if (!response.ok) {
      return { error: await apiErrorMessage(response, "Unable to load data.") }
    }
    return { data: await response.json() }
  } catch {
    return { error: "Unable to reach the server." }
  }
}

/**
 * Paginated admin list bound to query params. Re-fetches when params change.
 */
export function useAdminList<T>(path: string, key: string, params: Record<string, string | number | undefined>) {
  const [items, setItems] = useState<T[]>([])
  const [meta, setMeta] = useState<PageMeta | null>(null)
  const [extra, setExtra] = useState<Record<string, unknown>>({})
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(true)
  const query = new URLSearchParams(
    Object.entries(params).filter(([, value]) => value !== undefined && value !== "").map(([name, value]) => [name, String(value)]),
  ).toString()

  const url = `${path}${query ? `?${query}` : ""}`

  const apply = useCallback((result: { data?: Record<string, unknown>; error?: string }) => {
    if (result.data) {
      setItems(result.data[key] as T[])
      setMeta((result.data.meta as PageMeta) ?? null)
      setExtra(result.data)
      setError(null)
    } else {
      setError(result.error ?? "Unable to load data.")
    }
    setLoading(false)
  }, [key])

  const reload = useCallback(async () => {
    setLoading(true)
    apply(await fetchList(url))
  }, [url, apply])

  useEffect(() => {
    let active = true
    fetchList(url).then((result) => {
      if (active) apply(result)
    })
    return () => {
      active = false
    }
  }, [url, apply])

  return { items, setItems, meta, extra, error, loading, reload }
}

/**
 * JSON mutation helper returning either the parsed body or a user-facing error.
 */
export async function adminRequest(path: string, method: "POST" | "PATCH" | "DELETE", body?: object) {
  try {
    const response = await apiFetch(path, { method, body: body ? JSON.stringify(body) : undefined })
    if (!response.ok) {
      return { ok: false as const, error: await apiErrorMessage(response, "The change could not be saved.") }
    }
    return { ok: true as const, data: await response.json() }
  } catch {
    return { ok: false as const, error: "Unable to reach the server." }
  }
}

export function Pagination({ meta, onPage }: { meta: PageMeta | null; onPage: (page: number) => void }) {
  if (!meta || meta.lastPage <= 1) {
    return meta ? <p className="mt-3 text-right text-xs text-muted-foreground">{meta.total} total</p> : null
  }

  return (
    <div className="mt-4 flex items-center justify-end gap-2 text-sm">
      <span className="mr-auto text-xs text-muted-foreground">{meta.total} total</span>
      <button type="button" disabled={meta.currentPage <= 1} onClick={() => onPage(meta.currentPage - 1)} className="rounded-lg border border-border px-3 py-1 disabled:opacity-50">Previous</button>
      <span className="text-muted-foreground">Page {meta.currentPage} of {meta.lastPage}</span>
      <button type="button" disabled={meta.currentPage >= meta.lastPage} onClick={() => onPage(meta.currentPage + 1)} className="rounded-lg border border-border px-3 py-1 disabled:opacity-50">Next</button>
    </div>
  )
}

export function StatTile({ label, value, hint }: { label: string; value: React.ReactNode; hint?: React.ReactNode }) {
  return (
    <div className="rounded-2xl border border-border bg-card p-5">
      <p className="text-sm text-muted-foreground">{label}</p>
      <p className="mt-3 text-3xl font-semibold tabular-nums">{value}</p>
      {hint && <p className="mt-1 text-xs text-muted-foreground">{hint}</p>}
    </div>
  )
}

export function Panel({ title, action, children }: { title: string; action?: React.ReactNode; children: React.ReactNode }) {
  return (
    <section className="rounded-2xl border border-border bg-card p-5">
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h3 className="text-lg font-semibold">{title}</h3>
        {action}
      </div>
      {children}
    </section>
  )
}

export function ErrorNote({ message }: { message: string | null }) {
  return message ? <p role="alert" className="mb-4 rounded-xl border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">{message}</p> : null
}

export function Badge({ children, tone = "neutral" }: { children: React.ReactNode; tone?: "neutral" | "good" | "bad" | "warn" }) {
  const styles = {
    neutral: "border-border bg-muted text-muted-foreground",
    good: "border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400",
    bad: "border-destructive/30 bg-destructive/10 text-destructive",
    warn: "border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-400",
  }[tone]

  return <span className={`inline-flex rounded-full border px-2 py-0.5 text-xs font-medium ${styles}`}>{children}</span>
}

export const statusTone = (status: string): "neutral" | "good" | "bad" | "warn" =>
  ["active", "succeeded", "approved", "handled"].includes(status) ? "good"
    : ["failed", "expired", "refunded", "rejected"].includes(status) ? "bad"
      : ["trialing", "pending", "new"].includes(status) ? "warn" : "neutral"

export const inputClass = "rounded-lg border border-border bg-background px-3 py-2 text-sm"
export const primaryButton = "rounded-lg bg-primary px-3 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
export const ghostButton = "rounded-lg border border-border px-3 py-2 text-sm hover:bg-muted disabled:opacity-60"
export const formatMoney = (value: number, currency = "DZD") => `${value.toLocaleString("en-US")} ${currency}`
export const formatDate = (value: string | null | undefined) => (value ? new Date(value).toLocaleDateString() : "—")
export const formatDateTime = (value: string | null | undefined) => (value ? new Date(value).toLocaleString() : "—")

/**
 * Single-series bar chart: one hue, thin bars rounded at the data end, 2px gaps,
 * per-bar hover tooltip, and a table view for screen readers / exact values.
 */
export function BarChart({ title, data, format = (value) => value.toLocaleString("en-US") }: {
  title: string
  data: { label: string; value: number }[]
  format?: (value: number) => string
}) {
  const [hovered, setHovered] = useState<number | null>(null)
  const max = Math.max(1, ...data.map((point) => point.value))

  return (
    <figure className="grid gap-3">
      <figcaption className="text-sm font-medium">{title}</figcaption>
      <div className="relative">
        <div className="flex h-40 items-end gap-[2px] border-b border-border/70" role="img" aria-label={`${title} bar chart`}>
          {data.map((point, index) => (
            <div
              key={point.label}
              className="group relative flex h-full flex-1 items-end"
              onMouseEnter={() => setHovered(index)}
              onMouseLeave={() => setHovered(null)}
            >
              <div
                className={`w-full rounded-t-[4px] bg-primary transition-opacity ${hovered !== null && hovered !== index ? "opacity-50" : ""}`}
                style={{ height: point.value === 0 ? 0 : `max(2px, ${(point.value / max) * 100}%)` }}
              />
              {hovered === index && (
                <div className="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg border border-border bg-popover px-2 py-1 text-xs text-popover-foreground shadow">
                  <span className="text-muted-foreground">{point.label}</span> · <span className="font-medium tabular-nums">{format(point.value)}</span>
                </div>
              )}
            </div>
          ))}
        </div>
        <div className="mt-1 flex justify-between text-[11px] text-muted-foreground">
          <span>{data[0]?.label}</span>
          <span>max {format(max)}</span>
          <span>{data[data.length - 1]?.label}</span>
        </div>
      </div>
      <details className="text-xs text-muted-foreground">
        <summary className="cursor-pointer">Show table</summary>
        <table className="mt-2 w-full text-left">
          <tbody>
            {data.map((point) => (
              <tr key={point.label} className="border-b border-border/50">
                <td className="py-1">{point.label}</td>
                <td className="py-1 text-right tabular-nums text-foreground">{format(point.value)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </details>
    </figure>
  )
}
