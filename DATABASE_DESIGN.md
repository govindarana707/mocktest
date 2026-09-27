# Database Design

## Status

This is the initial Phase 1 design baseline. Domain migrations will be implemented and verified in their relevant phases; only Laravel's framework tables exist now.

## Planned entities

| Entity | Purpose | Key relationships and constraints |
|---|---|---|
| `users` | Admin and student identities | Indexed role/status; unique email |
| `subjects` | Question and examination subject grouping | Unique slug; creator audit fields where required |
| `questions` | MCQ prompt and metadata | Belongs to subject; status/difficulty indexes |
| `question_options` | Four choices and correctness flag | Belongs to question; exactly one correct option enforced by domain validation/transaction |
| `examinations` | Schedule, duration, marks, passing threshold, publication and review settings | Belongs to subject; indexed availability and publication state |
| `examination_questions` | Ordered question assignment with marks | Unique examination/question pair; unique order per examination |
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

Laravel's default `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, and `failed_jobs` tables are supplied by the initial migrations.

