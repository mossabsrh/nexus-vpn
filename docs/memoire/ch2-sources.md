# Chapter II — theory sources (working bibliography)

> Official sources for the état-de-l'art chapter (VPN + e-commerce). Complements `docs/pfe/16-sources.md` (Chargily). Access date to set when the chapter is written; the dated items below were checked **2026-10-10** (laws + WireGuard verified by web search; the others are stable official pages to re-open and cite with the final access date). Format for the bibliography = methodology guide (grouped, alphabetical, access date + time for websites). **Invent nothing: confirm each URL opens before citing.**

## VPN / networking (II.1–II.3)
| Ref | Source | URL | Note |
|---|---|---|---|
| WireGuard whitepaper | DONENFELD, Jason A.: *WireGuard: Next Generation Kernel Network Tunnel*, NDSS Symposium 2017 (27 Feb 2017; static draft 1 Jun 2020) | https://www.wireguard.com/papers/wireguard.pdf · NDSS copy: https://www.ndss-symposium.org/wp-content/uploads/2017/09/ndss2017_04A-3_Donenfeld_paper.pdf | **Verified.** Curve25519 keys; "fit under 4,000 lines"; replaces IPsec/OpenVPN |
| WireGuard protocol | WireGuard — official site (protocol, crypto, Noise) | https://www.wireguard.com/protocol/ | For II.1.3 |
| OpenVPN | OpenVPN — official documentation | https://openvpn.net/community-resources/ | Protocol comparison |
| IPsec | IETF RFC 4301 — *Security Architecture for the Internet Protocol* | https://www.rfc-editor.org/rfc/rfc4301 | Protocol comparison |
| VPN concepts / TLS | IETF RFC 8446 — *TLS 1.3* | https://www.rfc-editor.org/rfc/rfc8446 | Encryption/auth (II.3.1) |

## Security & compliance (II.3, II.5)
| Ref | Source | URL | Note |
|---|---|---|---|
| OWASP Top 10 | OWASP — *Top 10 Web Application Security Risks* | https://owasp.org/www-project-top-ten/ | SQLi/XSS/CSRF context (II.5.2, ties to our hardening) |
| OWASP ASVS | OWASP — *Application Security Verification Standard* | https://owasp.org/www-project-application-security-verification-standard/ | Portal security principles |
| ISO/IEC 27001 | ISO/IEC 27001 — *Information security management systems* | https://www.iso.org/standard/27001 | Compliance standards (II.3.3) |
| GDPR | Regulation (EU) 2016/679 (GDPR) | https://eur-lex.europa.eu/eli/reg/2016/679/oj | Compliance standards (II.3.3) |

## Algerian legal framework (II.3.3, II.4)
| Ref | Source | URL | Note |
|---|---|---|---|
| E-commerce law | **Loi n° 18-05 du 10 mai 2018** relative au commerce électronique (JO n° 28, 16 mai 2018 ; entrée en vigueur ~7 juin 2018) | https://commerce.gov.dz/fr/actualites/la-loi-relative-au-commerce-electronique-fixant-les-regles-generales-du-commerce-electronique-des-biens-et-des-services | **Verified.** Scope art. 2; prohibited categories; cites Law 15-04 (e-signature) + 18-04 | 
| Personal-data law | **Loi n° 18-07 du 10 juin 2018** relative à la protection des personnes physiques dans le traitement des données à caractère personnel (JO n° 34) | (JORADP — Journal officiel n° 34, 10 juin 2018) | **Verified.** Regulator **ANPDP**; consent default; sanctions |
| 2025 amendment | **Loi n° 25-11** (2025, JO n° 48, 24 juillet 2025) amending Law 18-07 (adds DPO, processing register, etc.) | (JORADP n° 48, 24 juillet 2025) | **Verified** — note it in a footnote (the 2018 text remains the base) |
| E-payment context | Chargily Pay v2 (EDAHABIA / CIB) — see `docs/pfe/16-sources.md` (5 Chargily refs, accessed 2026-10-06) | https://dev.chargily.com/ | Already collected |

## E-commerce (II.4)
| Ref | Source | URL | Note |
|---|---|---|---|
| E-commerce definition / models | OECD / WTO e-commerce references (choose one authoritative) | https://www.oecd.org/digital/ (to confirm) | II.4.1–II.4.2 — pick a stable official definition |

## Technologies used (cross-ref to Ch IV)
| Ref | Source | URL |
|---|---|---|
| Laravel 12 | Laravel — official documentation | https://laravel.com/docs/12.x |
| Next.js 16 | Next.js — official documentation | https://nextjs.org/docs |
| Laravel Sanctum | Sanctum — official documentation (mobile token auth) | https://laravel.com/docs/12.x/sanctum |
| Tailwind CSS 4 | Tailwind CSS — documentation | https://tailwindcss.com/docs |
| PostgreSQL | PostgreSQL — documentation | https://www.postgresql.org/docs/ |

## To do when writing Ch II
- Re-open each URL, confirm it loads, record the **exact access date & time** (website citation rule).
- For II.6 (competitors), add the official pages of the competitors chosen (e.g. NordVPN, Proton VPN) + an Algerian ISP VPN offer if relevant — **student validates the facts**.
- Pick **one** authoritative e-commerce definition source (OECD/WTO/academic) rather than a blog.
- Keep footnote format: AUTHOR (First name): *Title*, publisher, place, year, page / website: full URL + access date.
