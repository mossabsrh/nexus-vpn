# General Conclusion

This work set out to design and develop a secure web portal that sells and provisions a VPN service online for the Algerian market, as a case study carried out within the **Entreprise d'Appui au Développement du Numérique (EADN)**. The portal, named **NexusVPN**, was meant to cover the whole commercial chain — presenting offers, letting a customer subscribe and pay online in dinars, provisioning VPN access automatically, and giving an administrator a back-office — while applying the security and data-protection measures expected of an e-commerce application.

To reach this objective, the thesis first studied the host organisation and its existing process (Chapter I), then the theoretical background: the fundamentals of VPNs and their protocols (IPsec, OpenVPN and WireGuard), the types of VPN service, the security and data-protection frameworks, and the concepts of electronic commerce and online payment in the Algerian context (Chapter II). On this basis, the requirements, the actors and the use cases were analysed and the solution was modelled with UML (Chapter III), before it was implemented, tested and prepared for deployment (Chapter IV).

The main results are a complete and working portal. It publishes subscription plans priced in Algerian dinars; it lets a customer register, verify their e-mail, start a seven-day free trial, and subscribe and pay with EDAHABIA or CIB through the Chargily gateway, with the amount computed on the server and the payment applied exactly once through an HMAC-signed webhook and an idempotent, amount-checked activation. It provisions VPN access by generating a WireGuard (Curve25519) key pair, storing the private key encrypted, and delivering a ready-to-import configuration file. It offers an administration console to manage offers, subscriptions, payments and refunds, moderate testimonials, handle contact requests and consult an audit log. The back end is covered by an automated suite of **131 tests (508 assertions)**, and the front end passes linting, type-checking and a production build.

**Answer to the problem statement.** *PLACEHOLDER — to be completed once the problem statement is chosen (Option A, B or C). For the chosen option, state for each hypothesis whether it is **confirmed**, **partially confirmed** or **not confirmed**, with a one-line justification drawn from the results above (for example: the online dinar payment through Chargily, the automatic WireGuard provisioning, the exactly-once payment confirmation, the delegated card handling and the absence of VPN traffic data).*

**Value and limits.** For the authors, the project was a complete, realistic engineering experience: analysis of a real need, design with UML, full-stack development, integration of a real payment gateway and of modern cryptography, testing, and preparation for deployment. For the company, the portal shows how a manual service-sale process can be turned into an online, self-service and traceable one, adapted to the Algerian payment context. Its limits are stated honestly: the portal is the sales and provisioning layer, not the VPN gateway itself, which is operated by the network team and was not deployed during this work; the system is prepared for deployment on free hosting tiers but has not yet been operated in production; multi-factor authentication, a ticketing system, PDF invoices and an e-mail queue are not implemented; and, because the configured hosting is located outside Algeria, the cross-border processing of personal data would require the authorisation foreseen by Law 18-07 — a point to resolve before a real launch.

**Difficulties met.** *PLACEHOLDER — to be written by the authors (for example: integrating the payment gateway and testing it without a live deployment, modelling a real application faithfully, collecting the EADN information within the schedule, working as a team on a single code base …).*

## Perspectives

Several directions would extend this work:

- **Native mobile client:** resume the Flutter application (the backend token API already exists) so that customers can subscribe and manage their VPN from a phone.
- **Client-side key generation:** let the client generate its own WireGuard key pair and send only the public key, so the private key never exists on the server.
- **A live WireGuard gateway:** operate a real gateway and automate the synchronisation of issued peers, turning the provisioned configurations into working tunnels.
- **Stronger authentication:** add multi-factor authentication to the account and the administration area.
- **Support tooling:** add a proper ticketing / helpdesk system beside the current contact requests and moderation.
- **Billing:** generate downloadable PDF invoices in addition to the e-mail receipts.
- **Scalability and reliability:** move transactional e-mails and revocations to an asynchronous queue, and add monitoring and alerting.
- **Legal compliance:** host the application and its database within Algeria, or obtain the authorisation required for cross-border data processing under Law 18-07.
