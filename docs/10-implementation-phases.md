# Implementation Phases

The project is intentionally phased so different developers can work concurrently without turning the repository into uncontrolled parallel changes.

## Phase 0 — Foundation and team decisions

**Goal:** create a reproducible Laravel baseline and resolve only the decisions required before parallel feature work.

Covers:
- repository bootstrap;
- environment README;
- test baseline;
- formatter/build baseline;
- GitHub integration workflow;
- initial CI;
- high-priority team decisions;
- architecture skeleton only where needed.

Do not implement meeting analysis features yet.

## Phase 1 — Meeting intake

**Goal:** support validated meeting creation and retrieval without requiring a live AI provider.

Covers: FR-001, FR-002.

This creates a stable contract that frontend/backend work can integrate against.

## Phase 2 — AI analysis core

**Goal:** implement provider-neutral analysis orchestration, selected adapter, structured validation, persistence, and controlled failures.

Covers: FR-003, FR-004, FR-005, FR-008, FR-010.

This is the most important architectural collaboration phase.

## Phase 3 — Results and history

**Goal:** make completed/failed analyses understandable to the user.

Covers: FR-006, FR-007.

Frontend work should consume approved application/persistence behavior rather than inventing AI rules in the view.

## Phase 4 — Hardening and release

**Goal:** close integration defects and prove the project is releasable as an exercise.

Covers:
- FR-008 hardening;
- FR-009 decision/implementation or explicit deferral;
- FR-011 optional;
- FR-012;
- complete quality gate;
- clean migration;
- security checklist;
- manual acceptance;
- release tag candidate.

No broad new feature development.

## Phase 5 — Retrospective

No new product functionality.

Collect repository evidence and evaluate:

- what integrated cleanly;
- where branches conflicted;
- review turnaround/quality;
- defects found before vs after merge;
- decisions that worked/failed;
- AI-assisted coding effectiveness;
- process changes for the team's next project.

See `templates/RETROSPECTIVE.md`.
