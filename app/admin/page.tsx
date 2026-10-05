"use client"

import Link from "next/link"
import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"
import {
  CreditCard,
  FileText,
  LayoutDashboard,
  MessageSquare,
  PanelLeft,
  Receipt,
  ShieldCheck,
  TrendingUp,
  Users,
  WalletCards,
} from "lucide-react"

import { useAuth } from "@/components/auth-provider"
import { AccountsSection } from "@/components/admin/accounts"
import { PaymentsSection, SubscriptionsSection } from "@/components/admin/billing"
import { AuditLogSection, TestimonialsModeration } from "@/components/admin/moderation"
import { OffersSection } from "@/components/admin/offers"
import { AnalyticsSection, OverviewSection } from "@/components/admin/stats"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarHeader,
  SidebarInset,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarProvider,
  SidebarTrigger,
} from "@/components/ui/sidebar"

type AdminSection = "overview" | "accounts" | "offers" | "subscriptions" | "payments" | "analytics" | "testimonials" | "logs"

const navItems: { id: AdminSection; label: string; icon: typeof LayoutDashboard }[] = [
  { id: "overview", label: "Overview", icon: LayoutDashboard },
  { id: "accounts", label: "Accounts", icon: Users },
  { id: "offers", label: "Offers", icon: CreditCard },
  { id: "subscriptions", label: "Subscriptions", icon: WalletCards },
  { id: "payments", label: "Payments", icon: Receipt },
  { id: "analytics", label: "Analytics", icon: TrendingUp },
  { id: "testimonials", label: "Comments", icon: MessageSquare },
  { id: "logs", label: "Audit log", icon: FileText },
]

export default function AdminPage() {
  const router = useRouter()
  const { user, status, refresh } = useAuth()
  const [activeSection, setActiveSection] = useState<AdminSection>("overview")

  // proxy.ts already blocks non-admins server-side; this covers client-side session changes.
  useEffect(() => {
    if (status === "guest" || (status === "authenticated" && user?.role !== "admin")) {
      router.replace("/")
    }
  }, [status, user?.role, router])

  if (status === "error") {
    return (
      <main className="min-h-screen bg-background p-8 text-foreground">
        <p className="text-sm text-destructive">The server could not be reached to check your session.</p>
        <button type="button" onClick={refresh} className="mt-3 rounded-lg border border-border px-3 py-2 text-sm">Try again</button>
      </main>
    )
  }

  if (status !== "authenticated" || user?.role !== "admin") {
    return <main className="min-h-screen bg-background p-8 text-sm text-muted-foreground">Checking admin access...</main>
  }

  const activeLabel = navItems.find((item) => item.id === activeSection)?.label

  return (
    <SidebarProvider defaultOpen>
      <div className="flex min-h-screen w-full bg-background text-foreground">
        <Sidebar className="border-r border-border bg-card/80 backdrop-blur-sm">
          <SidebarHeader className="border-b border-border px-3 py-4">
            <div className="flex items-center gap-3">
              <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-sm font-bold text-primary-foreground">N</div>
              <div className="min-w-0">
                <p className="truncate text-sm font-semibold">NexusVPN</p>
                <p className="text-xs text-muted-foreground">Admin control</p>
              </div>
            </div>
          </SidebarHeader>

          <SidebarContent className="p-2">
            <SidebarGroup>
              <SidebarMenu>
                {navItems.map(({ id, label, icon: Icon }) => (
                  <SidebarMenuItem key={id}>
                    <SidebarMenuButton isActive={activeSection === id} onClick={() => setActiveSection(id)} className="justify-start">
                      <Icon className="size-4" />
                      <span>{label}</span>
                    </SidebarMenuButton>
                  </SidebarMenuItem>
                ))}
              </SidebarMenu>
            </SidebarGroup>
          </SidebarContent>

          <SidebarFooter className="border-t border-border p-3">
            <Link href="/" className="flex items-center justify-center rounded-xl border border-border px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted hover:text-foreground">
              Back to site
            </Link>
          </SidebarFooter>
        </Sidebar>

        <SidebarInset className="flex-1 bg-background">
          <header className="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-border bg-background/80 px-4 py-3 backdrop-blur-sm md:px-8">
            <div className="flex items-center gap-3">
              <SidebarTrigger className="md:hidden">
                <PanelLeft className="size-4" />
              </SidebarTrigger>
              <div>
                <p className="text-[10px] font-medium uppercase tracking-[0.2em] text-muted-foreground">NexusVPN control room</p>
                <h1 className="text-2xl font-semibold tracking-tight">{activeLabel}</h1>
              </div>
            </div>

            <div className="flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1.5 text-xs text-muted-foreground">
              <ShieldCheck className="size-3.5 text-primary" />
              {user.name}
            </div>
          </header>

          <main className="flex-1 p-4 md:p-8">
            {activeSection === "overview" && <OverviewSection />}
            {activeSection === "accounts" && <AccountsSection currentUserId={user.id} />}
            {activeSection === "offers" && <OffersSection />}
            {activeSection === "subscriptions" && <SubscriptionsSection />}
            {activeSection === "payments" && <PaymentsSection />}
            {activeSection === "analytics" && <AnalyticsSection />}
            {activeSection === "testimonials" && <TestimonialsModeration />}
            {activeSection === "logs" && <AuditLogSection />}
          </main>
        </SidebarInset>
      </div>
    </SidebarProvider>
  )
}
