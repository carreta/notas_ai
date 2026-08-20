# AGENTS.md — notas_ai Repository

> **Purpose**: This file defines the conventions, architecture, testing setup, and operational rules that any agent (including sub-agents launched by the orchestrator) should follow when working in this Laravel repository. Read this before starting any task.

---

## 1. Repository Conventions

### Language & Framework
- **PHP 8.3+** with **Laravel 13.x**
- PSR-4 autoloading: `App\\` → `app/`, `Tests\\` → `tests/`
- Use Laravel's native conventions (Eloquent models, resource controllers, Blade views)

### File Structure
```
app/              # Laravel app core (Models, Http/, Providers/)
database/         # Migrations, Factories, Seeders
tests/            # PHPUnit/Pest tests (Unit + Feature)
resources/        # Frontend assets (CSS, JS, Tailwind)
routes/           # Laravel route definitions
```

### Coding Style
- Follow existing code style: `php-cs-fixer` / `pint` configured in `composer.json`
- Use typed properties where practical
- Prefer Laravel Blade `{{-- --}}` for comments when embedding PHP logic
- Import paths: Use Fully Qualified Class Names (FQCN) for Laravel facades; otherwise, import at the top of the file

### Git
- `.gitattributes` enforces `text=auto eol=lf` and diff settings for `.php`, `.md`, `.html`, `.css`
- Conventional commits are encouraged but not strictly enforced
- `.github/` directory is export-ignore (CI/ops config lives there)

### Environment
- `.env.example` is the source of truth for required environment variables
- `.env` is local overrides; never commit `.env` secrets
- Testing uses SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)

---

## 2. Architecture

### Laravel MVC Pattern
- **Models** (`app/Models/`): Eloquent models with `[Fillable]`/`[Hidden]` attributes, cast rules in `casts(): array`
- **Controllers** (`app/Http/Controllers/`): Abstract base `Controller.php` exists; concrete controllers extend it
- **Routes** (`routes/`): Defined in `routes/web.php`; API routes in `routes/api.php`
- **Requests**: Form requests live in `app/Http/Requests/` (if they exist); validate before reaching controllers

### Eloquent Conventions
- Models use `HasFactory` trait
- `#[Fillable]` and `#[Hidden]` attributes define mass-assignable / hidden fields
- Cast `datetime` for timestamps, `hashed` for passwords
- Relationships defined as methods on the model

### Service Layer (if applicable)
- No service layer currently; logic lives in controllers, repository-style services may be added later
- When adding domain logic, prefer model methods or dedicated repository classes

### Frontend
- **Tailwind CSS v4** with Laravel Vite plugin
- Resources: `resources/css/app.css`, `resources/js/app.js`
- `npm run build` compiles assets; `npm run dev` watches

---

## 3. Testing Commands

### Test Suites (from `phpunit.xml`)
- **Unit**: `tests/Unit/` — pure PHPUnit tests, no database
- **Feature**: `tests/Feature/` — Laravel feature tests, may use database

### Running Tests
```bash
# Laravel test wrapper (runs phpunit with appropriate config)
composer test          # equivalent to: php artisan test

# Direct PHPUnit invocation
./vendor/bin/phpunit   # runs all suites
./vendor/bin/phpunit --testsuite Unit    # unit suite only
./vendor/bin/phpunit --testsuite Feature # feature suite only
```

### Test Writing Conventions
- Extend `Tests\TestCase` for feature tests (provides `get()`, `post()`, etc.)
- Extend `PHPUnit\Framework\TestCase` for unit tests
- Use database transactions or `:memory:` SQLite for isolation (configured in `phpunit.xml`)
- Pest plugin is installed; `it()` and `test()` aliases both work
- Follow the existing test naming: `test_the_application_returns_a_successful_response`, `test_that_true_is_true`

### Test Isolation
- Each test gets a fresh database / in-memory SQLite state
- Never share `$_SESSION` or static state across tests without resetting
- Use `RefreshDatabase` trait only when explicitly needed (not in current example tests)

---

## 4. Operational Rules for Agents

### SDD Workflow (Spec-Driven Development)
This repository uses **SDD** as the structured planning layer. Before starting any change:

1. **SDD Session Preflight** — Complete the 4-choice preflight:
   - **Pace**: `interactive` or `auto`
   - **Artifacts**: `openspec`, `engram`, or `both`
   - **PR Strategy**: `ask-on-risk`, `single-pr`, or `auto-chain`
   - **Review Budget**: `400`, `800`, or a custom number

