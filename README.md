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
    - [Configure .env](#configure-env)
    - [Configure models.php](#configure-modelsphp)
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
- **PostgreSQL** (local or Docker) — databases `notas_ia` (dev) and `notas_ia_test` (tests) **must exist before running setup**

> **Create the databases first** (if they don't exist):
> ```sql
> CREATE DATABASE notas_ia;
> CREATE DATABASE notas_ia_test;
> ```
> The `composer setup` script runs migrations against both databases, so they must be available.

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
4. `php artisan migrate --force` — runs database migrations against `notas_ia` (PostgreSQL)
5. `npm install --ignore-scripts` — installs frontend dependencies (ignores lifecycle scripts for security/speed)
6. `npm run build` — compiles frontend assets with Vite

> **After `composer setup`**, configure your `.env` with AI provider credentials (see [Configure .env](#configure-env)) before running the server or tests.

### Configure .env

Before starting the server or application, `.env` must be configured with the required environment variables for LLM provider integration.

**Essential AI configuration (from `.env.example`):**

| Variable | Purpose | Required? |
|----------|---------|-----------|
| `AI_PROVIDER` | Active provider: `openai` \| `lmstudio` \| `google` | Optinal, this is default provider and the active provider is choosen in execution. |
| `AI_DRIVER` | Development driver: `llm` (real calls) \| `fake` (deterministic tests) | Yes, llm by default |
| `AI_FAKE_OUTCOME` | When `AI_DRIVER=fake`: `valid` \| `error` \| `empty` | If using `fake` |
| `AI_TIMEOUT` | Approved finite request timeout | yes, specially if using heavy local LLM (default: `120`s)

The currently tested and supported providers are:

**OpenAI (default provider):**
- `OPENAI_BASE_URL` — API endpoint (default: `https://api.openai.com/v1`)
- `OPENAI_API_KEY` — Your OpenAI API key
- `OPENAI_MODEL` — Model identifier (e.g., `gpt-5.6-luna`)
- `OPENAI_TIMEOUT` — Request timeout in seconds (default: `120`)

**LM Studio (local OpenAI-compatible server):**
- `LMSTUDIO_BASE_URL` — Local server URL (default: `http://localhost:1234/v1`)
- `LMSTUDIO_API_KEY` — Optional API key if your LM Studio instance requires one
- `LMSTUDIO_MODEL` — Model identifier as served by LM Studio (e.g., `qwen/qwen3.5-9b`)
- `LMSTUDIO_TIMEOUT` — Request timeout in seconds (default: `120`)

> **Note:** LM Studio runs outside this project. Simply expose its URL and optional API key in `.env` while loading the desire models; further configuration is needed in 'models.php' as shown in the upcoming section.

**Google Gemini:**
- `GOOGLE_AI_API_KEY` — API key from [Google AI Studio](https://aistudio.google.com/apikey)
- `GOOGLE_AI_BASE_URL` — API endpoint (default: `https://generativelanguage.googleapis.com/v1beta`)
- `GOOGLE_AI_MODEL` — Model identifier (e.g., `gemini-3.7-flash`)
- `GOOGLE_AI_TIMEOUT` — Request timeout in seconds (default: `120`)

### Configure models.php

The `config/models.php` file defines the **approved model catalog** used by the application. It maps human-friendly keys to provider-specific model identifiers, token limits, and encoding.

**What to update:**
- Keep this file in sync with the **current models and endpoints offered by each provider**.
- Update `model` values when providers release new model versions or retire old ones.
- Adjust `max_chars`, `max_tokens`, and `encoding` to match the provider's documented limits or the ones to enforce.

**Do NOT use this file to configure local models** (e.g., models you load in LM Studio). Local model selection is done at runtime and this config is only the application's reference catalog for validation and UI display. 

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
# 1. Install PHP dependencies
composer install

# 2. Code style check (optional, but recommended)
./vendor/bin/pint --test

# 3. Environment configuration (create .env from example)
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Run migrations (requires PostgreSQL with `notas_ia` database created)
php artisan migrate

# 6. Install frontend dependencies (--ignore-scripts for security/speed)
npm install --ignore-scripts

# 7. Build frontend assets
npm run build
```

> **Before `npm run build`**, configure your `.env` and `models.php` with AI provider credentials and information (see [Configure .env](#configure-env)) before running the server or tests.

Run the automated test suite (requires `notas_ia_test` database):
```bash
composer test

or 

composer check
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

