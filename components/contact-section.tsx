"use client"

import { useState } from "react"
import { motion } from "framer-motion"

import { apiErrorMessage, apiFetch } from "@/lib/api"
import { useAuth } from "./auth-provider"
import { Input } from "./ui/input"
import { Label } from "./ui/label"
import { Textarea } from "./ui/textarea"

export function ContactSection() {
  const { user } = useAuth()
  const [form, setForm] = useState({ name: "", email: "", company: "", seats: "", message: "" })
  const [status, setStatus] = useState<"idle" | "sending" | "sent" | "error">("idle")
  const [feedback, setFeedback] = useState<string | null>(null)

  const [prefilledFor, setPrefilledFor] = useState<number | null>(null)
  if (user && prefilledFor !== user.id) {
    setPrefilledFor(user.id)
    setForm((current) => ({ ...current, name: current.name || user.name, email: current.email || user.email }))
  }

  const update = (field: keyof typeof form) => (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) =>
    setForm((current) => ({ ...current, [field]: event.target.value }))

  const submit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setStatus("sending")
    setFeedback(null)

    try {
      const response = await apiFetch("/contact", {
        method: "POST",
        body: JSON.stringify({
          name: form.name,
          email: form.email,
          company: form.company || null,
          seats: form.seats ? Number(form.seats) : null,
          message: form.message,
        }),
      })

      if (!response.ok) {
        setStatus("error")
        setFeedback(response.status === 429
          ? "You have sent several requests already. Please wait a little before trying again."
          : await apiErrorMessage(response, "Your message could not be sent."))
        return
      }

      setStatus("sent")
      setFeedback((await response.json()).message)
      setForm({ name: "", email: "", company: "", seats: "", message: "" })
    } catch {
      setStatus("error")
      setFeedback("Your message could not be sent. Please check your connection.")
    }
  }

  return (
    <section id="contact" className="relative py-20 md:py-28">
      <div className="container mx-auto max-w-3xl px-4 md:px-6">
        <motion.div
          initial={{ opacity: 0, y: 24 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6, ease: [0.22, 1, 0.36, 1] }}
          className="mb-8"
        >
          <div className="mb-5 inline-flex items-center gap-2 rounded-full border border-border bg-muted/40 px-3 py-1.5">
            <span className="text-xs font-medium uppercase tracking-wider text-primary">Teams</span>
          </div>
          <h2 className="text-3xl font-bold tracking-tight md:text-4xl">Talk to us about the Team plan</h2>
          <p className="mt-3 text-muted-foreground">
            Team pricing depends on the number of seats. Tell us what you need and we will set up your team account.
          </p>
        </motion.div>

        <form onSubmit={submit} className="glass-card grid gap-4 rounded-2xl border border-border p-6">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="contact-name">Name</Label>
              <Input id="contact-name" required value={form.name} placeholder="Your name" onChange={update("name")} />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="contact-email">Work email</Label>
              <Input id="contact-email" type="email" required value={form.email} placeholder="you@company.com" onChange={update("email")} />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="contact-company">Company (optional)</Label>
              <Input id="contact-company" value={form.company} onChange={update("company")} />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="contact-seats">Seats needed (optional)</Label>
              <Input id="contact-seats" type="number" min={1} value={form.seats} onChange={update("seats")} />
            </div>
          </div>
          <div className="grid gap-2">
            <Label htmlFor="contact-message">Message</Label>
            <Textarea id="contact-message" required minLength={10} maxLength={2000} value={form.message} onChange={update("message")} className="min-h-28" />
          </div>
          {feedback && (
            <p role="status" className={status === "error" ? "text-sm text-destructive" : "text-sm text-emerald-600"}>
              {feedback}
            </p>
          )}
          <div>
            <button
              type="submit"
              disabled={status === "sending"}
              className="rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 disabled:opacity-60"
            >
              {status === "sending" ? "Sending..." : "Send message"}
            </button>
          </div>
        </form>
      </div>
    </section>
  )
}
