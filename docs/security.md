# Security Checklist

Each item names the phase in which it is implemented, using the phase numbers in
[requirements.md](requirements.md) §13 as renumbered by ADR-023. Tick items off in PRs as they land.

## Authentication & access
- [x] (P6) Customers: Google sign-in only, stateful OAuth `state` check, verified email only, match on `google_id`
- [x] (P6) Staff (admin / delivery) cannot authenticate through Google, and customers cannot log in with a password
- [x] (P6) Inactive users (`is_active = false`) are blocked at login and on every request
- [ ] (P6) `UserRole` enum + middleware + policies. Every route, Livewire action and Filament resource is authorized
- [x] (P5/P6) Filament `canAccessPanel()` allows admin only. Admin 2FA (MFA) required in production
- [ ] (P10) Delivery boy can only view and update **their own active** assignments (policy + scoped queries)
- [ ] (P6) Customer can only see their own orders, addresses and payment proofs (policy + scoped queries)
- [x] (P6) Session regenerated on login, invalidated on logout. Secure, HttpOnly, SameSite=Lax cookies in production

## Input & output
- [ ] Validate all input server-side (Form Requests / Livewire `#[Validate]`)
- [ ] Use `{{ }}` escaping only. `{!! !!}` is banned unless the content is sanitized and the reason is commented
- [ ] Eloquent / query builder only. Raw SQL must use bindings
- [ ] Mass assignment: explicit `$fillable` on every model
- [ ] Livewire: sensitive public properties are `#[Locked]`, and models are re-authorized in each action

## Money, stock and orders
- [ ] (P8) Amounts computed server-side from database prices. Client-sent prices are ignored
- [ ] (P7) Stock changes inside a DB transaction with `lockForUpdate()`
- [ ] (P8) Status changes only via Actions that check `canTransitionTo()` and write history
- [ ] (P8) UTR unique constraint, and the admin sees a duplicate-UTR warning
- [ ] (P8) Audit log (activitylog) for payment verification, status changes, price and stock edits

## File uploads (payment proofs, product images)
- [ ] (P8) MIME check (jpg/png/webp), max 4 MB, random filename, re-encode image (strips metadata and payloads)
- [ ] (P8) Payment proofs on the private disk (`serve => false`), streamed only via a policy-checked controller
- [ ] (P7) Product images on the public disk, validated and resized

## Abuse protection
- [x] (P6) Rate limits: Google callback and staff login (5/min). Checkout, proof upload and OTP verify follow in P8 and P10
- [ ] (P10) OTP: random 6 digits, stored encrypted and compared with `hash_equals` (ADR-021 as corrected in ADR-024), with an attempt limit

## Secrets & configuration
- [x] (P0) `.env`, `.env.testing`, `.env.production` gitignored
- [x] (P0) App uses its own DB user (`shop_app`), not root
- [ ] (P11) Production: `APP_ENV=production`, `APP_DEBUG=false`, strong `APP_KEY`, `SESSION_SECURE_COOKIE=true`
- [ ] (P11) `.env` permissions `600`, owned by the deploy user
- [x] (P6) Google OAuth client secret only in `.env`, with production and local clients kept separate

## Server (Phase 10)
- [ ] SSH: key-only, `PermitRootLogin no`, dedicated `deploy` user with sudo limited as needed
- [ ] UFW: allow 22, 80, 443 only. MySQL bound to 127.0.0.1
- [ ] Fail2ban for SSH (and Nginx auth, optional)
- [ ] HTTPS with Let's Encrypt, HTTP redirects to HTTPS, HSTS
- [x] (P6) Security headers on every response: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS over HTTPS, and a **report-only** CSP with a logging endpoint. An enforcing CSP waits for the Alpine CSP build, because Livewire's inline scripts and Alpine's `x-data` need `unsafe-inline`/`unsafe-eval` today
- [ ] Nginx web root = `public/` only. Block dotfiles
- [ ] Automatic security updates (`unattended-upgrades`)
- [ ] GitHub **read-only deploy key** on the VPS

## Backups & monitoring (Phase 10)
- [ ] Daily DB + storage backup (spatie/laravel-backup) to off-server storage, 14–30 day retention
- [ ] Restore tested at least once before go-live
- [ ] Failed-job and error log monitoring, plus disk space alerts

## Local development note
XAMPP MariaDB `root` has **no password** (XAMPP default). It listens locally only. Do not expose port 3306,
and consider setting a root password (then update phpMyAdmin's config).
