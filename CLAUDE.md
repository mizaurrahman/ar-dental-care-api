# CLAUDE.md — AR Dental Care

Instructions for AI coding tools working on this repository.

## Product

Single-doctor dental clinic system branded **AR Dental Care**:

- Doctor auth (email or Bangladesh phone + OTP / email verification)
- Dashboard, appointments, availability configuration, clinic profile + appointment QR
- Pad-style prescriptions with medicine search, edit, print, email/WhatsApp/public share link
- Public patient appointment (`/appointment/:slug`) with email + SMS confirmation (log drivers by default)

## Stack

| Layer | Tech |
|-------|------|
| API | Laravel 13, Sanctum bearer tokens, Eloquent, SQLite or MySQL |
| SPA | React + Vite + Tailwind + React Router + Axios |
| Optional | Oushodh medicines API (`OUSHODH_KEY`), BD SMS gateway |

## Layout

```
backend/                 Laravel API
  app/Http/Controllers/Api/
  app/Models/
  app/Services/          SlotService, SmsService
  app/Support/BdPhone.php
  database/migrations/
  database/seeders/
frontend/                React SPA
  src/pages/
  src/components/
  src/context/AuthContext.jsx
  src/lib/api.js
HowToSetup.md            Clean-machine setup (source of truth for install)
docs/DEPLOYMENT.md       Docker + VPS
```

## Conventions

1. Keep frontend and backend separate; talk only over REST + Bearer token.
2. Scope every doctor-owned query by `user_id`. Use `abort_unless($model->user_id === $request->user()->id, 403)` on show/update/delete.
3. Bangladesh phones only — normalize with `App\Support\BdPhone`.
4. Never commit real secrets. Use `.env.example` placeholders only.
5. Demo credentials belong in seeders/docs, not production `.env`.
6. Prefer Eloquent (parameterised) over raw SQL. Dashboard SQLite chart uses `strftime`; for MySQL prefer `DAYOFWEEK` if you change DB (Docker uses a compatible approach or seed chart client-side if needed).
7. Public routes: `public/appointment/{slug}`, `public/prescriptions/{token}` — no auth.
8. After schema changes: update migrations + seeders + feature tests.

## Common tasks

- New API endpoint: controller method + `routes/api.php` + ownership check + feature test.
- UI screen: page under `frontend/src/pages` + route in `App.jsx` + sidebar link in `DoctorLayout.jsx` if doctor-facing.
- Medicines: local `medicines` table; sync via `php artisan medicines:sync` when `OUSHODH_KEY` is set.

## Do not

- Scrape medex.com.bd inside the product.
- Add multi-tenant billing or payment gateways without explicit buyer request (out of v1 scope).
- Store plaintext passwords or API keys in the repo.
