# Shop Platform

E-commerce and delivery management platform for a Cosmetics, Confectionery and Gifts business:
online shop, manual UPI/COD payment verification, billing, packing labels and multi-delivery-boy management.

**Stack:** Laravel 13 · PHP 8.4 · Livewire 4 · Tailwind CSS 4 · Filament 5 · MySQL · Pest · Larastan

| Doc | What's inside |
|---|---|
| [docs/requirements.md](docs/requirements.md) | Scope, roles, flows, phase plan |
| [docs/decisions.md](docs/decisions.md) | Architecture decisions (ADRs) |
| [docs/erd.md](docs/erd.md) | Draft database design and status machines |
| [docs/design-system.md](docs/design-system.md) | Colours, type, components, layouts, printing, accessibility |
| [docs/client-questions.md](docs/client-questions.md) | Open questions for the client |
| [docs/git-workflow.md](docs/git-workflow.md) | Branches, commits, PRs, releases |
| [docs/security.md](docs/security.md) | Security checklist |
| [docs/deployment.md](docs/deployment.md) | VPS setup and deploy steps |
| [CLAUDE.md](CLAUDE.md) | Working rules for developers and AI agents |

## Local setup (Windows)

### Requirements
- PHP **8.3+** with `bcmath curl exif fileinfo gd intl mbstring openssl pdo_mysql zip`
  (this machine: portable PHP 8.4 in `D:\dev-tools\php84`)
- Composer 2 (this machine: `D:\dev-tools\composer`)
- MySQL 8.4 or MariaDB 10.4+ (this machine: XAMPP MariaDB, start it from the XAMPP Control Panel)
- Node.js 22/24 LTS + npm
- Git

### First run
```bash
composer install
cp .env.example .env            # then set DB_PASSWORD
php artisan key:generate
```

Create the databases (once), using the XAMPP MySQL client:
```sql
CREATE DATABASE shop_platform      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE shop_platform_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'shop_app'@'localhost' IDENTIFIED BY '<password>';
CREATE USER 'shop_app'@'127.0.0.1' IDENTIFIED BY '<password>';
GRANT ALL ON shop_platform.*      TO 'shop_app'@'localhost';
GRANT ALL ON shop_platform_test.* TO 'shop_app'@'localhost';
GRANT ALL ON shop_platform.*      TO 'shop_app'@'127.0.0.1';
GRANT ALL ON shop_platform_test.* TO 'shop_app'@'127.0.0.1';
```

Create `.env.testing` (gitignored) with `APP_ENV=testing`, the same `APP_KEY`, and
`DB_DATABASE=shop_platform_test` plus the same DB credentials. Then:

```bash
php artisan migrate
npm ci --ignore-scripts
npm run build
```

### Daily commands
| Command | What it does |
|---|---|
| `php artisan serve` + `npm run dev` | Run the app at http://localhost:8000 with hot reload (or `composer dev`) |
| `composer test` | Pest test suite (MySQL test DB) |
| `composer lint` | Auto-fix code style (Pint) |
| `composer lint:check` | Check code style only |
| `composer analyse` | Static analysis (Larastan) |
| `composer check` | Everything CI runs: style + analysis + tests |

### Customer site preview (Phase 2, sample data)
Open `/` to browse. Everything runs on sample data kept in your browser session (ADR-016).
Sign in as the sample customer with the Google button on `/login`, or directly with `/dev/ui/as/customer`
(`/dev/ui/as/guest` signs out). Sample orders cover every status: `/account/orders`.

### Design system preview (local only)
With the app running, open `/dev/ui` for every component, `/dev/ui/delivery` and `/dev/ui/sign-in` for the
other layouts, and `/dev/ui/print/label?format=thermal_4x6|a5|a4` or `/dev/ui/print/invoice?format=a4|a5` for
print previews. These routes don't exist in production.

## Project structure
```
app/
  Actions/{Cart,Orders,Payments,Inventory,Delivery}/   business operations (one class = one operation)
  Enums/                 statuses, roles, payment methods
  Filament/              admin panel (Phase 7)
  Http/Controllers/      non-Livewire routes (invoice PDF, payment proof stream)
  Livewire/{Shop,Cart,Checkout,Account,Delivery}/      class-based Livewire components
  Models/  Policies/  Services/  Support/  Events/  Listeners/  Notifications/
resources/views/
  components/{ui,shop}/  Blade UI components
  layouts/               shop, account, delivery, print layouts
  livewire/              views for app/Livewire
  pdf/                   invoice and label templates
docs/                    project documentation
.github/workflows/ci.yml Pint + Larastan + Pest on MySQL 8.4
```

## Status
- **Phase 0 (foundation):** complete.
- **Phase 1 (design system):** complete, see [docs/design-system.md](docs/design-system.md).
- **Phase 2 (customer UI with dummy data):** complete, waiting for review before merging.
- **Phase 3 (delivery panel UI with dummy data):** starts after approval.
