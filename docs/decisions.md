# Architecture Decision Records

Short records of decisions that shape the codebase. To change a decision, add a new ADR that supersedes it
rather than editing the old one.

---

## ADR-001: TALL stack as a modular monolith
**Status:** Accepted, Phase 0

One Laravel application serves the shop (Livewire), the admin panel (Filament) and the delivery panel
(Livewire). Code is grouped by business area (`Cart`, `Orders`, `Payments`, `Inventory`, `Delivery`)
inside Laravel's standard folders, with no module package.
**Why:** lowest hosting and maintenance cost, one deploy, and a single developer can hold it in their head.
A module can be extracted later if one really needs it.

## ADR-002: Business logic lives in Actions
**Status:** Accepted

`app/Actions/<Area>/<Verb><Noun>.php`: final classes with one public `handle()` method. Livewire
components, Filament resources, controllers and jobs stay thin and call Actions.
**Why:** the customer site, admin and delivery panel share the same rules (e.g. `PlaceOrder`,
`VerifyPayment`, `AssignDeliveryBoy`), and the rules are easy to test.

## ADR-003: Versions
**Status:** Accepted

Laravel 13, PHP 8.4, Livewire 4, Filament 5, Tailwind CSS 4, Pest 5, Larastan 3. Checked as mutually
compatible on 2026-09-17 (Filament 5 requires Livewire ^4.1 and supports Laravel 13).

## ADR-004: Customers sign in with Google only
**Status:** Accepted (replaces the phone/password or phone OTP option in the original brief)

- Laravel Socialite, Google provider, with the default (stateful) `state` check.
- Match returning users on `google_id` first. Accept only Google-verified emails. Never link an existing
  staff account through Google.
- `users.password` is nullable (customers have none).
- Google does not give us a phone number, so **a phone number is required before the first order**
  (collected at checkout or in the profile) because delivery needs it.
- **Admin** logs in with email + password + 2FA (Filament MFA). **Delivery boys** log in with
  phone/email + password, and their accounts are created by the admin. Staff can never sign in through Google.

**Why:** no SMS cost, no password handling for customers, and faster sign-up. Staff accounts are kept separate so
a compromised personal Google account cannot reach admin or delivery functions.

## ADR-005: Money stored as integer paise
**Status:** Accepted

All amounts are stored in `unsignedBigInteger` columns with a `_paise` suffix (₹199.50 is stored as `19950`).
Formatting is done only at the view layer through one helper.
**Why:** no floating-point rounding errors in totals, discounts or COD reconciliation.

## ADR-006: Order status and payment status are separate enums
**Status:** Accepted

`OrderStatus` (fulfilment) and `PaymentStatus` (money) are independent PHP backed enums. Allowed
transitions are defined in the enum (`canTransitionTo()`), and every change goes through an Action that
writes `order_status_histories`. "Invoice generated" is an event, not a status. See [erd.md](erd.md).

## ADR-007: Order data is copied (snapshotted) at purchase time
**Status:** Accepted

`order_items` copies product name, variant name, SKU and prices. `orders` copies the delivery address.
**Why:** later edits to products or addresses must not change past orders or invoices.

## ADR-008: Livewire class-based components
**Status:** Accepted

Livewire 4 defaults to single-file components. This project uses **class-based** components in
`app/Livewire/<Area>` with views in `resources/views/livewire/<area>` (`config/livewire.php` →
`make_command.type = class`).
**Why:** matches the planned folder structure, works with Larastan, and keeps PHP logic out of Blade.

## ADR-009: Private files are never auto-served
**Status:** Accepted

The `local` disk (`storage/app/private`) has `serve => false`. Payment proofs and similar files are
streamed only by controllers that check a policy first. Uploads get random names and are validated by
MIME type and size, and images are re-encoded.

## ADR-010: Tests run on MySQL, not SQLite
**Status:** Accepted

Local tests use the `shop_platform_test` database (`.env.testing`, not committed). CI uses a MySQL 8.4
service container.
**Why:** stock locking (`lockForUpdate`), unique constraints and JSON/enum behaviour must match
production.

## ADR-011: Local database is XAMPP MariaDB 10.4 (temporary)
**Status:** Accepted, to be revisited

Chosen by the client/developer for now. Production and CI use MySQL 8.4, so avoid MySQL-8-only SQL in raw queries
and rely on CI to catch differences. Plan to switch local development to MySQL 8.4 before Phase 6
(orders and stock locking).

## ADR-012: Portable PHP runtime for local development
**Status:** Accepted