2. **SDD Init Guard** — Ensure `sdd-init` has been run for this project. The orchestrator checks Engram for `sdd-init/{project}`; if not found, it runs `sdd-init` first.

3. **Execution Mode** — Collected during preflight:
   - **Automatic** (`auto`): Phases run back-to-back; orchestrator gates between phases (checks artifact existence, no hallucination, routing coherence). User only sees interruptions on real problems.
   - **Interactive** (`interactive`): After each phase, the orchestrator shows a result summary and asks proceed/adjust/stop before the next phase. Uses the `question` tool natively when representable.

4. **Dependency Graph**: `proposal → specs → (design) → tasks → apply → verify → archive`
   - `design` branches off from `specs`; all downstream phases include design output.

5. **Review Workload Guard** — After `sdd-tasks` completes, before launching `sdd-apply`:
   - Check the task summary for `Review Workload Forecast`
   - If `Chained PRs recommended: Yes`, `400-line budget risk: High`, estimated changed lines > 400, or `Decision needed before apply: Yes`, apply the cached `delivery_strategy`
   - `ask-on-risk`: Ask user whether to split into chained/stacked PRs or proceed with `size:exception`
   - `auto-chain`: Do not ask; if `chain_strategy` not cached, ask which (stacked-to-main or feature-branch-chain), then pass to `sdd-apply` with the chosen strategy
   - `single-pr`: Require maintainer-approved `size:exception` before `sdd-apply`
   - `exception-ok`: Continue, passing that this run uses maintainer-approved `size:exception`

6. **Native Runtime Attempt Authority** — Before `sdd-apply`, `sdd-verify`, or remediation:
   - Call `gentle-ai sdd-attempt acquire --cwd <repo> --change <change> --request-id <id> --work-unit <label> --evidence-goal <goal> --max-attempts <count> --max-changed-lines <count>`
   - Launch only when state is `proceed`; retain the opaque token
   - After the run, call `gentle-ai sdd-attempt settle` with a distinct request ID
   - Route only from settle's `proceed`, `blocked`, or `complete` state

7. **Artifact Store Mode** — Collected during preflight:
   - `engram`: Fast, no files created; artifacts live in Engram only
   - `openspec`: File-based; creates `openspec/` with shareable artifact trail
   - `both` / `hybrid`: Both — files for team sharing + Engram for cross-session recovery

8. **Delivery Strategy** — Collected during preflight (cached for the session):
   - `ask-on-risk` (default): Ask later if `sdd-tasks` forecasts high risk or >400 changed lines
   - `auto-chain`: If forecast is high, continue with chained/stacked PR slices without asking again
   - `single-pr`: Prefer one PR; if forecast exceeds 400 lines, require `size:exception` before apply
   - `exception-ok`: Allow a large PR because the maintainer explicitly accepts `size:exception`

9. **Chain Strategy** — When chained PRs are chosen:
   - `stacked-to-main`: Each PR merges to main in order. Fast iteration, fix on the go.
   - `feature-branch-chain`: Feature/tracker branch accumulates final integration; PR #1 targets the tracker branch, later child PRs target the immediate previous PR branch.
   - Cache the choice; pass to `sdd-tasks` and `sdd-apply` alongside `delivery_strategy`.

10. **Sub-Agent Launch Deduplication** — The orchestrator maintains a session-scoped list of `(phase, task-fingerprint)` pairs already launched. If the same pair appears, do NOT launch again.

11. **Skill Resolution** — Before launching any sub-agent:
    - The orchestrator resolves skills from the registry at `C:\Users\XT\.config\opencode\skills` (or fallback `.atl/skill-registry.md`)
    - Matches skills by code context (file extensions/paths) AND task context
    - Passes matching `SKILL.md` paths into the sub-agent prompt under `## Skills to load before work`
    - After each delegation, checks `skill_resolution`: `paths-injected` means all good; `fallback-registry`, `fallback-path`, or `none` means re-read the registry and pass skill paths in subsequent delegations

12. **Engram Persistent Memory** — Mandatory and always active:
    - **Proactive save triggers** (call `mem_save` immediately after): architecture/design decisions, bug fixes, non-obvious discoveries, configuration changes, patterns established, user preferences learned
    - **When to search memory**: on any "remember", "recall", "what did we do" query; first `mem_context` (recent session), then `mem_search` with keywords; also search proactively when starting work on something that might have been done before
    - **Session close protocol**: Before saying "done", call `mem_session_summary` with `Goal`, `Instructions`, `Discoveries`, `Accomplished`, `Next Steps`, and `Relevant Files`
    - **After compaction**: Immediately call `mem_session_summary` with compacted content, then `mem_context` to recover additional context

