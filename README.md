# AR Dental Care

Self-hosted dental clinic software: appointments, public appointment link/QR, and pad-style prescriptions with Bangladesh medicine search.

Built for sale on [Vibe96](https://vibe96.com/) — see `HowToSetup.md` for clean-machine install (audit source of truth).

## Stack

- **Backend:** Laravel 13 + Sanctum (REST, bearer tokens)
- **Frontend:** React + Vite + Tailwind
- **Database:** SQLite (local) or MySQL 8 (Docker / production)

## Quick start

Follow **[HowToSetup.md](HowToSetup.md)**. Short version:

```bash
# API
cd backend && cp .env.example .env && composer install
php artisan key:generate && touch database/database.sqlite
php artisan migrate --seed && php artisan serve

# SPA
cd frontend && cp .env.example .env && npm ci && npm run dev
```

Demo login: `doctor@ardental.local` / `password`  
Appointment link: http://127.0.0.1:5173/appointment/ar-dental-care

## Docker

```bash
docker compose up -d --build
```

- App: http://localhost:8080  
- API: http://localhost:8000  

## Documentation

| File | Purpose |
|------|---------|
| `HowToSetup.md` | Clean install (required for Vibe96) |
| `CLAUDE.md` / `AGENTS.md` | AI coding agent map |
| `docs/DEPLOYMENT.md` | Docker + VPS deploy paths |
| `LISTING.md` | Marketplace listing draft |

## Security notes (marketplace)

- No real API keys in the archive — configure via `.env`
- Doctor-owned records are scoped by `user_id`
- Auth and public appointment endpoints are rate-limited
- Email/SMS default to `log` drivers for safe demos

## Licence

You own this project. When listing on Vibe96, buyers receive the Vibe96 Standard License; you keep copyright.
