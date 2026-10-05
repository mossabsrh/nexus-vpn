"use client"

import { useState } from "react"
import { Badge, ErrorNote, Pagination, adminRequest, ghostButton, inputClass, primaryButton, useAdminList } from "./shared"

type AdminPlan = {
  id?: number
  slug: string
  name: string
  description: string | null
  monthly_price: number | null
  yearly_price: number | null
  yearly_total: number | null
  currency: string
  features: string[] | null
  popular: boolean
  cta: string | null
  is_active: boolean
  active_subscriptions_count?: number
}

const emptyPlan: AdminPlan = {
  slug: "",
  name: "",
  description: "",
  monthly_price: null,
  yearly_price: null,
  yearly_total: null,
  currency: "DZD",
  features: [],
  popular: false,
  cta: "Start trial",
  is_active: true,
}

const numberOrNull = (value: string) => (value === "" ? null : Number(value))

export function OffersSection() {
  const [page, setPage] = useState(1)
  const [editing, setEditing] = useState<AdminPlan>(emptyPlan)
  const [notice, setNotice] = useState<string | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)
  const [confirmDelete, setConfirmDelete] = useState<AdminPlan | null>(null)
  const list = useAdminList<AdminPlan>("/admin/plans", "plans", { page })

  const save = async () => {
    setActionError(null)
    setNotice(null)
    const { id, active_subscriptions_count: _count, ...payload } = editing
    void _count
    const result = await adminRequest(id ? `/admin/plans/${id}` : "/admin/plans", id ? "PATCH" : "POST", {
      ...payload,
      features: payload.features ?? [],
    })
    if (!result.ok) {
      setActionError(result.error)
      return
    }
    setEditing(emptyPlan)
    setNotice("Offer saved. The public pricing page is updated.")
    list.reload()
  }

  const toggleActive = async (plan: AdminPlan) => {
    const { id, active_subscriptions_count: _count, ...payload } = plan
    void _count
    const result = await adminRequest(`/admin/plans/${id}`, "PATCH", { ...payload, features: payload.features ?? [], is_active: !plan.is_active })
    if (!result.ok) {
      setActionError(result.error)
      return
    }
    setNotice(plan.is_active ? `${plan.name} is hidden from new customers.` : `${plan.name} is available again.`)
    list.reload()
  }

  const remove = async (plan: AdminPlan) => {
    setConfirmDelete(null)
    const result = await adminRequest(`/admin/plans/${plan.id}`, "DELETE")
    if (!result.ok) {
      setActionError(result.error)
      return
    }
    setNotice(`${plan.name} deleted.`)
    list.reload()
  }

  const field = (key: keyof AdminPlan, label: string, type: "text" | "number" = "text") => (
    <label className="grid gap-1 text-xs text-muted-foreground">
      {label}
      <input
        type={type}
        min={type === "number" ? 0 : undefined}
        value={(editing[key] as string | number | null) ?? ""}
        onChange={(event) => setEditing({ ...editing, [key]: type === "number" ? numberOrNull(event.target.value) : event.target.value })}
        className={`${inputClass} text-foreground`}
      />
    </label>
  )

  return (
    <section className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <p className="text-sm text-muted-foreground">Edit what appears on the public pricing page. Plans with customers can be deactivated but not deleted.</p>
        <button type="button" onClick={() => setEditing(emptyPlan)} className={primaryButton}>New offer</button>
      </div>

      <ErrorNote message={list.error ?? actionError} />
      {notice && <p className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-sm text-emerald-700 dark:text-emerald-400">{notice}</p>}

      {confirmDelete && (
        <div className="rounded-xl border border-destructive/30 bg-destructive/10 p-3 text-sm">
          <p>Delete {confirmDelete.name}? This only works for plans that never had a subscription.</p>
          <div className="mt-2 flex gap-2">
            <button type="button" className="rounded-lg bg-destructive px-3 py-2 text-sm font-medium text-white" onClick={() => remove(confirmDelete)}>Delete</button>
            <button type="button" className={ghostButton} onClick={() => setConfirmDelete(null)}>Cancel</button>
          </div>
        </div>
      )}

      <div className="grid gap-3">
        {list.items.map((plan) => (
          <div key={plan.id} className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-card p-4">
            <div>
              <p className="font-medium">
                {plan.name} {plan.popular && <Badge>featured</Badge>} {!plan.is_active && <Badge tone="bad">inactive</Badge>}
              </p>
              <p className="text-sm text-muted-foreground">
                {plan.slug} · {plan.monthly_price === null ? "sales-assisted" : `${plan.monthly_price} ${plan.currency}/mo · ${plan.yearly_total ?? "—"} ${plan.currency}/yr`}
                {" · "}{plan.active_subscriptions_count ?? 0} current subscriber(s)
              </p>
            </div>
            <div className="flex flex-wrap gap-2">
              <button type="button" onClick={() => setEditing({ ...plan, features: plan.features ?? [] })} className={ghostButton}>Edit</button>
              <button type="button" onClick={() => toggleActive(plan)} className={ghostButton}>{plan.is_active ? "Deactivate" : "Activate"}</button>
              <button type="button" onClick={() => setConfirmDelete(plan)} className="rounded-lg border border-destructive/30 px-3 py-2 text-sm text-destructive hover:bg-destructive/10">Delete</button>
            </div>
          </div>
        ))}
      </div>
      <Pagination meta={list.meta} onPage={setPage} />

      <div className="grid gap-3 rounded-2xl border border-border bg-muted/30 p-5">
        <h3 className="font-medium">{editing.id ? `Edit ${editing.name}` : "Create offer"}</h3>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {field("slug", "Slug (lowercase, dashes)")}
          {field("name", "Name")}
          {field("cta", "Button label")}
          {field("monthly_price", "Monthly price (empty = contact sales)", "number")}
          {field("yearly_price", "Yearly price shown per month", "number")}
          {field("yearly_total", "Yearly total charged", "number")}
        </div>
        <label className="grid gap-1 text-xs text-muted-foreground">
          Description
          <textarea value={editing.description ?? ""} onChange={(event) => setEditing({ ...editing, description: event.target.value })} className={`${inputClass} min-h-20 text-foreground`} />
        </label>
        <label className="grid gap-1 text-xs text-muted-foreground">
          Features (comma separated)
          <input
            value={(editing.features ?? []).join(", ")}
            onChange={(event) => setEditing({ ...editing, features: event.target.value.split(",").map((feature) => feature.trim()).filter(Boolean) })}
            className={`${inputClass} text-foreground`}
          />
        </label>
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="flex gap-4">
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={editing.popular} onChange={(event) => setEditing({ ...editing, popular: event.target.checked })} />
              Featured
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={editing.is_active} onChange={(event) => setEditing({ ...editing, is_active: event.target.checked })} />
              Available to new customers
            </label>
          </div>
          <div className="flex gap-2">
            {editing.id && <button type="button" onClick={() => setEditing(emptyPlan)} className={ghostButton}>Cancel</button>}
            <button type="button" onClick={save} className={primaryButton}>Save offer</button>
          </div>
        </div>
      </div>
    </section>
  )
}
