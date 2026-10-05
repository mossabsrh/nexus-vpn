"use client"

import { useState } from "react"
import { Badge, ErrorNote, Pagination, adminRequest, formatDateTime, ghostButton, inputClass, statusTone, useAdminList } from "./shared"

type AdminTestimonial = {
  id: number
  content: string
  status: "pending" | "approved" | "rejected"
  name: string | null
  email: string | null
  createdAt: string | null
}

type AuditEntry = {
  id: number
  action: string
  subjectType: string | null
  subjectId: number | null
  metadata: Record<string, unknown> | null
  actor: { id: number; name: string; email: string } | null
  ipAddress: string | null
  createdAt: string | null
}

export function TestimonialsModeration() {
  const [status, setStatus] = useState("pending")
  const [page, setPage] = useState(1)
  const [actionError, setActionError] = useState<string | null>(null)
  const list = useAdminList<AdminTestimonial>("/admin/testimonials", "testimonials", { status, page })

  const act = async (testimonial: AdminTestimonial, action: "approved" | "rejected" | "delete") => {
    setActionError(null)
    const result = action === "delete"
      ? await adminRequest(`/admin/testimonials/${testimonial.id}`, "DELETE")
      : await adminRequest(`/admin/testimonials/${testimonial.id}`, "PATCH", { status: action })
    if (!result.ok) {
      setActionError(result.error)
      return
    }
    list.reload()
  }

  return (
    <section className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <p className="text-sm text-muted-foreground">
          New comments stay hidden until approved.{list.extra.pendingCount ? ` ${list.extra.pendingCount} waiting.` : ""}
        </p>
        <select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1) }} className={inputClass} aria-label="Filter comments">
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="">All</option>
        </select>
      </div>
      <ErrorNote message={list.error ?? actionError} />
      <div className="grid gap-3">
        {list.items.map((testimonial) => (
          <article key={testimonial.id} className="rounded-2xl border border-border bg-card p-4">
            <div className="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm">
              <span className="font-medium">{testimonial.name} <span className="text-muted-foreground">{testimonial.email}</span></span>
              <span className="flex items-center gap-2 text-xs text-muted-foreground">{formatDateTime(testimonial.createdAt)} <Badge tone={statusTone(testimonial.status)}>{testimonial.status}</Badge></span>
            </div>
            <p className="mb-3 whitespace-pre-line text-sm">{testimonial.content}</p>
            <div className="flex flex-wrap gap-2">
              {testimonial.status !== "approved" && <button type="button" onClick={() => act(testimonial, "approved")} className={ghostButton}>Approve</button>}
              {testimonial.status !== "rejected" && <button type="button" onClick={() => act(testimonial, "rejected")} className={ghostButton}>Reject</button>}
              <button type="button" onClick={() => act(testimonial, "delete")} className="rounded-lg border border-destructive/30 px-3 py-2 text-sm text-destructive hover:bg-destructive/10">Delete</button>
            </div>
          </article>
        ))}
        {!list.loading && list.items.length === 0 && <p className="text-sm text-muted-foreground">Nothing here.</p>}
      </div>
      <Pagination meta={list.meta} onPage={setPage} />
    </section>
  )
}

const actionGroups = [
  { value: "", label: "All events" },
  { value: "admin.", label: "Admin actions" },
  { value: "payment.", label: "Payments" },
  { value: "subscription.", label: "Subscriptions" },
  { value: "auth.", label: "Sign-ins" },
  { value: "account.", label: "Account changes" },
  { value: "vpn.", label: "VPN access" },
  { value: "team.", label: "Teams" },
]

export function AuditLogSection() {
  const [action, setAction] = useState("")
  const [page, setPage] = useState(1)
  const list = useAdminList<AuditEntry>("/admin/audit-logs", "logs", { action, page })

  return (
    <section className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <p className="text-sm text-muted-foreground">Recorded security and billing events.</p>
        <select value={action} onChange={(event) => { setAction(event.target.value); setPage(1) }} className={inputClass} aria-label="Filter events">
          {actionGroups.map((group) => <option key={group.value} value={group.value}>{group.label}</option>)}
        </select>
      </div>
      <ErrorNote message={list.error} />
      <div className="overflow-x-auto rounded-2xl border border-border bg-card">
        <table className="w-full min-w-[760px] text-left text-sm">
          <thead className="border-b border-border text-muted-foreground">
            <tr><th className="px-4 py-3 font-medium">When</th><th className="px-4 py-3 font-medium">Event</th><th className="px-4 py-3 font-medium">By</th><th className="px-4 py-3 font-medium">Details</th></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {list.items.map((entry) => (
              <tr key={entry.id} className="align-top">
                <td className="whitespace-nowrap px-4 py-3 text-xs text-muted-foreground">{formatDateTime(entry.createdAt)}</td>
                <td className="px-4 py-3">
                  <code className="text-xs">{entry.action}</code>
                  {entry.subjectType && <p className="text-xs text-muted-foreground">{entry.subjectType} #{entry.subjectId}</p>}
                </td>
                <td className="px-4 py-3 text-xs">{entry.actor?.email ?? "system"}{entry.ipAddress && <p className="text-muted-foreground">{entry.ipAddress}</p>}</td>
                <td className="px-4 py-3 text-xs text-muted-foreground">
                  {entry.metadata ? Object.entries(entry.metadata).map(([key, value]) => (
                    <span key={key} className="mr-3 inline-block">{key}: {typeof value === "object" ? JSON.stringify(value) : String(value)}</span>
                  )) : "—"}
                </td>
              </tr>
            ))}
            {!list.loading && list.items.length === 0 && <tr><td colSpan={4} className="px-4 py-6 text-muted-foreground">No events recorded yet.</td></tr>}
          </tbody>
        </table>
      </div>
      <Pagination meta={list.meta} onPage={setPage} />
    </section>
  )
}
