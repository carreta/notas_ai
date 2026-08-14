# Team Kickoff Runbook

## Purpose

Give the team enough shared context to start without the team lead pre-solving every implementation decision.

## Before the meeting

Team lead:

- creates the Laravel repository;
- adds this `docs/` package;
- ensures every developer can access GitHub;
- creates/assigns the initial GitHub Project/Issues if desired;
- does not pre-implement AI/schema decisions that are marked open.

## Kickoff agenda — 60 to 90 minutes

### 1. Product walkthrough — 10 min

Explain only:

- user pastes meeting text;
- AI returns structured notes;
- result can be revisited;
- audio, auth, integrations, and extra features are out of scope.

### 2. Engineering objective — 10 min

Explain that the exercise evaluates collaboration as much as code quality.

### 3. Documentation review — 15 min

Focus on:

- FRs;
- fixed architecture boundaries;
- Definition of Done;
- Git/PR rules;
- open decisions.

### 4. Resolve kickoff decisions — 15 to 25 min

Resolve at minimum:

- TD-007 UI approach;
- TD-008 database;
- TD-012 static analysis;
- TD-013 branch model;
- TD-014 merge strategy;
- TD-015 tie-break/decision mechanism.

Do not spend kickoff solving every Phase 2 AI detail.

### 5. Decompose Phase 0/1 — 15 min

Create Issues with acceptance criteria and dependencies.

### 6. Assign first work — 10 min

Prefer parallel but integrable work. Example for four developers:

- Developer A: meeting schema/model + tests;
- Developer B: request validation/create workflow + tests, coordinated with A;
- Developer C: UI skeleton/intake form according to agreed UI approach;
- Developer D: CI/quality baseline + fixture/demo data.

No one starts provider integration until Phase 2 decisions are ready.

## Team lead behavior during exercise

Prefer questions such as:

- "What requirement supports this change?"
- "What alternatives did you consider?"
- "How will another branch depend on this?"
- "How will we test that without the live provider?"
- "Is this MVP scope or future scope?"

Avoid immediately prescribing the solution unless a fixed project constraint is being violated.

## Evaluation notes

Capture examples, not impressions:

- PR that was especially reviewable/unreviewable;
- blocker surfaced early/late;
- decision documented well/poorly;
- conflict resolved collaboratively;
- defect caught by test/review/after merge;
- AI-generated code understood/not understood;
- estimate/dependency that proved inaccurate and why.
