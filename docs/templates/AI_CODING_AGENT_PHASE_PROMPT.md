# AI Coding Agent — Compact Phase Prompt

Implement Phase [N] — [NAME] for AI Meeting Notes.

Authoritative context:
- `docs/PROJECT_ENGINEERING_BLUEPRINT.md`
- `docs/phases/[phase-file].md`
- applicable FR/NFR IDs;
- accepted ADRs relevant to this phase.

Current baseline:
[Only durable facts from completed phases that materially affect this work.]

Scope:
- item;
- item.

Do not:
- implement future phases;
- perform unrelated refactors;
- resolve open TEAM DECISION items silently;
- add production dependencies unless required and justified;
- invent schema objects, routes, config keys, or file paths;
- call live AI services from mandatory automated tests.

Before editing:
Use targeted repository search. Inspect relevant migrations/models/config/tests before relying on them. Reuse established project context rather than rescanning unrelated files.

Verification:
1. smallest relevant tests while implementing;
2. focused phase tests;
3. full project quality gate once stable;
4. phase-specific security/integrity checks.

Final report:
- implementation summary;
- requirements completed;
- files changed;
- exact test/check results;
- schema/dependency/architecture changes;
- unresolved risks;
- suggested commit message.

Do not claim completion while a mandatory check fails.
