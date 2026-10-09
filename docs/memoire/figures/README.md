# Mémoire figures — English (Step 5)

English diagram sources for the mémoire (the French originals stay in `docs/pfe/memoire-assets/diagrams/` + `.../database/erd.mmd`, referenced by the French `docs/pfe/` files). Mobile elements are marked **POSTPONED** placeholders.

| File | Status | Note |
|---|---|---|
| `architecture-en.mmd` | ✅ EN | web + backend + external; mobile dashed/postponed; e-mail = Gmail (local) / Brevo (prod) |
| `use-case-en.mmd` | ✅ EN (Mermaid) | **to finalize as real UML in PlantUML** (actors + boundary + include/extend) for the figure |
| `erd-en.mmd` | ✅ EN | real schema; `past_due` added; `personal_access_tokens` placeholder (postponed) |
| class diagram | ➖ already English | `docs/pfe/memoire-assets/diagrams/03-class-diagram.mmd` is in English; add a `PersonalAccessToken` class (postponed) when mobile resumes |
| sequence diagrams (5) | ⏳ to translate | `04-seq-registration-verification`, `05-seq-login`, `06-seq-payment-chargily`, `07-seq-vpn-config`, `08-seq-admin-delete-user` — French labels → EN (same structure); translate when writing Ch III.6 |
| `09-deployment.mmd` | ⏳ to translate | FR → EN; update to the real deployed URLs after deployment |

## Rendering to PNG/SVG (production step, Phase 13)
Needs a headless browser for Mermaid or Java for PlantUML (both heavy / flaky here). When ready:
```
npx pnpm@10 dlx @mermaid-js/mermaid-cli -b white -i figures/erd-en.mmd -o figures/erd-en.png
```
(The project previously rendered Mermaid via mermaid-cli reusing Playwright's chromium — see `docs/pfe/memoire-assets/README.md`.) The **use-case** diagram should be produced with **PlantUML** (Java is installed) in proper UML notation, not Mermaid.
