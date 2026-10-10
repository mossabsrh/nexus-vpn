# Chapter II — theory sources (working bibliography)

> Official sources for the état-de-l'art chapter (VPN + e-commerce). Complements `docs/pfe/16-sources.md` (Chargily). Access date to set when the chapter is written; the dated items below were checked **2026-10-10** (laws + WireGuard verified by web search; the others are stable official pages to re-open and cite with the final access date). Format for the bibliography = methodology guide (grouped, alphabetical, access date + time for websites). **Invent nothing: confirm each URL opens before citing.**

## VPN / networking (II.1–II.3)
| Ref | Source | URL | Note |
|---|---|---|---|
| WireGuard whitepaper | DONENFELD, Jason A.: *WireGuard: Next Generation Kernel Network Tunnel*, NDSS Symposium 2017 (27 Feb 2017; static draft 1 Jun 2020) | https://www.wireguard.com/papers/wireguard.pdf · NDSS copy: https://www.ndss-symposium.org/wp-content/uploads/2017/09/ndss2017_04A-3_Donenfeld_paper.pdf | **Verified.** Curve25519 keys; "fit under 4,000 lines"; replaces IPsec/OpenVPN |
| WireGuard protocol | WireGuard — official site (protocol, crypto, Noise) | https://www.wireguard.com/protocol/ | For II.1.3. **Cited for BLAKE2s** + the full primitive set (ChaCha20-Poly1305, Curve25519, BLAKE2s, SipHash24, HKDF; Noise_IKpsk2). Verified 2026-10-11. |
| OpenVPN | OpenVPN — official documentation | https://openvpn.net/community-resources/ | Protocol comparison (TLS/SSL + OpenSSL) |
| IPsec (current) | IETF RFC 4301 — *Security Architecture for the Internet Protocol* | https://www.rfc-editor.org/rfc/rfc4301 | Protocol comparison (2005, current architecture) |
| IPsec (original) | IETF RFC 1825 — *Security Architecture for the Internet Protocol* (ATKINSON, Aug 1995; obsoleted by RFC 2401 → RFC 4301) | https://www.rfc-editor.org/rfc/rfc1825 | **Verified 2026-10-11.** Shows IPsec dates to 1995, not 2005 (Table II.1) |
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
| E-commerce law | **Loi n° 18-05 du 10 mai 2018** relative au commerce électronique (JO n° 28, 16 mai 2018 ; entrée en vigueur ~7 juin 2018) | https://commerce.gov.dz/fr/actualites/la-loi-relative-au-commerce-electronique-fixant-les-regles-generales-du-commerce-electronique-des-biens-et-des-services | **Verified 2026-10-11 (Ministry of Commerce guide).** **Definition of commerce électronique = Article 6** (e-fournisseur / e-consommateur); **scope = Article 2**. Student quotes art. 6 verbatim from JO n° 28. Guide: https://www.mpt.gov.dz/wp-content/uploads/2024/01/guide_FR2.pdf | 
| Personal-data law | **Loi n° 18-07 du 10 juin 2018** relative à la protection des personnes physiques dans le traitement des données à caractère personnel (JO n° 34) | (JORADP — Journal officiel n° 34, 10 juin 2018) | **Verified.** Regulator **ANPDP**; consent default; sanctions |
| 2025 amendment | **Loi n° 25-11** (2025, JO n° 48, 24 juillet 2025) amending Law 18-07 (adds DPO, processing register, etc.) | (JORADP n° 48, 24 juillet 2025) | **Verified** — note it in a footnote (the 2018 text remains the base) |
| E-payment context | Chargily Pay v2 (EDAHABIA / CIB) — see `docs/pfe/16-sources.md` (5 Chargily refs, accessed 2026-10-06) | https://dev.chargily.com/ | Already collected |

## E-commerce (II.4)
Authoritative definition **chosen** (no "to confirm" left): the **Algerian legal definition (Law 18-05, art. 2)** is the primary one for the local context; the **OECD** statistical definition is the international/economic one. The WTO wording is optional for the trade-policy framing. Wordings below were **confirmed by web search on 2026-10-10** (OECD, ILO and UNESCWA glossaries corroborate the OECD wording; WTO secondary sources corroborate the WTO wording). Note: oecd.org and wto.org block automated fetchers — open each in a browser when writing to record the exact access date (the same access-date step every website citation needs).

| Ref | Source | Definition (verbatim core) | URL | Note |
|---|---|---|---|---|
| **E-commerce — primary (Algerian law)** | **Loi n° 18-05 du 10 mai 2018, art. 2** (see *Algerian legal framework* above) | legal definition of "commerce électronique" applicable in Algeria | https://commerce.gov.dz/fr/actualites/la-loi-relative-au-commerce-electronique-fixant-les-regles-generales-du-commerce-electronique-des-biens-et-des-services | **Verified** — use as the primary definition (II.4.1) |
| **E-commerce — international (economic)** | **OECD** — definition of an e-commerce transaction (2009 standard, reaffirmed in *The 2025 OECD Definition of E-commerce and Guidelines for Interpretation*) | "An e-commerce transaction is the sale or purchase of goods or services, … conducted over computer networks by methods specifically designed for the purpose of receiving or placing of orders. … payment and … delivery … may be conducted on- or off-line." | https://www.oecd.org/en/publications/the-2025-oecd-definition-of-e-commerce-and-guidelines-for-interpretation_2254f1de-en.html | **Wording verified** (search 2026-10-10); record browser access date |
| E-commerce — trade-policy framing (optional) | **WTO** — Work Programme on Electronic Commerce (WT/L/274, 25 Sept 1998) | e-commerce = "the production, distribution, marketing, sale or delivery of goods and services by electronic means" | https://www.wto.org/english/tratop_e/ecom_e/ecom_e.htm | **Wording verified** (search 2026-10-10); gateway page |

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
- ~~Pick one authoritative e-commerce definition source~~ **Done:** Law 18-05 art. 2 (primary, Algerian) + OECD 2009/2025 (international). Just record the browser access date for the OECD/WTO URLs when writing.
- Keep footnote format: AUTHOR (First name): *Title*, publisher, place, year, page / website: full URL + access date.
