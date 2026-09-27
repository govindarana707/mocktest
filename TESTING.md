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

