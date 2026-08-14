# Team Working Agreement

## 1. Purpose

This agreement exists because the project is also an evaluation of how the team works together.

## 2. Expected behaviors

Each developer is expected to:

- communicate blockers early;
- keep assigned Issues current;
- work in small branches;
- avoid silent architectural changes;
- ask for review when work is actually reviewable;
- review another developer's code seriously;
- understand AI-generated code before submitting it;
- run required verification before requesting merge;
- distinguish facts from assumptions during technical discussions.

## 3. Daily coordination

Use a short daily update, written or spoken:

```text
Yesterday: what I completed / merged.
Today: what I intend to move forward.
Blocked: what prevents progress and who/what is needed.
Integration risk: any change that may affect another branch.
```

This should be concise. It is coordination, not a status-performance ceremony.

## 4. Decision protocol

For a `TEAM DECISION`:

1. owner frames the problem;
2. team identifies viable alternatives;
3. constraints/trade-offs are stated;
4. disagreement is discussed with evidence;
5. decision is made by the agreed decision owner/mechanism;
6. ADR/decision register is updated;
7. implementation follows the documented choice.

The team lead should avoid solving every open decision for the team unless the discussion is blocked or project constraints are being violated.

## 5. AI-assisted development

AI tools are allowed.

The human developer remains accountable for:

- correctness;
- security;
- architecture fit;
- tests;
- explaining the implementation during review.

If a developer cannot explain material AI-generated code, it is not ready to merge.

## 6. Review expectation

Every developer should both author and review at least one substantive PR during the exercise, when team size allows.

## 7. Definition of blocked

An Issue is blocked when progress requires information, a decision, code, access, or action outside the assignee's control. Mark it `blocked` and identify the dependency. Do not remain silently blocked.

## 8. Scope discipline

A useful idea discovered during development is not automatically part of v0.1. Create a future Issue or discuss scope change; do not quietly add it to a feature branch.

## 9. Team lead observation areas

The team lead should observe:

- quality of decomposition;
- clarity of technical communication;
- initiative without unilateral architecture changes;
- willingness to surface uncertainty;
- review quality;
- conflict-resolution behavior;
- ability to integrate;
- test discipline;
- use of AI as an engineering aid rather than a substitute for understanding;
- estimation/forecast accuracy;
- response to failed tests and defects.

Avoid ranking developers solely by commit count, lines changed, or number of Issues closed.
