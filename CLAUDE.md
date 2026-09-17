# Shop Platform: Working Rules

Laravel 13 · PHP 8.4 · Livewire 4 (class-based) · Tailwind 4 · Filament 5 (Phase 7) · MySQL · Pest 5 · Larastan 3.
E-commerce + UPI/COD payment verification + billing + packing + multi-delivery management.
Read `docs/requirements.md`, `docs/decisions.md` and `docs/erd.md` before changing behaviour or schema.

## Process
- Work is **phase-gated** (see the phase table in `docs/requirements.md`). Stay inside the approved phase. After
  planning a phase, stop and wait for the owner's approval. Do not start the next phase on your own.
- Order: **frontend first** (design system, then customer UI and delivery UI with dummy data), then the backend.
- Record new architectural decisions as an ADR in `docs/decisions.md`. Record client answers in `docs/client-questions.md`.
- Before finishing any change, run `composer check` (Pint, Larastan, Pest) and fix everything.

## Environment (this Windows machine)
- PHP 8.4 and Composer are portable: `D:\dev-tools\php84\php.exe`, `D:\dev-tools\composer\composer(.bat)`
  (on the user PATH. In Git Bash use `export PATH="/d/dev-tools/php84:/d/dev-tools/composer:$PATH"`).
- XAMPP's own PHP 8.0 is **not** used. XAMPP provides **MariaDB 10.4** only (`C:\xampp\mysql`), and it must be running.
- DBs: `shop_platform` (dev) and `shop_platform_test` (tests, via `.env.testing`), user `shop_app`.
- Production and CI use MySQL 8.4, so avoid MySQL-8-only or MariaDB-only SQL.
- `.npmrc` has `ignore-scripts=true`. Use `npm ci --ignore-scripts`.

## Architecture rules
- **Modular monolith.** Group code by area: Cart, Orders, Payments, Inventory, Billing, Delivery.
- **Business logic lives in Actions**: `app/Actions/<Area>/<VerbNoun>.php`, `final`, one public `handle()`.
  Livewire components, Filament resources and controllers stay thin and call Actions.
- Livewire components are class-based in `app/Livewire/<Area>` with views in `resources/views/livewire/<area>`
  (`php artisan make:livewire Shop/ProductList`).
- Reusable UI goes in anonymous Blade components: `resources/views/components/ui/*` (generic),
  `components/shop/*` (domain). Build UI from these, and don't repeat long Tailwind class lists.
- All statuses, roles and methods are **backed enums** in `app/Enums`. No raw status strings.
- Order status changes only go through an Action that checks `OrderStatus::canTransitionTo()` and writes
  `order_status_histories`. Order status and payment status are separate.
- **Money is integer paise** in `*_paise` columns. Format only in views. Never use floats for money.
- Copy product name, SKU and price into `order_items`, and the delivery address into `orders`.
- Stock changes run in `DB::transaction()` with `lockForUpdate()` and write `inventory_movements`.
- Totals are always computed server-side from DB prices.

## Auth & security rules
- Customers: **Google sign-in only** (Socialite). Match on `google_id`. `password` nullable. Phone required before the first order.
- Admin: email + password + 2FA (Filament). Delivery boys: password accounts created by the admin. Staff never use Google.
- Authorize everything: policies + role middleware, and scope queries to the owner (customer or delivery boy).
- Validate all input server-side. Use `{{ }}` only. Every model has explicit `$fillable`. Use `#[Locked]` for sensitive Livewire props.
- Private files (payment proofs) live on the `local` disk (`serve => false`) and are streamed only by policy-checked controllers.
- Rate-limit logins, the OAuth callback, checkout, uploads and OTP. OTPs are stored hashed with an expiry and an attempt limit.
- Never commit secrets or `.env*` files (except `.env.example`). Never use `dd()`/`dump()` in committed code (arch test enforces this).
- Full checklist: `docs/security.md`.

## Code style
- Pint `laravel` preset (`composer lint`). Larastan level 6 (`composer analyse`). Pest tests (`composer test`).
- Type-hint parameters and return types. Use constructor property promotion.
- Tests: feature tests in `tests/Feature` (RefreshDatabase, MySQL test DB), arch rules in `tests/Unit/ArchTest.php`.
  Every Action and policy gets tests.
- Mobile-first UI (360px wide first), accessible labels, and loading states on Livewire actions.

## Git
- Branches: `main` (prod), `develop`, `feature/<area>-<name>`, `fix/…`, `hotfix/…`. Conventional Commits.
- Commit or push only when asked. Details are in `docs/git-workflow.md`.

## Optional tooling
Laravel ships a stub suggesting **Laravel Boost** (`composer require laravel/boost --dev && php artisan boost:install`),
which adds an MCP server and Laravel-specific agent guidelines. It is not installed yet. If it is added later, keep these
project rules and merge Boost's generated guidelines below them.
