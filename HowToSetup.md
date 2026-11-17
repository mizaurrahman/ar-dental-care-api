# HowToSetup — AR Dental Care

Clean-machine setup for buyers and reviewers. Follow this file alone to restore, build, and start the product.

## What you get

- `backend/` — Laravel 13 REST API (PHP 8.2+, Sanctum)
- `frontend/` — React 19 + Vite + Tailwind SPA
- Demo doctor account after seeding
- Local SQLite (dev) or MySQL/MariaDB (Docker / production)

## Requirements

| Tool | Version |
|------|---------|
| PHP | 8.2+ with `pdo_sqlite` or `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo` |
| Composer | 2.x |
| Node.js | 20+ (24 recommended) |
| npm | 10+ |
| MySQL 8 / MariaDB 10.6+ | Optional for local; required for Docker compose stack |

## Demo login (after seed)

- Email: `doctor@ardental.local`
- Password: `password`
- Phone: `01700000000`
- Public appointment: `/appointment/ar-dental-care`

## Option A — Local (SQLite, fastest)

### 1. Backend

```bash
cd backend
cp .env.example .env
# APP_KEY empty is fine; next command generates it
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

API: http://127.0.0.1:8000

### 2. Frontend

```bash
cd frontend
cp .env.example .env
npm ci
npm run dev -- --host 127.0.0.1 --port 5173
```

App: http://127.0.0.1:5173

`frontend/.env` must point at the API:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

Also set in `backend/.env`:

```env
FRONTEND_URL=http://127.0.0.1:5173
APP_URL=http://127.0.0.1:8000
```

## Option B — Docker Compose (MySQL)

From the repository root:

```bash
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
# Edit backend/.env: set DB_* for docker (see comments), APP_KEY will be generated on first boot
docker compose up -d --build
```

Defaults:

- Frontend: http://localhost:8080
- API: http://localhost:8000
- MySQL: localhost:3306 (`ardental` / `ardental` / `ardental`)

First boot runs `migrate --seed` inside the API container.

## Production notes

1. Set `APP_DEBUG=false`, strong `APP_KEY`, real `DB_*`, SMTP, and optional `OUSHODH_KEY` / SMS gateway.
2. Build frontend: `cd frontend && npm ci && npm run build` and serve `frontend/dist` behind nginx (or use the Docker frontend image).
3. Point `FRONTEND_URL` and `VITE_API_URL` at your public domains.
4. Run `php artisan storage:link` and ensure `storage/` and `bootstrap/cache` are writable.
5. Email/SMS default to `log` drivers so the demo works without third-party accounts.

## Optional: sync medicines from Oushodh

```bash
# backend/.env
OUSHODH_KEY=your_key_here
php artisan medicines:sync
```

Without a key, the seeded Bangladesh medicine catalog is enough for prescriptions.

## Smoke test checklist

1. Open `/login` → sign in with demo credentials.
2. Dashboard loads stats and appointment QR.
3. Open `/appointment/ar-dental-care` → pick a slot with a BD phone (`01XXXXXXXXX`).
4. Create a prescription → add medicine from search → save → copy share link `/rx/:token`.
5. Log out; public Rx and appointment pages still work without login.

## Tests

```bash
cd backend
php artisan test
```

## Support files

- `README.md` — product overview
- `CLAUDE.md` / `AGENTS.md` — architecture for AI coding tools
- `docs/DEPLOYMENT.md` — VPS and Docker deployment detail
- `LISTING.md` — suggested Vibe96 listing copy
