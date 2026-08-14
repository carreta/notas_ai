# AI Meeting Notes — Engineering Documentation

**Project type:** Small collaborative engineering exercise  
**Primary objective:** Evaluate how a newly formed development team plans, divides, implements, reviews, integrates, tests, and releases a small AI-enabled Laravel application using GitHub.  
**Product objective:** Convert meeting notes/transcripts into a structured summary of decisions, action items, owners, due dates, and open questions.

## How to use this documentation

Read these documents in order before coding:

1. `PROJECT_ENGINEERING_BLUEPRINT.md` — project authority, scope, governance, and engineering rules.
2. `01-functional-requirements.md` — stable FR identifiers and acceptance criteria.
3. `02-non-functional-requirements.md` — quality, security, reliability, and maintainability requirements.
4. `03-architecture.md` — fixed architectural boundaries and intentionally open design areas.
5. `04-data-model.md` — required domain concepts and schema constraints.
6. `05-ai-integration.md` — AI contract, failure modes, validation, and testing rules.
7. `06-security-privacy.md` — data handling and secret-management rules.
8. `07-testing-quality-gates.md` — mandatory verification before merge/release.
9. `08-git-github-workflow.md` — branches, commits, pull requests, reviews, and conflict rules.
10. `09-team-working-agreement.md` — collaboration protocol and expectations.
11. `10-implementation-phases.md` — project sequence and phase ownership model.
12. `11-open-team-decisions.md` — decisions deliberately left to the team.
13. `12-definition-of-done-traceability.md` — completion criteria and traceability matrix.
14. `13-kickoff-runbook.md` — first-session procedure for the team lead.
15. `14-team-evaluation-scorecard.md` — evidence-based evaluation guide for the exercise.
16. `phases/` — executable phase contracts.
17. `adr/` — architecture decision records.
18. `templates/` — reusable templates for PRs, checkpoints, retrospectives, and issues.

## Governing principle

This is an engineering exercise, not a race to generate code. A successful result demonstrates:

- traceable requirements;
- small, coherent branches;
- understandable commits;
- reviewable pull requests;
- evidence-based code review;
- automated tests;
- explicit architecture decisions;
- safe AI integration;
- successful integration into a stable branch;
- a reproducible release;
- useful retrospective evidence about team performance.

## Deliberately unresolved decisions

Some technical choices are intentionally marked `TEAM DECISION`. The team must resolve them through discussion and, when architectural, an ADR. These are not omissions. They exist to evaluate technical judgment, communication, trade-off analysis, and ability to reach documented decisions.

A `TEAM DECISION` may not be silently resolved in code.
