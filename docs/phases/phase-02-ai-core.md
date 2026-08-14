# Phase 2 — AI Analysis Core

## Goal

Implement the provider-neutral analysis workflow and one real provider adapter while keeping automated tests independent of the live API.

## Requirements

FR-003, FR-004, FR-005, FR-008, FR-010.

## Decisions required before/early in phase

TD-002, TD-003, TD-004, TD-006, TD-009, TD-010, TD-011.

Record required ADRs before dependent implementation is merged.

## In scope

- application-owned analysis input/result contract;
- fake provider for tests;
- selected real provider adapter;
- prompt/schema version;
- structured output parsing/validation;
- state transitions;
- analysis/result persistence;
- failure mapping;
- safe metadata;
- unit/feature tests.

## Required tests

See `05-ai-integration.md` plus state/persistence tests.

## Manual acceptance

One non-sensitive real-provider request after deterministic tests pass.

## Exit criteria

- no mandatory test requires network;
- valid fake result reaches completed state;
- provider failures are controlled;
- malformed output cannot become trusted completed analysis;
- real smoke request succeeds or external-provider limitation is documented without weakening deterministic verification.
