# Deployment

- Production runs on a **Hostinger VPS** (Ubuntu, Nginx, PHP 8.4-FPM, MySQL 8.4, Supervisor queue worker, cron). Laravel Cloud is not used.
- Follow `docs/deployment.md` for server setup, deploy steps and rollback. Production is only updated from a tagged `main`, never edited on the server.
