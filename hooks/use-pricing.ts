"use client"

import { useCallback, useEffect, useState } from "react"
import { apiFetch } from "@/lib/api"
import type { PricingPlan } from "@/lib/pricing"

type PricingState =
  | { status: "loading"; plans: PricingPlan[] }
  | { status: "ready"; plans: PricingPlan[] }
  | { status: "error"; plans: PricingPlan[] }

// Shared across components on the page so the plan list is requested once.
let cached: PricingPlan[] | null = null
let inflight: Promise<PricingPlan[]> | null = null

function loadPlans(force = false) {
  if (cached && !force) return Promise.resolve(cached)

  if (!inflight || force) {
    inflight = apiFetch("/pricing")
      .then(async (response) => {
        if (!response.ok) throw new Error("Failed to load pricing")
        const data = await response.json()
        cached = data.plans as PricingPlan[]
        return cached
      })
      .finally(() => {
        inflight = null
      })
  }

  return inflight
}

/**
 * Live plans from the API. There is deliberately no hard-coded fallback: showing a price
 * that differs from what checkout charges is worse than showing an error with a retry.
 */
export function usePricing() {
  const [state, setState] = useState<PricingState>(
    cached ? { status: "ready", plans: cached } : { status: "loading", plans: [] },
  )

  const load = useCallback((force = false) => {
    setState((current) => ({ status: "loading", plans: current.plans }))
    loadPlans(force)
      .then((plans) => setState({ status: "ready", plans }))
      .catch(() => setState({ status: "error", plans: [] }))
  }, [])

  useEffect(() => {
    if (cached) return
    let active = true
    loadPlans()
      .then((plans) => active && setState({ status: "ready", plans }))
      .catch(() => active && setState({ status: "error", plans: [] }))
    return () => {
      active = false
    }
  }, [])

  return { ...state, retry: () => load(true) }
}
