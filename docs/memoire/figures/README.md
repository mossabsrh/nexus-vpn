# Mémoire figures — English (Step 5, rendered)

English diagram sources **and rendered PNGs** for the mémoire. The French originals stay in `docs/pfe/memoire-assets/diagrams/` (referenced by the French `docs/pfe/` files). Mobile elements are marked **POSTPONED** placeholders.

## Rendered (PNG, white background, scale 3 — sharp at ~16 cm print width)

| Figure | Source | PNG | Tool |
|---|---|---|---|
| Global use-case (real UML) | `use-case-global.puml` | `use-case-global.png` | PlantUML (Smetana) |
| Client use-case (real UML) | `use-case-client.puml` | `use-case-client.png` | PlantUML |
| Admin use-case — support merged, G-05 (real UML) | `use-case-admin.puml` | `use-case-admin.png` | PlantUML |
| Global architecture | `architecture-en.mmd` | `architecture-en.png` | Mermaid |
| Class diagram | `class-diagram-en.mmd` | `class-diagram-en.png` | Mermaid |
| ERD | `erd-en.mmd` | `erd-en.png` | Mermaid |
| Deployment | `deployment-en.mmd` | `deployment-en.png` | Mermaid |
| Sequence — registration + verification | `seq-registration-verification-en.mmd` | `.png` | Mermaid |
| Sequence — login | `seq-login-en.mmd` | `.png` | Mermaid |
| Sequence — Chargily payment | `seq-payment-chargily-en.mmd` | `.png` | Mermaid |
| Sequence — VPN config | `seq-vpn-config-en.mmd` | `.png` | Mermaid |
| Sequence — admin delete user | `seq-admin-delete-user-en.mmd` | `.png` | Mermaid |

- `use-case-en.mmd` is a Mermaid **draft, superseded** by the three PlantUML use-case diagrams (real UML with actors, system boundary, «include»/«extend»). Not rendered.
- All PNGs were **checked visually** for correctness and readability (Step 5). Mobile appears only as a dashed/noted placeholder (architecture, deployment, use-case notes).

## How they were rendered (to regenerate)
- **PlantUML** (Java 25; `dot` not installed → Smetana layout):
  `java -jar plantuml.jar -tpng -Playout smetana use-case-*.puml`
- **Mermaid** (mermaid-cli pointed at Playwright's Chromium via a puppeteer config, since its own download times out):
  `npx pnpm@10 dlx @mermaid-js/mermaid-cli -p <puppeteer-config.json> -b white -s 3 -i X.mmd -o X.png`
  (`puppeteer-config.json` sets `executablePath` to the Playwright `chrome.exe`; env `PUPPETEER_SKIP_DOWNLOAD=true`.)
