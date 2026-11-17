# Deployment — AR Dental Care API

Three paths: Docker/VPS MySQL, manual VPS, and **free PaaS** (Render + Cloudflare Pages for the SPA).

## Target 1 — Docker (MySQL)

Use the product monorepo `docker-compose.yml`, or build this repo’s `Dockerfile` with MySQL env vars (`DB_CONNECTION=mysql`, etc.).

## Target 2 — Manual VPS

PHP 8.2–8.4 FPM, Composer, MySQL 8, nginx → `public/`.

```bash
cp .env.example .env
# APP_URL, FRONTEND_URL, DB_*, APP_DEBUG=false
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan config:cache && php artisan route:cache
```

## Target 3 — Free PaaS (Render API + Cloudflare Pages SPA)

No paid domain. Free subdomains:

| Layer | Host | Example |
|-------|------|---------|
| This API + Postgres | Render | `https://ar-dental-care-api.onrender.com` |
| SPA | Cloudflare Pages | `https://ar-dental-care-web.pages.dev` |

Web repo: [ar-dental-care-web](https://github.com/mizaurrahman/ar-dental-care-web).

### Deploy this API

1. `php artisan key:generate --show` → copy `base64:…`.
2. Render → **New** → **Blueprint** → this repository ([`render.yaml`](../render.yaml)).
3. Set env:
   - `APP_KEY` = `base64:…`
   - `APP_URL` = `https://YOUR-SERVICE.onrender.com`
   - `FRONTEND_URL` = `https://YOUR-PROJECT.pages.dev` (after Pages deploy)
4. Free Postgres is provisioned; `DATABASE_URL` is injected. Boot migrates + seeds.
5. Health: `GET /up`.

### Deploy the SPA

1. Cloudflare Pages → connect `ar-dental-care-web`.
2. Build: `npm ci && npm run build` → output `dist`.
3. Env: `VITE_API_URL=https://YOUR-SERVICE.onrender.com/api`.
4. Paste Pages URL into Render `FRONTEND_URL` and redeploy API once.

### URLs / CORS

- `FRONTEND_URL` → appointment QR + Rx share links.
- `APP_URL` → absolute avatar/logo URLs.
- `config/cors.php` allows `FRONTEND_URL` and free-host patterns (`*.pages.dev`, etc.).
- Optional `CORS_ALLOWED_ORIGINS` for extra preview hosts.

### Limits

- Free Render **sleeps** when idle (cold start 30–60s).
- Uploaded files on free disk are ephemeral.

## Smoke test

1. Pages login: `doctor@ardental.local` / `password`.
2. Public booking + Rx share links use the Pages host (not localhost).
