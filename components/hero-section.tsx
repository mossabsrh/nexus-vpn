"use client"

import { useEffect, useRef, useState } from "react"
import { motion } from "framer-motion"

const statusItems = [
  { label: "Tunnel", value: "WireGuard®", color: "text-emerald-400" },
  { label: "Cipher", value: "ChaCha20", color: "text-emerald-400" },
  { label: "Keys", value: "Curve25519", color: "text-emerald-400" },
  { label: "DNS", value: "1.1.1.1", color: "text-emerald-400" },
]

/**
 * Decorative preview of the client app. The numbers are illustrative, not live telemetry;
 * a single 1s tick drives them, and it stops while the card is off-screen, the tab is hidden,
 * or the user prefers reduced motion.
 */
function VPNDashboard() {
  const cardRef = useRef<HTMLDivElement>(null)
  const [sessionTime, setSessionTime] = useState(0)
  const [traffic, setTraffic] = useState({ dataIn: 0, dataOut: 0 })

  useEffect(() => {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return

    let visible = true
    let interval: number | undefined

    const tick = () => {
      setSessionTime((time) => time + 1)
      setTraffic((current) => ({
        dataIn: current.dataIn + Math.random() * 0.4 + 0.1,
        dataOut: current.dataOut + Math.random() * 0.15 + 0.02,
      }))
    }
    const sync = () => {
      const shouldRun = visible && document.visibilityState === "visible"
      if (shouldRun && interval === undefined) interval = window.setInterval(tick, 1000)
      if (!shouldRun && interval !== undefined) {
        window.clearInterval(interval)
        interval = undefined
      }
    }

    const observer = new IntersectionObserver(([entry]) => {
      visible = entry.isIntersecting
      sync()
    })
    if (cardRef.current) observer.observe(cardRef.current)
    document.addEventListener("visibilitychange", sync)
    sync()

    return () => {
      observer.disconnect()
      document.removeEventListener("visibilitychange", sync)
      if (interval !== undefined) window.clearInterval(interval)
    }
  }, [])

  const dataIn = traffic.dataIn.toFixed(1)
  const dataOut = traffic.dataOut.toFixed(2)
  const dots = [0, 1, 2].map((index) => (index === sessionTime % 3 ? "●" : "○"))

  const formatTime = (s: number) => {
    const h = Math.floor(s / 3600)
    const m = Math.floor((s % 3600) / 60)
    const sec = s % 60
    return `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}:${String(sec).padStart(2, "0")}`
  }

  return (
    <div ref={cardRef} className="relative animate-float" aria-hidden="true">
      {/* Main card */}
      <div className="glass-card rounded-2xl overflow-hidden glow-cyan">
        {/* Header bar */}
        <div className="flex items-center gap-2 px-4 py-3 border-b border-border/50">
          <div className="w-2.5 h-2.5 rounded-full bg-red-500/70" />
          <div className="w-2.5 h-2.5 rounded-full bg-yellow-500/70" />
          <div className="w-2.5 h-2.5 rounded-full bg-green-500/70" />
          <span className="ml-2 text-xs text-muted-foreground font-mono">nexus-vpn — connected</span>
        </div>

        <div className="p-5 space-y-4">
          {/* Connection status */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2.5">
              <div className="relative">
                <div className="w-2.5 h-2.5 bg-emerald-400 rounded-full" />
                <div className="absolute inset-0 w-2.5 h-2.5 bg-emerald-400 rounded-full animate-ping-slow opacity-60" />
              </div>
              <span className="text-sm font-medium text-emerald-400">Secured</span>
            </div>
            <span className="text-xs text-muted-foreground font-mono">{formatTime(sessionTime)}</span>
          </div>

          {/* Shield visual */}
          <div className="flex items-center justify-center py-4">
            <div className="relative">
              {/* Outer ring */}
              <div className="w-28 h-28 rounded-full border border-primary/20 flex items-center justify-center">
                {/* Inner ring */}
                <div className="w-20 h-20 rounded-full border border-primary/30 bg-primary/5 flex items-center justify-center">
                  <svg viewBox="0 0 48 48" fill="none" className="w-10 h-10">
                    <path
                      d="M24 4L8 12v12c0 10.77 6.88 20.85 16 23.5C33.12 44.85 40 34.77 40 24V12L24 4z"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                      className="text-primary"
                    />
                    <path
                      d="M17 24l5 5 9-10"
                      stroke="currentColor"
                      strokeWidth="2.5"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      className="text-primary"
                    />
                  </svg>
                </div>
              </div>
              {/* Orbiting dot */}
              <div className="absolute inset-0 flex items-center justify-center animate-orbit" style={{ animationDuration: "10s" }}>
                <div className="w-2 h-2 bg-primary rounded-full -translate-x-14 opacity-70" />
              </div>
              {/* Scan line */}
              <div className="absolute inset-0 rounded-full overflow-hidden opacity-30">
                <div className="w-full h-0.5 bg-gradient-to-r from-transparent via-primary to-transparent animate-scan" />
              </div>
            </div>
          </div>

          {/* Status grid */}
          <div className="grid grid-cols-2 gap-2">
            {statusItems.map((item) => (
              <div key={item.label} className="rounded-xl bg-muted/40 p-3 border border-border/50">
                <p className="text-xs text-muted-foreground mb-0.5">{item.label}</p>
                <p className={`text-xs font-mono font-medium ${item.color}`}>{item.value}</p>
              </div>
            ))}
          </div>

          {/* Traffic */}
          <div className="rounded-xl bg-muted/40 p-3 border border-border/50">
            <div className="flex justify-between text-xs text-muted-foreground mb-2">
              <span>Traffic this session</span>
              <span className="font-mono text-primary animate-data-blink">{dots.join(" ")}</span>
            </div>
            <div className="flex justify-between">
              <div>
                <p className="text-xs text-muted-foreground">↓ Received</p>
                <p className="text-sm font-mono text-foreground">{dataIn} MB</p>
              </div>
              <div className="text-right">
                <p className="text-xs text-muted-foreground">↑ Sent</p>
                <p className="text-sm font-mono text-foreground">{dataOut} MB</p>
              </div>
            </div>
          </div>

          {/* Tunnel row */}
          <div className="flex items-center justify-between rounded-xl bg-primary/8 border border-primary/15 px-3 py-2.5">
            <div className="flex items-center gap-2.5">
              <div className="w-6 h-6 rounded-md bg-primary/20 flex items-center justify-center text-xs">🔒</div>
              <div>
                <p className="text-xs font-medium text-foreground">Encrypted tunnel</p>
                <p className="text-xs text-muted-foreground">WireGuard® · ChaCha20</p>
              </div>
            </div>
            <span className="text-xs font-medium text-emerald-400">Active</span>
          </div>
        </div>
      </div>

      {/* Floating badge — encryption */}
      <motion.div
        className="absolute -top-3 -right-3 glass rounded-xl px-3 py-2 glow-cyan"
        animate={{ y: [0, -8, 0] }}
        transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
      >
        <div className="flex items-center gap-1.5">
          <div className="w-1.5 h-1.5 rounded-full bg-emerald-400" />
          <span className="text-xs font-mono text-foreground">ChaCha20</span>
        </div>
      </motion.div>

      {/* Floating badge — dns */}
      <motion.div
        className="absolute -bottom-3 -left-3 glass rounded-xl px-3 py-2"
        animate={{ y: [0, 8, 0] }}
        transition={{ duration: 5, repeat: Infinity, delay: 1, ease: "easeInOut" }}
      >
        <div className="flex items-center gap-1.5">
          <div className="w-1.5 h-1.5 rounded-full bg-primary" />
          <span className="text-xs font-mono text-foreground">Curve25519</span>
        </div>
      </motion.div>
    </div>
  )
}

const containerVariants = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.12 } },
}

