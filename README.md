# Online Mock Examination and Performance Ranking System

A Laravel-based educational SaaS application for creating mock examinations, managing student attempts, grading submissions, and publishing deterministic leaderboards.

## Current status

Phase 1 is complete: the Laravel application is initialized, MySQL is configured, the Vite/Tailwind frontend toolchain is installed, and baseline verification is documented. Examination modules are intentionally not implemented yet.

## Requirements

- PHP 8.3 or later within Laravel 13's supported range
- Composer 2.9+
- MySQL 8.0+
- Node.js 22.12+ or 24+
- npm 11+
- Laragon on Windows is recommended for local development

Versions verified on the initial development machine are listed in `DEVELOPMENT_LOG.md`.

## Local setup

1. Copy the repository into your web root (for Laragon, `C:\laragon\www\mocktest`).
2. Run `composer install`.
3. Run `npm install`.
4. Copy `.env.example` to `.env`.
5. Create a MySQL database named `mocktest` using `utf8mb4`.
6. Update the `DB_*` values in `.env` if your local MySQL credentials differ.
7. Run `php artisan key:generate`.
8. Run `php artisan migrate`.
9. Run `npm run build`.
10. Start Laragon and visit `http://mocktest.test`, or run `composer run dev` and use its displayed URL.

Do not commit `.env`; it contains machine-specific settings and may contain credentials.

## Verification commands

```text
php artisan about
php artisan migrate:status
php artisan test
vendor\bin\pest
npm run build
```

Playwright is installed for browser testing in later phases. Install its Chromium runtime before the first browser-test run with `npx playwright install chromium`.

## Troubleshooting

- **Database connection refused:** start MySQL in Laragon and confirm port 3306 is available.
- **Access denied for MySQL:** update `DB_USERNAME` and `DB_PASSWORD` in `.env` for the local machine.
- **Vite reports an unsupported Node version:** use Node 22.12+ or the current Node 24 release supported by Vite 8.
- **Missing PHP extensions:** enable Laravel-required extensions in the active Laragon PHP installation, then rerun `composer install`.
- **Stale configuration:** run `php artisan optimize:clear` after changing `.env`.

## Documentation

- `PROJECT_OVERVIEW.md` — project scope and module boundaries
- `REQUIREMENTS.md` — functional and non-functional requirements
- `DATABASE_DESIGN.md` — initial normalized data model plan
- `DEVELOPMENT_LOG.md` — factual phase-by-phase implementation record
- `TESTING.md` — test strategy and actual execution results

