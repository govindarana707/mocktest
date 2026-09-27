# Requirements

## Functional requirements

### Administration

- Authenticate and authorize administrators.
- Manage students, subjects, questions, options, examinations, assignments, and results.
- Publish examinations within explicit availability windows.
- Show dashboard metrics and charts based only on stored records.

### Student experience

- Register, sign in, maintain a profile, and see eligible examinations.
- Start one valid attempt, save answers, resume after refresh, and submit before the enforced deadline.
- View permitted results, performance history, leaderboard position, and approved answer reviews.

### Examination integrity

- Never expose correct answers during an active attempt.
- Enforce eligibility, availability, duration, and submission state on the server.
- Autosave answers safely and reject mutations after submission.
- Prevent duplicate attempts and duplicate result creation.
- Grade and calculate marks exclusively on the server.

### Results and ranking

- Store marks, percentage, pass/fail state, correctness counts, and completion duration.
- Rank valid submitted results by marks descending, completion time ascending, then a stable unique key.
- Reveal only approved public profile data on leaderboards.

## Non-functional requirements

- Responsive, accessible Blade interface with light and dark modes.
- Normalized MySQL schema with indexes, foreign keys, and meaningful constraints.
- CSRF protection, validation, password hashing, policies, and least-privilege routes.
- Maintainable Laravel conventions and automated coverage for critical workflows.
- Portable Windows setup without machine-specific paths or real credentials in committed configuration.

## Out of scope for Version 1

- Multiple attempts per examination
- Essay/manual-grading workflows
- Remote proctoring
- Payments, subscriptions, multi-tenancy, or external identity providers
- Docker, Redis, React, and microservice deployment