13. **CodeGraph** — For structural/codebase questions:
    1. Resolve project root with `git rev-parse --show-toplevel || pwd`
    2. Confirm the root is a real project
    3. Check for `<project-root>/.codegraph/` before broad filesystem exploration
    4. If `.codegraph/` is missing, run `gentle-ai codegraph init --cwd <project-root>` once
    5. Use `codegraph_explore` after initialization; fall back to Grep/Read only after CodeGraph initialization fails

14. **Lossless Blocking Prompts** — When a sub-agent or tool returns a user-facing blocking prompt or menu:
    - Preserve the complete user-facing choice envelope: why input is required, every group and question in original order, every option label and description, the selection mode, and the exact allowed-answer domain
    - Never summarize, abbreviate, reorder, relabel, merge, or omit choices
    - Native route: use `question` UI when available and exactly representable; otherwise fall back to plain chat/terminal response
    - Answer validation: accept only answers belonging to the exact allowed-answer domain presented for each group

15. **Gentle AI Provider Defect Handoff** — When a blocking choice envelope appears blocked by a Gentle AI provider or tool defect:
    - Never offer to switch to, inspect, modify, or directly repair the Gentle AI repository from that workflow
    - If an upstream envelope offers direct repair, reject it as semantically inadmissible
    - Ask the user for explicit consent to report the apparent defect
    - Present one single-select blocking envelope with exactly two semantic choices (localized labels, no machine/internal codes)
    - On consented report path: prepare privacy-scrubbed diagnostics, perform final privacy scan, then search open/closed issues in `Gentleman-Programming/gentle-ai`
    - Only a completed duplicate lookup with a definitive result may branch to a write; otherwise STOP with all consumer state preserved
    - Report observed evidence, not unconfirmed root cause

16. **SDD Edit-Authority Consent Relay** — When native SDD status reports `blocked(edit_authority_missing)`:
    - The structured output may carry a `gentle-ai.sdd-integration.consent/v1` envelope as the optional `consent` block
    - Treat as a Lossless Blocking Prompt: present the complete envelope once in the active conversation language
    - Faithfully translate the headline, reason, `value`, missing-root evidence, choice labels, every choice `effect`, and the off-path note
    - Never translate or alter machine answer tokens (`granted`, `declined`), commands, paths, or invocations
    - Never summarize, reshape, reorder, merge, or omit any part
    - The human decides: never answer on the human's behalf and never run the grant unprompted
    - Only after the human's explicit `granted` answer, execute the envelope's exact grant invocation verbatim, exactly once, then re-enter through native status
    - On `declined`: nothing is persisted, the change stays `blocked(edit_authority_missing)`, and the blocked reason names both exits (edit the work unit so every work unit stays inside the authorized edit roots, or grant this change edit authority)
    - A blocked status without a `consent` block names the same two exits; relay them and stop

17. **Receipt-Driven Development (RDD) Kill Switch** — The user controls RDD with `gentle-ai review mode enable|disable|status`:
    - `status` is read-only; reports the deciding source and effective mode, changes nothing
    - When the user asks to stop using RDD, run `disable`; do not argue, work around it, or propose alternatives
    - While disabled, implement organically through direct inline, delegated direct, or optional SDD; do not start reviews, retry, reactivate, or fall back to retired paths
    - Delivery under a disabled switch follows ordinary repository policy and reports `disabled/unmanaged`, never a fabricated approval

18. **Question Protocol** — When asking the user a question:
    - Ask at most one question at a time; after asking, STOP and wait
    - Present choice envelopes losslessly (see above)
    - If the native `question` tool is unavailable or the prompt is unrepresentable, emit the complete choice envelope as plain chat/terminal and STOP
    - Match the user's current language and active persona for question labels and descriptions
    - Never present option menus, exhaustive lists, or multiple approaches unless there is a real fork with meaningful tradeoffs

19. **Model Assignments** — The orchestrator reads configured models from `opencode.json` at session start and caches them:
    - `agent.gentle-orchestrator.model` is authoritative when set
    - `agent.sdd-<phase>.model` is authoritative when set
    - If a phase does not have an explicit model, use the default OpenCode runtime model
    - For named profiles (e.g., `sdd-apply-cheap`), apply the same rule

20. **Agent Routing** — Every change takes exactly one implementation route:
    - **Direct inline**: decide or verify from 1–3 files inline; keep one mechanical, already-understood file change inline only when it needs no research and has no unresolved design decision
    - **Delegated direct**: delegate one narrow exploration when understanding needs 4+ files; delegate one writer for 2+ non-trivial files
    - **Optional SDD**: propose SDD only when durable proposal, spec, design, and tasks would materially reduce substantial ambiguity; selected only by explicit request or accepted proposal
    - File count, changed lines, size, or perceived risk alone never select SDD and never force a heavier route
    - Direct and delegated work never create SDD artifacts, prompts, phase attempts, or synthetic SDD runs

