# AI Meeting Notes — Engineering Documentation

**Project type:** Small collaborative engineering exercise  
**Primary objective:** Evaluate how a newly formed development team plans, divides, implements, reviews, integrates, tests, and releases a small AI-enabled Laravel application using GitHub.  
**Product objective:** Convert meeting notes/transcripts into a structured summary of decisions, action items, owners, due dates, and open questions.

## How to use this documentation

Read these documents in order before coding:

1. `PROJECT_ENGINEERING_BLUEPRINT.md` — project authority, scope, governance, and engineering rules.
2. `01-functional-requirements.md` — stable FR identifiers and acceptance criteria.
3. `02-non-functional-requirements.md` — quality, security, reliability, and maintainability requirements.
4. `03-architecture.md` — fixed architectural boundaries and intentionally open design areas.
5. `04-data-model.md` — required domain concepts and schema constraints.
6. `05-ai-integration.md` — AI contract, failure modes, validation, and testing rules.
7. `06-security-privacy.md` — data handling and secret-management rules.
8. `07-testing-quality-gates.md` — mandatory verification before merge/release.
9. `08-git-github-workflow.md` — branches, commits, pull requests, reviews, and conflict rules.
10. `09-team-working-agreement.md` — collaboration protocol and expectations.
11. `10-implementation-phases.md` — project sequence and phase ownership model.
12. `11-open-team-decisions.md` — decisions deliberately left to the team.
13. `12-definition-of-done-traceability.md` — completion criteria and traceability matrix.
14. `13-kickoff-runbook.md` — first-session procedure for the team lead.
15. `14-team-evaluation-scorecard.md` — evidence-based evaluation guide for the exercise.
16. `phases/` — executable phase contracts.
17. `adr/` — architecture decision records.
18. `templates/` — reusable templates for PRs, checkpoints, retrospectives, and issues.

## Governing principle

This is an engineering exercise, not a race to generate code. A successful result demonstrates:

- traceable requirements;
- small, coherent branches;
- understandable commits;
- reviewable pull requests;
- evidence-based code review;
- automated tests;
- explicit architecture decisions;
- safe AI integration;
- successful integration into a stable branch;
- a reproducible release;
- useful retrospective evidence about team performance.

## Deliberately unresolved decisions

Some technical choices are intentionally marked `TEAM DECISION`. The team must resolve them through discussion and, when architectural, an ADR. These are not omissions. They exist to evaluate technical judgment, communication, trade-off analysis, and ability to reach documented decisions.

A `TEAM DECISION` may not be silently resolved in code.

## Setup from clean checkout

The easiest way to get this project running locally is the `composer setup` script, which handles dependency installation, environment configuration, database migration, and frontend asset compilation in a single command.

### Prerequisites

- **PHP 8.3+** with required extensions (`mbstring`, `dom`, `fileinfo`, `pdo_sqlite`, `sqlite3`, etc.)
- **Composer** (latest)
- **Node.js v20+** and **npm**

### Quick start

```bash
git clone <repo-url>
cd notas_ai
composer setup
```

`composer setup` runs the following steps:

1. `composer install` — installs PHP dependencies
2. Copies `.env.example` to `.env` (if not already present)
3. `php artisan key:generate` — sets the application encryption key
4. `php artisan migrate --force` — runs database migrations (SQLite)
5. `npm install --ignore-scripts` — installs frontend dependencies
6. `npm run build` — compiles frontend assets with Vite

### Running the local server

After setup, start the development server:

```bash
php artisan serve
```

Visit [http://localhost:8000](http://localhost:8000) — you should see the Laravel welcome page.

### Running tests

Verify your setup by running the test suite:

```bash
composer test
```

This runs the full PHPUnit suite (Unit + Feature), including the migration smoke test that verifies all default migrations succeed on an in-memory SQLite database.

### Running quality checks

Run all quality checks (tests, static analysis, code style, validation, build, migrations) with a single command:

```bash
composer check
# or directly via Artisan
php artisan check
```

This executes the following checks and produces a formatted report:

| Check | Command | Description |
|-------|---------|-------------|
| Route List | `php artisan route:list` | Verifies all routes load without boot errors |
| Static Analysis | `composer analyse` | Larastan level 5 on `app/` |
| Tests | `composer test` | PHPUnit suite (Unit + Feature) |
| Code Style | `composer pint` / `./vendor/bin/pint --test` | Laravel Pint style check |
| Composer Validate | `composer validate --strict` | Validates `composer.json` and `composer.lock` |
| Platform Reqs | `composer check-platform-reqs` | Verifies PHP/extension requirements |
| Frontend Build | `npm run build` | Compiles assets with Vite |
| Clean Migrations | `php artisan migrate:fresh --force` | Verifies migrations run from zero |

Example output:
```
============================================================
QUALITY CHECK REPORT
============================================================

[PASS] Route List Verification (222ms)
[PASS] Static Analysis (Larastan) (3646ms)
[PASS] Tests (PHPUnit) (2249ms)
       Tests: 3 | Assertions: 8
[PASS] Code Style (Pint) (620ms)
[PASS] Composer Validate (1024ms)
[PASS] Platform Requirements (820ms)
[PASS] Frontend Build (1632ms)
[PASS] Clean Migrations (822ms)

------------------------------------------------------------
Total: 8/8 checks passed
Duration: 11035ms

All checks passed!
```

### Manual setup (alternative)

If you prefer to run each step individually:

```bash
composer install
./vendor/bin/pint --test

Copy-Item .env.example .env
cp .env.example .env

php artisan key:generate
php artisan migrate
npm install
npm run build
```
