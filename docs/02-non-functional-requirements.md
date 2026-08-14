# Non-Functional Requirements

## NFR-SEC-001 — Secret management

AI/API credentials must exist only in environment/secret configuration and must never be committed. `.env.example` may contain variable names and safe placeholders only.

## NFR-SEC-002 — Untrusted content

User text and AI output are untrusted. Server validation and safe output rendering are mandatory.

## NFR-SEC-003 — Logging minimization

Logs must not contain API keys. Raw meeting transcripts should not be logged by default. If the team proposes content logging for debugging, it requires an accepted ADR/security decision with a clear reason and retention policy.

## NFR-REL-001 — Predictable state

A provider or persistence failure must not leave the meeting in a false completed state.

## NFR-REL-002 — Transaction boundaries

Related writes that must succeed together must have explicit transaction behavior. The team decides the exact boundary after schema/workflow discussion.

## NFR-REL-003 — External dependency isolation

Automated tests must not depend on live LLM availability unless explicitly marked as optional/manual integration checks.

## NFR-MAINT-001 — Separation of concerns

Controllers coordinate HTTP behavior; AI/provider logic lives in dedicated application/infrastructure components; validation belongs at boundaries; presentation does not contain core processing logic.

## NFR-MAINT-002 — Provider replaceability

Application/domain code must not require widespread changes to replace the selected AI provider. Exact abstraction shape is a team decision, but the boundary is mandatory.

## NFR-MAINT-003 — Architecture documentation

Any architectural deviation from approved docs requires an ADR or explicit documented change before merge.

## NFR-TEST-001 — Deterministic automated suite

The mandatory suite must be deterministic and use fake/stubbed AI responses.

## NFR-TEST-002 — Regression discipline

Every confirmed defect fixed during the exercise should receive a regression test when practical.

## NFR-PERF-001 — User-input request validation

Input validation should complete without contacting the AI provider. The exact text-size limit is `TD-001`.

## NFR-PERF-002 — AI timeout budget

The team must define an application-level AI timeout under `TD-003`. The system must not wait indefinitely.

## NFR-UX-001 — Visible processing state

The UI must clearly distinguish at least idle/input, processing, completed, empty result sections, and error states.

## NFR-UX-002 — Responsive baseline

The core workflow must remain usable on common desktop and mobile-width browsers. Pixel-perfect cross-browser parity is not required.

## NFR-COMP-001 — Runtime compatibility

Supported PHP, Laravel, Node/build, and database versions must be recorded in the repository after project initialization. Do not claim compatibility not exercised by the team.

## NFR-COLLAB-001 — Reviewability

Feature changes should be small enough for another developer to review meaningfully. Oversized PRs must be split unless there is a documented reason.

## NFR-COLLAB-002 — Integration evidence

A merged PR must have passing required checks and at least one non-author review unless the team lead explicitly documents an exception.
