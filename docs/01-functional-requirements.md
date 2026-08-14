# Functional Requirements

Identifiers are stable after kickoff.

## FR-001 — Submit meeting text

**Priority:** Must  
**Actor:** User

The application must allow the user to submit plain-text meeting content.

**Rules**
- Input is text, not a file.
- Server-side validation is mandatory.
- Empty/blank-only input is rejected.
- Maximum accepted length is `TEAM DECISION TD-001` and must be enforced server-side.

**Acceptance criteria**
- Valid text can be submitted.
- Blank input is rejected with actionable feedback.
- Over-limit input is rejected without calling the AI provider.
- HTML/script content is rendered safely when later displayed.

## FR-002 — Persist meeting record

**Priority:** Must

A successfully validated submission must create a durable meeting record before or as part of the analysis workflow according to the approved transaction/state design.

**Minimum persisted information**
- application-generated identifier;
- source text or approved source representation;
- processing status;
- timestamps.

Additional metadata is defined in the schema/AI documents.

**Acceptance criteria**
- Valid submissions can be retrieved later.
- Invalid submissions do not create a misleading completed record.
- Persistence failure produces a controlled failure.

## FR-003 — Request AI analysis

**Priority:** Must

The application must submit validated meeting content to the selected LLM provider through an application-owned AI abstraction.

**The application must request**
- summary;
- decisions;
- action items;
- owner when identifiable;
- due date when available;
- priority according to the agreed extraction/inference policy;
- open questions.

**Acceptance criteria**
- Provider-specific code does not leak into controllers/views.
- Tests can replace the provider with a fake/stub.
- Provider timeout/error does not mark analysis as completed.

## FR-004 — Validate structured AI response

**Priority:** Must

AI output must be parsed and validated against the application's expected structure before persistence/display as trusted structured data.

**Acceptance criteria**
- Malformed JSON/structure is rejected safely.
- Missing required top-level fields follow the approved validation/fallback policy.
- Incorrect types are rejected or normalized only according to documented rules.
- Invalid output generates `AI_INVALID_RESPONSE` or equivalent.

## FR-005 — Store analysis result

**Priority:** Must

A valid structured analysis must be associated with its meeting and persisted.

**Acceptance criteria**
- Result belongs to the correct meeting.
- Completion status changes only after valid result persistence.
- Failure during persistence cannot leave a false `COMPLETED` state.

## FR-006 — Display analysis

**Priority:** Must

The user must be able to view a completed meeting analysis.

**Minimum presentation**
- summary;
- decisions;
- action items;
- owners;
- due dates;
- priority when present;
- open questions.

**Acceptance criteria**
- Empty collections are represented intentionally, not as rendering errors.
- Missing optional owner/date/priority values are understandable.
- Meeting text and AI content are escaped/sanitized appropriately.

## FR-007 — Meeting history

**Priority:** Must

The application must provide a simple list/history of previously submitted meetings and allow opening a meeting/result.

**Acceptance criteria**
- History ordering is explicit and tested.
- Status is visible.
- A failed meeting does not appear as completed.
- Detail navigation retrieves the correct meeting.

## FR-008 — Controlled AI failure behavior

**Priority:** Must

The system must handle at least:

- missing provider configuration;
- network/provider failure;
- timeout;
- rate limiting if distinguishable;
- invalid provider response.

**Acceptance criteria**
- User sees safe feedback.
- Internal failure category is retained for diagnosis.
- Secrets/raw credentials are never displayed.
- Retry behavior, if offered, follows `TEAM DECISION TD-005`.

## FR-009 — Re-analysis / retry

**Priority:** Should

The team must decide whether v0.1 permits retrying a failed analysis and/or re-running a completed analysis.

This behavior is intentionally unresolved under `TD-005`. If deferred, UI and routes must not imply it exists.

## FR-010 — Analysis metadata

**Priority:** Should

For diagnosis and reproducibility, the application should persist safe analysis metadata, such as:

- provider identifier;
- model/config identifier where appropriate;
- start/completion timestamps;
- duration if practical;
- failure category;
- prompt/schema version identifier.

Do not persist provider secrets.

## FR-011 — Health/readiness indication

**Priority:** Could

A lightweight development/admin-only indicator may show whether required AI configuration exists. It must not reveal the secret value.

## FR-012 — Release/demo seed or fixture

**Priority:** Should

The repository should contain at least one non-sensitive sample meeting transcript or test fixture usable for deterministic demonstration and acceptance testing.

## MVP traceability summary

| ID | Priority | Target phase |
|---|---|---|
| FR-001 | Must | 1 |
| FR-002 | Must | 1 |
| FR-003 | Must | 2 |
| FR-004 | Must | 2 |
| FR-005 | Must | 2 |
| FR-006 | Must | 3 |
| FR-007 | Must | 3 |
| FR-008 | Must | 2/4 |
| FR-009 | Should | 4 or deferred |
| FR-010 | Should | 2 |
| FR-011 | Could | 4 |
| FR-012 | Should | 0/4 |