The PHP 8.0 bundled with XAMPP is too old. PHP 8.4.25 (official Windows NTS build, checksum-verified) and Composer
2 are installed portably in `D:\dev-tools` and added to the user PATH. XAMPP is used only for MariaDB.

## ADR-013: Shop details are settings, never hardcoded
**Status:** Accepted, Phase 1

The shop name (and later logo, phone, address, GSTIN, UPI VPA, payee name) is a **system setting** that
the admin can change from the admin panel. It is used everywhere: header, footer, page titles, sign-in page,
invoices, labels and emails.

- All code reads shop details through `App\Support\ShopSettings`, which is shared with every view as `$shop`.
  No view, PDF or notification may contain a literal shop name.
- **Phase 1:** values come from `config/shop.php` defaults, with the temporary demo name
  **"Demo Gift Store"** (env `SHOP_DEMO_NAME`).
- **Phase 4:** `ShopSettings` reads the database settings store (cached) and falls back to the config defaults
  until the admin saves real values. The settings package (likely `spatie/laravel-settings`) is decided then.
- **Phase 7:** Filament "Shop settings" page to edit the values. Saving clears the cache.
- `APP_NAME` is a technical identifier only and is never shown to customers.
- A test fails if the demo name appears anywhere in application code (`app/`, `resources/`, `routes/`, `database/`,
  other `config/` files). Only `config/shop.php` may contain it.

## ADR-014: Selectable print formats
**Status:** Accepted, Phase 1

| Document | Formats | Default |
|---|---|---|
| Parcel label | `thermal_4x6` (100×150 mm), `a5`, `a4` | `thermal_4x6` |
| Invoice | `a4`, `a5` | `a4` |

- The default format per document is a shop setting (`print.label_format`, `print.invoice_format`) that the admin
  chooses in the admin panel. The print screen also offers a per-print format override.
- Printing uses browser print with CSS `@page` sizes (one layout, `layouts/print`), which works with thermal and
  office printers alike. A PDF download of the same markup is added in Phase 8.
- On A4/A5 a label prints one per sheet, scaled up. A multi-label sheet option can be added later.
- Formats are a backed enum (`App\Enums\PrintFormat`) that knows its page size and which documents allow it.

## ADR-015: Velvet and gold palette, role-based colour names
**Status:** Accepted, Phase 2 (owner asked for a premium, modern palette)

- Actions use deep mulberry `#7B1E45` (was berry `#A3214F`). Offers use antique gold `#D6A64B` (was marigold
  `#F3A712`). Text is plum-black `#1F1424` on a porcelain page `#FAF7F8`. Status colours were retuned to match.
- Colour tokens are named by **role**, not hue: `brand`, `brand-dark`, `brand-tint`, `accent`, `accent-ink`,
  `accent-tint`. A future rebrand then changes values in `resources/css/app.css` without renaming classes.
  The status tone `berry` is now `brand`.
- Every text pairing still meets WCAG AA (table in `docs/design-system.md`).

## ADR-016: Clickable prototype on a temporary demo layer (Phase 2)
**Status:** Accepted, Phase 2. To be removed step by step in Phases 4–6.

- The customer site is built as real Livewire pages, routes and form validation on top of
  `App\Support\Demo\*`: a fixed sample catalogue (33 products), and a session-backed bag, customer
  (profile, addresses) and order history (sample orders in every status plus orders placed in the preview).
- An architecture test keeps demo classes out of domain code. Only Livewire components, the dev
  controllers, the sign-in controller and `RequireDemoCustomer` may use them.
- `RequireDemoCustomer` stands in for `auth` until Google sign-in (Phase 4). The local-only
  `/dev/ui/as/{guest|customer}` switch lets reviewers preview both states. The sign-in page's Google button uses
  it on developer machines.
- Replacement plan: Phase 4 swaps customers and addresses for models and Socialite, Phase 5 swaps the catalogue and bag,
  Phase 6 swaps orders and payments (including storing re-encoded payment screenshots). The page components keep their public
  behaviour, so the Phase 2 tests carry over with new setup helpers.
- `OrderStatus`, `PaymentStatus` and `PaymentMethod` enums were created now (ADR-006) because the screens need
  their labels, colours and rules.
- Phase 2 defaults for open client questions are recorded in `docs/client-questions.md` and live in `config/shop.php`.
- Friendly wording for image upload errors lives in `lang/en/validation.php`, which is merged over Laravel's messages.

## ADR-017: Rose Atelier theme (supersedes the values in ADR-015)
**Status:** Accepted, Phase 2. Chosen by the owner after comparing four candidates on the live site.

