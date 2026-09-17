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

- Home, categories, product list, search, filters, product details
- Cart (change quantity, remove), checkout, address selection or creation, payment method, place order
- Google sign-in; profile with a **required phone number before the first order**
- Saved addresses, My Orders, order details, order status timeline
- Delivery partner name/phone shown once assigned; delivery OTP shown to the customer

Initial categories: Cosmetics, Confectionery, Gifts. Subcategories are supported.

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

## 8. Delivery

- Admin manages delivery boys (name, phone, active/inactive), assigns orders, and sees assigned, pending and
  completed orders plus history per delivery boy.
- Delivery panel (mobile web): assigned orders, customer and address details, order items, payment status,
  COD amount, instructions.
- Flow: Assigned → Accept → Picked up → Out for delivery → Reached → **OTP verification** → Delivered.
- The COD amount collected is recorded at delivery, and cash handover is reconciled by the admin.

## 9. Admin dashboard

Metrics: today's orders, pending, payment verification, confirmed, packing pending, ready for delivery,
out for delivery, delivered, cancelled, revenue, COD collected, COD pending, low-stock products.

Management: products (images, price, discount, stock, category, description, status), categories,
inventory, orders, payments, customers (details, addresses, order history, total spend), delivery boys,
assignments, reports, settings.

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
| 4 | Database schema, models, seeders, auth (Google + staff), roles and policies |
| 5 | Catalog, cart, inventory |
| 6 | Orders, COD, UPI payments, order state machine |
| 7 | Filament admin |
| 8 | Invoice and package label |
| 9 | Delivery workflow, OTP, COD reconciliation |
| 10 | Hardening, tests, VPS deployment, UAT, go-live |

Each phase starts only after explicit approval.
