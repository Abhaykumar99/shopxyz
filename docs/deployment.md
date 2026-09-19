# Deployment (outline; finalized in Phase 10)

## Target server
Hostinger VPS, Ubuntu LTS, Nginx, PHP 8.4-FPM (bcmath, curl, exif, gd, intl, mbstring, mysql, xml, zip),
MySQL 8.4, Composer 2, Node 24 LTS (build only), Git, Certbot, Supervisor (queue worker), cron.

## One-time server setup
1. Create a `deploy` user, add your SSH public key, disable password and root SSH login.
2. UFW (22/80/443), Fail2ban, unattended-upgrades.
3. Install the stack above. Create MySQL database `shop_platform` and user `shop_app` (localhost only).
4. Generate an SSH key for `deploy` and add it to GitHub as a **read-only deploy key**.
5. `git clone git@github.com:<org>/<repo>.git /var/www/shop` (branch `main`).
6. Create `.env` from `.env.example` with production values, `chmod 600 .env`, then `php artisan key:generate`.
   The production values that must differ from the example: `APP_ENV=production`, `APP_DEBUG=false`,
   `APP_URL=https://<domain>`, `LOG_LEVEL=warning` and `SESSION_SECURE_COOKIE=true`. The seeders that carry demo
   passwords and sample orders refuse to run when `APP_ENV=production`.
7. Nginx server block with root `/var/www/shop/public`, then Certbot SSL.
8. `storage/` and `bootstrap/cache/` writable by the PHP-FPM user.
9. Cron: `* * * * * cd /var/www/shop && php artisan schedule:run >> /dev/null 2>&1`
10. Supervisor: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`
11. Google OAuth: add the production callback `https://<domain>/auth/google/callback` to the production OAuth client.

## Manual deploy (initial)
```bash
cd /var/www/shop
php artisan down --retry=30
git fetch --tags && git checkout <tag>        # e.g. v1.0.0
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --ignore-scripts && npm run build
php artisan migrate --force
php artisan optimize                           # config, route, view, event cache
php artisan filament:optimize                  # after Phase 7
php artisan storage:link                       # first deploy only
php artisan queue:restart
sudo systemctl reload php8.4-fpm
php artisan up
```
A `deploy.sh` script will wrap these steps in Phase 10.

## Later: automated deploy
A GitHub Actions job runs on a tag push to `main` after CI passes. It connects over SSH with a
restricted key stored in GitHub Secrets and runs `deploy.sh`.

## Rollback
`git checkout <previous-tag>`, then repeat the deploy steps. Restore the database from backup only if a migration
was destructive (destructive migrations need a written rollback plan in the PR).
