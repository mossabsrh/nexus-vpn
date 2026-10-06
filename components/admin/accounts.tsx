"use client"

import { useEffect, useState } from "react"
import { Badge, ErrorNote, Pagination, adminRequest, formatDate, ghostButton, inputClass, primaryButton, statusTone, useAdminList } from "./shared"

type AdminUser = {
  id: number
  name: string
  email: string
  role: "user" | "admin"
  emailVerified: boolean
  created_at: string
  subscription: { status: string; planId: string | null; currentPeriodEnd: string | null } | null
}

export function AccountsSection({ currentUserId }: { currentUserId?: number }) {
  const [search, setSearch] = useState("")
  const [query, setQuery] = useState("")
  const [role, setRole] = useState("")
  const [subscription, setSubscription] = useState("")
  const [page, setPage] = useState(1)
  const [notice, setNotice] = useState<string | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)
  const [pendingRole, setPendingRole] = useState<{ user: AdminUser; role: AdminUser["role"] } | null>(null)
  const [pendingDelete, setPendingDelete] = useState<AdminUser | null>(null)
  const [deleting, setDeleting] = useState(false)
  const list = useAdminList<AdminUser>("/admin/users", "users", { q: query, role, subscription, page, per_page: 20 })

  // Debounce search so typing does not fire a request per keystroke.
  useEffect(() => {
    const timeout = window.setTimeout(() => {
      setQuery(search.trim())
      setPage(1)
    }, 300)
    return () => window.clearTimeout(timeout)
  }, [search])

  const save = async (user: AdminUser, changes: Partial<AdminUser>) => {
    setNotice(null)
    setActionError(null)
    const result = await adminRequest(`/admin/users/${user.id}`, "PATCH", changes)
    if (!result.ok) {
      setActionError(result.error)
      return false
    }
    list.setItems((items) => items.map((item) => (item.id === user.id ? { ...item, ...changes } : item)))
    setNotice(`${user.email} updated.`)
    return true
  }

  const confirmDelete = async () => {
    if (!pendingDelete) return
    setNotice(null)
    setActionError(null)
    setDeleting(true)
    const result = await adminRequest(`/admin/users/${pendingDelete.id}`, "DELETE")
    setDeleting(false)
    if (!result.ok) {
      setActionError(result.error)
      setPendingDelete(null)
      return
    }
    const email = pendingDelete.email
    setPendingDelete(null)
    await list.reload()
    setNotice(`${email} has been permanently deleted.`)
  }

  return (
    <section className="space-y-4">
      <div className="flex flex-wrap gap-2">
        <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search name or email" className={`${inputClass} min-w-56 flex-1`} aria-label="Search accounts" />
        <select value={role} onChange={(event) => { setRole(event.target.value); setPage(1) }} className={inputClass} aria-label="Filter by role">
          <option value="">All roles</option>
          <option value="user">Users</option>
          <option value="admin">Admins</option>
        </select>
        <select value={subscription} onChange={(event) => { setSubscription(event.target.value); setPage(1) }} className={inputClass} aria-label="Filter by subscription">
          <option value="">Any subscription</option>
          <option value="active">Paying</option>
          <option value="trialing">Trialing</option>
          <option value="none">No current subscription</option>
        </select>
      </div>

      <ErrorNote message={list.error ?? actionError} />
      {notice && <p className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-sm text-emerald-700 dark:text-emerald-400">{notice}</p>}

      {pendingRole && (
        <div className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-sm">
          <p>
            {pendingRole.role === "admin"
              ? `Give ${pendingRole.user.email} full admin access (billing, refunds, all accounts)?`
              : `Remove admin access from ${pendingRole.user.email}?`}
          </p>
          <div className="mt-2 flex gap-2">
            <button type="button" className={primaryButton} onClick={() => { save(pendingRole.user, { role: pendingRole.role }); setPendingRole(null) }}>Confirm</button>
            <button type="button" className={ghostButton} onClick={() => setPendingRole(null)}>Cancel</button>
          </div>
        </div>
      )}

      {pendingDelete && (
        <div className="rounded-xl border border-destructive/30 bg-destructive/10 p-3 text-sm">
          <p>
            Permanently delete <strong>{pendingDelete.email}</strong>? Their personal data is erased and VPN access
            stops. Payment history is kept (anonymised). This cannot be undone.
          </p>
          <div className="mt-2 flex gap-2">
            <button type="button" className={primaryButton} disabled={deleting} onClick={confirmDelete}>
              {deleting ? "Deleting..." : "Delete user"}
            </button>
            <button type="button" className={ghostButton} disabled={deleting} onClick={() => setPendingDelete(null)}>Cancel</button>
          </div>
        </div>
      )}

      <div className="overflow-x-auto rounded-2xl border border-border bg-card">
        <table className="w-full min-w-[760px] text-left text-sm">
          <thead className="border-b border-border text-muted-foreground">
            <tr>
              <th className="px-4 py-3 font-medium">Account</th>
              <th className="px-4 py-3 font-medium">Subscription</th>
              <th className="px-4 py-3 font-medium">Joined</th>
              <th className="px-4 py-3 font-medium">Role</th>
              <th className="px-4 py-3" />
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {list.loading && list.items.length === 0 ? (
              <tr><td colSpan={5} className="px-4 py-6 text-muted-foreground">Loading accounts...</td></tr>
            ) : list.items.length === 0 ? (
              <tr><td colSpan={5} className="px-4 py-6 text-muted-foreground">No accounts match these filters.</td></tr>
            ) : list.items.map((account) => (
              <AccountRow
                key={account.id}
                account={account}
                isSelf={account.id === currentUserId}
                onRename={(name) => save(account, { name })}
                onRoleChange={(nextRole) => setPendingRole({ user: account, role: nextRole })}
                onDelete={() => { setPendingRole(null); setPendingDelete(account) }}
              />
            ))}
          </tbody>
        </table>
      </div>
      <Pagination meta={list.meta} onPage={setPage} />
    </section>
  )
}

