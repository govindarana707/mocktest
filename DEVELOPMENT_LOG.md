# Development Log

## Phase 1 — Environment, initialization, and database connection

Date: 2026-09-27

### Environment observed

- Windows with Laragon 8.6.1.60301 (product version 8)
- PHP 8.3.30, ZTS, x64
- Composer 2.9.4
- Node.js 24.18.0
- npm 11.16.0
- MySQL Community Server 8.4.3
- Requested target `C:\laragon\www\mocktest` did not exist before initialization

### Work completed

- Initialized Laravel application skeleton 13.10.1; dependency resolution installed Laravel Framework 13.33.0.
- Retained Laravel's Vite 8 and Tailwind CSS 4 integration.
- Added Alpine.js, ApexCharts, Lucide, and SweetAlert2 as frontend dependencies.
- Added Pest and its Laravel plugin. PHP 8.3 is incompatible with Pest 5, so Composer selected Pest 4.7.8 and plugin 4.1.0.
- Added Playwright test dependency; browser binaries are intentionally deferred until browser tests are introduced.
- Configured the application template for the `mocktest` MySQL database.
- Created initial project, requirements, database, development, and testing documentation.

### Verification completed

- Created MySQL database `mocktest` with `utf8mb4_unicode_ci` and applied all initial Laravel migrations.
- Confirmed Laravel reports the `mysql` driver and Laravel Framework 13.33.0.
- Composer validation passed.
- Pest passed 2 baseline tests with 2 assertions.
- Vite production build passed after network access was allowed for the configured font download.
- A local HTTP smoke request returned status 200.

### Known limitations

- Playwright's package and configuration are present, but browser binaries and UI tests are deferred until browser workflows exist.
- The project currently shows Laravel's default welcome page.
- Local `.env` assumes Laragon's default MySQL `root` user with an empty password; other machines must provide their own local credentials.

### Scope boundary

No authentication, dashboard, examination, grading, result, or leaderboard features were implemented in Phase 1.

## Phase 2 — Authentication, authorization, dashboards, and profiles

Date: 2026-09-27

### Work completed

- Initialized Git and captured the verified Phase 1 baseline in commit `e1021f6`.
- Added a forward migration for user roles and optional profile fields without rebuilding or deleting existing records.
- Implemented student registration, student login/logout, separate administrator login/logout, session regeneration/invalidation, CSRF forms, server-side validation, and five-attempt-per-minute portal-specific throttles.
- Added typed Admin/Student roles and route middleware; public registration always creates Student accounts.
- Added administrator overview and protected student directory with real database statistics.
- Added student overview and functional profile editing with unique-email and date/content validation.
- Added responsive Tailwind CSS 4/Alpine.js shells with collapsible navigation, sticky headers, profile menus, persisted light/dark mode, Lucide icons, and SweetAlert2 success notices.
- Added all requested navigation destinations; Phase 3 modules are explicitly marked Coming Soon and contain no Phase 3 behavior.
- Added a development-only, idempotent demo account seeder that refuses production execution.

### Verification completed

- Forward MySQL migration passed; `migrate:fresh` was never used.
- Demo seeder ran twice successfully without duplicate accounts.
- Focused Phase 2 suite passed: 16 tests, 76 assertions.
- Full Pest suite passed: 18 tests, 78 assertions.
- Blade view compilation passed.
- Vite 8.3.1 production build passed.

### Scope boundary

No subject, question bank, examination lifecycle, attempt, grading, result calculation, or leaderboard implementation was started.

## Phase 3 — Content management and examination catalog

Date: 2026-09-28

### Work completed

- Added forward-only MySQL migrations for subjects, MCQ questions, examinations, and ordered examination-question assignments.
- Implemented complete administrator Subject CRUD with search, pagination, validation, and referenced-record protection.
- Implemented complete administrator Question Bank CRUD with four stored options, one validated correct-option key, subject filtering, and assignment protection.
- Implemented examination creation, editing, scheduling, duration, passing percentage, draft/published state, filtering, pagination, and protected deletion behavior.
- Implemented asynchronous question assignment with CSRF-protected JSON requests and same-subject validation.
- Updated the administrator dashboard with live subject, question, examination, and publication counts.
- Added a read-only student Available Exams catalog that limits records to currently available published examinations and never loads or renders answers.
- Added idempotent development-only demo content seeding for subjects, questions, and a published sample examination.

### Verification completed

- Forward MySQL migrations passed; existing records were preserved and `migrate:fresh` was never used.
- Demo content seeder ran twice without duplicates.
- Focused Phase 3 suite passed: 14 tests, 44 assertions.
- Full Pest suite passed: 32 tests, 122 assertions.
- Blade view compilation and Vite 8.3.1 production build passed.

### Scope boundary

No exam-taking workflow, attempt storage, autosave, submission, grading, results, or leaderboard behavior was implemented.

## Phase 4 — Secure examination attempts

Date: 2026-09-28

### Work completed

- Added forward-only attempt, answer, and question-snapshot tables with one-attempt-per-student database protection.
- Added secure instructions, start/resume, autosave, timeout/manual submission, and attempt-history routes for students.
- Enforced ownership, question membership, CSRF, stale-write versions, server expiry, and submitted-at read-only behavior.
- Added a responsive timed MCQ interface with automatic save/recovery and a submission confirmation flow.
- Verified Apache's `DocumentRoot` targets `public/`; `.env`, SQLite database, and traversal requests returned 404.
- Added Playwright checks for protected exam routes and private-path requests; their execution is blocked only by the missing local Chromium headless-shell executable.

### Scope boundary

No grading, result calculation, result display, or leaderboard behavior was implemented.

## Phase 5 — Result management

- Added immutable attempt-level grading snapshots and the one-to-one `examination_results` table.
- Added transactional, idempotent automatic grading on both manual and timeout finalization.
- Added student result history, result detail, authorized answer review, and read-only admin results pages.
- No leaderboard or ranking behavior was added.

## Phase 6 — Leaderboards and performance ranking

- Added exam-scoped student and admin leaderboard routes backed exclusively by finalized `examination_results` and server-side attempt timestamps.
- Ranking order is obtained marks descending, completion duration ascending, graded time ascending, then result ID ascending. Ranks are positional.
- Student visibility allows published exams after their start time, including expired historical exams; drafts and future exams remain hidden. Admins may view all states.
- Leaderboards expose display names and score summaries only. Incomplete and legacy/unrankable attempts are excluded.

