# Team Evaluation Scorecard

## Purpose

Use this after the exercise to evaluate collaboration and engineering behavior. It is not intended to rank people mechanically. Scores should be supported by concrete repository/project evidence.

## Suggested scale

| Score | Meaning |
|---|---|
| 1 | Needs significant support; behavior repeatedly created risk/blockage |
| 2 | Inconsistent; required frequent correction |
| 3 | Meets expectation for the exercise |
| 4 | Strong; improved team execution beyond own assigned work |
| 5 | Excellent; consistently raised engineering/team quality with evidence |

## Dimensions

### A. Requirement comprehension

Observe whether the developer:
- connects code to FR/NFR/Issue acceptance criteria;
- avoids adding unapproved scope;
- identifies ambiguity before embedding assumptions.

Evidence:
- Issue/PR references;
- review discussion;
- rework caused by misunderstood requirements.

### B. Git discipline

Observe:
- branch scope;
- coherent commits;
- conflict handling;
- ability to keep work integrable.

Do not score by commit quantity.

### C. Pull request quality

Observe:
- size/reviewability;
- clear description;
- evidence of testing;
- disclosure of schema/dependency/architecture changes;
- responsiveness to review.

### D. Code review quality

Observe whether reviews find meaningful issues involving:
- correctness;
- tests;
- architecture;
- security;
- integration risk;
- maintainability.

A large number of superficial comments is not stronger than a few useful comments.

### E. Testing and verification

Observe:
- tests written with behavior changes;
- failure/edge cases;
- response to failing checks;
- avoidance of live-AI dependence in deterministic suite;
- regression tests for defects.

### F. Architecture and technical judgment

Observe:
- ability to compare alternatives;
- preference for minimum sufficient complexity;
- respect for boundaries;
- quality of ADR reasoning;
- avoidance of unnecessary dependencies/abstractions.

### G. Communication and blocker management

Observe:
- blockers surfaced early;
- dependencies communicated;
- questions are specific;
- technical disagreement remains evidence-focused;
- integration risks are announced before merge.

### H. Collaboration

Observe:
- assistance to other developers;
- willingness to adapt when interfaces change;
- ownership without territorial behavior;
- constructive review/disagreement;
- shared responsibility for integration.

### I. AI-assisted development discipline

Observe:
- developer understands generated code;
- verifies AI suggestions against real repository/schema;
- avoids blind copy/paste;
- uses AI to accelerate tests/explanation/refactoring without delegating accountability;
- detects hallucinated APIs/classes/configuration.

### J. Delivery reliability

Observe:
- forecast vs actual completion;
- early warning when estimate changes;
- definition of done respected;
- finished work is actually integrable.

## Individual evidence worksheet

```text
Developer:
Primary Issues/PRs:
Substantive reviews performed:

A. Requirement comprehension: __/5
Evidence:

B. Git discipline: __/5
Evidence:

C. PR quality: __/5
Evidence:

D. Review quality: __/5
Evidence:

E. Testing: __/5
Evidence:

F. Technical judgment: __/5
Evidence:

G. Communication: __/5
Evidence:

H. Collaboration: __/5
Evidence:

I. AI discipline: __/5
Evidence:

J. Delivery reliability: __/5
Evidence:

Strengths to preserve:
1.
2.

Development focus:
1.
2.
```

## Team-level evaluation

Also evaluate the system, not only individuals:

- Did work decompose cleanly?
- Were interface decisions made before dependent branches diverged?
- Did reviews catch defects before merge?
- Did CI provide useful feedback?
- Was the branch strategy appropriate?
- Did any process rule create unnecessary overhead?
- Did the team converge on a release without the lead integrating everything personally?

The goal is to improve the team's operating model, not merely to assign scores.
