# Open Team Decisions

These decisions are intentionally unresolved. They are part of the exercise.

## Rules

- Do not resolve a decision silently in implementation.
- Architectural decisions use an ADR.
- The team lead may impose a deadline/constraint but should allow the team to propose the solution.
- Choose the simplest option that satisfies the actual MVP, not hypothetical future scale.

| ID | Decision | Must resolve by | ADR? | Status |
|---|---|---:|---|---|
| TD-001 | Maximum meeting text length | Phase 1 | No | Open |
| TD-002 | Exact AI port/interface and application result types | Phase 2 start | Yes | Open |
| TD-003 | AI timeout budget | Phase 2 | No/ADR if broader | Open |
| TD-004 | Synchronous request vs queue/job processing | Phase 2 start | Yes | Open |
| TD-005 | Retry/re-analysis behavior | Phase 4 | Yes if implemented materially | Open |
| TD-006 | Normalized result tables vs JSON/hybrid persistence | Before Phase 2 persistence | Yes | Open |
| TD-007 | Laravel UI approach (e.g. Blade baseline vs justified alternative) | Phase 0 | Yes if adding framework/dependency | Open |
| TD-008 | Development/CI database engine | Phase 0 | Yes | Open |
| TD-009 | LLM provider/model strategy | Phase 2 start | Yes | Open |
| TD-010 | Priority extraction/inference policy | Phase 2 | No/ADR if complex | Open |
| TD-011 | Relative due-date representation/resolution | Phase 2 | Yes | Open |
| TD-012 | Static-analysis tool/level | Phase 0 | No | Open |
| TD-013 | `develop` branch vs trunk-based integration | Kickoff | No/ADR optional | Open |
| TD-014 | GitHub merge strategy | Kickoff | No | Open |
| TD-015 | Decision-making mechanism when team cannot reach consensus | Kickoff | No | Open |

## Decision prompts

### TD-002 — AI abstraction

Team must answer:
- What does application code need from AI?
- What PHP type represents input?
- What PHP type represents success?
- How are failures represented?
- How is a fake injected in tests?
- How much provider-specific configuration may leak across the boundary?

### TD-004 — sync vs queue

Evaluate:
- MVP complexity;
- request timeouts;
- user loading experience;
- need for workers/infrastructure;
- failure/retry semantics;
- whether queueing teaches useful collaboration or merely adds scope.

A queue is not automatically "more professional".

### TD-006 — persistence

Evaluate what queries the MVP actually needs. Prefer minimum sufficient structure.

### TD-008 — database

Evaluate developer parity, CI simplicity, Laravel support, and whether production parity matters for this exercise.

### TD-009 — provider/model

Evaluate current official provider documentation, structured-output capabilities, cost, rate limits, credentials, and developer setup. Record the API/model assumptions used by the code.

### TD-011 — dates

Use explicit examples:
- "Friday"
- "tomorrow"
- "August 20"
- "next week"
- no date

The selected rule must lead to testable expected outputs.

## Closed-decision format

```text
ID:
Decision:
Date:
Owner/participants:
Alternatives considered:
Choice:
Reason:
Consequences:
Affected docs/code:
ADR: <path or N/A>
```
