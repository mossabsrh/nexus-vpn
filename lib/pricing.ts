export type PricingPlan = {
  slug: string
  name: string
  description: string
  monthlyPrice: number | null
  yearlyPrice: number | null
  yearlyTotal: number | null
  currency: string
  features: string[]
  popular: boolean
  cta: string
  /** false for sales-assisted plans (Team): no online trial or checkout. */
  selfServe: boolean
}

export function formatPrice(value: number | null, currency = 'DZD') {
  return value === null ? 'Custom pricing' : `${value.toLocaleString('en-US')} ${currency}`
}
