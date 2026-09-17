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
