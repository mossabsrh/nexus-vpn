# Problem Statement

*Three options are proposed below, all grounded in the real project. The final thesis keeps **only one** (the one the author chooses); the other two are removed. Each option states one main question, two or three sub-questions, and exactly one hypothesis per sub-question. The hypotheses are confirmed or not in the General Conclusion.*

## Option A — Modernising the sale and delivery of the VPN service

**Main question.** How can a web portal modernise the way the service is sold and delivered, compared with the current manual process?

- **Sub-question 1.** How can the offer, the subscription and the payment be handled online in the Algerian context (local cards, payment in dinars)?
  - **Hypothesis 1.** Integrating a hosted Algerian payment gateway (Chargily, EDAHABIA/CIB) with a server-computed amount and an exactly-once confirmation lets a customer subscribe and pay online securely, without a foreign card.
- **Sub-question 2.** How can VPN access be provisioned without manual account creation?
  - **Hypothesis 2.** Generating the WireGuard key pair on the server and delivering a ready-to-import configuration file automates the provisioning and removes the manual delivery step.
- **Sub-question 3.** How can the portal remain secure and respect the rules on personal data?
  - **Hypothesis 3.** Delegating card data to a certified gateway, applying standard web-security controls and storing no VPN traffic data provide an adequate level of security and privacy, cross-border hosting being a stated limitation.

## Option B — Secure e-commerce for a digital service in Algeria

**Main question.** Can a secure e-commerce platform make it possible to commercialise a VPN service online in the Algerian context?

- **Sub-question 1.** Does local online payment remove the main barrier to selling a digital service in Algeria?
  - **Hypothesis 1.** Paying in dinars with EDAHABIA or CIB through a hosted gateway removes the dependence on foreign cards and currencies and makes online selling possible.
- **Sub-question 2.** Can a self-service subscription-and-provisioning flow reduce delays and manual work compared with a manual process?
  - **Hypothesis 2.** A self-service flow (free trial → online payment → automatic configuration download) shortens the processing time and reduces errors compared with a manual, paper-based process.
- **Sub-question 3.** Can such a platform meet the security and legal requirements for handling payments and personal data?
  - **Hypothesis 3.** Combining delegated card handling, data minimisation and standard security controls satisfies the main security requirements and those of Law 18-07, within the limits of the chosen hosting.

## Option C — Feasibility and reliability of the solution

**Main question.** How can one design and build a reliable and secure web portal that sells and provisions a VPN service end to end?

- **Sub-question 1.** Which software architecture suits a small team and a single, well-defined domain?
  - **Hypothesis 1.** A decoupled Next.js / Laravel architecture built as a modular monolith offers a simple, testable and deployable solution adapted to the scale of the project.
- **Sub-question 2.** How can a payment be confirmed reliably, exactly once and never on unverified data?
  - **Hypothesis 2.** Recording the payment before contacting the gateway, verifying an HMAC-signed webhook and applying an idempotent, amount-checked activation guarantee a trustworthy, exactly-once confirmation.
- **Sub-question 3.** How can VPN access be provisioned safely from the application?
  - **Hypothesis 3.** Generating Curve25519 keys on the server, storing the private key encrypted and never reusing a tunnel address provision access safely, with client-side key generation kept as future work.
