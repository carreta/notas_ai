# AI Meeting Notes — Project Engineering Blueprint

## 0. Document control

| Field | Value |
|---|---|
| Project | AI Meeting Notes |
| Version | 0.1.0 |
| Status | Ready for Team Kickoff |
| Owner | Team Lead |
| Repository | To be assigned after Laravel repository creation |
| Primary stack | Laravel / PHP / relational database / web UI / external LLM API |
| Target | Local development first; deployment is not required for the exercise unless added by the team lead |
| Updated | 2026-08-14 |

## 0.1 Source of truth

Order of authority:

1. Approved functional requirements.
2. Accepted ADRs and approved schema migrations.
3. Architecture and data-model documents.
4. AI, security, and privacy contracts.
5. Phase specifications.
6. Automated tests.
7. Application code.
8. Informal chat or assumptions.

If code and documentation disagree, the discrepancy must be resolved explicitly. Code does not silently redefine a requirement.

## 1. Project definition

### 1.1 Problem

Meeting notes are often unstructured. Important decisions, tasks, owners, dates, and unresolved questions become difficult to identify and follow up.

### 1.2 Product goal

Provide a small web application where a user submits plain-text meeting notes or a transcript and receives a validated, structured AI analysis.

### 1.3 Engineering goal

Use a bounded product to evaluate how the team:

- decomposes work;
- negotiates interfaces;
- uses Git branches;
- writes and reviews pull requests;
- handles merge conflicts;
- tests boundaries;
- integrates an AI dependency safely;
- documents architectural decisions;
- communicates blockers;
- integrates independently developed work.

The team-process goal has equal importance to the product goal.

### 1.4 Primary user

A single user who wants to turn meeting text into structured notes. Authentication and multi-tenant behavior are intentionally excluded from v0.1 unless the team lead later changes scope.

### 1.5 MVP input

Plain text only.

### 1.6 MVP output

The analysis must support these conceptual fields:

- concise summary;
- decisions;
- action items;
- action-item owner when identifiable;
- due date when stated or safely extractable;
- priority only when supported by the meeting content or explicitly inferred according to the agreed AI policy;
- open questions / unresolved topics.

### 1.7 In scope

- text submission;
- server-side validation;
- meeting persistence;
- AI analysis request;
- structured AI response validation;
- result persistence;
- result display;
- meeting history;
- failure handling;
- automated tests;
- GitHub collaboration workflow;
- CI baseline;
- engineering documentation.

### 1.8 Out of scope

- audio/video upload;
- speech-to-text;
- live meeting capture;
- calendar integrations;
- email/Slack/Teams integrations;
- authentication;
- organizations/workspaces;
- billing;
- mobile app;
- vector databases/RAG;
- autonomous follow-up agents;
- production-scale deployment;
- analytics dashboards;
- editing third-party meeting data.

Anything not explicitly in scope is excluded unless approved by scope change.

## 2. Fixed project constraints

1. Laravel is the application framework.
2. GitHub is the collaboration and code-review platform.
3. All production code reaches the integration branch through a pull request.
4. No developer pushes feature work directly to `main`.
5. AI access must be behind an application-owned interface/service boundary.
6. Raw AI output is untrusted external input and must be validated before being treated as application data.
7. API keys and secrets must never enter Git history.
8. Controllers/views must not contain the core AI orchestration logic.
9. The exercise should remain small enough to reach a release candidate in approximately one working week of coordinated effort; scope must be reduced rather than expanded if necessary.
10. Deliberately open decisions must be resolved explicitly and documented.

## 3. Success criteria

The exercise succeeds when:

- every Must requirement has evidence;
- the project can be installed from repository instructions;
- clean migrations succeed;
- the complete automated suite passes;
- formatting/static checks configured by the team pass;
- production assets build if the selected UI stack requires them;
- critical manual scenarios pass;
- AI failures are handled predictably;
- the repository contains no committed secret;
- every merged feature has a reviewed PR;
- no unresolved critical/high defect remains;
- open architectural choices have an accepted ADR or an explicit deferral;
- the team conducts a retrospective based on repository evidence rather than impressions alone.

## 4. Requirement and change discipline

Functional requirement identifiers are stable and must not be renumbered after work begins. Requirement changes update acceptance criteria, tests, and affected phase documents.

Architecture changes require an ADR. Production dependency additions require justification in the PR and, if architecturally significant, an ADR.

## 5. Architectural principles

- requirements before implementation;
- validate every external boundary;
- business rules separate from presentation;
- database schema is authoritative for persisted relationships;
- AI response is probabilistic but the application contract around it must be deterministic and testable;
- persist enough metadata to diagnose failures without logging unnecessary meeting content;
- fail explicitly rather than silently fabricating data;
- do not add abstractions or dependencies for hypothetical future needs;
- design seams that allow AI calls to be replaced by fakes in tests.

## 6. Working state model

Minimum analysis lifecycle:

```text
DRAFT / SUBMITTED
        |
        v
VALIDATED
        |
        v
ANALYZING
   |         |
   v         v
COMPLETED   FAILED
```

The team may refine names and persistence strategy through `TEAM DECISION TD-004`, but illegal transitions and failure semantics must remain explicit.

## 7. Error categories

Minimum categories:

- `VALIDATION_ERROR`
- `AI_CONFIGURATION_ERROR`
- `AI_DEPENDENCY_ERROR`
- `AI_TIMEOUT`
- `AI_RATE_LIMIT`
- `AI_INVALID_RESPONSE`
- `PERSISTENCE_ERROR`
- `AUTHORIZATION_ERROR` if authorization is later introduced
- `INTERNAL_ERROR`

User-facing messages must not expose secrets, provider credentials, stack traces, or internal filesystem paths.

## 8. Project phases

- Phase 0 — Repository foundation and team contract.
- Phase 1 — Meeting intake and persistence without AI.
- Phase 2 — AI contract and provider integration.
- Phase 3 — Structured results and history UI.
- Phase 4 — Integration hardening, failures, CI, and release.
- Phase 5 — Team retrospective and engineering evaluation.

Phase specifications are in `docs/phases/`.

## 9. Quality discipline

During implementation, run the smallest relevant tests first. Before a PR is considered merge-ready, run all checks required by its phase. Before release, run the complete project quality gate.

No developer or AI coding agent may claim a phase complete with a mandatory failing check.

## 10. AI coding-agent discipline

Developers may use coding agents. The developer remains responsible for every line submitted.

An agent must:

- inspect relevant repository evidence before editing;
- not invent schema/routes/configuration;
- implement only assigned scope;
- avoid unrelated refactors;
- add/update tests with behavior changes;
- report actual checks run;
- identify dependencies/schema/architecture changes;
- stop after the assigned definition of done is reached.

Token optimization means less redundant repository exploration, not less verification.

## 11. Deliberate team decisions

The canonical register is `11-open-team-decisions.md`. A team decision is resolved only when:

1. alternatives were considered;
2. constraints were checked;
3. consequences were articulated;
4. the decision was recorded;
5. affected documentation/tests were updated.

## 12. Definition of done

See `12-definition-of-done-traceability.md`. Writing code alone never satisfies Done.