- Colours: rose-wine `#9B2C55` for actions, rose gold `#E8B4A0` for offers and waiting states (always with ink
  text), deep rose-brown text `#2A1A20` on a blush-white page `#FFFAFA`. Role-based token names from ADR-015 are
  unchanged.
- Type: **DM Serif Display** for headings and prices, **Figtree** for text, **Mukta** only as the Devanagari fallback.
  DM Serif Display has one weight, so faux bold is switched off (`font-synthesis-weight: none`).
- Shapes: softer corners (12 / 20 / 28 px) and a soft rose card shadow (`--card-shadow`).
- Every text pairing meets WCAG AA (see `docs/design-system.md`).

## ADR-018: Wholesale as catalogue + enquiry, and a desktop hero carousel
**Status:** The wholesale part is **superseded by ADR-019**. The hero carousel stands.

**Wholesale** (`/wholesale`, "Wholesale" tab in the category bar on every screen size, footer link):
- A public price list of bulk-ready products with **price slabs** (e.g. 5–19, 20–49, 50+), a minimum
  order quantity and the retail price for comparison. No wholesale account or separate checkout.
- Customers build an **enquiry list** (quantities never below the minimum) and send a **quote request**
  (business name and type, contact, mobile, optional email/GSTIN, delivery city and pincode, needed-by
  date, message). With no products listed, a message of at least 20 characters is required. Rate limited to
  3 enquiries per 10 minutes per session. The confirmation shows a reference (`WQ-…`) and a WhatsApp follow-up.
- Phase 2 keeps slabs and enquiries in `App\Support\Demo\DemoWholesale` (ADR-016). Later phases add wholesale
  slabs to product variants, a `wholesale_enquiries` table and an admin screen to manage enquiries.

**Home hero**:
- From desktop width (`lg`, 1024 px) up, the hero is a four-slide carousel (welcome, festive hampers, beauty
  offers, wholesale). It auto-advances every 6 s with a progress indicator and pauses on hover, keyboard focus,
  hidden tabs and a visible pause button. It never auto-plays for people who prefer reduced motion. Slides are `inert`
  when hidden and the carousel is `wire:ignore` so page updates don't reset it.
- Below desktop width the existing single hero is unchanged; phones and tablets get a wholesale card further
  down the page instead of a slide.

## ADR-019: Wholesale sells through the ordinary bag; a quote is optional
**Status:** Accepted, Phase 2 (owner request, supersedes the wholesale part of ADR-018)

Wholesale is **not a separate ordering process**. It is the same shop, at quantity prices.

**Slab prices apply automatically.** A product that has wholesale slabs is priced at the shop's retail price
below its minimum quantity and at the matching slab price at or above it. There is no wholesale mode, no second
bag and no duplicate line for the same product, and a bulk quantity of a product reached from a category page
earns the same price as one reached from `/wholesale`. The bag line shows the slab it is on, the saving against
retail and what the next slab would cost ("add 30 more to pay ₹840 each"); a product page shows the same slabs
under "Buying in bulk?".

- Anyone can see and order at wholesale prices. No wholesale account, approval or GSTIN is required, and
  sign-in is only needed at checkout, exactly as for a retail order.
- A wholesale line is **not limited by shelf stock** (the shop orders bulk quantities in) and is capped at
  `DemoWholesale::MAX_QUANTITY` (10,000). Retail lines keep the per-line limit of 10 and the stock check. A line
  above the shelf count is marked "ordered in for you".
- Checkout, payment (COD/UPI with manual verification), invoices, labels, delivery and tracking are unchanged.
  Order items snapshot the price actually charged plus a `wholesale` flag, so invoices and the order history
  show what the customer paid.
- **Cash on delivery stops at ₹20,000** (`shop.payment.cod_max_paise`, admin-editable later). Above it only UPI
  is offered, in the bag, at checkout and in a server-side check when the order is placed. Zero removes the ceiling.

**Quote requests stay, as an optional feature** at `/wholesale/quote` (linked from the wholesale page, the bag
and the footer, never as the main call to action). They are for what the price list cannot answer: custom
hampers or packing, branding, quantities beyond the slabs, a supply arrangement or payment terms. The form asks
what is needed (message of at least 20 characters, always required), the business details (name and type,
contact, mobile, optional email/GSTIN, delivery city and pincode, needed-by date) and can **attach the current
bag** for context. Attaching neither orders nor empties the bag. Rate limited to 3 requests per 10 minutes per
session; the confirmation shows a `WQ-…` reference and a WhatsApp follow-up. The separate enquiry list from
ADR-018 is gone: the bag is the only list a customer manages.

