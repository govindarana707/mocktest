# Online Mock Examination and Performance Ranking System

A Laravel-based educational SaaS application for creating mock examinations, managing student attempts, grading submissions, and publishing deterministic leaderboards.

## Current status

Phase 3 is complete: administrators can manage subjects, four-option MCQ questions, examinations, scheduling, and question assignment. Students can browse a safe, read-only catalog of currently published examinations. Exam-taking, autosave, grading, results, and ranking remain intentionally out of scope.

## Requirements

- PHP 8.3 or later within Laravel 13's supported range
- Composer 2.9+
- MySQL 8.0+
- Node.js 22.12+ or 24+
- npm 11+
- Laragon on Windows is recommended for local development

Versions verified on the initial development machine are listed in `DEVELOPMENT_LOG.md`.

## Local development URL

The Laragon development URL is `http://mocktest.test/`.

- Keep the project at `C:\laragon\www\mocktest`.
- Set `APP_URL=http://mocktest.test` in `.env`.
- Serve `C:\laragon\www\mocktest\public` as the Apache `DocumentRoot`.
- Use Laragon's **Reload** action after adding the project so its automatic virtual host and local hostname mapping are active.
- Do not use `http://localhost/mocktest`; the application expects the `mocktest.test` origin.

## Local setup

1. Copy the repository into your web root (for Laragon, `C:\laragon\www\mocktest`).
2. Run `composer install`.
3. Run `npm install`.
4. Copy `.env.example` to `.env`.
5. Create a MySQL database named `mocktest` using `utf8mb4`.
6. Update the `DB_*` values in `.env` if your local MySQL credentials differ.
7. Run `php artisan key:generate`.
8. Run `php artisan migrate`.
9. Optionally run `php artisan db:seed` in a local/development environment for idempotent accounts and content, or use `php artisan db:seed --class=DemoContentSeeder` for content only.
10. Run `npm run build`.
11. Start Laragon, click **Reload**, and visit `http://mocktest.test/`. Alternatively, run `composer run dev` and use its displayed URL.

Do not commit `.env`; it contains machine-specific settings and may contain credentials.

## Phase 2 accounts and portals

- Students may self-register at `/register` and sign in at `/login`.
- Administrators sign in separately at `/admin/login`; there is no public administrator registration.
- The development-only demo seeder is idempotent and refuses to run in production.
- Demo administrator: `admin@mocktest.test` / `Admin123!`
- Demo student: `student@mocktest.test` / `Student123!`

Change demo passwords before using these accounts outside local development.

## Security controls

Authentication uses Laravel's session guard with session regeneration after login, complete invalidation on logout, CSRF-protected forms, per-portal login throttling, server-side form requests, and role middleware. Student profile and directory data are available only through authenticated, role-authorized routes.

## Phase 3 content management

- Administrators manage subjects at `/admin/subjects`, MCQ questions at `/admin/questions`, and examinations at `/admin/examinations`.
- Questions have exactly four stored options and one validated correct-option key.
- Examination assignment is saved asynchronously and accepts only questions from the examination subject.
- A subject or assigned question cannot be deleted while referenced. Deleting an examination removes only its question assignments.
- Students see only published examinations within their scheduled availability at `/student/exams`; no question, option, explanation, or answer data is exposed.

## Phase 4 examination engine

- Students review instructions at `/student/exams/{examination}/instructions`, then receive one server-recorded attempt at `/student/attempts/{attempt}`.
- Each attempt snapshots its assigned questions, saves answers through CSRF-protected AJAX requests, and rejects stale answer versions.
- Countdown expiry and manual submission are server-enforced and idempotent. Submitted attempts appear at `/student/my-exams`; scoring and results remain out of scope.
- The Laragon Apache virtual host serves only `public/`; direct `.env`, database, and traversal requests returned 404 during verification.

## Verification commands

```text
php artisan about
php artisan migrate:status
php artisan test
vendor\bin\pest
npm run build
```

## Phase 5 results

Finalized attempts are graded server-side from immutable attempt snapshots. Students use `/student/results`; administrators use `/admin/results`. Answer review is available only to the owning student when an examination permits it. No ranking or leaderboard behavior is included.

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

