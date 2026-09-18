# Requirements: E-Commerce & Delivery Management Platform

> Source: the client project brief. This file is the working summary. Changes to scope are recorded in
> [decisions.md](decisions.md), and open questions are in [client-questions.md](client-questions.md).

## 1. Goal

Replace the phone/WhatsApp ordering and manual delivery process of a shop that sells **Cosmetics,
Confectionery and Gift items** with one digital platform that covers:

**E-commerce + order management + payment verification + billing + packing + multi-delivery management**

Constraints: low build cost, low running cost, simple maintenance, secure by default, able to grow
without a rewrite.

## 2. Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13 (PHP 8.4), modular monolith |
| UI | Livewire 4 (class-based components) + Tailwind CSS 4 + Blade components |
| Admin | Filament 5 |
| Database | MySQL 8.4 (production). Local: XAMPP MariaDB 10.4 for now |
| Customer auth | **Google sign-in (Laravel Socialite)** — see ADR-004 |
| Hosting | Hostinger VPS, Ubuntu, Nginx, PHP-FPM, SSL, cron, queue worker |
| VCS / CI | Git + GitHub, GitHub Actions |

## 3. Roles

| Role | How they log in | Where they work |
|---|---|---|
| Customer | Google sign-in only | Shop website (`/`, `/account`) |
| Admin | Email + password + 2FA | Filament panel (`/admin`) |
| Delivery boy | Phone/email + password (account created by admin) | Mobile web panel (`/delivery`) |

## 4. Customer website

- Home (auto-sliding banners on desktop), categories, product list, search, filters, product details, wholesale
- Cart (change quantity, remove), checkout, address selection or creation, payment method, place order
- Google sign-in; profile with a **required phone number before the first order**
- Saved addresses, My Orders, order details, order status timeline
- Delivery partner name/phone shown once assigned; delivery OTP shown to the customer

Initial categories: Cosmetics, Confectionery, Gifts. Subcategories are supported.

**Wholesale** (ADR-019): a Wholesale section with bulk price slabs and minimum quantities for shops, event
planners, hotels and corporate gifting. Wholesale is ordered through the **same bag, checkout, payment and
tracking** as any other order: the slab price applies automatically from the minimum quantity, and no wholesale
account or approval is needed. Cash on delivery stops at the COD ceiling (₹20,000 by default), above which only
UPI is offered. A **quote request** (`/wholesale/quote`) is an optional extra for custom pricing, packing,
branding or supply arrangements, and can attach the current bag; the shop replies by phone or WhatsApp.

## 5. Payments (no gateway)

- **COD**: order placed, then the admin confirms it.
- **UPI**: the site shows a UPI QR for the exact amount with the order number as reference. The customer pays,
  uploads a screenshot and enters the UTR. The payment is then *pending verification* until the admin approves
  or rejects it.
- Payment proofs are private files and are served only to authorized users.
- A UTR can be used only once in the system.

## 6. Order lifecycle

```
Placed → (Payment verification) → Confirmed → Invoice generated → Packing → Packed
→ Delivery boy assigned → Picked up → Out for delivery → (OTP) → Delivered
```
Plus: Cancelled, Delivery failed (see client-questions.md). Every change is logged with who, when and a note.

## 7. Billing & labels

- **Invoice** (printable/PDF): shop details, invoice and order number, customer name/address/phone, items, qty,
  price, discount, total, payment method and status, order date.
- **Package label** (printable): shop name, order ID, customer name, phone, address, pincode, items, payment
  method, total, barcode/QR.
- **Print formats** (admin selects a default and can override per print): labels in **4×6" thermal**, **A5** or **A4**.
  Invoices in **A4** or **A5**. See ADR-014.

## 8. Delivery

- Admin manages delivery boys (name, phone, active/inactive), assigns orders, and sees assigned, pending and
  completed orders plus history per delivery boy.
