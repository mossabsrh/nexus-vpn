"use client"

const footerLinks = {
  Product: [
    { name: "Features", href: "#features" },
    { name: "Network", href: "#map" },
    { name: "Pricing", href: "#pricing" },
  ],
  Support: [
    { name: "Contact", href: "#contact" },
  ],
}

export function Footer() {
  return (
    <footer className="border-t border-border bg-background/80">
      <div className="container mx-auto px-4 md:px-6 max-w-6xl py-14">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12">
          {/* Brand */}
          <div className="col-span-2">
            <a href="#" className="flex items-center gap-2.5 mb-4">
              <div className="relative w-7 h-7 flex items-center justify-center">
                <svg viewBox="0 0 32 32" fill="none" className="w-7 h-7">
                  <path d="M16 2L4 8v8c0 7.18 5.16 13.9 12 15.5C22.84 29.9 28 23.18 28 16V8L16 2z" stroke="currentColor" strokeWidth="1.5" className="text-primary"/>
                  <path d="M11 16l3.5 3.5L21 11" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="text-primary"/>
                </svg>
              </div>
              <span className="font-semibold tracking-tight">
                <span className="text-foreground">Nexus</span>
                <span className="text-primary">VPN</span>
              </span>
            </a>
            <p className="text-sm text-muted-foreground max-w-xs leading-relaxed mb-5">
              Encrypted WireGuard tunnels, with billing in Algerian dinars through EDAHABIA and CIB.
            </p>
          </div>

          {/* Link columns */}
          {Object.entries(footerLinks).map(([category, links]) => (
            <div key={category}>
              <h4 className="text-sm font-medium text-foreground mb-4">{category}</h4>
              <ul className="space-y-2.5">
                {links.map((link) => (
                  <li key={link.name}>
                    <a href={link.href} className="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">
                      {link.name}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        {/* Bottom bar */}
        <div className="flex flex-col sm:flex-row items-center justify-between pt-8 border-t border-border gap-4">
          <p className="text-xs text-muted-foreground">
            © {new Date().getFullYear()} NexusVPN. All rights reserved.
          </p>
          <div className="flex items-center gap-4 text-xs text-muted-foreground">
            <span>WireGuard® is a registered trademark of Jason A. Donenfeld.</span>
          </div>
        </div>
      </div>
    </footer>
  )
}
