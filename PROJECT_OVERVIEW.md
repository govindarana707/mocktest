# Project Overview

## Purpose

The Online Mock Examination and Performance Ranking System will let administrators manage MCQ examinations and let students take one valid attempt per published examination. Grading, result calculation, and ranking will be performed on the server.

## Planned modules

1. Admin Dashboard
2. Student Dashboard
3. Online Examination
4. Result Management
5. Leaderboard

## Architecture

The application uses Laravel MVC with Blade views, Eloquent models, migrations, controllers, form requests, policies, services, factories, seeders, and reusable Blade components where appropriate. MySQL is the system of record. Vite builds Tailwind CSS and JavaScript assets. Alpine.js provides lightweight UI behavior; Fetch API will handle answer autosave; ApexCharts, Lucide, and SweetAlert2 support charts, icons, and dialogs.

Critical examination and grading rules will remain server-side. Transactions, uniqueness constraints, authorization policies, and idempotent submission logic will protect attempt integrity.

## Delivery phases

Development follows the nine phases in the master development prompt. Only Phase 1 setup and documentation exist at present. No examination feature is represented as complete.

## Version 1 constraints

- MCQ examinations with four options per question
- One valid attempt per student per examination
- Admin and student roles
- Examination-wise ranking from submitted, valid results
- Completion time and a deterministic final key used as score tie-breakers
- Blade-based interface; no React, Docker, Redis, or microservices