- Delivery panel (mobile web, ADR-020, ADR-022): its own sign-in; today's round grouped into **to pick up,
  picked up, out for delivery, delivered and failed** with counts and a one-tap next action; one delivery
  (boxes, customer, address, call and directions, items, payment and COD amount, instructions); the cash
  trail; a searchable history of finished deliveries; and a profile with sign out.
- Flow (ADR-021): the shop packs the order into boxes, each with a **package id and pickup code** on its label
  → the delivery boy accepts → at the counter he enters the **pickup code of every box** → the order goes
  `Out for delivery` → at the address he enters the customer's **6-digit OTP** and collects the full cash for a
  COD order → `Delivered`, otherwise `Delivery failed`.
- **Pickup code = the shop's confirmation** that the right boxes left the counter. **OTP = the customer's
  confirmation** that the parcel reached them. Neither is ever shown in the delivery panel; both allow at most
  3 tries per order.
- The delivery boy's steps are separate from the order status: picking up every box sets the order to Out for
  delivery, delivering or failing ends it.
- A delivery that cannot be completed is recorded with a reason (and a note when needed) that the customer
  reads on their order, and the panel says how many boxes to take back to the shop.
- COD cash has its own trail (ADR-022): **collected** at the door → **handed over** to the shop as one batch →
  **verified** by the admin → **settled**, with every batch kept in the delivery boy's cash history. The panel
  records the handover; only the admin marks a batch settled.

## 8b. Homepage management (ADR-024)

The whole homepage is managed from the admin: hero banners for desktop and phone, promotion cards, and the
blocks of the page (rows of products, categories, promises, how ordering works). Every banner and block can be
added, edited, reordered by dragging, switched off, and scheduled with a start and end date. Rows of products
choose their source (bestsellers, new arrivals, discounts, featured, a category, or a hand-picked list).
Nothing on the homepage is hardcoded.

## 9. Admin dashboard

Metrics: today's orders, pending, payment verification, confirmed, packing pending, ready for delivery,
out for delivery, delivered, cancelled, revenue, COD collected, COD pending, low-stock products.

Management: products (images, price, discount, stock, category, description, status), categories,
inventory, orders, payments, customers (details, addresses, order history, total spend), delivery boys,
assignments, reports, settings.

**Shop settings (admin-editable, never hardcoded):** shop name (a temporary demo name is used until the admin sets it),
logo, contact details, address, GSTIN, UPI details, and the default print formats. See ADR-013.

## 10. Inventory

Stock is reduced when an order is placed and restored when it is cancelled. Low stock is highlighted. Every stock change is
recorded in an inventory movement log.

## 11. Security (summary, details in [security.md](security.md))

Role-based access, policies, CSRF, validation, XSS/SQLi protection, secure uploads, private payment
proofs, rate limiting, HTTPS, env-based secrets, backups, and an audit trail for orders, payments and prices.

## 12. Out of scope for v1 (future)

Native mobile app, live GPS, routes and zones, auto-assignment, reviews, wishlist, coupons, loyalty,
scheduled delivery, gift wrapping, multi-branch, barcode scanning workflow, advanced reports,
returns/refunds, WhatsApp/SMS notifications.

## 13. Delivery phases

| Phase | Scope |
|---|---|
| 0 | Foundation: environment, repo, scaffold, docs, CI |
| 1 | Design system (Tailwind theme, Blade UI components) |
| 2 | Customer UI with dummy data |
| 3 | Delivery panel UI with dummy data |
| 4 | Database schema, models, factories, seeders |
| 5 | Admin panel (Filament) on seeded data, including homepage management (ADR-023, ADR-024) |
| 6 | Auth: Google sign-in for customers, staff accounts, roles and policies |
| 7 | Catalog, cart and inventory on the database |
| 8 | Orders, COD, UPI payments, order state machine |
| 9 | Invoice and package label from real orders |
| 10 | Delivery workflow, OTP, COD reconciliation |
| 11 | Hardening, tests, VPS deployment, UAT, go-live |

Each phase starts only after explicit approval. The order changed once the owner chose Filament for the admin
panel: Filament reads Eloquent models, so the database came before the panel and the old "Filament admin"
phase is gone (ADR-023).
