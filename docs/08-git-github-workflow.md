# Git and GitHub Workflow

## 1. Branch model

Recommended baseline:

```text
main
  |
  +-- develop
        |
        +-- feature/<issue>-<short-name>
        +-- fix/<issue>-<short-name>
        +-- docs/<issue>-<short-name>
```

`TEAM DECISION TD-013`: the team may propose trunk-based development instead of `develop`, but must document why it better serves this exercise before feature work begins.

## 2. Protected branches

At minimum:

- no direct feature pushes to `main`;
- release enters `main` through PR;
- feature work enters the agreed integration branch through PR;
- required CI must pass before merge;
- at least one non-author approval unless the team lead records an exception.

## 3. Branch naming

Examples:

```text
feature/12-meeting-intake
feature/21-ai-provider
feature/27-analysis-results-ui
fix/34-invalid-ai-response
```

Include the GitHub Issue number when possible.

## 4. Commit discipline

Commits should be coherent and understandable.

Recommended style:

```text
feat: persist meeting submissions
feat: add AI analysis contract
fix: reject malformed analysis payload
 test: cover provider timeout state
 docs: record AI provider ADR
```

Do not create meaningless history such as `changes`, `final`, `fix stuff`, `again`.

## 5. Pull request contract

Every substantive PR should include:

- linked Issue;
- problem/scope;
- implementation summary;
- screenshots when UI changed;
- tests added/changed;
- commands/checks executed;
- schema changes;
- dependencies added;
- architecture decisions/deviations;
- known limitations;
- review focus/request.

Use `templates/PULL_REQUEST_TEMPLATE.md` as the content template when configuring GitHub.

## 6. Review rules

Review the change, not the author.

Reviewers should check:

1. requirement fit;
2. architecture boundary;
3. schema reality;
4. failure cases;
5. security;
6. tests;
7. readability/maintainability;
8. unintended scope expansion.

Comments should distinguish:

- **BLOCKING** — must change before merge;
- **QUESTION** — needs explanation/discussion;
- **SUGGESTION** — optional improvement;
- **NIT** — minor style preference.

## 7. Merge strategy — TEAM DECISION `TD-014`

Choose one primary approach before feature PRs merge:

- squash merge;
- merge commit;
- rebase merge.

Discuss traceability, readability, revert behavior, and team familiarity.

## 8. Conflict rule

When a conflict occurs, the branch author should not blindly choose "ours" or "theirs". The developer must understand both changes, coordinate with the other owner when behavior overlaps, resolve intentionally, rerun tests, and note meaningful conflict resolution in the PR.

## 9. Ownership is not exclusivity

Assigning an Issue means accountability, not private ownership of that area. Developers may collaborate, pair, or review each other's code.

## 10. GitHub Issues

Each implementation Issue should include:

- objective;
- relevant FR/NFR IDs;
- acceptance criteria;
- dependencies/blockers;
- out-of-scope notes;
- test expectation.

## 11. Suggested labels

```text
area:frontend
area:backend
area:ai
area:testing
area:docs
kind:feature
kind:bug
kind:decision
priority:must
priority:should
blocked
ready-for-review
```

## 12. Initial issue set

Suggested kickoff backlog:

1. Bootstrap repository quality baseline.
2. Resolve database strategy (`TD-008`).
3. Resolve branch/merge strategy (`TD-013`, `TD-014`).
4. Resolve UI approach (`TD-007`).
5. Implement meeting migration/model.
6. Implement meeting submission validation.
7. Implement meeting create workflow.
8. Design AI application contract (`TD-002`).
9. Select provider/model (`TD-009`).
10. Implement provider adapter.
11. Implement structured-response validation.
12. Implement analysis persistence.
13. Implement result UI.
14. Implement history/detail UI.
15. Implement AI failure cases.
16. Add CI workflow.
17. Execute release verification.
18. Run retrospective and engineering evaluation.
