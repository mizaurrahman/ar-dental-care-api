# AR Dental Care

Self-hosted dental clinic software: appointments, public appointment link/QR, and pad-style prescriptions with Bangladesh medicine search.

Built for sale on [Vibe96](https://vibe96.com/) — see `HowToSetup.md` for clean-machine install (audit source of truth).

## Stack

- **Backend:** Laravel 13 + Sanctum (REST, bearer tokens)
- **Frontend:** React + Vite + Tailwind
- **Database:** SQLite (local), MySQL 8 (Docker / VPS), or Postgres (Render free)

This repository is the **API only**. Pair with [ar-dental-care-web](https://github.com/mizaurrahman/ar-dental-care-web).

## Quick start

Follow **[HowToSetup.md](HowToSetup.md)**. Short version:

```bash
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Demo login: `doctor@ardental.local` / `password`

## Free deploy — Render (API + Postgres)

1. Generate a key locally: `php artisan key:generate --show` (copy the `base64:…` value).
2. In [Render](https://render.com) → **New** → **Blueprint** → connect this repo (`render.yaml`).
3. Set env vars when prompted:
   - `APP_KEY` = the `base64:…` value
   - `APP_URL` = `https://ar-dental-care-api.onrender.com` (or your service URL)
   - `FRONTEND_URL` = your Cloudflare Pages URL, e.g. `https://ar-dental-care-web.pages.dev`
4. Deploy. Health check: `GET /up`.
5. On the web repo, set Pages build env `VITE_API_URL=https://YOUR-API.onrender.com/api` and redeploy.

Free tier sleeps when idle; first request can take ~30–60s. Uploaded avatars/logos on free disk are ephemeral.

## Docker

See monorepo `docker-compose.yml` or build this `Dockerfile` alone.

## Documentation

| File | Purpose |
|------|---------|
| `HowToSetup.md` | Clean install (required for Vibe96) |
| `CLAUDE.md` / `AGENTS.md` | AI coding agent map |
| `docs/DEPLOYMENT.md` | Docker + VPS + free PaaS |
| `LISTING.md` | Marketplace listing draft |
| `render.yaml` | Render Blueprint (free API + Postgres) |

## Security notes (marketplace)

- No real API keys in the archive — configure via `.env`
- Doctor-owned records are scoped by `user_id`
- Auth and public appointment endpoints are rate-limited
- Email/SMS default to `log` drivers for safe demos

## Licence

You own this project. When listing on Vibe96, buyers receive the Vibe96 Standard License; you keep copyright.
