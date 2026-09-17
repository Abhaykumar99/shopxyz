# Open Questions for the Client

Answers must be in before **Phase 4 (database)**. Record each answer here and, if it changes the design,
update [erd.md](erd.md) / [decisions.md](decisions.md).

| # | Question | Why it matters | Answer |
|---|---|---|---|
| 1 | Is the business **GST-registered**? If yes: GSTIN, HSN codes, and are prices tax-inclusive? | Invoice format, tax columns, invoice numbering | |
| 2 | Do products have **variants** (cosmetic shades/sizes, confectionery weights)? | Product/variant schema, product page UI | |
| 3 | Which **pincodes / areas** are served? | Checkout validation | |
| 4 | **Delivery charge** rules: flat, by distance, free above an amount? **Minimum order value**? | Checkout totals | |
| 5 | Can customers browse and add to cart **without signing in** (sign in only at checkout)? | Guest cart logic | |
| 6 | Delivery boy login ID: **phone number or email**? | Staff auth | |
| 7 | Can the customer **cancel** an order? Until which stage? | Status rules, stock restore | |
| 8 | **Failed delivery** (customer not available or refuses): reattempt, reassign, or return to shop? | Delivery statuses | |
| 9 | **Rejected UPI payment**: can the customer re-upload proof? What happens to the order? | Payment attempts | |
| 10 | Is stock held for an unpaid UPI order? If not paid within X hours, auto-cancel? | Inventory, scheduled job | |
| 11 | How do delivery boys **hand over COD cash**, and how often (daily)? | COD settlement table | |
| 12 | Track **expiry dates / batches** for confectionery and cosmetics now or later? | Inventory schema | |
| 13 | UPI details: **VPA (UPI ID)** and payee name shown on the QR | Payment page | |
| 14 | Shop details for invoice/label: legal name, address, phone, logo | Invoice, label, branding | |
| 15 | Brand look: logo, colours, fonts, reference websites | Phase 1 design system | |
| 16 | Should customers get email updates (free) on order status? | Notifications | |
| 17 | Domain name, and the Hostinger VPS plan | Deployment, Google OAuth redirect URL | |
| 18 | Roughly how many products, orders/day, and delivery boys at launch? | Sizing, pagination, VPS plan | |
