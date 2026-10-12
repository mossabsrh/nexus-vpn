# Problem Statement

Selling and delivering a VPN service involves a complete commercial chain — presenting the offers, taking the subscription and the payment, provisioning the access and keeping the whole process secure. This work examines how that chain can be moved online for the Algerian market. It is guided by one main question, three sub-questions and one hypothesis for each sub-question; the hypotheses are tested in the General Conclusion.

**Main question.** How can a web portal modernise the way the service is sold and delivered, compared with the current manual process?

- **Sub-question 1.** How can the offer, the subscription and the payment be handled online in the Algerian context (local cards, payment in dinars)?
  - **Hypothesis 1.** Integrating a hosted Algerian payment gateway (Chargily, EDAHABIA/CIB) with a server-computed amount and an exactly-once confirmation lets a customer subscribe and pay online securely, without a foreign card.
- **Sub-question 2.** How can VPN access be provisioned without manual account creation?
  - **Hypothesis 2.** Generating the WireGuard key pair on the server and delivering a ready-to-import configuration file automates the provisioning and removes the manual delivery step.
- **Sub-question 3.** How can the portal remain secure and respect the rules on personal data?
  - **Hypothesis 3.** Delegating card data to a certified gateway, applying standard web-security controls and ensuring that the portal stores no VPN connection or traffic data provide an adequate level of security and privacy, cross-border hosting being a stated limitation.
