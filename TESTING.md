# Testing

## Strategy

- Pest feature tests for authentication, authorization, examination rules, grading, and ranking.
- Pest unit tests for isolated domain calculations and services.
- Playwright browser tests for critical responsive user journeys when UI flows exist.
- Database-backed tests with factories and transaction/reset isolation.

## Required future coverage

- Registration/login and role-based access
- Subject, question, and examination management
- Examination availability and deadline enforcement
- Answer autosave and refresh recovery
- Duplicate attempt/submission prevention
- Automatic grading and result calculations
- Leaderboard ordering and deterministic tie-breakers
- Cross-student data isolation and unauthorized-route rejection

## Actual results

### Phase 1 — 2026-09-27

| Check | Result |
|---|---|
| `composer validate --strict` | Passed; `composer.json` is valid |
| `php artisan migrate --force` against MySQL | Passed; three initial migration groups ran |
| `php artisan migrate:status` | Passed; all initial migrations reported `Ran` |
| `vendor\bin\pest --colors=never` | Passed; 2 tests, 2 assertions |
| `npm run build` | Passed; Vite 8.3.1 production assets generated |
| HTTP smoke request to Laravel development server | Passed; HTTP 200, HTML response |

The first sandboxed asset build could not fetch the configured Bunny font because outbound access was denied. The same build was rerun with permitted network access and passed. Vite emitted a non-blocking notice that optimized font fallbacks can use the optional `fontaine` package; this package was not added because it is not required.

Playwright browser tests were not run because Phase 1 has no application browser journeys yet and its Chromium binary has not been downloaded. No future feature test is claimed as passing before implementation and execution.

### Phase 2 — 2026-09-27

| Check | Result |
|---|---|
| Authentication, throttling, and validation feature tests | Passed |
| Admin/Student authorization and private-data isolation tests | Passed |
| Dashboard statistics and Coming Soon boundary tests | Passed |
| Student profile view/update/validation tests | Passed |
| Public HTTP and CSRF-form tests | Passed |
| Focused Phase 2 suite | Passed; 16 tests, 76 assertions |
| Full `php artisan test --compact` suite | Passed; 18 tests, 78 assertions |
| Blade template compilation | Passed |
| `npm run build` | Passed; Vite 8.3.1 production assets generated |

The suite uses an in-memory SQLite database and `RefreshDatabase`, leaving development records untouched. The development MySQL migration was applied forward only. The demo seeder was executed twice to verify idempotency.

### Phase 3 — 2026-09-28

| Check | Result |
|---|---|
| Subject CRUD, validation, reference protection, and role test coverage | Passed |
| MCQ Question Bank CRUD, correct-option validation, and assignment protection | Passed |
| Examination scheduling, publish rules, deletion behavior, and AJAX assignment coverage | Passed |
| Student catalog availability and answer-leakage coverage | Passed |
| Focused Phase 3 suite | Passed; 14 tests, 44 assertions |
| Full `php artisan test --compact` suite | Passed; 32 tests, 122 assertions |
| Blade template compilation | Passed |
| `npm run build` | Passed; Vite 8.3.1 production assets generated |
| `composer validate --strict` | Passed; `composer.json` is valid |

The Phase 3 focused tests use the in-memory SQLite test database. The development MySQL migration ran forward only, and `DemoContentSeeder` ran twice successfully to verify that its upserts and question assignments are idempotent.

### Phase 4 — 2026-09-28

| Check | Result |
|---|---|
| Forward attempt migration | Passed after an explicit short MySQL index name was applied; no reset used |
| Attempt lifecycle, autosave, stale-write, timeout, and access tests | Passed; 5 tests, 28 assertions |
| Full `php artisan test --compact` suite | Passed; 39 tests, 156 assertions |
| Blade template compilation | Passed |
| `npm run build` | Passed; Vite 8.3.1 production assets generated |
| Apache private-path smoke checks | Passed; `.env`, database path, and traversal path returned 404 |

Playwright coverage for unauthenticated examination-route protection and private-path behavior is included in `tests/Browser/attempt-engine.spec.js`. The runner was invoked, but Chromium could not launch because its expected headless-shell executable is missing after the runtime download attempt; the browser checks therefore remain unverified in this environment.

