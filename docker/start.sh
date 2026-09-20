#!/usr/bin/env bash
set -e

echo "Starting deployment process..."

# Cache configuration, routes, and views
echo "Caching Laravel configuration..."
php artisan optimize

# Create storage symlink if it doesn't exist
echo "Creating storage symlink..."
php artisan storage:link || true

# Check if arguments were passed
if [ $# -eq 0 ]; then
    # No arguments - run the web server (Nginx + PHP-FPM via Supervisor)
    echo "Starting Web Server (Nginx + PHP-FPM)..."
    
    # We could also run migrations here automatically on Web Server start,
    # but for safety in multi-instance setups, it's better to run them manually
    # or via a dedicated release phase in Render.
    # Uncomment the next line if you want auto-migrations on start:
    php artisan migrate --force || true

    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
else
    # Arguments passed - execute the custom command instead
    # This is useful for Render Background Workers (e.g. `php artisan queue:work`)
    # and Cron Jobs (e.g. `php artisan schedule:run`)
    echo "Executing custom command: $@"
    exec "$@"
fi
