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
  - [Release Notes](#release-notes)
      - [Included Features](#included-features)
      - [Major Changes](#major-changes)
      - [Deferred Requirements](#deferred-requirements)
      - [Known Limitations](#known-limitations)
      - [Release Checklist (Pre-Tag)](#release-checklist-pre-tag)

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

Additionally, `php artisan ai:status` verifies AI provider configuration readiness (FR-011) and reports READY / NOT READY per provider without exposing secrets.

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

## Release Notes

> **v0.1.0-rc.1 (Release Candidate)** — Based on current implementation state as of 2026-08-25

**Target release date:** 2026-08-25  
**Branch:** `main`  
**Base commit:** Current HEAD

---

#### Included Features

| Feature ID | Description | Implementation Status |
|------------|-------------|----------------------|
| **FR-001** | Submit meeting text — plain-text input with server-side validation (blank rejection, character/token limits, SafeText sanitization) | ✅ Implemented (`AnalyzeForm`, `HasMeetingValidation`, `SafeText` rule) |
| **FR-002** | Persist meeting record — UUID, title, raw_text, status enum (DRAFT/VALIDATED/ANALYZING/COMPLETED/FAILED), optional `meeting_time`, timestamps | ✅ Implemented (`Meeting` model, migration `0001_01_01_000003_create_meetings_table`) |
| **FR-003** | Request AI analysis — provider-neutral port (`AnalysisProvider`), `LLMAdapter` for OpenAI-compatible endpoints (OpenAI, LM Studio, Google), per-request model selection | ✅ Implemented (`AnalysisOrchestrator`, `LLMAdapter`, `FakeAnalysisProvider`) |
| **FR-004** | Validate structured AI response — strict JSON parsing, schema validation (`StructuredAnalysisValidator`), normalization (`StructuredAnalysisNormalizer`), rejection of malformed/incorrect types | ✅ Implemented (`StructuredAnalysisParser`, `StructuredAnalysisValidator`, `StructuredAnalysisNormalizer`) |
| **FR-005** | Store analysis result — hybrid persistence (normalized core fields + full `jsonb` result in `analyses` table), linked to `meeting_id`, metadata FK to `analysis_logs` | ✅ Implemented (`AnalysisPersistenceService`, `Analysis` model, migration `0001_01_01_000006_create_analyses_table`) |
| **FR-006** | Display analysis — Livewire modal with tabs (Analysis/History), renders summary, decisions, action items (owner, priority, due date with provenance), open questions; empty collections handled gracefully | ✅ Implemented (`AnalysisDetailModal`, `analysis-detail-modal` view) |
| **FR-007** | Meeting history — searchable/filterable list with status badges, date range, sorting, full-text search across title/transcript/analysis JSON | ✅ Implemented (`HistoryList`, `history-list` view) |
| **FR-008** | Controlled AI failure behavior — typed failure categories (`AI_CONFIGURATION_ERROR`, `AI_DEPENDENCY_ERROR`, `AI_TIMEOUT`, `AI_RATE_LIMIT`, `AI_INVALID_RESPONSE`, `PERSISTENCE_ERROR`, `INTERNAL_ERROR`); safe user messages; secrets never exposed; meeting status set to FAILED | ✅ Implemented (`AnalysisFailureMapper`, `AnalysisOrchestrator.fail()`, `FakeAnalysisProvider` for deterministic tests) |
| **FR-010** | Analysis metadata — provider, model, schema version, start/completion timestamps, duration, token usage (prompt/completion/total), failure category; persisted in `analysis_logs` | ✅ Implemented (`AnalysisMetadata`, `AnalysisLog` model, `AnalysisPersistenceService`) |
| **FR-011** | Health/readiness indication — `php artisan ai:status` command checks provider configuration without exposing secrets; reports READY/NOT_READY per provider | ✅ Implemented (`AiStatusCommand`, `AiReadiness`) |
| **FR-012** | Release/demo fixture — active prompt template seeded via migration (`2026_08_20_225907_seed_meeting_analysis_prompt_v1`) | ✅ Implemented |

---

#### Major Changes

| Area | Change Summary | Impact | Migration Notes |
|------|----------------|--------|-----------------|
| **Backend** | Provider-neutral AI architecture (TD-002): application owns `AnalysisProvider` port; `LLMAdapter` implements OpenAI-compatible protocol; `FakeAnalysisProvider` for tests. No provider SDK types leak to application layer. | High | New projects: configure `config/ai.php` providers and `config/models.php` catalog. Existing: no migration needed — this is initial release. |
| **Backend** | Synchronous AI analysis (TD-004/ADR-003): analysis runs in HTTP request bounded by 120s global timeout (TD-003). No queue/jobs infrastructure required for MVP. | Medium | Workers occupy request thread up to 120s. Future async migration would require queue setup and state machine adjustments. |
| **Backend** | Hybrid persistence (TD-006): `analyses.result` (jsonb) stores full validated `AnalysisResult`; `analysis_logs` captures metadata + failure details; FK from `analyses.analysis_metadata` → `analysis_logs.id`. | Medium | Requires PostgreSQL `jsonb`. Migration `0001_01_01_000006_create_analyses_table.php` creates schema. |
| **Backend** | Meeting status state machine: DRAFT → VALIDATED → ANALYZING → COMPLETED \| FAILED. Re-analysis transitions back to ANALYZING. | Low | State is authoritative for UI; do not bypass via direct DB writes. |
| **Config** | Three-provider registry in `config/ai.php` (OpenAI, LM Studio, Google) + model catalog in `config/models.php` with `max_chars`, `max_tokens`, `encoding`, `provider`, `temperature`. Per-request model selection via UI dropdown. | Low | Add new models to `config/models.php`; add providers to `config/ai.php`. |
| **AI/Providers** | OpenAI-compatible HTTP adapter with structured error mapping: 401/403 → config error, 429 → rate limit, 5xx → dependency, timeout exceptions → `AI_TIMEOUT`. Token usage extracted best-effort. | Medium | Local models (LM Studio/Ollama) need `base_url` + optional `api_key`. Temperature configurable per model. |
| **Frontend** | Livewire + Alpine.js UI (TD-007): `AnalyzeForm` (multi-stage: validate → save → analyze → complete), `HistoryList` (filters, search, sort), `AnalysisDetailModal` (tabs, re-analyze, manual edit with provenance tracking). | Medium | Requires `npm run build` for assets. Vite + Tailwind v4. |
| **Frontend** | Token-aware validation in UI: per-model `max_chars`/`max_tokens` from `config/models.php` enforced client-side (Alpine) and server-side (`TokenCounter`, `HasMeetingValidation`). | Low | Character counter updates live; token estimation uses `cl100k_base`/`o200k_base` encodings. |

---

#### Deferred Requirements

| Requirement ID | Description | Reason for Deferral | Target Release |
|----------------|-------------|---------------------|----------------|
| **FR-009** | Re-analysis / retry — explicit user-triggered re-analysis exists (`AnalysisDetailModal::reAnalyze()`), but **automatic retry** for transient failures (TD-005 Option C: bounded auto-retry + explicit re-analysis) is **not implemented**. TD-005 remains `Proposed`. | TD-005 decision deferred; automatic retry requires classification of retryable failures, backoff, idempotency keys, and queue infrastructure (not present in synchronous MVP). | v0.2.0 (post-MVP) |
| **FR-011 (full)** | Health/readiness endpoint exposed via HTTP/API for external monitoring (currently only `artisan ai:status` CLI command). | Scope: MVP only required CLI check; HTTP health endpoint adds operational surface. | v0.2.0 |
| **Auth/Authorization** | User authentication, policies, resource ownership (FR mentions "users cannot access resources without permission" but MVP has no auth). | Explicitly out of scope for MVP per `01-functional-requirements.md` and `03-architecture.md` anti-patterns. | v0.3.0+ (if productized) |
| **Search/filtering beyond MVP** | History list supports basic search/status/date/sort; no advanced filters (tags, owner, priority, date ranges with presets). | Out of Phase 3 scope per `phase-03-results-history.md`. | v0.2.0 |
| **Analytics/dashboard** | No aggregate metrics, usage trends, or admin analytics. | Out of scope for engineering exercise. | — |

---

#### Known Limitations

| Limitation | Description | Workaround | Tracking |
|------------|-------------|------------|----------|
| **LIM-001** | **Synchronous 120s timeout** blocks PHP worker during AI analysis; concurrent analyses consume workers; local models (LM Studio) often need 300s (configured in `config/ai.php` for `lmstudio`). | Use `AI_DRIVER=fake` for local development; increase `AI_TIMEOUT`/`LMSTUDIO_TIMEOUT` in `.env`; plan async queue migration for production. | TD-003 / TD-004 |
| **LIM-002** | **Single provider active at a time** — `config/ai.php` `provider` key selects one; UI allows per-request model selection but all models must belong to the same provider type (OpenAI/LM Studio/Google). No automatic fallback or multi-provider routing. | Change `AI_PROVIDER` in `.env` and restart; or implement a provider selector in UI (not yet built). | TD-009 (Option D) |
| **LIM-003** | **No authentication/authorization** — any user with URL access can submit meetings, view history, re-analyze, and manually edit results. No multi-tenancy or data isolation. | Deploy behind VPN/SSO proxy; add Laravel Breeze/Fortify/Sanctum if productized. | Explicit MVP exclusion |
| **LIM-004** | **Token estimation is approximate** — `TokenCounter` uses `cl100k_base`/`o200k_base` encodings; actual provider tokenization may differ, especially for non-OpenAI models (Gemini, local). Over-limit inputs may still reach provider. | Set conservative `max_tokens` in `config/models.php`; server-side validation catches most cases; provider returns 400 if context exceeded. | TD-001 (Option D) |
| **LIM-005** | **Re-analysis provenance reset** — manual edits clear `priority_source`/`due_date_source` (by design per TD-010/TD-011), but re-analysis does not preserve original AI provenance; new analysis overwrites with fresh provenance. | Document that re-analysis creates a new canonical result; manual edits are one-off corrections. | TD-010 / TD-011 |
| **LIM-006** | **Prompt template only from DB** — `LLMAdapter::systemPrompt()` fetches active `prompt_templates` row; no file-based fallback if DB unavailable. | Ensure `prompt_templates` table seeded (migration `2026_08_20_225907_seed_meeting_analysis_prompt_v1`). | TD-002 port contract |
| **LIM-007** | **No structured output enforcement at provider level** — relies on prompt + post-hoc validation; some local models (LM Studio) may emit extra text causing `AI_INVALID_RESPONSE`. | `FakeAnalysisProvider` tests validation pipeline; increase `temperature: 0` for local models in `config/models.php`. | FR-004 validation |
| **LIM-008** | **Meeting time optional but affects date resolution** — without `meeting_time`, relative dates (TD-011) become `UNRESOLVED` or `INFERRED`; AI may hallucinate dates. | Always provide `meeting_date` in form for accurate `due_date` resolution (`RESOLVED` provenance). | TD-017 |

---

#### Release Checklist (Pre-Tag)

- [x] All `composer check` checks pass (8/8: Route List, Static Analysis/Larastan level 5, Tests/PHPUnit, Code Style/Pint, Composer Validate, Platform Reqs, Frontend Build, Clean Migrations)
- [x] Feature tests pass against `notas_ia_test` database (22 feature + 10 unit test classes)
- [x] `php artisan ai:status` reports READY for configured providers (OpenAI, LM Studio, Google)
- [x] Security checklist reviewed and signed off (see Security checklist section above)
- [ ] CHANGELOG.md updated (if separate from this section)
- [ ] Version bumped in `composer.json` and relevant config files
- [ ] Release candidate tag created: `git tag -a v0.1.0-rc.1 -m "Release candidate v0.1.0-rc.1"`
- [ ] Release notes published to GitHub Releases (draft or pre-release)

