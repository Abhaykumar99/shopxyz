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
- **Never hardcode the shop name or shop details.** Read them from `App\Support\ShopSettings` (shared with views as `$shop`,
  e.g. `{{ $shop->name }}`). The demo default lives only in `config/shop.php` (ADR-013). `APP_NAME` is never shown to customers.
- Print formats come from `App\Enums\PrintFormat` (labels: 4×6" thermal / A5 / A4; invoices: A4 / A5). The admin picks the
  default, with a per-print override (ADR-014).

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

## AI tooling
- **Laravel Boost** (dev dependency) provides the `laravel-boost` MCP server (`.mcp.json`) and the generated guidelines
  below. Config: `boost.json` (agent: Claude Code) and `config/boost.php` (Laravel Cloud guideline/skill excluded).
  Project-specific Boost guidelines live in `.ai/guidelines/`. After a package upgrade run
  `php artisan boost:update --no-discover` and keep this top section unchanged.
- **Skills** in `.claude/skills/`:
  - Boost-generated: `laravel-best-practices`, `livewire-development`, `tailwindcss-development`,
    `testing-best-practices`, `infer-conventions`
  - Added via `npx skills` (tracked in `skills-lock.json`, update with `npx skills update -p`): `frontend-design`,
    `tailwind-design-system`, `laravel-security`, `accessibility`
- **Precedence:** the project rules above win over generic skill or Boost advice. Examples: Livewire stays class-based even if
  a skill shows single-file components; `docs/` is kept up to date as described in Process; auth follows ADR-004,
  not Sanctum/Breeze examples.

===

<laravel-boost-guidelines>
=== .ai/deployment rules ===

# Deployment

- Production runs on a **Hostinger VPS** (Ubuntu, Nginx, PHP 8.4-FPM, MySQL 8.4, Supervisor queue worker, cron). Laravel Cloud is not used.
- Follow `docs/deployment.md` for server setup, deploy steps and rollback. Production is only updated from a tagged `main`, never edited on the server.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record a rule with `record-rule` only when the user explicitly asks for one. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here" are work to do, not rules to record. Never record a rule on your own initiative, as a byproduct of a change, or to summarize what you just did. When the user does ask, pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Use `record-rule` rather than your native memory or notes tool, because native memory is personal and session-scoped, while only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
