# AGENTS.md — AR Dental Care

Short agent brief for Cursor / Claude Code / Codex.

## Goal

Ship a sellable, self-hosted dental clinic app (Laravel API + React SPA) that passes marketplace audits: no secrets, scoped authorization, reproducible setup from `HowToSetup.md`, Docker + VPS docs.

## Before coding

1. Read `HowToSetup.md` and `CLAUDE.md`.
2. Prefer existing patterns in `backend/app/Http/Controllers/Api` and `frontend/src/pages`.
3. Run `cd backend && php artisan test` after API changes.
4. Never commit `.env`, `vendor/`, `node_modules/`, or `composer.phar`.

## Auth model

- Doctors register with email **or** BD phone, then verify.
- Sanctum personal access tokens for SPA / future mobile.
- Patients have no accounts; appointment and Rx views are public token/slug based.

## Ownership rule

Any `Patient`, `Appointment`, `Prescription`, `AvailabilityRule`, or `AvailabilityException` mutation must verify `user_id` matches the authenticated doctor.

## Setup commands (local)

```bash
# API
cd backend && cp .env.example .env && composer install && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed && php artisan serve

# SPA
cd frontend && cp .env.example .env && npm ci && npm run dev
```

## Vibe96 compliance checklist

- [ ] `HowToSetup.md` works on a clean machine
- [ ] `CLAUDE.md` + `AGENTS.md` present
- [ ] At least two deploy paths documented (`docs/DEPLOYMENT.md`)
- [ ] No real keys in the archive
- [ ] Feature tests cover login + IDOR denial
- [ ] Demo login documented for live demo
