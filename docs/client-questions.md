# Open Questions for the Client

Answers must be in before **Phase 4 (database)**. Record each answer here and, if it changes the design,
update [erd.md](erd.md) / [decisions.md](decisions.md).

| # | Question | Why it matters | Answer |
|---|---|---|---|
| 1 | Is the business **GST-registered**? If yes: GSTIN, HSN codes, and are prices tax-inclusive? | Invoice format, tax columns, invoice numbering | |
| 2 | Do products have **variants** (cosmetic shades/sizes, confectionery weights)? | Product/variant schema, product page UI | Default in Phase 2: yes. Shade swatches for cosmetics, weight/size choices for sweets and gifts |
| 3 | Which **pincodes / areas** are served? | Checkout validation | Default in Phase 2: demo list of Patna pincodes (`config/shop.php`) |
| 4 | **Delivery charge** rules: flat, by distance, free above an amount? **Minimum order value**? | Checkout totals | Default in Phase 2: ₹40 delivery, free from ₹499, minimum order ₹99 |
| 5 | Can customers browse and add to cart **without signing in** (sign in only at checkout)? | Guest cart logic | Default in Phase 2: yes, guests browse and fill the bag; Google sign-in at checkout |
| 6 | Delivery boy login ID: **phone number or email**? | Staff auth | Mobile number (owner, Phase 6). The admin still records an email on the account, but the delivery panel signs in with the phone |
| 7 | Can the customer **cancel** an order? Until which stage? | Status rules, stock restore | Default in Phase 2: customers can cancel until the order is packed |
| 8 | **Failed delivery** (customer not available or refuses): reattempt, reassign, or return to shop? | Delivery statuses | |
| 9 | **Rejected UPI payment**: can the customer re-upload proof? What happens to the order? | Payment attempts | Default in Phase 2: customer can re-upload screenshot and UTR |
| 10 | Is stock held for an unpaid UPI order? If not paid within X hours, auto-cancel? | Inventory, scheduled job | |
| 11 | How do delivery boys **hand over COD cash**, and how often (daily)? | COD settlement table | |
| 12 | Track **expiry dates / batches** for confectionery and cosmetics now or later? | Inventory schema | |
| 13 | UPI details: **VPA (UPI ID)** and payee name shown on the QR | Payment page | Placeholder UPI ID in `config/shop.php` until provided |
| 14 | Shop details for invoice/label: legal name, address, phone, logo | Invoice, label, branding | |
| 15 | Brand look: logo, colours, fonts, reference websites | Phase 1 design system | Phase 2: velvet and gold palette (ADR-015) until brand assets arrive |
| 16 | Should customers get email updates (free) on order status? | Notifications | |
| 17 | Domain name, and the Hostinger VPS plan | Deployment, Google OAuth redirect URL | |
| 18 | Roughly how many products, orders/day, and delivery boys at launch? | Sizing, pagination, VPS plan | |
| 19 | **Cash on delivery ceiling** for large (wholesale) orders? | Checkout payment options (ADR-019) | Default in Phase 2: COD up to ₹20,000, UPI only above that (`config/shop.php`) |
| 20 | Should wholesale prices be public, or only for **approved business accounts**? | Wholesale access, admin approval queue | Default in Phase 2: public, no account or approval needed (ADR-019) |
| 21 | Which products get **wholesale slabs**, with what minimum quantities and prices? | `price_slabs` rows, admin screen | Phase 2 uses 12 sample products with three slabs each |