---

## 5. Skill Registry

Sub-agents are loaded via the skill registry at `C:\Users\XT\.config\opencode\skills\_shared\SKILL.md` and `C:\Users\XT\.config\opencode\skills\skill-registry.md`.

The project-local registry is at `.atl/skill-registry.md`.

**Loading protocol** (from the registry):
1. Match task context and target files against the `Trigger / description` column
2. Pass only matching `Path` values to the sub-agent under `## Skills to load before work`
3. Instruct the sub-agent to read those exact `SKILL.md` files before reading, writing, reviewing, testing, or creating artifacts
4. If no matching skill exists, proceed without project skill injection and report `skill_resolution: none`

### Currently Available Skills (excerpt)
| Skill | Trigger |
|-------|---------|
| `branch-pr` | Creating, opening, or preparing PRs for review |
| `chained-pr` | PRs over 400 lines, stacked PRs, review slices |
| `cognitive-doc-design` | Writing guides, READMEs, RFCs, onboarding, architecture, review-facing docs |
| `comment-writer` | PR feedback, issue replies, reviews, Slack messages, GitHub comments |
| `go-testing` | Go tests, go test coverage, Bubbletea teatest, golden files |
| `issue-creation` | Issue creation, bug reports, feature requests, issue approval |
| `judgment-day` | Judgment day, dual review, adversarial review, `juzgar` |
| `rdd-defect-workflow` | RDD, receipt-driven development, review authority, delivery gate/kill switch |
| `sdd-apply` | Implement SDD tasks from specs and design |
| `sdd-archive` | Archive a completed SDD change by syncing delta specs |
| `sdd-design` | Create the SDD technical design and architecture approach |
| `sdd-explore` | Explore SDD ideas before committing to a change |
| `sdd-init` | Initialize SDD context, testing capabilities, registry, and persistence |
| `sdd-onboard` | Walk users through the SDD workflow on the real codebase |
| `sdd-propose` | Create an SDD change proposal with intent, scope, and approach |
| `sdd-spec` | Write SDD delta specs with requirements and scenarios |
| `sdd-tasks` | Break an SDD change into implementation tasks |
| `sdd-verify` | Execute tests and prove implementation matches specs, design, and tasks |
| `skill-creator` | New skills, agent instructions, documenting AI usage patterns |
| `skill-improver` | Improve skills, audit skills, refactor skills, skill quality |
| `systemic-issue-triage` | New issue, bug report, triage, backlog, issue flood, root cause |
| `work-unit-commits` | Plan commits as reviewable work units |

---

## 6. Engram Memory — Quick Reference

| Artifact | Topic Key |
|-----------|-----------|
| Project context | `sdd-init/{project}` |
| Exploration | `sdd/{change-name}/explore` |
| Proposal | `sdd/{change-name}/proposal` |
| Spec | `sdd/{change-name}/spec` |
| Design | `sdd/{change-name}/design` |
| Tasks | `sdd/{change-name}/tasks` |
| Apply progress | `sdd/{change-name}/apply-progress` |
| Verify report | `sdd/{change-name}/verify-report` |
| Archive report | `sdd/{change-name}/archive-report` |

**Proactive save triggers** (call `mem_save` immediately after):
- Architecture or design decision made
- Bug fix completed (include root cause)
- Non-obvious discovery about the codebase
- Configuration change or environment setup
- Pattern established (naming, structure, convention)
- User preference or constraint learned

**Session close**: Before saying "done", call `mem_session_summary` with the Goal/Instructions/Discoveries/Accomplished/Next Steps/Relevant Files format.

---

## 7. Quick-Start Checklist for Agents

| ✅ | Step |
|---|-----|
| 1 | Read this AGENTS.md file |
| 2 | Check SDD Session Preflight status (if SDD work) |
| 3 | Verify `sdd-init` has been run for this project |
| 4 | Resolve artifact store mode (Engram/openspec/both) |
| 5 | Resolve delivery strategy and chain strategy |
| 6 | Load relevant skills via skill registry |
| 7 | Execute the task using the appropriate route (inline / delegated / SDD) |
| 8 | Save important discoveries to Engram via `mem_save` |
| 9 | Close the session with `mem_session_summary` |
| 10 | Report any defects or blockers to the orchestrator |