Phase 2 keeps this in `App\Support\Demo\DemoWholesale` and `DemoCartLine` (ADR-016). Later phases add a
`price_slabs` table on product variants (min quantity, unit price in paise), an `is_wholesale` flag on order
items, and a `wholesale_enquiries` table with an admin screen.

## ADR-020: Delivery steps are separate from the order status
**Status:** Accepted, Phase 3 (owner decision)

The delivery panel (`/delivery`, phone-first, its own sign-in) has more steps than the customer's timeline.

- **Order status stays as it is**: Ready for delivery → Out for delivery → Delivered / Delivery failed. The
  delivery boy's own steps live in `App\Enums\DeliveryStep` (assigned, accepted, picked_up, reached, delivered,
  failed) as timestamps on the assignment. Only the milestones move the order: **picked up** makes it Out for
  delivery, **delivered** and **failed** end it. Accepting and reaching the address change nothing the customer
  sees, so their timeline stays short while the shop keeps the detail.
- **Delivery is confirmed with the customer's 6-digit code**, at most 3 tries per order, after which the delivery
  boy has to call the shop. For a COD order the cash collected is entered and must match the amount due; a
  customer who cannot pay is a failed delivery, not a short payment (`DeliveryFailureReason::NoCash`).
- **Failures carry a reason** (`App\Enums\DeliveryFailureReason`), each with wording written for the customer's
  order page, and a note that is required when the reason alone is not enough (wrong address, rescheduled).
- **Cash to hand over** is shown in the panel: collected today, already settled and what the boy is still
  holding. The admin confirms the handover in Phase 9; the panel only reports.
- Screens: sign-in, today's round, one delivery, cash to hand over, history of finished deliveries, profile with
  sign out, plus a bottom navigation. The panel is phone-only (`max-w-lg`), with the one next action pinned
  above the navigation in thumb reach.
- Phase 3 keeps all of this in `App\Support\Demo\DemoDeliveries`, `DemoDeliveryJob` and `DemoDeliveryBoy`
  (ADR-016), with `RequireDemoDeliveryBoy` standing in for auth. Phase 4 replaces the sign-in with a staff
  account and the `delivery` role; Phase 9 replaces the data with `delivery_assignments` and the delivery
  actions, and adds COD settlement. The screens and their tests carry over.

## ADR-021: Packages, pickup codes and the delivery OTP
**Status:** Accepted, Phase 3 (owner request, extends ADR-020)

Two different codes guard the two ends of a delivery, and neither is ever shown in the delivery panel:

| Code | Belongs to | Printed / shown on | Confirms |
|---|---|---|---|
| **Pickup code** (6 digits, one per box) | the shop | the box's own label | that the delivery boy is carrying that box |
| **Delivery OTP** (6 digits, one per order) | the customer | their order page, only while the parcel is on the way | that the parcel reached the right person |

- **The shop packs an order into one or more boxes.** Each box gets a package id (`PKG-10245-1`) and its own
  pickup code, both printed on its label together with "Box 1 of 2" and what is inside that box. Labels print
  one per box.
- **Pickup is per box.** At the counter the delivery boy types the code on each label. The order only becomes
  `Out for delivery` when **every** box is verified, so a half-collected order cannot leave the shop. Until
  then the panel shows "1 of 2 verified" and the customer sees "1 of 2 collected from the shop".
- **Wrong codes are limited**: 3 tries per order for pickup (after which the shop has to check the boxes) and
  3 tries per order for the delivery OTP (after which the delivery boy calls the shop). A correct pickup code
  clears the counter, so an honest mistake on one box does not lock the whole round.
- **A code that belongs to another box of the same order is refused**, which is what makes the check worth
  doing: it catches a box picked up by mistake.
- **Delivery needs the OTP and, for COD, the full cash.** A customer who cannot pay is a failed delivery
  (`DeliveryFailureReason::NoCash`), never a short payment.
- **A failed delivery counts the boxes to take back**, shown in the panel and to the shop.
- The customer sees: their delivery partner's name and phone once assigned, how many boxes the order was
  packed into and how many have been collected, the OTP only while the parcel is on the way (never before
  pickup or after delivery), and the reason in their own words if a delivery fails.

Phase 3 keeps packages in `App\Support\Demo\DemoPackage` and `DemoDeliveries` (ADR-016). Phase 8 prints real
labels per package and Phase 9 adds the `order_packages` table, hashed codes and the delivery actions.
