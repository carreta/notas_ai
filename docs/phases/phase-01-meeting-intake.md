# Phase 1 — Meeting Intake

## Goal

Implement a stable, tested meeting-submission workflow independent of the AI provider.

## Requirements

FR-001, FR-002.

## Before coding

Resolve TD-001 and inspect approved Phase 0 schema/database decisions.

## In scope

- meeting migration/model according to approved schema;
- text validation;
- create/store workflow;
- initial status;
- basic route/view/response needed to submit;
- retrieval/detail skeleton if necessary for acceptance;
- feature tests.

## Out of scope

- live/provider-specific AI code;
- structured analysis persistence;
- polished result/history UI.

## Required tests

- valid submission;
- blank input;
- whitespace-only input;
- exact/near size boundaries;
- too-long input;
- persisted status/source content;
- safe rendering where source is displayed.

## Exit criteria

FR-001/002 acceptance criteria pass and the full baseline quality gate remains green.
