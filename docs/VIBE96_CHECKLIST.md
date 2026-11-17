# Vibe96 submission checklist — AR Dental Care

Mapped to [vibe96.com/sell](https://vibe96.com/sell) requirements.

## Automated audit themes

| Check | Status in this repo |
|-------|---------------------|
| 01 Secrets & credentials | `.env` gitignored; `.env.example` placeholders only; demo password only in seeder/docs |
| 02 Dependency vulnerabilities | Lockfiles committed (`composer.lock`, `package-lock.json`); run `composer audit` / `npm audit` before zip |
| 03 Authorisation | Sanctum on doctor routes; `abort_unless` ownership; feature test IDOR denial |
| 04 Database access | Eloquent parameterised queries; migrations create schema + indexes from empty DB |
| 05 Code structure | Separate `backend/` + `frontend/`; controllers/services/models layered |
| 06 Build reproducibility | `HowToSetup.md` + Docker Compose + VPS docs |

**Minimum score to continue:** 75/100 with no failing check.

## Submission package

1. Source: Git repo or ZIP **without** `vendor/`, `node_modules/`, `.env`, `composer.phar`
2. Live demo URL (keep online during review + listing; Vibe96 pings every 24h)
3. Docs: `HowToSetup.md`, `README.md`, `CLAUDE.md`, `AGENTS.md`, `docs/DEPLOYMENT.md`
4. Listing copy: use `LISTING.md`
5. ≥5 screenshots at **1920×1080** PNG/JPG
6. Sign per-project seller agreement
7. Human review

## Before you zip

```bash
cd backend && composer audit
cd ../frontend && npm audit --omit=dev
cd backend && php artisan test
```

Confirm a stranger can follow `HowToSetup.md` on a clean machine.

## Seller economics (reference)

- Listing fee: $0  
- You keep **65%**; Vibe96 **35%**  
- Price band: $15–$300  
- Payouts monthly (5th), $200 minimum  
- 30 days buyer support; 12 months updates  
