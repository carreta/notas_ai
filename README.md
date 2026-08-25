# AI Meeting Notes — Engineering Documentation

**Project type:** Small collaborative engineering exercise  
**Primary objective:** Evaluate how a newly formed development team plans, divides, implements, reviews, integrates, tests, and releases a small AI-enabled Laravel application using GitHub.  
**Product objective:** Convert meeting notes/transcripts into a structured summary of decisions, action items, owners, due dates, and open questions.

## Table of Contents

- [AI Meeting Notes — Engineering Documentation](#ai-meeting-notes--engineering-documentation)
  - [Table of Contents](#table-of-contents)
  - [Setup from clean checkout](#setup-from-clean-checkout)
    - [Prerequisites](#prerequisites)
    - [Quick start](#quick-start)
    - [Running the local server](#running-the-local-server)
    - [Running tests](#running-tests)
    - [Running quality checks](#running-quality-checks)
    - [Manual setup (alternative)](#manual-setup-alternative)
  - [Security checklist](#security-checklist)

## Setup from clean checkout

The easiest way to get this project running locally is the `composer setup` script, which handles dependency installation, environment configuration, database migration, and frontend asset compilation in a single command.

### Prerequisites

- **PHP 8.4+** with required extensions (`mbstring`, `dom`, `fileinfo`, `pdo_pgsql`, etc.)
- **Composer** (latest)
- **Node.js v20+** and **npm**
- **PostgreSQL** (local or Docker) — databases `notas_ia` (dev) and `notas_ia_test` (tests)

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
4. `php artisan migrate --force` — runs database migrations (PostgreSQL)
5. `npm install --ignore-scripts` — installs frontend dependencies
6. `npm run build` — compiles frontend assets with Vite

### Running the local server

After setup, start the development server:

```bash
php artisan serve
```

Visit [http://localhost:8000](http://localhost:8000) — you should see the home page. Additionally, if you are in a development environment, the Vite API must be started simultaneously:

```bash
npm run dev
```

### Running tests

Verify your setup by running the test suite:

```bash
 composer test
 ```
This runs the full PHPUnit suite (Unit + Feature) against the PostgreSQL test database (`notas_ia_test`). The test environment is configured in `phpunit.xml` with `DB_CONNECTION=pgsql` and `DB_DATABASE=notas_ia_test`.

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

Run the automated test suite
```bash
 composer test
```

## Security checklist

| Item | What is verified | How it is ensured in this project |
|------|----------------|----------------------------------|
| **No hardcoded secrets** | No passwords, API keys, tokens, or other secrets in source code | `.env` is in `.gitignore`; `.env.example` contains placeholders only; `git-secrets` / `truffleHog` scan in CI before merge |
| **Sensitive variables outside the repository** | Database credentials, API keys, and application secrets are not versioned | `.env` is never committed; real values exist only in local/CI environments (GitHub Secrets); `php artisan config:cache` validation fails if required variables are missing |
| **Proper authentication** | Login, registration, logout, password reset work and use secure hashing | Laravel Breeze/Fortify/Sanctum depending on the stack; `Hashed` cast on the `User` model; Feature tests cover authentication flows (login, logout, password reset, email verification). But user authentications aren't really used in the application for the MVP. |
| **Authorization / Resource access** | Users cannot access resources without permission (IDOR, privilege escalation) | Policies (`app/Policies/`) + Gates; `auth`/`can` middleware on routes; Feature tests verify 403/404 responses for unauthorized access. But again user management aren't really used in the application for the MVP. |
| **Input validation** | Requests validate type, format, length, and business rules before persistence | Form Requests (`app/Http/Requests/`) with strict rules; `validated()` exclusively; sanitization in mutators/casts; validation tests (valid, invalid, and edge cases) |
| **No information leakage in errors** | Exceptions and logs do not expose stack traces, SQL, secrets, or user data in production | `APP_DEBUG=false` in production; `render()` in `App\Exceptions\Handler` normalizes responses; `config/logging.php` uses `single`/`daily` with `error` level in production; tests verify that 500 errors do not return internal details |
| **Dependencies without critical vulnerabilities** | `composer audit` / `npm audit` report no unmitigated critical CVEs | `composer audit` and `npm audit --audit-level=high` in the CI pipeline; `dependabot.yml` configured for automatic PRs; manual review before release |
| **Sensitive routes/endpoints protected** | Admin, internal APIs, webhooks, and exports require authentication and permissions | Route prefixes with middleware (`auth`, `role:admin`, `throttle`); CSRF for web forms; `signed` URLs for temporary public links; Feature tests cover protected endpoints. But admin components weren't really created in the application for the MVP. |
| **Sample data contains no real information** | Factories/seeders use Faker, not real user or company data | `database/factories/` use `Faker\Factory`; development seeders (`DevelopmentSeeder`) are isolated by environment; `.env.example` contains no real data |

> **Note:** This checklist runs as part of the quality pipeline (`composer check` + CI) and is reviewed manually before tagging a release. Temporary project logs are not used as a baseline for this review.