function AccountRow({ account, isSelf, onRename, onRoleChange, onDelete }: {
  account: AdminUser
  isSelf: boolean
  onRename: (name: string) => Promise<boolean>
  onRoleChange: (role: AdminUser["role"]) => void
  onDelete: () => void
}) {
  const [name, setName] = useState(account.name)
  const [saving, setSaving] = useState(false)
  const isDirty = name !== account.name && name.trim() !== "" && name.trim() !== account.name

  const rename = async () => {
    setSaving(true)
    const saved = await onRename(name.trim())
    setSaving(false)
    // Show exactly what was stored (trimmed); on failure keep the edit so it can be retried.
    if (saved) setName(name.trim())
  }

  return (
    <tr>
      <td className="px-4 py-3">
        <input value={name} onChange={(event) => setName(event.target.value)} className={`${inputClass} w-full max-w-60 py-1`} aria-label={`Name of ${account.email}`} />
        <p className="mt-1 text-xs text-muted-foreground">
          {account.email} {account.emailVerified ? "" : <Badge tone="warn">unverified</Badge>}
        </p>
      </td>
      <td className="px-4 py-3">
        {account.subscription ? (
          <div className="grid gap-1">
            <Badge tone={statusTone(account.subscription.status)}>{account.subscription.status}</Badge>
            <span className="text-xs text-muted-foreground">{account.subscription.planId} · until {formatDate(account.subscription.currentPeriodEnd)}</span>
          </div>
        ) : <span className="text-xs text-muted-foreground">None</span>}
      </td>
      <td className="px-4 py-3 text-muted-foreground">{formatDate(account.created_at)}</td>
      <td className="px-4 py-3">
        <select
          value={account.role}
          disabled={isSelf}
          title={isSelf ? "You cannot change your own role" : undefined}
          onChange={(event) => onRoleChange(event.target.value as AdminUser["role"])}
          className={`${inputClass} py-1`}
          aria-label={`Role of ${account.email}`}
        >
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>
      </td>
      <td className="px-4 py-3 text-right">
        <div className="flex justify-end gap-2">
          <button
            type="button"
            disabled={!isDirty || saving}
            onClick={rename}
            className={isDirty ? primaryButton : `${ghostButton} disabled:cursor-not-allowed`}
          >
            {saving ? "Saving..." : "Save name"}
          </button>
          <button
            type="button"
            disabled={isSelf}
            onClick={onDelete}
            title={isSelf ? "You cannot delete your own account here" : undefined}
            className="rounded-md border border-destructive/40 px-3 py-1 text-sm font-medium text-destructive hover:bg-destructive/10 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Delete
          </button>
        </div>
      </td>
    </tr>
  )
}
