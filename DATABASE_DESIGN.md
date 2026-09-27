# Database Design

## Status

Phase 3 implements the content-authoring foundation. Student attempts, answers, grading, results, and leaderboard entities remain planned only.

## Planned entities

| Entity | Purpose | Key relationships and constraints |
|---|---|---|
| `users` | Admin and student identities | Indexed role/status; unique email |
| `subjects` | Question and examination subject grouping | Unique `code` and `name`; deletion is restricted while referenced |
| `questions` | MCQ prompt and four options | Belongs to subject; one `correct_option` constrained by request validation; deletion is restricted while assigned |
| `examinations` | Schedule, duration, passing threshold, and publication state | Belongs to subject; `draft`/`published` status and indexed availability dates |
| `examination_question` | Ordered question assignment | Unique examination/question pair and position; deleting an examination cascades assignments only |
| `examination_attempts` | Student's timed examination session | Unique examination/student pair for Version 1; indexed status/deadline |
| `student_answers` | Saved option for each attempted question | Unique attempt/question pair; belongs to an option validated against the question |
| `results` | Immutable calculated outcome for a submitted attempt | Unique attempt; indexed examination/marks/time for ranking |

## Integrity principles

- Use unsigned foreign keys matching Laravel model keys and explicit cascade/restrict behavior.
- Store examination snapshots or immutable grading inputs when later edits could invalidate historical results.
- Use transactions and row locks around start, autosave where needed, and final submission.
- Use database uniqueness constraints in addition to request-level checks.
- Derive leaderboard positions from valid results; do not maintain a duplicate ranking table.
- Use decimal/integer representations that avoid floating-point scoring errors.

## Framework tables currently initialized

Laravel's default `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, and `failed_jobs` tables are supplied by the initial migrations. Phase 2 adds the user role/profile columns. Phase 3 adds `subjects`, `questions`, `examinations`, and `examination_question`.

