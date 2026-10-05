"use client"

import Link from "next/link"
import { AccountMenu } from "./account-menu"
import { ThemeToggle } from "./theme-toggle"

export function PageShell({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <main className="min-h-screen bg-background text-foreground">
      <header className="border-b border-border bg-background/80 backdrop-blur-sm">
        <div className="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-4 md:px-6">
          <Link href="/" className="flex items-center gap-2.5">
            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-sm font-bold text-primary-foreground">N</span>
            <span className="font-semibold">NexusVPN</span>
          </Link>
          <div className="flex items-center gap-3">
            <ThemeToggle />
            <AccountMenu />
          </div>
        </div>
      </header>
      <div className="mx-auto max-w-5xl px-4 py-8 md:px-6 md:py-12">
        <h1 className="mb-6 text-2xl font-semibold tracking-tight md:text-3xl">{title}</h1>
        {children}
      </div>
    </main>
  )
}

export function Card({ title, children, action }: { title: string; children: React.ReactNode; action?: React.ReactNode }) {
  return (
    <section className="rounded-2xl border border-border bg-card p-5">
      <div className="mb-4 flex items-center justify-between gap-3">
        <h2 className="text-lg font-semibold">{title}</h2>
        {action}
      </div>
      {children}
    </section>
  )
}

export function Notice({ tone = "info", children }: { tone?: "info" | "error" | "success" | "warning"; children: React.ReactNode }) {
  const styles = {
    info: "border-border bg-muted/50 text-foreground",
    error: "border-destructive/30 bg-destructive/10 text-destructive",
    success: "border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400",
    warning: "border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-400",
  }[tone]

  return <div role={tone === "error" ? "alert" : "status"} className={`rounded-xl border p-3 text-sm ${styles}`}>{children}</div>
}

export const formatDate = (value: string | null | undefined) => (value ? new Date(value).toLocaleDateString() : "—")
export const formatDateTime = (value: string | null | undefined) => (value ? new Date(value).toLocaleString() : "—")
export const formatAmount = (amount: number, currency = "DZD") => `${amount.toLocaleString("en-US")} ${currency}`
