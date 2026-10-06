"use client"

import { createContext, useCallback, useContext, useEffect, useState } from "react"
import { apiErrorMessage, apiFetch } from "@/lib/api"

export type SubscriptionStatus = "trialing" | "active" | "past_due" | "canceled" | "expired"

export type SubscriptionSummary = {
  id: number
  planId: string | null
  planName: string | null
  status: SubscriptionStatus
  billingPeriod: "monthly" | "yearly"
  seats: number
  startedAt: string | null
  trialEndsAt: string | null
  currentPeriodEnd: string | null
  canceledAt: string | null
  grantsAccess: boolean
  createdAt: string | null
}

export type AuthUser = {
  id: number
  email: string
  name: string
  role: "user" | "admin"
  emailVerified: boolean
  planId?: string | null
  subscriptionStatus?: SubscriptionStatus | null
  lastPlanId?: string | null
  subscription?: SubscriptionSummary | null
  hasVpnAccess: boolean
  team?: { id: number; name: string; isOwner: boolean } | null
}

/** "loading" until /me answers; "error" when the session could not be checked (network/server). */
export type AuthStatus = "loading" | "authenticated" | "guest" | "error"

type Result = { success: boolean; error?: string }

type AuthContextType = {
  user: AuthUser | null
  status: AuthStatus
  isAuthenticated: boolean
  refresh: () => Promise<void>
  setUser: (user: AuthUser | null) => void
  login: (email: string, password: string) => Promise<Result>
  signup: (email: string, name: string, password: string, passwordConfirmation: string, planId: string) => Promise<Result>
  startTrial: (planSlug: string) => Promise<Result & { unauthenticated?: boolean }>
  cancelSubscription: () => Promise<Result & { message?: string }>
  updateEmail: (currentPassword: string, email: string) => Promise<Result>
  updatePassword: (currentPassword: string, password: string) => Promise<Result>
  deleteAccount: (currentPassword: string) => Promise<Result & { message?: string }>
  resendVerification: () => Promise<Result & { message?: string }>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

async function fetchSession(): Promise<{ user: AuthUser | null; status: AuthStatus }> {
  try {
    const response = await apiFetch("/me", {}, 8000)

    if (response.ok) {
      return { user: (await response.json()).user, status: "authenticated" }
    }

    return { user: null, status: response.status === 401 || response.status === 419 ? "guest" : "error" }
  } catch {
    return { user: null, status: "error" }
  }
}

const networkError = (err: unknown, fallback: string) =>
  err instanceof DOMException && err.name === "AbortError"
    ? "The server took too long to respond. Please try again."
    : err instanceof Error ? err.message : fallback

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null)
  const [status, setStatus] = useState<AuthStatus>("loading")

  const applySession = useCallback((session: { user: AuthUser | null; status: AuthStatus }) => {
    setUser(session.user)
    setStatus(session.status)
  }, [])

  const refresh = useCallback(async () => {
    applySession(await fetchSession())
  }, [applySession])

  useEffect(() => {
    let active = true
    fetchSession().then((session) => {
      if (active) applySession(session)
    })
    return () => {
      active = false
    }
  }, [applySession])

  const applyUser = (nextUser: AuthUser | null) => {
    setUser(nextUser)
    setStatus(nextUser ? "authenticated" : "guest")
  }

  const login = async (email: string, password: string) => {
    try {
      const response = await apiFetch("/auth/login", {
        method: "POST",
        body: JSON.stringify({ email, password }),
      })

      if (!response.ok) {
        return { success: false, error: await apiErrorMessage(response, "Invalid email or password.") }
      }

      applyUser((await response.json()).user)
      return { success: true }
    } catch (err) {
      return { success: false, error: networkError(err, "Login failed") }
    }
  }

  const signup = async (email: string, name: string, password: string, passwordConfirmation: string, planId: string) => {
    try {
      const response = await apiFetch("/auth/register", {
        method: "POST",
        body: JSON.stringify({
          name,
          email,
          password,
          password_confirmation: passwordConfirmation,
          plan_slug: planId,
        }),
      })

      if (!response.ok) {
        return { success: false, error: await apiErrorMessage(response, "Registration failed") }
      }

      applyUser((await response.json()).user)
      return { success: true }
    } catch (err) {
      return { success: false, error: networkError(err, "Registration failed") }
    }
  }

  const startTrial = async (planSlug: string) => {
    try {
      const response = await apiFetch("/subscriptions/trial", {
        method: "POST",
        body: JSON.stringify({ plan_slug: planSlug }),
      })

      const data = await response.json().catch(() => ({}))

      if (!response.ok) {
        if (data.user) {
          applyUser(data.user)
        }
        return {
          success: false,
          error: data.message || data.error || "Unable to start the trial.",
          unauthenticated: response.status === 401,
        }
      }

      applyUser(data.user)
      return { success: true }
    } catch (err) {
      return { success: false, error: networkError(err, "Unable to start the trial.") }
    }
  }

  const cancelSubscription = async () => {
    try {
      const response = await apiFetch("/subscriptions/cancel", { method: "POST" })

      if (!response.ok) {
        return { success: false, error: await apiErrorMessage(response, "Unable to cancel.") }
      }

      const data = await response.json()
      applyUser(data.user)
      return { success: true, message: data.message as string }
    } catch (err) {
      return { success: false, error: networkError(err, "Unable to cancel.") }
    }
  }

  const updateEmail = async (currentPassword: string, email: string) => {
    try {
      const response = await apiFetch("/account/email", {
        method: "PUT",
        body: JSON.stringify({ current_password: currentPassword, email }),
      })

      if (!response.ok) {
        return { success: false, error: await apiErrorMessage(response, "Failed to update email.") }
      }

      applyUser((await response.json()).user)
      return { success: true }
    } catch (err) {
      return { success: false, error: networkError(err, "Failed to update email.") }
    }
  }

  const updatePassword = async (currentPassword: string, password: string) => {
    try {
      const response = await apiFetch("/account/password", {
        method: "PUT",
        body: JSON.stringify({ current_password: currentPassword, password }),
      })

      if (!response.ok) {
        return { success: false, error: await apiErrorMessage(response, "Failed to update password.") }
      }

      return { success: true }
    } catch (err) {
      return { success: false, error: networkError(err, "Failed to update password.") }
    }
  }

  const deleteAccount = async (currentPassword: string) => {
    try {
      const response = await apiFetch("/account", {
        method: "DELETE",
        body: JSON.stringify({ current_password: currentPassword }),
      })

      if (!response.ok) {
        return { success: false, error: await apiErrorMessage(response, "Failed to delete your account.") }
      }

      const data = await response.json()
      // The account is gone server-side; clear the local session too.
      applyUser(null)
      return { success: true, message: data.message as string }
    } catch (err) {
      return { success: false, error: networkError(err, "Failed to delete your account.") }
    }
  }

  const resendVerification = async () => {
    try {
      const response = await apiFetch("/email/verification-notification", { method: "POST" })

      if (!response.ok) {
        return { success: false, error: await apiErrorMessage(response, "Unable to send the verification email.") }
      }

      return { success: true, message: (await response.json()).message as string }
    } catch (err) {
      return { success: false, error: networkError(err, "Unable to send the verification email.") }
    }
  }

  const logout = async () => {
    try {
      await apiFetch("/auth/logout", { method: "POST" })
    } finally {
      applyUser(null)
    }
  }

  const value: AuthContextType = {
    user,
    status,
    isAuthenticated: Boolean(user),
    refresh,
    setUser: applyUser,
    login,
    signup,
    startTrial,
    cancelSubscription,
    updateEmail,
    updatePassword,
    deleteAccount,
    resendVerification,
    logout,
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error("useAuth must be used within AuthProvider")
  }
  return context
}