const itemVariants = {
  hidden: { opacity: 0, y: 20 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.6, ease: [0.22, 1, 0.36, 1] as const } },
}

export function HeroSection() {
  return (
    <section className="relative min-h-screen flex items-center pt-24 pb-16 overflow-hidden">
      <div className="container mx-auto px-4 md:px-6 max-w-6xl relative z-10">
        <div className="grid lg:grid-cols-2 gap-16 items-center">
          {/* Left */}
          <motion.div
            variants={containerVariants}
            initial="hidden"
            animate="visible"
            className="text-center lg:text-left"
          >
            {/* Eyebrow */}
            <motion.div variants={itemVariants} className="mb-6 flex justify-center lg:justify-start">
              <div className="flex max-w-full flex-wrap items-center justify-center gap-2 rounded-full border border-border bg-muted/50 px-3 py-1.5">
                <div className="h-1.5 w-1.5 rounded-full bg-emerald-400" />
                <span className="text-[11px] font-medium uppercase tracking-[0.18em] text-muted-foreground">
                  WireGuard® · ChaCha20 · dinar billing
                </span>
              </div>
            </motion.div>

            {/* Headline */}
            <motion.h1
              variants={itemVariants}
              className="mb-5 text-4xl font-bold leading-[0.98] tracking-[-0.04em] text-foreground md:text-5xl lg:text-6xl xl:text-7xl"
            >
              <span className="block">Simple privacy.</span>
              <span className="text-gradient block">Fast connections.</span>
              <span className="block text-foreground/90">No compromise.</span>
            </motion.h1>

            {/* Subtext */}
            <motion.p
              variants={itemVariants}
              className="mx-auto mb-8 max-w-lg text-base leading-relaxed text-muted-foreground lg:mx-0 lg:text-lg"
            >
              NexusVPN routes your traffic through a fast, encrypted WireGuard tunnel, so your connection stays private on any network — with billing in Algerian dinars.
            </motion.p>

            {/* CTAs */}
            <motion.div variants={itemVariants} className="flex flex-col justify-center gap-3 sm:flex-row lg:justify-start">
              <a
                href="#pricing"
                className="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-sm font-medium text-primary-foreground transition-all duration-200 hover:bg-primary/90 glow-cyan"
              >
                Start free trial
                <svg viewBox="0 0 16 16" fill="none" className="h-4 w-4">
                  <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </a>
              <a
                href="#features"
                className="inline-flex items-center justify-center gap-2 rounded-xl border border-border px-6 py-3.5 text-sm font-medium text-muted-foreground transition-all duration-200 hover:border-border/80 hover:text-foreground"
              >
                See how it works
              </a>
            </motion.div>

            {/* Trust signals */}
            <motion.div
              variants={itemVariants}
              className="mt-8 flex flex-wrap items-center justify-center gap-x-5 gap-y-3 lg:justify-start"
            >
              {[
                "7-day free trial",
                "WireGuard® protocol",
                "Pay in Algerian dinars",
              ].map((item) => (
                <div key={item} className="flex items-center gap-1.5 text-xs text-muted-foreground">
                  <svg viewBox="0 0 16 16" fill="none" className="w-3.5 h-3.5 text-primary flex-shrink-0">
                    <path d="M3 8l3.5 3.5L13 5" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                  {item}
                </div>
              ))}
            </motion.div>
          </motion.div>

          {/* Right — Dashboard */}
          <motion.div
            initial={{ opacity: 0, x: 40 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.8, delay: 0.4, ease: [0.22, 1, 0.36, 1] }}
            className="relative max-w-sm mx-auto lg:max-w-none"
          >
            <VPNDashboard />
          </motion.div>
        </div>
      </div>
    </section>
  )
}
