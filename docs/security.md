# Security Checklist

Each item names the phase in which it is implemented. Tick items off in PRs as they land.

## Authentication & access
- [ ] (P4) Customers: Google sign-in only, stateful OAuth `state` check, verified email only, match on `google_id`
- [ ] (P4) Staff (admin / delivery) cannot authenticate through Google, and customers cannot log in with a password
- [ ] (P4) Inactive users (`is_active = false`) are blocked at login and on every request
- [ ] (P4) `UserRole` enum + middleware + policies. Every route, Livewire action and Filament resource is authorized
- [ ] (P7) Filament `canAccessPanel()` allows admin only. Admin 2FA (MFA) required
- [ ] (P9) Delivery boy can only view and update **their own active** assignments (policy + scoped queries)
- [ ] (P4) Customer can only see their own orders, addresses and payment proofs (policy + scoped queries)
- [ ] (P4) Session regenerated on login, invalidated on logout. Secure, HttpOnly, SameSite=Lax cookies in production

## Input & output
- [ ] Validate all input server-side (Form Requests / Livewire `#[Validate]`)
- [ ] Use `{{ }}` escaping only. `{!! !!}` is banned unless the content is sanitized and the reason is commented
- [ ] Eloquent / query builder only. Raw SQL must use bindings
- [ ] Mass assignment: explicit `$fillable` on every model
- [ ] Livewire: sensitive public properties are `#[Locked]`, and models are re-authorized in each action

## Money, stock and orders
- [ ] (P6) Amounts computed server-side from database prices. Client-sent prices are ignored
- [ ] (P6) Stock changes inside a DB transaction with `lockForUpdate()`
- [ ] (P6) Status changes only via Actions that check `canTransitionTo()` and write history
- [ ] (P6) UTR unique constraint, and the admin sees a duplicate-UTR warning
- [ ] (P6) Audit log (activitylog) for payment verification, status changes, price and stock edits

## File uploads (payment proofs, product images)
- [ ] (P6) MIME check (jpg/png/webp), max 4 MB, random filename, re-encode image (strips metadata and payloads)
- [ ] (P6) Payment proofs on the private disk (`serve => false`), streamed only via a policy-checked controller
- [ ] (P7) Product images on the public disk, validated and resized

## Abuse protection
- [ ] (P4) Rate limits: Google callback, staff login (5/min), checkout, proof upload, OTP verify (5 attempts per OTP)
- [ ] (P9) OTP: random 6 digits, stored hashed, 15-minute expiry, single use

## Secrets & configuration
- [x] (P0) `.env`, `.env.testing`, `.env.production` gitignored
- [x] (P0) App uses its own DB user (`shop_app`), not root
- [ ] (P10) Production: `APP_ENV=production`, `APP_DEBUG=false`, strong `APP_KEY`, `SESSION_SECURE_COOKIE=true`
- [ ] (P10) `.env` permissions `600`, owned by the deploy user
- [ ] (P4) Google OAuth client secret only in `.env`, with production and local clients kept separate

## Server (Phase 10)
- [ ] SSH: key-only, `PermitRootLogin no`, dedicated `deploy` user with sudo limited as needed
- [ ] UFW: allow 22, 80, 443 only. MySQL bound to 127.0.0.1
- [ ] Fail2ban for SSH (and Nginx auth, optional)
- [ ] HTTPS with Let's Encrypt, HTTP redirects to HTTPS, HSTS
- [ ] Security headers: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, basic CSP
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
